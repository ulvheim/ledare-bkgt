# BKGT API Documentation

## Overview

The BKGT API provides comprehensive REST endpoints for the BKGT Manager desktop application and mobile clients. It supports equipment management, team/player data, events, documents, statistics, and auto-updates.

**Base URL:** `https://ledare.bkgt.se/wp-json/bkgt/v1/`

**Version:** 1.3.0

**Last Updated:** November 14, 2025

## Authentication

All API endpoints require authentication via API keys. The API supports two authentication methods:

### API Key Authentication (Recommended)
```http
Headers:
  X-API-Key: your_api_key_here
  Content-Type: application/json
```

### Bearer Token Authentication
```http
Headers:
  Authorization: Bearer your_api_key_here
  Content-Type: application/json
```

### API Key Management
- API keys are managed through the WordPress admin interface
- Keys can have different permission levels (read, write, admin)
- Keys can be revoked or have expiration dates

## Rate Limiting

- **Default Limit:** 100 requests per minute per API key/IP
- **Headers Returned:**
  - `X-RateLimit-Limit`: Maximum requests per window
  - `X-RateLimit-Remaining`: Remaining requests
  - `X-RateLimit-Reset`: Unix timestamp when limit resets

## Error Responses

All errors follow this format:
```json
{
  "code": "error_code",
  "message": "Human readable message",
  "data": {
    "status": 400
  }
}
```

### Common Error Codes
- `invalid_api_key`: Authentication failed
- `insufficient_permissions`: API key lacks required permissions
- `rate_limit_exceeded`: Too many requests
- `validation_error`: Invalid request data
- `not_found`: Resource not found
- `server_error`: Internal server error

---

# API Endpoints

## Authentication Endpoints

### POST /auth/login
Authenticate user and get JWT tokens.

**Request:**
```json
{
  "username": "user@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "token": "jwt_token_here",
    "refresh_token": "refresh_token_here",
    "user": {
      "id": 1,
      "username": "user",
      "email": "user@example.com",
      "display_name": "User Name",
      "roles": ["administrator"]
    }
  }
}
```

### POST /auth/refresh
Refresh JWT token using refresh token.

**Request:**
```json
{
  "refresh_token": "refresh_token_here"
}
```

**Response (200):** Same as login response.

### POST /auth/logout
Invalidate current session.

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

### GET /auth/me
Get current user information.

**Response (200):**
```json
{
  "id": 1,
  "username": "user",
  "email": "user@example.com",
  "display_name": "User Name",
  "roles": ["administrator"],
  "capabilities": ["read", "write"]
}
```

## Health Check Endpoints

### GET /health
Check API health and system status.

**Response (200):**
```json
{
  "status": "healthy",
  "timestamp": "2025-11-14T10:00:00Z",
  "version": "1.3.0",
  "services": {
    "database": "connected",
    "cache": "available",
    "storage": "available"
  }
}
```

## Team Management Endpoints

### GET /teams
Get list of teams.

**Query Parameters:**
- `page` (int): Page number (default: 1)
- `per_page` (int): Items per page (default: 20, max: 100)
- `search` (string): Search term
- `status` (string): Filter by status (active, inactive)

**Response (200):**
```json
{
  "teams": [
    {
      "id": 1,
      "name": "BKGT Stockholm",
      "description": "Stockholm team",
      "status": "active",
      "created_at": "2025-01-01T00:00:00Z",
      "updated_at": "2025-11-01T00:00:00Z"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 45,
    "total_pages": 3
  }
}
```

### POST /teams
Create new team.

**Request:**
```json
{
  "name": "New Team Name",
  "description": "Team description",
  "status": "active"
}
```

**Response (201):** Team object.

### GET /teams/{id}
Get specific team.

**Response (200):** Team object.

### PUT /teams/{id}
Update team.

**Request:** Same as create, all fields optional.

**Response (200):** Updated team object.

### DELETE /teams/{id}
Delete team.

**Response (200):**
```json
{
  "deleted": true,
  "message": "Team deleted successfully"
}
```

### GET /teams/{id}/players
Get players for specific team.

**Query Parameters:** Same as /players endpoint.

**Response (200):** Players array with pagination.

### GET /teams/{id}/events
Get events for specific team.

**Query Parameters:**
- `start_date` (date): Filter events from this date
- `end_date` (date): Filter events until this date

**Response (200):** Events array with pagination.

## Player Management Endpoints

### GET /players
Get list of players.

**Query Parameters:**
- `page`, `per_page`, `search` (same as teams)
- `team_id` (int): Filter by team
- `position` (string): Filter by position

**Response (200):**
```json
{
  "players": [
    {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "+46 123 456 789",
      "position": "QB",
      "jersey_number": 12,
      "team_id": 1,
      "status": "active",
      "date_of_birth": "1990-01-01",
      "created_at": "2025-01-01T00:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

### POST /players
Create new player.

**Request:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+46 123 456 789",
  "position": "QB",
  "jersey_number": 12,
  "team_id": 1,
  "date_of_birth": "1990-01-01"
}
```

### GET /players/{id}
Get specific player.

### PUT /players/{id}
Update player.

### DELETE /players/{id}
Delete player.

### GET /players/{id}/stats
Get player statistics.

**Query Parameters:**
- `season` (string): Filter by season
- `event_type` (string): Filter by event type

**Response (200):** Statistics object.

## Event Management Endpoints

### GET /events
Get list of events.

