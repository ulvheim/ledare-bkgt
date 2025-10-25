#!/bin/bash

# BKGT Ledare Deployment Script
# Deploys the WordPress site to the production server via SCP

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_ROOT/.env"

# Load environment variables
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}Error: .env file not found at $ENV_FILE${NC}"
    exit 1
fi

echo -e "${BLUE}Loading environment variables from .env file...${NC}"
source "$ENV_FILE"

# Validate required environment variables
required_vars=("SSH_KEY_PATH" "SSH_HOST" "SSH_USER" "REMOTE_FOLDER")
for var in "${required_vars[@]}"; do
    if [ -z "${!var}" ]; then
        echo -e "${RED}Error: Required environment variable $var is not set${NC}"
        exit 1
    fi
done

# Configuration variables
EXCLUDE_PATTERNS=(
    ".git"
    ".gitignore"
    ".env"
    "node_modules"
    ".DS_Store"
    "*.log"
    "deploy.sh"
    "README.md"
    ".vscode"
    "*.tmp"
    "wp-config-sample.php"
)

# Deployment options
DRY_RUN=false
SKIP_DB_BACKUP=false
FORCE_DEPLOY=false

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --skip-db-backup)
            SKIP_DB_BACKUP=true
            shift
            ;;
        --force)
            FORCE_DEPLOY=true
            shift
            ;;
        --help)
            echo "BKGT Ledare Deployment Script"
            echo ""
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --dry-run         Show what would be deployed without actually deploying"
            echo "  --skip-db-backup   Skip database backup before deployment"
            echo "  --force            Force deployment even if there are warnings"
            echo "  --help             Show this help message"
            echo ""
            echo "Environment variables required in .env file:"
            echo "  SSH_KEY_PATH       Path to SSH private key"
            echo "  SSH_HOST           SSH host (e.g., ssh.loopia.se)"
            echo "  SSH_USER           SSH username"
            echo "  REMOTE_FOLDER      Remote folder path (e.g., ~/ledare.bkgt.se/public_html)"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Function to print status messages
print_status() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Function to check if SSH connection works
test_ssh_connection() {
    print_status "Testing SSH connection..."
    if ssh -i "$SSH_KEY_PATH" -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" "echo 'SSH connection successful'" >/dev/null 2>&1; then
        print_success "SSH connection established"
        return 0
    else
        print_error "SSH connection failed"
        return 1
    fi
}

# Function to create exclude arguments for rsync
create_exclude_args() {
    local exclude_args=""
    for pattern in "${EXCLUDE_PATTERNS[@]}"; do
        exclude_args="$exclude_args --exclude='$pattern'"
    done
    echo "$exclude_args"
}

