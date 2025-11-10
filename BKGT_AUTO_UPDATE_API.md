# BKGT Manager Auto-Update API Specification

## Overview
This specification defines the API endpoints required for implementing automatic updates in the BKGT Manager desktop application. The system allows the application to check for updates, download new versions, and apply them seamlessly.

## Authentication
All update endpoints require API key authentication via the `X-API-Key` header, using the same keys used for other BKGT API endpoints.

## Endpoints

### 1. Get Latest Version Information
**Endpoint:** `GET /wp-json/bkgt/v1/updates/latest`

**Purpose:** Retrieve information about the latest available version of BKGT Manager.

**Request:**
```http
GET /wp-json/bkgt/v1/updates/latest
Headers:
  X-API-Key: your_api_key_here
  User-Agent: BKGT-Manager/{current_version} ({platform})
```

**Response (200):**
```json
{
  "version": "1.2.3",
  "release_date": "2025-11-09T10:00:00Z",
  "download_url": "https://ledare.bkgt.se/wp-json/bkgt/v1/updates/download/1.2.3",
  "changelog": "Fixed equipment update issues, improved performance",
  "critical": false,
  "platforms": {
    "win32-x64": {
      "filename": "BKGT-Manager-1.2.3-win32-x64.exe",
      "size": 85431234,
      "hash": "sha256:abc123..."
    },
    "darwin-x64": {
      "filename": "BKGT-Manager-1.2.3-darwin-x64.dmg",
      "size": 92345678,
      "hash": "sha256:def456..."
    },
    "linux-x64": {
      "filename": "BKGT-Manager-1.2.3-linux-x64.AppImage",
      "size": 78901234,
      "hash": "sha256:ghi789..."
    }
  },
  "minimum_version": "1.0.0"
}
```

**Response Fields:**
- `version`: Semantic version string (e.g., "1.2.3")
- `release_date`: ISO 8601 timestamp
- `download_url`: Base URL for downloading the update
- `changelog`: Human-readable description of changes
- `critical`: Boolean indicating if this is a critical security update
- `platforms`: Object containing platform-specific download information
- `minimum_version`: Minimum version required to update to this version

### 2. Download Update Package
**Endpoint:** `GET /wp-json/bkgt/v1/updates/download/{version}/{platform}`

**Purpose:** Download the update package for a specific version and platform.

**Request:**
```http
GET /wp-json/bkgt/v1/updates/download/1.2.3/win32-x64
Headers:
  X-API-Key: your_api_key_here
  User-Agent: BKGT-Manager/{current_version} ({platform})
```

**Response (200):**
- Content-Type: `application/octet-stream`
- Content-Disposition: `attachment; filename="BKGT-Manager-1.2.3-win32-x64.exe"`
- Body: Binary update package

### 3. Check Update Compatibility
**Endpoint:** `GET /wp-json/bkgt/v1/updates/compatibility/{current_version}`

**Purpose:** Check if a specific version is compatible with available updates.

**Request:**
```http
GET /wp-json/bkgt/v1/updates/compatibility/1.1.0
Headers:
  X-API-Key: your_api_key_here
```

**Response (200):**
```json
{
  "compatible": true,
  "latest_compatible_version": "1.2.3",
  "requires_update": true,
  "reason": "Version 1.1.0 can update to 1.2.3"
}
```

### 4. Report Update Status
**Endpoint:** `POST /wp-json/bkgt/v1/updates/status`

**Purpose:** Allow the application to report update installation status for analytics and monitoring.

**Request:**
```http
POST /wp-json/bkgt/v1/updates/status
Headers:
  X-API-Key: your_api_key_here
  Content-Type: application/json

Body:
{
  "current_version": "1.1.0",
  "target_version": "1.2.3",
  "platform": "win32-x64",
  "status": "completed|failed|cancelled",
  "error_message": "Optional error description",
  "install_time_seconds": 45
}
```

**Response (200):**
```json
{
  "recorded": true,
  "message": "Update status recorded successfully"
}
```

## Version Management

### Version Format
Versions must follow semantic versioning (semver): `MAJOR.MINOR.PATCH`

### Platform Support
Supported platforms:
- `win32-x64`: Windows 64-bit
- `darwin-x64`: macOS Intel
- `darwin-arm64`: macOS Apple Silicon
- `linux-x64`: Linux 64-bit

### File Storage
Update packages should be stored securely with:
- SHA256 hash verification
- Proper access controls
- CDN distribution for performance
- Backup retention policies

## Security Considerations