**Query Parameters:**
- `page`, `per_page`, `search`
- `team_id` (int): Filter by team
- `start_date`, `end_date` (date): Date range
- `event_type` (string): Filter by type (game, practice, tournament)

**Response (200):**
```json
{
  "events": [
    {
      "id": 1,
      "title": "BKGT vs Stockholm",
      "description": "Regular season game",
      "event_type": "game",
      "start_date": "2025-11-15T14:00:00Z",
      "end_date": "2025-11-15T16:00:00Z",
      "location": "Stockholm Stadium",
      "team_id": 1,
      "status": "scheduled",
      "created_at": "2025-11-01T00:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

### POST /events
Create new event.

**Request:**
```json
{
  "title": "Event Title",
  "description": "Event description",
  "event_type": "game",
  "start_date": "2025-11-15T14:00:00Z",
  "end_date": "2025-11-15T16:00:00Z",
  "location": "Venue name",
  "team_id": 1
}
```

### GET /events/{id}
Get specific event.

### PUT /events/{id}
Update event.

### DELETE /events/{id}
Delete event.

### GET /events/{id}/players
Get players attending event.

### POST /events/{id}/players
Add player to event.

**Request:**
```json
{
  "player_id": 1,
  "status": "confirmed"
}
```

### DELETE /events/{id}/players/{player_id}
Remove player from event.

---

# Document Management System (DMS) API

## Overview

The BKGT Document Management System (DMS) API provides comprehensive document lifecycle management capabilities including document creation, version control, template management, advanced search, access control, and bulk operations. The DMS is designed to handle enterprise-level document workflows for sports organizations.

### Development Phases

#### Phase 1 (Minimum Viable DMS) - ✅ IMPLEMENTED
- **Basic document CRUD operations**: Complete create, read, update, delete functionality
- **File upload/download functionality**: Support for multiple file formats with secure upload/download
- **Document search and filtering**: Advanced search with multiple filter criteria
- **Categories management**: Hierarchical category system for document organization

#### Phase 2 (Enhanced Features) - ✅ IMPLEMENTED
- **Tags system**: Flexible tagging for document classification and quick retrieval
- **Document templates**: Dynamic templates with variable substitution for standardized documents
- **Version control**: Complete version history with comparison and restoration capabilities
- **Permissions and sharing**: Granular access control with time-limited sharing

#### Phase 3 (Advanced Features) - ✅ IMPLEMENTED
- **Bulk operations**: Efficient batch processing for multiple documents
- **Analytics and statistics**: Comprehensive usage statistics and reporting
- **Document viewer integration**: Built-in document viewing with pagination and zoom

### Key Features

- **Document Upload & Storage**: Support for multiple file formats (PDF, DOC, DOCX, TXT, RTF, ODT)
- **Version Control**: Full document versioning with comparison and restoration
- **Template System**: Dynamic document templates with variable substitution
- **Advanced Search**: Multi-criteria search with filtering and sorting
- **Access Control**: Granular permissions and document sharing
- **Categories & Tags**: Hierarchical organization and tagging system
- **Bulk Operations**: Efficient batch processing for multiple documents
- **Document Viewer**: Integrated viewing capabilities with pagination and zoom
- **Statistics & Analytics**: Document usage and system statistics

### Core Concepts

#### Documents
Documents are the central entity in the DMS. Each document contains:
- File content and metadata
- Version history
- Access permissions
- Categories and tags
- Statistics (views, downloads, etc.)

#### Templates
Templates enable standardized document creation with:
- Variable placeholders (e.g., `{{player_name}}`, `{{team_name}}`)
- Predefined content structures
- Category-based organization
- Preview functionality

#### Versions
Document versions track changes over time:
- Automatic version creation on updates
- Version comparison and diffing
- Restoration to previous versions
- Change summaries and metadata

#### Permissions
Granular access control system:
- User-specific permissions
- Role-based access
- Time-limited sharing
- Read/write/delete permissions

## Security Implementation

### File Upload Security ✅ IMPLEMENTED
- **File Type Validation**: Strict validation of allowed file types (PDF, DOC, DOCX, TXT, RTF, ODT)
- **Malware Scanning**: Integration with security scanning services
- **Size Limits**: Configurable file size limits (max 50MB, recommended <10MB)
- **Content Validation**: Server-side validation of file content and metadata

### Access Control ✅ IMPLEMENTED
- **User Permissions**: Granular read/write/delete/manage permissions per document
- **Role-Based Access**: Hierarchical permission system with admin/manage/write/read levels
- **Time-Limited Sharing**: Temporary access with automatic expiration
- **Audit Logging**: Complete audit trail of all document access and modifications

### File Storage Security ✅ IMPLEMENTED
- **Secure Storage**: Files stored with proper filesystem permissions
- **Encryption**: Optional encryption for sensitive documents
- **Backup**: Automated backup and disaster recovery procedures
- **Access Logging**: Detailed logging of all file access attempts

### API Authentication ✅ IMPLEMENTED
- **API Key Authentication**: Secure API key-based authentication
- **Bearer Token Support**: JWT token authentication for client applications
- **Rate Limiting**: Comprehensive rate limiting (100 req/min standard, 20/min uploads)
- **Request Validation**: All requests validated for proper authentication and authorization

### Additional Security Measures
- **HTTPS Only**: All API communications require HTTPS
- **Input Sanitization**: All user inputs sanitized to prevent injection attacks
- **Error Handling**: Secure error responses that don't leak sensitive information
- **CORS Protection**: Cross-origin request protection
- **Session Management**: Secure session handling with automatic timeouts

## Authentication & Permissions

### Authentication Methods
All DMS endpoints require authentication. The API supports:
- **API Key Authentication** (recommended for server-to-server)
- **Bearer Token Authentication** (recommended for client applications)

### Permission Levels
DMS operations require specific permissions:

| Permission | Description | Required Endpoints |
|------------|-------------|-------------------|
| `read` | View documents and metadata | GET endpoints |
| `write` | Create, update documents | POST/PUT document endpoints |
| `delete` | Delete documents | DELETE document endpoints |
| `manage` | Manage permissions, categories | Permission and category endpoints |
| `admin` | Full system access | All endpoints including bulk operations |

### Rate Limiting
DMS endpoints are subject to rate limiting:
- **Standard requests**: 100 per minute
- **File uploads**: 20 per minute
- **Bulk operations**: 10 per minute

## Document Management Endpoints

### Core Document Operations

#### GET /documents
Retrieve a paginated list of documents with optional filtering.

**Authentication:** Required (read permission)

**Query Parameters:**
- `page` (integer, default: 1): Page number
- `per_page` (integer, default: 20, max: 100): Items per page
- `search` (string): Search in title, description, and content
- `document_type` (string): Filter by document type (contract, rules, report, etc.)
- `category` (string): Filter by category slug
- `author` (integer): Filter by author user ID
- `status` (string): Filter by status (publish, draft, private)
- `date_from` (string): Start date filter (YYYY-MM-DD)
- `date_to` (string): End date filter (YYYY-MM-DD)

**Response (200):**
```json
{
  "documents": [
    {
      "id": 123,
      "title": "Player Contract - John Doe",
      "description": "Standard player contract for 2025 season",
      "document_type": "contract",
      "category": {
        "id": 5,
        "name": "Contracts",
        "slug": "contracts"
      },
      "author": {
        "id": 1,
        "name": "Admin User"
      },
      "file_url": "https://ledare.bkgt.se/wp-content/uploads/documents/contract_123.pdf",
      "file_size": 245760,
      "mime_type": "application/pdf",
      "current_version": 2,
      "status": "publish",
      "created_at": "2025-01-15T10:30:00Z",
      "modified_at": "2025-01-20T14:15:00Z",
      "permissions": {
        "can_read": true,
        "can_write": false,
        "can_delete": false
      }
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 150,
    "total_pages": 8,
    "has_next": true,
    "has_prev": false
  }
}
```

**Error Responses:**
- `403 Forbidden`: Insufficient permissions
- `400 Bad Request`: Invalid query parameters

#### GET /documents/{id}
Retrieve detailed information about a specific document.

**Authentication:** Required (read permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Response (200):** Document object (same format as list endpoint)

**Error Responses:**
- `404 Not Found`: Document not found
- `403 Forbidden`: Access denied

#### POST /documents
Create a new document by uploading a file.

**Authentication:** Required (write permission)

**Content-Type:** `multipart/form-data`

**Form Parameters:**
- `file` (file, required): Document file
- `title` (string): Document title (auto-generated from filename if not provided)
- `description` (string): Document description
- `document_type` (string): Document type (contract, rules, report, other)
- `category` (integer): Category ID
- `tags` (string): Comma-separated tag names

**Response (201):**
```json
{
  "id": 124,
  "title": "Uploaded Document",
  "description": "Document uploaded via API",
  "document_type": "report",
  "file_url": "https://ledare.bkgt.se/wp-content/uploads/documents/report_124.pdf",
  "file_size": 1024000,
  "mime_type": "application/pdf",
  "current_version": 1,
  "status": "publish",
  "created_at": "2025-01-15T10:30:00Z",
  "author": {
    "id": 1,
    "name": "API User"
  }
}
```

**Supported File Types:**
- PDF (.pdf)
- Microsoft Word (.doc, .docx)
- Plain Text (.txt)
- Rich Text Format (.rtf)
- OpenDocument (.odt)

**File Size Limits:**
- Maximum: 50MB per file
- Recommended: < 10MB for optimal performance

#### PUT /documents/{id}
Update document metadata (does not modify file content).

**Authentication:** Required (write permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Request Body:**
```json
{
  "title": "Updated Title",
  "description": "Updated description",
  "document_type": "contract",
  "category": 5,
  "status": "publish"
}
```

**Response (200):** Updated document object

#### DELETE /documents/{id}
Delete a document and all its versions.

**Authentication:** Required (delete permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Response (200):**
```json
{
  "deleted": true,
  "message": "Document deleted successfully"
}
```

#### GET /documents/{id}/download
Download the document file.

**Authentication:** Required (read permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Query Parameters:**
- `version` (integer): Specific version to download (default: latest)

**Response:** Binary file data with appropriate Content-Type and Content-Disposition headers

### Advanced Document Operations

#### POST /documents/upload
Enhanced document upload with advanced options.

**Authentication:** Required (write permission)

**Content-Type:** `multipart/form-data`

**Form Parameters:**
- `file` (file, required): Document file
- `title` (string): Document title
- `description` (string): Document description
- `document_type` (string): Document type
- `category` (integer): Category ID
- `tags` (string): Comma-separated tag names
- `auto_version` (boolean): Auto-create version on upload (default: true)
- `share_with` (string): JSON array of user IDs to share with
- `permissions` (string): JSON object with permission settings

**Response (201):** Enhanced document object with sharing information

#### GET /documents/search
Advanced document search with multiple filters and sorting options.

**Authentication:** Required (read permission)

**Query Parameters:**
- `query` (string): Full-text search query
- `category` (string): Category slug filter
- `document_type` (string): Document type filter
- `author` (integer): Author user ID filter
- `status` (string): Status filter
- `date_from` (string): Start date (YYYY-MM-DD)
- `date_to` (string): End date (YYYY-MM-DD)
- `tags` (array): Tag ID filters
- `has_file` (boolean): Filter documents with/without files
- `sort_by` (string): Sort field (date, title, modified, size, downloads)
- `sort_order` (string): Sort order (asc, desc)
- `page` (integer): Page number
- `per_page` (integer): Items per page

**Advanced Search Examples:**

```bash
# Search for contracts by John Doe
GET /documents/search?query=john%20doe&document_type=contract

# Find documents modified in the last 30 days
GET /documents/search?date_from=2025-01-01&sort_by=modified&sort_order=desc

# Find large documents (>1MB) in PDF format
GET /documents/search?document_type=pdf&min_size=1048576
```

**Response (200):** Paginated document list with search metadata

### Template Management

#### GET /documents/templates
Retrieve available document templates.

**Authentication:** Required (read permission)

**Query Parameters:**
- `category` (string): Filter by category
- `search` (string): Search in name and description
- `page` (integer): Page number
- `per_page` (integer): Items per page

**Response (200):**
```json
{
  "templates": [
    {
      "id": 1,
      "name": "Player Contract Template",
      "description": "Standard player contract with variable placeholders",
      "category": "contracts",
      "variables": [
        "player_name",
        "player_email",
        "team_name",
        "season",
        "salary",
        "start_date",
        "end_date"
      ],
      "content_preview": "This contract is between {{team_name}} and {{player_name}}...",
      "usage_count": 45,
      "created_by": {
        "id": 1,
        "name": "Admin User"
      },
      "created_at": "2025-01-01T00:00:00Z",
      "updated_at": "2025-01-10T15:30:00Z"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 15,
    "total_pages": 1
  }
}
```

#### POST /documents/templates
Create a new document template.

**Authentication:** Required (write permission)

**Request Body:**
```json
{
  "name": "New Contract Template",
  "description": "Template for new player contracts",
  "content": "This contract is between {{team_name}} and {{player_name}} for the {{season}} season. Salary: {{salary}} SEK. Contract period: {{start_date}} to {{end_date}}.",
  "category": "contracts",
  "variables": [
    "team_name",
    "player_name",
    "season",
    "salary",
    "start_date",
    "end_date"
  ],
  "is_public": true
}
```

**Response (201):** Created template object

#### GET /documents/templates/{id}/preview
Preview a template with variable substitution.

**Authentication:** Required (read permission)

**Path Parameters:**
- `id` (integer, required): Template ID

**Query Parameters:**
- `variables` (object): JSON object with variable values

**Example Request:**
```
GET /documents/templates/1/preview?variables={"team_name":"BKGT","player_name":"John Doe","season":"2025"}
```

**Response (200):**
```json
{
  "template_id": 1,
  "rendered_content": "This contract is between BKGT and John Doe for the 2025 season. Salary: {{salary}} SEK. Contract period: {{start_date}} to {{end_date}}.",
  "variables_used": ["team_name", "player_name", "season"],
  "variables_missing": ["salary", "start_date", "end_date"]
}
```

#### POST /documents/templates/{id}/create
Create a document from a template.

**Authentication:** Required (write permission)

**Path Parameters:**
- `id` (integer, required): Template ID

**Request Body:**
```json
{
  "variables": {
    "team_name": "BKGT Stockholm",
    "player_name": "John Doe",
    "season": "2025",
    "salary": "50000",
    "start_date": "2025-01-01",
    "end_date": "2025-12-31"
  },
  "title": "Contract - John Doe 2025",
  "description": "Player contract created from template",
  "document_type": "contract",
  "category": 5
}
```

**Response (201):** Created document object

### Version Control

#### GET /documents/{id}/versions
Get version history for a document.

**Authentication:** Required (read permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Response (200):**
```json
{
  "document_id": 123,
  "current_version": 3,
  "versions": [
    {
      "version_number": 3,
      "title": "Updated Contract",
      "file_size": 256000,
      "created_at": "2025-01-20T14:15:00Z",
      "created_by": {
        "id": 1,
        "name": "Admin User"
      },
      "change_summary": "Updated salary terms",
      "is_current": true
    },
    {
      "version_number": 2,
      "title": "Contract v2",
      "file_size": 245760,
      "created_at": "2025-01-15T10:30:00Z",
      "created_by": {
        "id": 1,
        "name": "Admin User"
      },
      "change_summary": "Added bonus clauses",
      "is_current": false
    }
  ]
}
```

#### GET /documents/{id}/versions/{version}
Get details of a specific version.

**Authentication:** Required (read permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID
- `version` (integer, required): Version number

**Response (200):** Version object with content details

#### POST /documents/{id}/versions
Create a new version by updating the document.

**Authentication:** Required (write permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Request Body:**
```json
{
  "comment": "Updated contract terms and conditions"
}
```

**Note:** This endpoint is typically called automatically when updating document content.

#### GET /documents/{id}/versions/compare
Compare two document versions.

**Authentication:** Required (read permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Query Parameters:**
- `version1` (integer, required): First version number
- `version2` (integer, required): Second version number

**Response (200):**
```json
{
  "comparison": {
    "version1": {
      "number": 2,
      "title": "Contract v2",
      "content_length": 245760,
      "created_at": "2025-01-15T10:30:00Z"
    },
    "version2": {
      "number": 3,
      "title": "Updated Contract",
      "content_length": 256000,
      "created_at": "2025-01-20T14:15:00Z"
    },
    "differences": {
      "title_changed": true,
      "content_changed": true,
      "size_difference": 10240
    }
  }
}
```

#### PUT /documents/{id}/versions/{version}/restore
Restore document to a previous version.

**Authentication:** Required (write permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID
- `version` (integer, required): Version number to restore to

**Response (200):**
```json
{
  "restored": true,
  "restored_version": 2,
  "new_version": 4,
  "message": "Document restored to version 2"
}
```

### Access Control & Permissions

#### GET /documents/{id}/permissions
Get current permissions for a document.

**Authentication:** Required (manage permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Response (200):**
```json
{
  "document_id": 123,
  "permissions": [
    {
      "id": 1,
      "user_id": 456,
      "user_name": "John Doe",
      "role_id": null,
      "can_read": true,
      "can_write": true,
      "can_delete": false,
      "expires_at": "2025-12-31T23:59:59Z",
      "granted_by": {
        "id": 1,
        "name": "Admin User"
      },
      "granted_at": "2025-01-15T10:30:00Z"
    }
  ]
}
```

#### PUT /documents/{id}/permissions
Update document permissions.

**Authentication:** Required (manage permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Request Body:**
```json
{
  "permissions": [
    {
      "user_id": 456,
      "can_read": true,
      "can_write": true,
      "can_delete": false,
      "expires_at": "2025-12-31T23:59:59Z"
    }
  ]
}
```

**Response (200):**
```json
{
  "updated": true,
  "permissions_count": 1
}
```

#### POST /documents/{id}/share
Share document with specific users.

**Authentication:** Required (manage permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Request Body:**
```json
{
  "recipients": [456, 789],
  "permissions": {
    "can_read": true,
    "can_write": false,
    "can_delete": false
  },
  "message": "Please review this updated contract",
  "expires_at": "2025-12-31T23:59:59Z",
  "notify_users": true
}
```

**Response (200):**
```json
{
  "shared": true,
  "shared_count": 2,
  "message": "Document shared successfully",
  "notification_sent": true
}
```

### Categories & Organization

#### GET /documents/categories
Get document categories hierarchy.

**Authentication:** Required (read permission)

**Response (200):**
```json
{
  "categories": [
    {
      "id": 1,
      "name": "Contracts",
      "description": "Player and staff contracts",
      "slug": "contracts",
      "parent": 0,
      "count": 25,
      "color": "#FF5733",
      "children": [
        {
          "id": 5,
          "name": "Player Contracts",
          "description": "Individual player contracts",
          "slug": "player-contracts",
          "parent": 1,
          "count": 20,
          "color": "#FF5733"
        }
      ]
    }
  ]
}
```

#### POST /documents/categories
Create a new category.

**Authentication:** Required (manage permission)

**Request Body:**
```json
{
  "name": "New Category",
  "description": "Category description",
  "parent": 0,
  "color": "#FF5733"
}
```

**Response (201):** Created category object

#### PUT /documents/categories/{id}
Update a category.

**Authentication:** Required (manage permission)

**Path Parameters:**
- `id` (integer, required): Category ID

**Request Body:**
```json
{
  "name": "Updated Category Name",
  "description": "Updated description",
  "color": "#00FF00"
}
```

#### DELETE /documents/categories/{id}
Delete a category (moves documents to parent category).

**Authentication:** Required (manage permission)

### Tags Management

#### GET /documents/tags
Get available tags.

**Authentication:** Required (read permission)

**Query Parameters:**
- `search` (string): Search term
- `min_count` (integer): Minimum usage count

**Response (200):**
```json
{
  "tags": [
    {
      "id": 1,
      "name": "Important",
      "slug": "important",
      "description": "High priority documents",
      "count": 15,
      "color": "#FF0000",
      "created_by": 1
    }
  ]
}
```

#### POST /documents/tags
Create a new tag.

**Authentication:** Required (write permission)

**Request Body:**
```json
{
  "name": "Urgent",
  "description": "Time-sensitive documents",
  "color": "#FF0000"
}
```

#### GET /documents/{id}/tags
Get tags for a specific document.

**Authentication:** Required (read permission for the document)

#### PUT /documents/{id}/tags
Update document tags.

**Authentication:** Required (write permission for the document)

**Request Body:**
```json
{
  "tags": [1, 2, 3]
}
```

### Bulk Operations

#### POST /documents/bulk
Perform bulk operations on multiple documents.

**Authentication:** Required (manage permission for all documents)

**Request Body:**
```json
{
  "operation": "delete",
  "document_ids": [123, 124, 125],
  "data": {}
}
```

**Supported Operations:**
- `delete`: Delete multiple documents
- `move`: Move to different category
- `update`: Update metadata
- `archive`: Change status to archived
- `publish`: Change status to published

**Example - Move to category:**
```json
{
  "operation": "move",
  "document_ids": [123, 124],
  "data": {
    "category": 5
  }
}
```

**Response (200):**
```json
{
  "operation": "move",
  "results": [
    {"id": 123, "status": "moved"},
    {"id": 124, "status": "moved"}
  ],
  "errors": [],
  "total_processed": 2,
  "total_errors": 0
}
```

#### POST /documents/bulk/export
Export multiple documents as ZIP archive.

**Authentication:** Required (read permission for all documents)

**Request Body:**
```json
{
  "document_ids": [123, 124, 125],
  "format": "zip",
  "include_versions": false
}
```

**Response (200):**
```json
{
  "download_url": "https://ledare.bkgt.se/wp-content/uploads/exports/documents_export_2025-01-15.zip",
  "filename": "documents_export_2025-01-15.zip",
  "format": "zip",
  "document_count": 3,
  "total_size": 5242880,
  "expires_at": "2025-01-15T11:30:00Z"
}
```

### Document Viewer & Statistics

#### GET /documents/{id}/viewer
Get document viewer data and settings.

**Authentication:** Required (read permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Query Parameters:**
- `page` (integer): Page number for multi-page documents
- `zoom` (float): Zoom level (0.5 to 3.0)

**Response (200):**
```json
{
  "document_id": 123,
  "title": "Player Contract",
  "content": "Document content or file URL for viewer",
  "file_url": "https://ledare.bkgt.se/wp-content/uploads/documents/contract_123.pdf",
  "mime_type": "application/pdf",
  "viewer_settings": {
    "page": 1,
    "zoom": 1.0,
    "total_pages": 5,
    "can_print": true,
    "can_download": true,
    "can_annotate": false
  },
  "permissions": {
    "can_print": true,
    "can_download": true,
    "can_share": false
  }
}
```

#### GET /documents/{id}/stats
Get document usage statistics.

**Authentication:** Required (read permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Response (200):**
```json
{
  "document_id": 123,
  "stats": {
    "views": 150,
    "downloads": 25,
    "shares": 5,
    "versions_created": 3,
    "last_viewed_at": "2025-01-20T14:15:00Z",
    "last_downloaded_at": "2025-01-19T10:30:00Z",
    "unique_viewers": 12,
    "total_view_time": 3600
  }
}
```

#### GET /documents/stats
Get system-wide document statistics.

**Authentication:** Required (admin permission)

**Query Parameters:**
- `period` (string): Time period (day, week, month, year)
- `category` (integer): Filter by category
- `document_type` (string): Filter by document type

**Response (200):**
```json
{
  "period": "month",
  "stats": {
    "total_documents": 1500,
    "published_documents": 1200,
    "draft_documents": 250,
    "archived_documents": 50,
    "new_documents": 45,
    "total_file_size": 1073741824,
    "average_file_size": 716800,
    "documents_by_type": {
      "contract": 500,
      "rules": 200,
      "report": 300
    },
    "top_categories": [
      {"name": "Contracts", "count": 500},
      {"name": "Rules", "count": 200}
    ]
  }
}
```

### Metadata Management

#### GET /documents/{id}/metadata
Get document metadata fields.

**Authentication:** Required (read permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Response (200):**
```json
{
  "document_id": 123,
  "metadata": {
    "custom_field": "value",
    "_bkgt_expiry_date": "2025-12-31",
    "_bkgt_confidential": true,
    "_bkgt_review_date": "2025-06-01",
    "author_department": "Legal",
    "document_priority": "high"
  }
}
```

#### PUT /documents/{id}/metadata
Update document metadata.

**Authentication:** Required (write permission for the document)

**Path Parameters:**
- `id` (integer, required): Document ID

**Request Body:**
```json
{
  "custom_field": "new value",
  "_bkgt_expiry_date": "2025-12-31",
  "_bkgt_confidential": true
}
```

## DMS Workflows & Use Cases

### Document Creation Workflow

1. **Template Selection**
   ```bash
   GET /documents/templates?category=contracts
   ```

2. **Template Preview**
   ```bash
   GET /documents/templates/1/preview?variables={"player_name":"John Doe"}
   ```

3. **Document Creation**
   ```bash
   POST /documents/templates/1/create
   ```

4. **Document Upload (Alternative)**
   ```bash
   POST /documents/upload
   ```

### Document Review & Approval Workflow

1. **Create Document**
2. **Set Permissions for Reviewers**
   ```bash
   PUT /documents/123/permissions
   ```
3. **Share with Reviewers**
   ```bash
   POST /documents/123/share
   ```
4. **Track Changes via Versions**
   ```bash
   GET /documents/123/versions
   ```

### Bulk Document Management

1. **Search for Documents to Process**
   ```bash
   GET /documents/search?document_type=contract&status=draft
   ```

2. **Bulk Update Status**
   ```bash
   POST /documents/bulk
   ```

3. **Bulk Export for Backup**
   ```bash
   POST /documents/bulk/export
   ```

## Error Handling

### Common Error Codes

| Error Code | HTTP Status | Description |
|------------|-------------|-------------|
| `document_not_found` | 404 | Document does not exist |
| `access_denied` | 403 | Insufficient permissions |
| `invalid_file_type` | 400 | Unsupported file format |
| `file_too_large` | 400 | File exceeds size limit |
| `template_not_found` | 404 | Template does not exist |
| `version_not_found` | 404 | Document version does not exist |
| `category_not_found` | 404 | Category does not exist |
| `bulk_operation_failed` | 400 | Bulk operation partially failed |

### Error Response Format
```json
{
  "code": "access_denied",
  "message": "You do not have permission to access this document",
  "data": {
    "status": 403,
    "document_id": 123,
    "required_permission": "read"
  }
}
```

## Best Practices

### File Management
- Use descriptive filenames and titles
- Include document descriptions for better searchability
- Organize documents with appropriate categories and tags
- Regularly archive old documents

### Security
- Implement least-privilege access control
- Use time-limited sharing for sensitive documents
- Regularly audit document access logs
- Enable version control for important documents

### Performance
- Use pagination for large document lists
- Implement caching for frequently accessed documents
- Compress large files before upload
- Use bulk operations for multiple document updates

### Integration Examples

#### JavaScript (Fetch API)
```javascript
// Upload document
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('title', 'New Document');
formData.append('category', '5');

fetch('/wp-json/bkgt/v1/documents/upload', {
  method: 'POST',
  headers: {
    'X-API-Key': 'your_api_key'
  },
  body: formData
})
.then(response => response.json())
.then(data => console.log('Document uploaded:', data));
```

#### Python (requests)
```python
import requests

# Search documents
response = requests.get('/wp-json/bkgt/v1/documents/search', 
                       params={'query': 'contract', 'document_type': 'contract'},
                       headers={'X-API-Key': 'your_api_key'})
documents = response.json()
```

#### cURL Examples
```bash
# List documents
curl -H "X-API-Key: your_api_key" \
     "https://ledare.bkgt.se/wp-json/bkgt/v1/documents?page=1&per_page=20"

# Upload document
curl -X POST \
     -H "X-API-Key: your_api_key" \
     -F "file=@document.pdf" \
     -F "title=New Document" \
     "https://ledare.bkgt.se/wp-json/bkgt/v1/documents/upload"
```

## Changelog

### Version 1.4.0 (January 15, 2025)
- Added comprehensive DMS API endpoints covering all development phases
- **Phase 1 (MVP)**: Basic CRUD, file upload/download, search/filtering, categories
- **Phase 2 (Enhanced)**: Tags system, templates, version control, permissions/sharing
- **Phase 3 (Advanced)**: Bulk operations, analytics, document viewer integration
- Implemented comprehensive security measures for file uploads, access control, and API authentication
- Added enterprise-grade document lifecycle management capabilities

---

## Equipment Management Endpoints

### GET /equipment
Get list of equipment items.

**Query Parameters:**
- `page`, `per_page`, `search`
- `category` (string): Filter by category
- `status` (string): Filter by status (available, assigned, maintenance)
- `location_id` (int): Filter by location

**Response (200):**
```json
{
  "equipment": [
    {
      "id": 1,
      "name": "Wilson Football",
      "description": "Official game ball",
      "category": "balls",
      "serial_number": "WF2025001",
      "purchase_date": "2025-01-01",
      "purchase_price": 299.99,
      "warranty_expiry": "2026-01-01",
      "size": "Official",
      "status": "available",
      "location_id": 1,
      "condition": "new",
      "created_at": "2025-01-01T00:00:00Z"
    }
  ],
  "pagination": { ... }
}
```

### POST /equipment
Create new equipment item.

**Request:**
```json
{
  "name": "Equipment Name",
  "description": "Equipment description",
  "category": "balls",
  "serial_number": "SN123456",
  "purchase_date": "2025-01-01",
  "purchase_price": 299.99,
  "warranty_expiry": "2026-01-01",
  "size": "Official",
  "location_id": 1
}
```

### GET /equipment/{id}
Get specific equipment item.

### PUT /equipment/{id}
Update equipment item.

### DELETE /equipment/{id}
Delete equipment item.

### POST /equipment/{id}/assignment
Assign equipment to player/team.

**Request:**
```json
{
  "assignee_type": "player",
  "assignee_id": 1,
  "assigned_by": 1,
  "expected_return_date": "2025-12-01",
  "notes": "Assignment notes"
}
```

**Response (201):**
```json
{
  "assignment_id": 123,
  "equipment_id": 1,
  "assignee_type": "player",
  "assignee_id": 1,
  "assigned_date": "2025-11-14T10:00:00Z",
  "expected_return_date": "2025-12-01",
  "status": "active"
}
```

### DELETE /equipment/{id}/assignment
Return/unassign equipment.

**Response (200):**
```json
{
  "returned": true,
  "message": "Equipment returned successfully"
}
```

### GET /equipment/{id}/history
Get assignment history for equipment.

**Response (200):**
```json
{
  "history": [
    {
      "id": 123,
      "assignee_type": "player",
      "assignee_id": 1,
      "assigned_date": "2025-11-01T00:00:00Z",
      "returned_date": "2025-11-14T00:00:00Z",
      "assigned_by": 1,
      "returned_by": 2,
      "notes": "Assignment notes"
    }
  ]
}
```

## Statistics Endpoints

### GET /stats/players
Get player statistics.

**Query Parameters:**
- `player_id` (int): Specific player
- `team_id` (int): Filter by team
- `season` (string): Filter by season
- `event_type` (string): Filter by event type

**Response (200):**
```json
{
  "stats": [
    {
      "player_id": 1,
      "player_name": "John Doe",
      "season": "2025",
      "games_played": 12,
      "passing_yards": 2450,
      "passing_tds": 18,
      "rushing_yards": 450,
      "rushing_tds": 5,
      "receiving_yards": 320,
      "receiving_tds": 3
    }
  ]
}
```

### GET /stats/teams
Get team statistics.

**Query Parameters:** Similar to player stats.

### GET /stats/events
Get event statistics.

### POST /stats/players/{id}
Update player statistics.

**Request:**
```json
{
  "season": "2025",
  "event_id": 1,
  "stats": {
    "passing_yards": 245,
    "passing_tds": 2,
    "rushing_yards": 45
  }
}
```

## User Management Endpoints

### GET /users
Get list of users.

**Query Parameters:**
- `page`, `per_page`, `search`
- `role` (string): Filter by role

### GET /users/{id}
Get specific user.

### PUT /users/{id}
Update user profile.

### GET /users/{id}/permissions
Get user permissions.

## Admin Endpoints

### GET /admin/stats
Get system statistics.

**Response (200):**
```json
{
  "users": {
    "total": 150,
    "active": 120
  },
  "teams": {
    "total": 12,
    "active": 10
  },
  "equipment": {
    "total": 500,
    "available": 450,
    "assigned": 45,
    "maintenance": 5
  },
  "api_keys": {
    "total": 25,
    "active": 20
  }
}
```

### GET /admin/logs
Get system logs.

**Query Parameters:**
- `page`, `per_page`
- `level` (string): Filter by log level (error, warning, info)
- `start_date`, `end_date`

### POST /admin/cache/clear
Clear system cache.

### GET /admin/health/detailed
Get detailed health check.

## Auto-Update Endpoints

### GET /updates/latest
Get latest version information.

**Response (200):**
```json
{
  "version": "1.2.3",
  "release_date": "2025-11-09T10:00:00Z",
  "changelog": "Fixed equipment update issues, improved performance",
  "critical": false,
  "platforms": {
    "win32-x64": {
      "filename": "BKGT-Manager-1.2.3-win32-x64.exe",
      "size": 85431234,
      "hash": "sha256:abc123...",
      "download_url": "https://ledare.bkgt.se/wp-json/bkgt/v1/updates/download/1.2.3/win32-x64"
    }
  },
  "minimum_version": "1.0.0"
}
```

### GET /updates/download/{version}/{platform}
Download update package.

**Response:** Binary file data with appropriate headers.

### GET /updates/compatibility/{current_version}
Check version compatibility.

**Response (200):**
```json
{
  "compatible": true,
  "latest_compatible_version": "1.2.3",
  "requires_update": true,
  "reason": "Version 1.1.0 can update to 1.2.3"
}
```

### POST /updates/status
Report update installation status.

**Request:**
```json
{
  "current_version": "1.1.0",
  "target_version": "1.2.3",
  "platform": "win32-x64",
  "status": "completed",
  "error_message": null,
  "install_time_seconds": 45
}
```

### POST /updates/upload (Admin)
Upload new update package.

**Content-Type:** `multipart/form-data`

### GET /updates/admin/list (Admin)
List all updates for admin management.

### DELETE /updates/{version} (Admin)
Deactivate update version.

## Documentation Endpoints

### GET /docs
Get API documentation in HTML format.

**Query Parameters:**
- `format` (string): Output format (html, json) - default: html

### GET /routes
Get list of all available API routes.

**Response (200):**
```json
{
  "routes": {
    "GET /wp-json/bkgt/v1/teams": {
      "description": "Get list of teams",
      "parameters": ["page", "per_page", "search"],
      "authentication": "api_key"
    }
  }
}
```

---

## Data Types

### Common Data Types

#### Pagination Object
```json
{
  "page": 1,
  "per_page": 20,
  "total": 150,
  "total_pages": 8
}
```

#### Error Object
```json
{
  "code": "error_code",
  "message": "Human readable message",
  "data": {
    "status": 400,
    "params": {}
  }
}
```

#### Date Format
All dates use ISO 8601 format: `YYYY-MM-DDTHH:MM:SSZ`

#### Status Values
- **Teams:** `active`, `inactive`
- **Players:** `active`, `inactive`, `injured`
- **Events:** `scheduled`, `in_progress`, `completed`, `cancelled`
- **Equipment:** `available`, `assigned`, `maintenance`, `retired`
- **Assignments:** `active`, `returned`, `overdue`

---

## Security Considerations

1. **API Key Security**
   - Store API keys securely
   - Rotate keys regularly
   - Use different keys for different applications

2. **Rate Limiting**
   - Respect rate limits to avoid service disruption
   - Implement exponential backoff for retries

3. **Data Validation**
   - Validate all input data
   - Use parameterized queries to prevent SQL injection
   - Sanitize file uploads

4. **HTTPS Only**
   - All API calls must use HTTPS
   - Never transmit API keys over HTTP

5. **Error Handling**
   - Don't expose sensitive information in error messages
   - Log errors securely for debugging

---

## Changelog

### Version 1.3.0 (November 14, 2025)
- Added comprehensive auto-update API endpoints
- Enhanced equipment assignment management
- Improved error handling and validation
- Added admin interface for update management

### Version 1.2.0 (November 10, 2025)
- Added equipment management endpoints
- Enhanced API key authentication
- Improved rate limiting and security
- Added comprehensive documentation

### Version 1.1.0 (October 2025)
- Added team and player management
- Implemented JWT authentication
- Added event management endpoints

### Version 1.0.0 (September 2025)
- Initial release with basic CRUD operations
- API key authentication
- Health check endpoints

---

## Support

For API support and questions:
- Check the `/docs` endpoint for interactive documentation
- Review the `/health` endpoint for system status
- Contact the development team for technical issues

**API Version:** 1.3.0
**Last Updated:** November 14, 2025</content>
<parameter name="filePath">c:\Users\Olheim\Desktop\GH\ledare-bkgt\API_DOCUMENTATION.md