# Function to perform database backup
backup_database() {
    if [ "$SKIP_DB_BACKUP" = true ]; then
        print_warning "Skipping database backup as requested"
        return 0
    fi

    print_status "Creating database backup..."

    # This assumes wp-cli is available on the server
    local backup_result
    backup_result=$(ssh -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" "
        cd $REMOTE_FOLDER
        if command -v wp >/dev/null 2>&1; then
            wp db export backup_$(date +%Y%m%d_%H%M%S).sql --allow-root
            echo 'Database backup created'
        else
            echo 'WP-CLI not available, skipping database backup'
        fi
    " 2>&1)

    if echo "$backup_result" | grep -q "Database backup created"; then
        print_success "Database backup completed"
    else
        print_warning "Database backup may have failed or WP-CLI not available: $backup_result"
    fi
}

# Function to deploy files
deploy_files() {
    print_status "Starting file deployment..."

    if [ "$DRY_RUN" = true ]; then
        print_warning "DRY RUN MODE - No files will be transferred"
        echo "Would deploy from: $PROJECT_ROOT"
        echo "Would deploy to: $SSH_USER@$SSH_HOST:$REMOTE_FOLDER"
        echo "Exclude patterns: ${EXCLUDE_PATTERNS[*]}"
        return 0
    fi

    # Create exclude arguments
    local exclude_args=$(create_exclude_args)

    # Use rsync over SSH for efficient deployment
    print_status "Syncing files to server..."

    # Create a temporary script for rsync with proper error handling
    local rsync_command="rsync -avz --delete --no-perms --no-owner --no-group $exclude_args -e 'ssh -i $SSH_KEY_PATH -o StrictHostKeyChecking=no' $PROJECT_ROOT/ $SSH_USER@$SSH_HOST:$REMOTE_FOLDER/"

    if eval "$rsync_command"; then
        print_success "File deployment completed successfully"
    else
        print_error "File deployment failed"
        return 1
    fi
}

# Function to set proper permissions
set_permissions() {
    if [ "$DRY_RUN" = true ]; then
        print_warning "DRY RUN MODE - Permissions would be set"
        return 0
    fi

    print_status "Setting proper file permissions..."

    local permissions_result
    permissions_result=$(ssh -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" "
        cd $REMOTE_FOLDER

        # Set directory permissions
        find . -type d -exec chmod 755 {} \;

        # Set file permissions
        find . -type f -exec chmod 644 {} \;

        # Special permissions for WordPress
        if [ -f wp-config.php ]; then
            chmod 600 wp-config.php
        fi

        # wp-content permissions
        if [ -d wp-content ]; then
            chmod 755 wp-content
            find wp-content -type d -exec chmod 755 {} \;
            find wp-content -type f -exec chmod 644 {} \;

            # uploads directory should be writable
            if [ -d wp-content/uploads ]; then
                chmod 755 wp-content/uploads
                find wp-content/uploads -type d -exec chmod 755 {} \;
                find wp-content/uploads -type f -exec chmod 644 {} \;
            fi
        fi

        echo 'Permissions set successfully'
    " 2>&1)

    if echo "$permissions_result" | grep -q "Permissions set successfully"; then
        print_success "File permissions configured"
    else
        print_warning "Some permission settings may have failed: $permissions_result"
    fi
}

# Function to perform post-deployment tasks
post_deployment_tasks() {
    if [ "$DRY_RUN" = true ]; then
        print_warning "DRY RUN MODE - Post-deployment tasks would be performed"
        return 0
    fi

    print_status "Performing post-deployment tasks..."

    local post_deploy_result
    post_deploy_result=$(ssh -i "$SSH_KEY_PATH" -o StrictHostKeyChecking=no "$SSH_USER@$SSH_HOST" "
        cd $REMOTE_FOLDER

        # Clear WordPress caches if plugins are available
        if [ -f wp-config.php ]; then
            # Try to clear various caches
            if command -v wp >/dev/null 2>&1; then
                wp cache flush --allow-root 2>/dev/null || true
                wp transient delete --all --allow-root 2>/dev/null || true
                echo 'WordPress caches cleared'
            fi
        fi

        # Check if this is a WordPress site
        if [ -f wp-settings.php ]; then
            echo 'WordPress site detected and updated'
        fi

        echo 'Post-deployment tasks completed'
    " 2>&1)

    if echo "$post_deploy_result" | grep -q "Post-deployment tasks completed"; then
        print_success "Post-deployment tasks completed"
    else
        print_warning "Some post-deployment tasks may have failed: $post_deploy_result"
    fi
}

# Function to show deployment summary
show_summary() {
    echo ""
    echo -e "${BLUE}=== Deployment Summary ===${NC}"
    echo "Source: $PROJECT_ROOT"
    echo "Destination: $SSH_USER@$SSH_HOST:$REMOTE_FOLDER"
    echo "SSH Key: $SSH_KEY_PATH"
    echo "Dry Run: $DRY_RUN"
    echo "Database Backup: $([ "$SKIP_DB_BACKUP" = true ] && echo "Skipped" || echo "Performed")"
    echo ""

    if [ "$DRY_RUN" = true ]; then
        echo -e "${YELLOW}This was a dry run. No files were actually transferred.${NC}"
        echo -e "${YELLOW}Run without --dry-run to perform actual deployment.${NC}"
    else
        echo -e "${GREEN}Deployment completed successfully!${NC}"
        echo ""
        echo -e "${BLUE}Next steps:${NC}"
        echo "1. Test your website at the production URL"
        echo "2. Check that all plugins are activated"
        echo "3. Verify database connections if applicable"
        echo "4. Clear any external caches (CDN, etc.)"
    fi
}

# Main deployment function
main() {
    echo -e "${BLUE}=== BKGT Ledare Deployment Script ===${NC}"
    echo "Starting deployment at $(date)"
    echo ""

    # Pre-deployment checks
    if ! test_ssh_connection; then
        exit 1
    fi

    # Backup database
    if ! backup_database; then
        if [ "$FORCE_DEPLOY" = false ]; then
            print_error "Database backup failed. Use --force to continue anyway."
            exit 1
        fi
    fi

    # Deploy files
    if ! deploy_files; then
        print_error "File deployment failed"
        exit 1
    fi

    # Set permissions
    if ! set_permissions; then
        print_warning "Permission setting failed, but continuing..."
    fi

    # Post-deployment tasks
    if ! post_deployment_tasks; then
        print_warning "Some post-deployment tasks failed, but deployment completed"
    fi

    # Show summary
    show_summary

    print_success "Deployment process completed!"
}

# Run main function
main "$@"