1. **API Key Validation**: All endpoints must validate API keys
2. **Rate Limiting**: Implement rate limiting to prevent abuse
3. **File Integrity**: Provide cryptographic hashes for verification
4. **Access Logging**: Log all update requests for security monitoring
5. **Version Validation**: Prevent downgrade attacks by enforcing minimum versions

## Implementation Requirements

### Database Schema
```sql
-- Updates table
CREATE TABLE bkgt_updates (
  id INT PRIMARY KEY AUTO_INCREMENT,
  version VARCHAR(20) NOT NULL,
  release_date DATETIME NOT NULL,
  changelog TEXT,
  critical BOOLEAN DEFAULT FALSE,
  minimum_version VARCHAR(20),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Update files table
CREATE TABLE bkgt_update_files (
  id INT PRIMARY KEY AUTO_INCREMENT,
  update_id INT NOT NULL,
  platform VARCHAR(20) NOT NULL,
  filename VARCHAR(255) NOT NULL,
  file_size BIGINT NOT NULL,
  sha256_hash VARCHAR(64) NOT NULL,
  download_count INT DEFAULT 0,
  FOREIGN KEY (update_id) REFERENCES bkgt_updates(id)
);

-- Update status tracking
CREATE TABLE bkgt_update_status (
  id INT PRIMARY KEY AUTO_INCREMENT,
  api_key_hash VARCHAR(64) NOT NULL,
  current_version VARCHAR(20) NOT NULL,
  target_version VARCHAR(20) NOT NULL,
  platform VARCHAR(20) NOT NULL,
  status ENUM('completed', 'failed', 'cancelled') NOT NULL,
  error_message TEXT,
  install_time_seconds INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### WordPress Integration
- Create a custom post type for managing update releases
- Use WordPress media library for file storage
- Implement proper user capabilities for update management
- Add admin interface for uploading and managing update packages

### 5. Upload Update Package
**Endpoint:** `POST /wp-json/bkgt/v1/updates/upload`

**Purpose:** Upload a new update package for a specific platform (admin only).

**Request:**
```http
POST /wp-json/bkgt/v1/updates/upload
Headers:
  X-API-Key: your_admin_api_key_here
  Content-Type: multipart/form-data

Form Data:
  version: "1.2.3"
  platform: "win32-x64"
  changelog: "Fixed equipment update issues..."
  critical: false
  minimum_version: "1.0.0"
  file: [binary file data]
```

**Response (201):**
```json
{
  "message": "Update package uploaded successfully",
  "update_id": 123,
  "version": "1.2.3",
  "platform": "win32-x64",
  "file_hash": "sha256:abc123...",
  "download_url": "https://ledare.bkgt.se/wp-json/bkgt/v1/updates/download/1.2.3/win32-x64"
}
```

### 6. Update Management (Admin)
**Endpoint:** `GET /wp-json/bkgt/v1/updates/admin/list`

**Purpose:** List all available updates for admin management.

**Request:**
```http
GET /wp-json/bkgt/v1/updates/admin/list?page=1&per_page=20
Headers:
  X-API-Key: your_admin_api_key_here
```

**Response (200):**
```json
{
  "updates": [
    {
      "id": 123,
      "version": "1.2.3",
      "release_date": "2025-11-09T10:00:00Z",
      "critical": false,
      "platforms": ["win32-x64", "darwin-x64"],
      "download_count": 150,
      "status": "active"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 45
  }
}
```

### 7. Deactivate Update
**Endpoint:** `DELETE /wp-json/bkgt/v1/updates/{version}`

**Purpose:** Deactivate an update version (admin only).

**Request:**
```http
DELETE /wp-json/bkgt/v1/updates/1.2.3
Headers:
  X-API-Key: your_admin_api_key_here
```

**Response (200):**
```json
{
  "message": "Update version 1.2.3 deactivated successfully"
}
```

## Testing Requirements

1. **Unit Tests**: Test all API endpoints with various inputs
2. **Integration Tests**: Test complete update flow
3. **Load Tests**: Verify performance under high concurrent requests
4. **Security Tests**: Validate authentication and authorization
5. **Cross-Platform Tests**: Verify downloads work for all supported platforms

## Monitoring and Analytics

Track metrics such as:
- Update adoption rates by version
- Platform distribution
- Failure rates and common error patterns
- Download performance and success rates
- Geographic distribution of update requests</content>
<parameter name="filePath">c:\Users\Olheim\Desktop\GH\bkgt-manager\BKGT_AUTO_UPDATE_API.md