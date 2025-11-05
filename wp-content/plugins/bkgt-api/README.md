# BKGT API Plugin

A comprehensive REST API plugin for the BKGT WordPress site, providing secure access to teams, players, events, documents, and statistics data for mobile and desktop applications.

## Features

- **JWT Authentication**: Secure token-based authentication with refresh tokens
- **API Key Management**: Generate and manage API keys for different applications
- **Rate Limiting**: Prevent abuse with configurable rate limits
- **Security Monitoring**: Comprehensive logging and threat detection
- **CORS Support**: Configurable cross-origin resource sharing
- **Input Validation**: Robust validation and sanitization
- **Admin Dashboard**: Complete admin interface for monitoring and management

## API Endpoints

### Authentication

### Equipment

#### GET `/wp-json/bkgt/v1/equipment/preview-identifier`
Preview the unique identifier that would be generated for new equipment.

**Authentication:** JWT Bearer token or API Key  
**Query Parameters:**
- `manufacturer_id` (integer, required): Manufacturer ID
- `item_type_id` (integer, required): Item type ID

**Response (200):**
```json
{
    "unique_identifier": "0001-0001-00001",
    "manufacturer_id": 1,
    "item_type_id": 1
}
```

#### GET `/wp-json/bkgt/v1/equipment`

**Authentication:** JWT Bearer token or API Key  
**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 10, max: 100)
- `manufacturer_id` (integer): Filter by manufacturer
- `item_type_id` (integer): Filter by item type
- `condition_status` (string): Filter by condition (normal, needs_repair, repaired, reported_lost, scrapped)
- `assigned_to` (string): Filter by assignment (club, team, individual)
- `location_id` (integer): Filter by storage location
- `search` (string): Search in item titles and identifiers

**Response (200):**
```json
{
    "equipment": [
        {
            "id": 1,
            "unique_identifier": "0001-0001-00001",
            "title": "Football Helmet - Adult Large",
            "manufacturer_id": 1,
            "manufacturer_name": "Schutt",
            "item_type_id": 1,
            "item_type_name": "Helmet",
            "storage_location": "Storage Room A",
            "condition_status": "normal",
            "condition_date": null,
            "condition_reason": null,
            "sticker_code": "HELM-001",
            "assignment_type": "individual",
            "assigned_to_id": 25,
            "assigned_to_name": "John Doe",
            "assignment_date": "2024-01-15T10:00:00Z",
            "due_date": "2024-06-30",
            "created_date": "2024-01-01T00:00:00Z",
            "updated_date": "2024-01-15T10:00:00Z"
        }
    ],
    "total": 1,
    "page": 1,
    "per_page": 10,
    "total_pages": 1
}
```

#### GET `/wp-json/bkgt/v1/equipment/{id}`
Get specific equipment item details.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Equipment item ID

#### POST `/wp-json/bkgt/v1/equipment`
Create a new equipment item (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**Request Body:**
```json
{
    "manufacturer_id": 1,
    "item_type_id": 1,
    "title": "Football Helmet - Adult Large",
    "storage_location": "Storage Room A",
    "sticker_code": "HELM-001"
}
```

**Response (201):**
```json
{
    "id": 2,
    "unique_identifier": "0001-0001-00002",
    "title": "Football Helmet - Adult Large",
    "manufacturer_id": 1,
    "manufacturer_name": "Schutt",
    "item_type_id": 1,
    "item_type_name": "Helmet",
    "storage_location": "Storage Room A",
    "condition_status": "normal",
    "sticker_code": "HELM-001",
    "created_date": "2024-01-20T14:30:00Z"
}
```

#### PUT `/wp-json/bkgt/v1/equipment/{id}`
Update equipment item information (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**URL Parameters:**
- `id` (integer): Equipment item ID

**Request Body:**
```json
{
    "title": "Football Helmet - Adult Large - Updated",
    "condition_status": "needs_repair",
    "condition_reason": "Crack in shell"
}
```

#### DELETE `/wp-json/bkgt/v1/equipment/{id}`
Delete an equipment item (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**URL Parameters:**
- `id` (integer): Equipment item ID

### Equipment Manufacturers

#### GET `/wp-json/bkgt/v1/equipment/manufacturers`
Get all equipment manufacturers.

**Authentication:** JWT Bearer token or API Key  

**Response (200):**
```json
{
    "manufacturers": [
        {
            "id": 1,
            "manufacturer_id": 1,
            "name": "Schutt",
            "contact_info": "Phone: 555-0101, Email: info@schutt.com",
            "created_date": "2024-01-01T00:00:00Z",
            "updated_date": "2024-01-01T00:00:00Z"
        }
    ]
}
```

#### GET `/wp-json/bkgt/v1/equipment/manufacturers/{id}`
Get specific manufacturer details.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Manufacturer ID

### Equipment Types

#### GET `/wp-json/bkgt/v1/equipment/types`
Get all equipment item types.

**Authentication:** JWT Bearer token or API Key  

**Response (200):**
```json
{
    "types": [
        {
            "id": 1,
            "item_type_id": 1,
            "name": "Helmet",
            "description": "Protective headgear for American football",
            "custom_fields": "{\"size\": \"string\", \"certification\": \"string\"}",
            "created_date": "2024-01-01T00:00:00Z",
            "updated_date": "2024-01-01T00:00:00Z"
        }
    ]
}
```

#### GET `/wp-json/bkgt/v1/equipment/types/{id}`
Get specific equipment type details.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Equipment type ID

### Equipment Assignments

#### POST `/wp-json/bkgt/v1/equipment/{id}/assign`
Assign equipment to a user, team, or club (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**URL Parameters:**
- `id` (integer): Equipment item ID

**Request Body:**
```json
{
    "assignment_type": "individual",
    "assignee_id": 25,
    "due_date": "2024-06-30",
    "notes": "Assigned for spring season"
}
```

**Assignment Types:**
- `individual` - Assign to specific user (requires `assignee_id`)
- `team` - Assign to team (requires `assignee_id` with team ID)
- `club` - Assign to club storage (no `assignee_id` required)

#### POST `/wp-json/bkgt/v1/equipment/{id}/return`
Return equipment from assignment (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**URL Parameters:**
- `id` (integer): Equipment item ID

**Request Body:**
```json
{
    "return_date": "2024-01-20",
    "condition_status": "normal",
    "notes": "Returned in good condition"
}
```

### Equipment Locations

#### GET `/wp-json/bkgt/v1/equipment/locations`
Get all storage locations.

**Authentication:** JWT Bearer token or API Key  

**Response (200):**
```json
{
    "locations": [
        {
            "id": 1,
            "name": "Storage Room A",
            "slug": "storage-room-a",
            "location_type": "storage",
            "address": "123 Main St, Stockholm",
            "contact_person": "Jane Smith",
            "contact_phone": "+46 70 123 4567",
            "capacity": 200,
            "is_active": true,
            "created_date": "2024-01-01T00:00:00Z",
            "updated_date": "2024-01-01T00:00:00Z"
        }
    ]
}
```

#### GET `/wp-json/bkgt/v1/equipment/locations/{id}`
Get specific location details.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Location ID

### Equipment Analytics

#### GET `/wp-json/bkgt/v1/equipment/analytics/overview`
Get equipment analytics overview.

**Authentication:** JWT Bearer token or API Key  

**Response (200):**
```json
{
    "total_items": 150,
    "items_by_condition": {
        "normal": 120,
        "needs_repair": 15,
        "repaired": 10,
        "reported_lost": 3,
        "scrapped": 2
    },
    "items_by_type": {
        "Helmet": 45,
        "Shoulder Pads": 35,
        "Pants": 30,
        "Jersey": 25,
        "Football": 15
    },
    "assignment_stats": {
        "assigned": 110,
        "available": 40,
        "overdue": 5
    },
    "maintenance_needed": 15
}
```

#### GET `/wp-json/bkgt/v1/equipment/analytics/usage`
Get equipment usage statistics.

**Authentication:** JWT Bearer token or API Key  
**Query Parameters:**
- `start_date` (string): Start date (YYYY-MM-DD)
- `end_date` (string): End date (YYYY-MM-DD)

**Response (200):**
```json
{
    "usage_stats": [
        {
            "item_type": "Helmet",
            "total_assignments": 45,
            "avg_assignment_duration_days": 180,
            "most_assigned_to": "John Doe",
            "condition_changes": 3
        }
    ]
}
```

#### POST `/wp-json/bkgt/v1/auth/login`
Authenticate user and receive JWT tokens.

**Authentication:** API Key required  
**Request Body:**
```json
{
    "username": "string",
    "password": "string"
}
```

**Response (200):**
```json
{
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
        "id": 1,
        "username": "admin",
        "email": "admin@example.com",
        "role": "administrator"
    }
}
```

#### POST `/wp-json/bkgt/v1/auth/refresh`
Refresh access token using refresh token.

**Authentication:** API Key required  
**Request Body:**
```json
{
    "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response (200):**
```json
{
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
}
```

#### POST `/wp-json/bkgt/v1/auth/logout`
Invalidate the current session.

**Authentication:** JWT Bearer token required  
**Response (200):**
```json
{
    "message": "Successfully logged out"
}
```

### Teams

#### GET `/wp-json/bkgt/v1/teams`
Get all teams with optional filtering.

**Authentication:** JWT Bearer token or API Key  
**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 10, max: 100)
- `search` (string): Search in team names
- `orderby` (string): Sort field (name, created_date)
- `order` (string): Sort order (asc, desc)

**Response (200):**
```json
{
    "teams": [
        {
            "id": 1,
            "name": "BKGT Team Alpha",
            "description": "Premier team",
            "coach": "John Doe",
            "founded_year": 2020,
            "logo_url": "https://ledare.bkgt.se/logo.png",
            "created_date": "2020-01-01T00:00:00Z",
            "updated_date": "2024-01-01T00:00:00Z"
        }
    ],
    "total": 1,
    "page": 1,
    "per_page": 10,
    "total_pages": 1
}
```

#### GET `/wp-json/bkgt/v1/teams/{id}`
Get specific team details.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Team ID

**Response (200):**
```json
{
    "id": 1,
    "name": "BKGT Team Alpha",
    "description": "Premier team",
    "coach": "John Doe",
    "founded_year": 2020,
    "logo_url": "https://ledare.bkgt.se/logo.png",
    "players_count": 15,
    "created_date": "2020-01-01T00:00:00Z",
    "updated_date": "2024-01-01T00:00:00Z"
}
```

#### POST `/wp-json/bkgt/v1/teams`
Create a new team (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**Request Body:**
```json
{
    "name": "BKGT Team Beta",
    "description": "New development team",
    "coach": "Jane Smith",
    "founded_year": 2024,
    "logo_url": "https://ledare.bkgt.se/logo.png"
}
```

**Response (201):**
```json
{
    "id": 2,
    "name": "BKGT Team Beta",
    "description": "New development team",
    "coach": "Jane Smith",
    "founded_year": 2024,
    "logo_url": "https://ledare.bkgt.se/logo.png",
    "created_date": "2024-01-15T10:30:00Z",
    "updated_date": "2024-01-15T10:30:00Z"
}
```

#### PUT `/wp-json/bkgt/v1/teams/{id}`
Update team information (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**URL Parameters:**
- `id` (integer): Team ID

**Request Body:**
```json
{
    "name": "BKGT Team Alpha Updated",
    "description": "Updated premier team",
    "coach": "John Doe Jr."
}
```

#### DELETE `/wp-json/bkgt/v1/teams/{id}`
Delete a team (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**URL Parameters:**
- `id` (integer): Team ID

**Response (200):**
```json
{
    "message": "Team deleted successfully"
}
```

### Players

#### GET `/wp-json/bkgt/v1/players`
Get all players with optional filtering.

**Authentication:** JWT Bearer token or API Key  
**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page (max: 100)
- `team_id` (integer): Filter by team
- `search` (string): Search in player names
- `position` (string): Filter by position
- `orderby` (string): Sort field
- `order` (string): Sort order

**Response (200):**
```json
{
    "players": [
        {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "position": "Forward",
            "jersey_number": 10,
            "team_id": 1,
            "team_name": "BKGT Team Alpha",
            "date_of_birth": "1995-05-15",
            "height_cm": 185,
            "weight_kg": 80,
            "nationality": "Swedish",
            "created_date": "2020-01-01T00:00:00Z",
            "updated_date": "2024-01-01T00:00:00Z"
        }
    ],
    "total": 1,
    "page": 1,
    "per_page": 10,
    "total_pages": 1
}
```

#### GET `/wp-json/bkgt/v1/players/{id}`
Get specific player details.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Player ID

#### GET `/wp-json/bkgt/v1/teams/{team_id}/players`
Get all players for a specific team.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `team_id` (integer): Team ID

### Events

#### GET `/wp-json/bkgt/v1/events`
Get all events with optional filtering.

**Authentication:** JWT Bearer token or API Key  
**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `team_id` (integer): Filter by team
- `type` (string): Event type (game, training, tournament)
- `start_date` (string): Start date (YYYY-MM-DD)
- `end_date` (string): End date (YYYY-MM-DD)
- `status` (string): Event status (upcoming, completed, cancelled)

**Response (200):**
```json
{
    "events": [
        {
            "id": 1,
            "title": "BKGT Championship Final",
            "description": "Season finale championship game",
            "type": "game",
            "status": "upcoming",
            "start_date": "2024-06-15T15:00:00Z",
            "end_date": "2024-06-15T17:00:00Z",
            "location": "BKGT Arena",
            "team_id": 1,
            "opponent": "Rival Team",
            "home_away": "home",
            "created_date": "2024-01-01T00:00:00Z",
            "updated_date": "2024-01-01T00:00:00Z"
        }
    ],
    "total": 1,
    "page": 1,
    "per_page": 10,
    "total_pages": 1
}
```

#### GET `/wp-json/bkgt/v1/events/{id}`
Get specific event details.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Event ID

#### POST `/wp-json/bkgt/v1/events`
Create a new event (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**Request Body:**
```json
{
    "title": "Training Session",
    "description": "Regular training",
    "type": "training",
    "start_date": "2024-01-20T10:00:00Z",
    "end_date": "2024-01-20T12:00:00Z",
    "location": "Training Ground A",
    "team_id": 1
}
```

#### PUT `/wp-json/bkgt/v1/events/{id}`
Update event information (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**URL Parameters:**
- `id` (integer): Event ID

#### DELETE `/wp-json/bkgt/v1/events/{id}`
Delete an event (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**URL Parameters:**
- `id` (integer): Event ID

### Documents

#### GET `/wp-json/bkgt/v1/documents`
Get all documents with optional filtering.

**Authentication:** JWT Bearer token or API Key  
**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `type` (string): Document type (rules, handbook, contract)
- `team_id` (integer): Filter by team
- `search` (string): Search in document titles

**Response (200):**
```json
{
    "documents": [
        {
            "id": 1,
            "title": "BKGT Rulebook 2024",
            "description": "Official rules and regulations",
            "type": "rules",
            "file_url": "https://ledare.bkgt.se/documents/rulebook.pdf",
            "file_size": 2048576,
            "mime_type": "application/pdf",
            "team_id": null,
            "uploaded_by": 1,
            "uploaded_date": "2024-01-01T00:00:00Z",
            "version": "1.0"
        }
    ],
    "total": 1,
    "page": 1,
    "per_page": 10,
    "total_pages": 1
}
```

#### GET `/wp-json/bkgt/v1/documents/{id}`
Get specific document details.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Document ID

#### POST `/wp-json/bkgt/v1/documents`
Upload a new document (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**Content-Type:** `multipart/form-data`

**Form Data:**
- `title` (string): Document title
- `description` (string): Document description
- `type` (string): Document type
- `team_id` (integer, optional): Associated team ID
- `file` (file): The document file

#### DELETE `/wp-json/bkgt/v1/documents/{id}`
Delete a document (Admin only).

**Authentication:** JWT Bearer token (admin role)  
**URL Parameters:**
- `id` (integer): Document ID

### Statistics

#### GET `/wp-json/bkgt/v1/stats/overview`
Get overview statistics.

**Authentication:** JWT Bearer token or API Key  

**Response (200):**
```json
{
    "total_teams": 5,
    "total_players": 75,
    "total_events": 120,
    "upcoming_events": 15,
    "completed_events": 105,
    "total_documents": 25,
    "api_requests_today": 1250,
    "active_users": 45
}
```

#### GET `/wp-json/bkgt/v1/stats/teams`
Get team statistics.

**Authentication:** JWT Bearer token or API Key  

**Response (200):**
```json
{
    "teams": [
        {
            "id": 1,
            "name": "BKGT Team Alpha",
            "players_count": 15,
            "events_count": 25,
            "wins": 18,
            "losses": 7,
            "points_for": 450,
            "points_against": 280,
            "total_yards": 3200,
            "passing_yards": 1800,
            "rushing_yards": 1400
        }
    ]
}
```

#### GET `/wp-json/bkgt/v1/stats/players`
Get player statistics.

**Authentication:** JWT Bearer token or API Key  
**Query Parameters:**
- `season` (string): Filter by season (YYYY-YYYY)

**Response (200):**
```json
{
    "players": [
        {
            "id": 1,
            "name": "John Doe",
            "team_name": "BKGT Team Alpha",
            "games_played": 12,
            "touchdowns": 8,
            "passing_yards": 1250,
            "rushing_yards": 450,
            "receiving_yards": 320,
            "interceptions": 2,
            "tackles": 45,
            "sacks": 3.5,
            "field_goals_made": 15,
            "field_goals_attempted": 18
        }
    ]
}
```

## Authentication

The BKGT API supports two authentication methods: API Key authentication and JWT token authentication. API Key authentication is simpler for server-to-server communication, while JWT authentication is better for user sessions.

### API Key Authentication

API keys are generated through the WordPress admin interface and provide immediate access to the API without user login.

**Header Format:**
```
X-API-Key: your-api-key-here
```

**Example Request:**
```bash
curl -X GET "https://ledare.bkgt.se/wp-json/bkgt/v1/teams" \
  -H "X-API-Key: bkgt_1234567890abcdef"
```

**When to Use:**
- Server-to-server API calls
- Background processes
- Third-party integrations
- Mobile apps (with proper key storage)

### JWT Token Authentication

JWT authentication requires user login and provides session-based access with refresh tokens.

**Login Flow:**
1. Send login request with username/password
2. Receive access_token and refresh_token
3. Include access_token in subsequent requests
4. Use refresh_token to get new access_token when expired

**Header Format:**
```
Authorization: Bearer your-jwt-token-here
```

**Complete Authentication Example:**
```javascript
// 1. Login to get tokens
const loginResponse = await fetch('/wp-json/bkgt/v1/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your-api-key'  // API key still needed for login
    },
    body: JSON.stringify({
        username: 'admin',
        password: 'password123'
    })
});

const { access_token, refresh_token } = await loginResponse.json();

// 2. Use access token for authenticated requests
const teamsResponse = await fetch('/wp-json/bkgt/v1/teams', {
    headers: {
        'Authorization': `Bearer ${access_token}`
    }
});

// 3. Refresh token when needed (when access token expires)
const refreshResponse = await fetch('/wp-json/bkgt/v1/auth/refresh', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your-api-key'
    },
    body: JSON.stringify({
        refresh_token: refresh_token
    })
});

const { access_token: new_access_token } = await refreshResponse.json();
```

**Token Expiration:**
- Access tokens: 1 hour
- Refresh tokens: 30 days

**When to Use:**
- User-facing applications
- Mobile apps with user accounts
- Admin interfaces
- When user-specific permissions are needed

### Authentication Priority

When both authentication methods are provided:
1. JWT Bearer token takes precedence
2. Falls back to API Key if JWT is invalid/expired
3. Returns 401 if both are invalid

### Security Best Practices

**API Keys:**
- Store securely (environment variables, secure key management)
- Rotate regularly
- Use different keys for different applications
- Monitor usage in admin dashboard

**JWT Tokens:**
- Never store tokens in localStorage (use httpOnly cookies)
- Implement token refresh logic
- Handle token expiration gracefully
- Clear tokens on logout

**General:**
- Always use HTTPS in production
- Validate SSL certificates
- Implement proper error handling
- Monitor for suspicious activity

## Rate Limiting

- **Authenticated requests**: 1000 requests per hour
- **Unauthenticated requests**: 100 requests per hour
- **Login attempts**: 5 attempts per 15 minutes per IP

## Security Features

- **IP Blocking**: Automatic blocking of suspicious IPs
- **Request Logging**: All API requests are logged
- **Input Validation**: All inputs are validated and sanitized
- **CORS Protection**: Configurable allowed origins
- **SQL Injection Protection**: Prepared statements used throughout
- **XSS Protection**: Output escaping and validation

## Installation

1. Upload the `bkgt-api` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Configure settings in **BKGT API > Settings**
4. Generate API keys in **BKGT API > API Keys**

## Configuration

### Required Settings
- **JWT Secret Key**: Generate a secure random key for JWT signing
- **API Base URL**: Your site's base URL
- **Allowed Origins**: Comma-separated list of allowed CORS origins

### Optional Settings
- **Rate Limit**: Adjust rate limiting thresholds
- **Log Retention**: Days to keep logs (default: 30)
- **Enable Debug**: Enable detailed logging for development

## Admin Interface

The plugin adds a **BKGT API** menu to WordPress admin with:

- **Dashboard**: Overview of API usage and security status
- **API Keys**: Generate and manage API keys
- **Activity Logs**: View all API requests
- **Security Logs**: Monitor security events
- **Settings**: Configure plugin options

## Mobile App Integration

### iOS/Android SDK Preparation
The API is designed to work with mobile apps through:
- JWT token authentication
- Standard REST endpoints
- JSON responses
- Error handling with appropriate HTTP status codes

### Example Usage

```javascript
// Login
const loginResponse = await fetch('/wp-json/bkgt/v1/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your-api-key'
    },
    body: JSON.stringify({
        username: 'user',
        password: 'pass'
    })
});

const { access_token, refresh_token } = await loginResponse.json();

// Get teams
const teamsResponse = await fetch('/wp-json/bkgt/v1/teams', {
    headers: {
        'Authorization': `Bearer ${access_token}`
    }
});

const teams = await teamsResponse.json();
```

## Error Handling

The API returns standard HTTP status codes with detailed error information in JSON format.

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Authentication required or invalid |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 405 | Method Not Allowed | HTTP method not supported |
| 409 | Conflict | Resource conflict (e.g., duplicate) |
| 422 | Unprocessable Entity | Validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |
| 503 | Service Unavailable | Service temporarily unavailable |

### Error Response Format

All error responses follow this structure:

```json
{
    "code": "error_code",
    "message": "Human readable error message",
    "data": {
        "status": 400,
        "details": {
            "field": "specific_field_name",
            "issue": "validation_rule_violated"
        }
    }
}
```

### Common Error Codes

| Error Code | HTTP Status | Description |
|------------|-------------|-------------|
| `invalid_api_key` | 401 | API key is invalid or expired |
| `jwt_token_expired` | 401 | JWT access token has expired |
| `jwt_token_invalid` | 401 | JWT token is malformed or invalid |
| `insufficient_permissions` | 403 | User lacks required permissions |
| `resource_not_found` | 404 | Requested resource doesn't exist |
| `validation_failed` | 422 | Request data failed validation |
| `rate_limit_exceeded` | 429 | Too many requests in time window |
| `duplicate_resource` | 409 | Resource already exists |
| `invalid_request` | 400 | Malformed request |
| `server_error` | 500 | Internal server error |

### Validation Errors

For validation failures (422), the response includes field-specific details:

```json
{
    "code": "validation_failed",
    "message": "Validation failed for one or more fields",
    "data": {
        "status": 422,
        "details": {
            "name": "required",
            "email": "invalid_format",
            "password": "min_length_8"
        }
    }
}
```

### Rate Limiting Errors

When rate limits are exceeded (429), the response includes reset information:

```json
{
    "code": "rate_limit_exceeded",
    "message": "Too many requests. Please try again later.",
    "data": {
        "status": 429,
        "retry_after": 3600,
        "limit": 1000,
        "remaining": 0,
        "reset_time": "2024-01-15T11:00:00Z"
    }
}
```

## Request/Response Formats

### Content Types

- **Request Body:** `application/json` for JSON data, `multipart/form-data` for file uploads
- **Response Body:** Always `application/json`
- **Character Encoding:** UTF-8

### Pagination

List endpoints support pagination with these parameters:

**Request Parameters:**
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 10, max: 100)

**Response Format:**
```json
{
    "data": [...],           // Array of items
    "total": 150,            // Total number of items
    "page": 1,               // Current page
    "per_page": 10,          // Items per page
    "total_pages": 15,       // Total number of pages
    "has_next": true,        // Whether there are more pages
    "has_prev": false        // Whether there are previous pages
}
```

### Filtering and Sorting

**Common Query Parameters:**
- `search` (string): Search within text fields
- `orderby` (string): Sort field (id, name, created_date, updated_date)
- `order` (string): Sort direction (asc, desc)

**Example:**
```
GET /wp-json/bkgt/v1/teams?search=alpha&orderby=name&order=asc&page=1&per_page=20
```

### Date/Time Format

All dates use ISO 8601 format in UTC:
- Format: `YYYY-MM-DDTHH:MM:SSZ`
- Example: `2024-01-15T10:30:00Z`

### File Uploads

Document uploads use `multipart/form-data`:

```javascript
const formData = new FormData();
formData.append('title', 'Document Title');
formData.append('description', 'Document description');
formData.append('type', 'rules');
formData.append('file', fileInput.files[0]);

fetch('/wp-json/bkgt/v1/documents', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${access_token}`
    },
    body: formData
});
```

## Code Examples

### JavaScript (ES6+)

#### Complete Authentication and Data Fetching
```javascript
class BKGTApiClient {
    constructor(baseUrl, apiKey) {
        this.baseUrl = baseUrl;
        this.apiKey = apiKey;
        this.accessToken = null;
        this.refreshToken = null;
    }

    async login(username, password) {
        const response = await fetch(`${this.baseUrl}/wp-json/bkgt/v1/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': this.apiKey
            },
            body: JSON.stringify({ username, password })
        });

        if (!response.ok) {
            throw new Error(`Login failed: ${response.status}`);
        }

        const data = await response.json();
        this.accessToken = data.access_token;
        this.refreshToken = data.refresh_token;
        return data;
    }

    async refreshAccessToken() {
        const response = await fetch(`${this.baseUrl}/wp-json/bkgt/v1/auth/refresh`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': this.apiKey
            },
            body: JSON.stringify({ refresh_token: this.refreshToken })
        });

        if (!response.ok) {
            throw new Error(`Token refresh failed: ${response.status}`);
        }

        const data = await response.json();
        this.accessToken = data.access_token;
        return data;
    }

    async getTeams(options = {}) {
        const params = new URLSearchParams({
            page: options.page || 1,
            per_page: options.perPage || 10,
            ...options
        });

        const response = await fetch(`${this.baseUrl}/wp-json/bkgt/v1/teams?${params}`, {
            headers: {
                'Authorization': `Bearer ${this.accessToken}`,
                'X-API-Key': this.apiKey
            }
        });

        if (response.status === 401) {
            // Try to refresh token
            await this.refreshAccessToken();
            return this.getTeams(options); // Retry with new token
        }

        if (!response.ok) {
            throw new Error(`Failed to fetch teams: ${response.status}`);
        }

        return response.json();
    }

    async createTeam(teamData) {
        const response = await fetch(`${this.baseUrl}/wp-json/bkgt/v1/teams`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.accessToken}`
            },
            body: JSON.stringify(teamData)
        });

        if (!response.ok) {
            throw new Error(`Failed to create team: ${response.status}`);
        }

        return response.json();
    }
}

// Usage
const api = new BKGTApiClient('https://ledare.bkgt.se', 'your-api-key');

try {
    await api.login('admin', 'password');
    const teams = await api.getTeams({ perPage: 20 });
    console.log('Teams:', teams);
} catch (error) {
    console.error('API Error:', error.message);
}
```

### Python (requests library)

```python
import requests
from typing import Optional, Dict, Any

class BKGTApiClient:
    def __init__(self, base_url: str, api_key: str):
        self.base_url = base_url.rstrip('/')
        self.api_key = api_key
        self.access_token: Optional[str] = None
        self.refresh_token: Optional[str] = None
        self.session = requests.Session()
        self.session.headers.update({
            'X-API-Key': self.api_key,
            'Content-Type': 'application/json'
        })

    def login(self, username: str, password: str) -> Dict[str, Any]:
        response = self.session.post(
            f"{self.base_url}/wp-json/bkgt/v1/auth/login",
            json={"username": username, "password": password}
        )
        response.raise_for_status()
        
        data = response.json()
        self.access_token = data['access_token']
        self.refresh_token = data['refresh_token']
        self.session.headers['Authorization'] = f"Bearer {self.access_token}"
        return data

    def refresh_access_token(self) -> Dict[str, Any]:
        response = self.session.post(
            f"{self.base_url}/wp-json/bkgt/v1/auth/refresh",
            json={"refresh_token": self.refresh_token}
        )
        response.raise_for_status()
        
        data = response.json()
        self.access_token = data['access_token']
        self.session.headers['Authorization'] = f"Bearer {self.access_token}"
        return data

    def get_teams(self, **params) -> Dict[str, Any]:
        response = self.session.get(
            f"{self.base_url}/wp-json/bkgt/v1/teams",
            params=params
        )
        
        if response.status_code == 401:
            self.refresh_access_token()
            response = self.session.get(
                f"{self.base_url}/wp-json/bkgt/v1/teams",
                params=params
            )
        
        response.raise_for_status()
        return response.json()

    def create_team(self, team_data: Dict[str, Any]) -> Dict[str, Any]:
        response = self.session.post(
            f"{self.base_url}/wp-json/bkgt/v1/teams",
            json=team_data
        )
        response.raise_for_status()
        return response.json()

# Usage
api = BKGTApiClient('https://ledare.bkgt.se', 'your-api-key')

try:
    api.login('admin', 'password')
    teams = api.get_teams(per_page=20, orderby='name')
    print(f"Found {teams['total']} teams")
    
    new_team = api.create_team({
        'name': 'New Team',
        'description': 'A new team',
        'coach': 'Coach Name'
    })
    print(f"Created team: {new_team['name']}")
    
except requests.exceptions.RequestException as e:
    print(f"API Error: {e}")
```

### cURL Examples

#### Login and Get Teams
```bash
# Login
LOGIN_RESPONSE=$(curl -s -X POST "https://ledare.bkgt.se/wp-json/bkgt/v1/auth/login" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -d '{"username":"admin","password":"password"}')

ACCESS_TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.access_token')

# Get teams
curl -X GET "https://ledare.bkgt.se/wp-json/bkgt/v1/teams" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -H "X-API-Key: your-api-key"
```

#### Create Team
```bash
curl -X POST "https://ledare.bkgt.se/wp-json/bkgt/v1/teams" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -d '{
    "name": "BKGT Team Gamma",
    "description": "New team for 2024",
    "coach": "Mike Johnson",
    "founded_year": 2024
  }'
```

#### Upload Document
```bash
curl -X POST "https://ledare.bkgt.se/wp-json/bkgt/v1/documents" \
  -H "Authorization: Bearer $ACCESS_TOKEN" \
  -F "title=BKGT Rulebook 2024" \
  -F "description=Official rules" \
  -F "type=rules" \
  -F "file=@rulebook.pdf"
```

### PHP (WordPress Integration)

```php
class BKGT_API_Client {
    private $base_url;
    private $api_key;
    private $access_token;
    private $refresh_token;

    public function __construct($base_url, $api_key) {
        $this->base_url = rtrim($base_url, '/');
        $this->api_key = $api_key;
    }

    public function login($username, $password) {
        $response = wp_remote_post($this->base_url . '/wp-json/bkgt/v1/auth/login', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key
            ],
            'body' => json_encode([
                'username' => $username,
                'password' => $password
            ])
        ]);

        if (is_wp_error($response)) {
            throw new Exception('Login request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            throw new Exception('Login failed: ' . ($body['message'] ?? 'Unknown error'));
        }

        $this->access_token = $body['access_token'];
        $this->refresh_token = $body['refresh_token'];
        
        return $body;
    }

    public function get_teams($params = []) {
        $url = add_query_arg($params, $this->base_url . '/wp-json/bkgt/v1/teams');
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'X-API-Key' => $this->api_key
            ]
        ]);

        if (wp_remote_retrieve_response_code($response) === 401) {
            $this->refresh_access_token();
            return $this->get_teams($params); // Retry
        }

        if (is_wp_error($response)) {
            throw new Exception('Request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            throw new Exception('API Error: ' . ($body['message'] ?? 'Unknown error'));
        }

        return $body;
    }

    private function refresh_access_token() {
        $response = wp_remote_post($this->base_url . '/wp-json/bkgt/v1/auth/refresh', [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key
            ],
            'body' => json_encode([
                'refresh_token' => $this->refresh_token
            ])
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            throw new Exception('Token refresh failed');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $this->access_token = $body['access_token'];
    }
}

// Usage
$api = new BKGT_API_Client('https://ledare.bkgt.se', 'your-api-key');

try {
    $api->login('admin', 'password');
    $teams = $api->get_teams(['per_page' => 20]);
    echo "Found {$teams['total']} teams\n";
} catch (Exception $e) {
    echo "Error: " . $e->get_message() . "\n";
}
```

## Development

### File Structure
```
bkgt-api/
├── bkgt-api.php              # Main plugin file
├── includes/
│   ├── class-bkgt-api.php     # Core API class
│   ├── class-bkgt-auth.php    # Authentication
│   ├── class-bkgt-endpoints.php # API endpoints
│   ├── class-bkgt-security.php # Security features
│   └── class-bkgt-notifications.php # Notifications
├── admin/
│   ├── class-bkgt-api-admin.php # Admin interface
│   ├── css/
│   │   └── admin.css          # Admin styles
│   └── js/
│       └── admin.js           # Admin JavaScript
└── README.md                  # This file
```

### Hooks and Filters

The plugin provides several WordPress hooks:

- `bkgt_api_before_request` - Before processing API request
- `bkgt_api_after_request` - After processing API request
- `bkgt_api_auth_success` - On successful authentication
- `bkgt_api_rate_limit_exceeded` - When rate limit is exceeded

## Webhooks and Real-time Updates

The BKGT API supports webhooks for real-time notifications of data changes.

### Available Webhooks

| Event | Description | Payload |
|-------|-------------|---------|
| `team.created` | New team created | Team object |
| `team.updated` | Team information updated | Team object + changes |
| `team.deleted` | Team deleted | Team ID |
| `player.created` | New player added | Player object |
| `player.updated` | Player information updated | Player object + changes |
| `player.deleted` | Player removed | Player ID |
| `event.created` | New event scheduled | Event object |
| `event.updated` | Event information updated | Event object + changes |
| `event.deleted` | Event cancelled | Event ID |
| `document.uploaded` | New document uploaded | Document object |
| `document.deleted` | Document removed | Document ID |

### Webhook Configuration

Webhooks are configured through the WordPress admin interface:

1. Go to **BKGT API > Settings > Webhooks**
2. Add webhook URL
3. Select events to subscribe to
4. Set authentication headers (optional)

### Webhook Payload Format

```json
{
    "event": "team.created",
    "timestamp": "2024-01-15T10:30:00Z",
    "webhook_id": "wh_123456",
    "data": {
        "id": 1,
        "name": "BKGT Team Alpha",
        "description": "Premier team",
        "coach": "John Doe",
        "created_date": "2024-01-15T10:30:00Z"
    },
    "changes": null
}
```

For update events, the `changes` field contains old vs new values:

```json
{
    "event": "team.updated",
    "timestamp": "2024-01-15T11:00:00Z",
    "webhook_id": "wh_123456",
    "data": { /* current team object */ },
    "changes": {
        "coach": {
            "old": "John Doe",
            "new": "Jane Smith"
        }
    }
}
```

### Webhook Security

- **Signature Verification:** Each webhook includes an `X-BKGT-Signature` header
- **Timestamp Validation:** Webhooks include timestamp for replay attack prevention
- **Retry Logic:** Failed webhooks are retried up to 3 times with exponential backoff

### Handling Webhooks

```javascript
app.post('/webhooks/bkgt', (req, res) => {
    const signature = req.headers['x-bkgt-signature'];
    const timestamp = req.headers['x-bkgt-timestamp'];
    const body = JSON.stringify(req.body);
    
    // Verify signature
    const expectedSignature = crypto
        .createHmac('sha256', WEBHOOK_SECRET)
        .update(`${timestamp}.${body}`)
        .digest('hex');
    
    if (signature !== expectedSignature) {
        return res.status(401).json({ error: 'Invalid signature' });
    }
    
    // Process webhook
    const { event, data } = req.body;
    
    switch (event) {
        case 'team.created':
            console.log('New team:', data.name);
            break;
        case 'player.updated':
            console.log('Player updated:', data.id);
            break;
    }
    
    res.json({ received: true });
});
```

## Testing and Development Tools

### API Testing Tools

#### Postman Collection
Import the BKGT API Postman collection for comprehensive testing:

```json
{
    "info": {
        "name": "BKGT API",
        "description": "Complete API collection for BKGT WordPress plugin"
    },
    "variable": [
        {
            "key": "base_url",
            "value": "https://ledare.bkgt.se"
        },
        {
            "key": "api_key",
            "value": "your-api-key"
        }
    ]
}
```

#### Insomnia Workspace
Use the provided Insomnia workspace file for organized API testing with environments for development, staging, and production.

### Development Environment Setup

#### Local WordPress Installation
```bash
# Using Local by Flywheel or similar
# Or manual setup with MAMP/XAMPP

# Install WordPress
wp core download
wp core config --dbname=bkgt_dev --dbuser=root --dbpass=password
wp core install --url=http://localhost:8888 --title="BKGT Dev" --admin_user=admin --admin_password=password --admin_email=admin@example.com

# Install and activate plugin
wp plugin install /path/to/bkgt-api.zip --activate

# Generate test data
wp eval "
global \$wpdb;
\$wpdb->insert('{$wpdb->prefix}bkgt_teams', ['name' => 'Test Team', 'coach' => 'Test Coach']);
\$wpdb->insert('{$wpdb->prefix}bkgt_players', ['first_name' => 'John', 'last_name' => 'Doe', 'team_id' => 1]);
"
```

#### Docker Development Environment
```dockerfile
# Dockerfile
FROM wordpress:latest

# Copy plugin
COPY ./bkgt-api /var/www/html/wp-content/plugins/bkgt-api

# Install dependencies
RUN docker-php-ext-install mysqli

# Enable plugin
RUN echo "php_value upload_max_filesize 50M" >> /usr/local/etc/php/conf.d/uploads.ini
```

```yaml
# docker-compose.yml
version: '3.8'
services:
  wordpress:
    build: .
    ports:
      - "8080:80"
    environment:
      WORDPRESS_DB_HOST: db
      WORDPRESS_DB_USER: wordpress
      WORDPRESS_DB_PASSWORD: wordpress
      WORDPRESS_DB_NAME: wordpress
    volumes:
      - ./bkgt-api:/var/www/html/wp-content/plugins/bkgt-api
  db:
    image: mysql:5.7
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wordpress
      MYSQL_PASSWORD: wordpress
      MYSQL_ROOT_PASSWORD: root
```

### Automated Testing

#### PHPUnit Tests
```bash
# Run plugin tests
cd wp-content/plugins/bkgt-api
composer install
./vendor/bin/phpunit
```

#### API Integration Tests
```javascript
// test/api.test.js
const { expect } = require('chai');
const BKGTApiClient = require('../client');

describe('BKGT API Integration Tests', () => {
    let client;
    
    before(async () => {
        client = new BKGTApiClient('http://localhost:8080', 'test-api-key');
        await client.login('admin', 'password');
    });
    
    it('should get teams', async () => {
        const response = await client.getTeams();
        expect(response.teams).to.be.an('array');
        expect(response.total).to.be.at.least(0);
    });
    
    it('should create team', async () => {
        const team = await client.createTeam({
            name: 'Test Team',
            description: 'Test description',
            coach: 'Test Coach'
        });
        
        expect(team.id).to.be.a('number');
        expect(team.name).to.equal('Test Team');
    });
});
```

### Debugging Tools

#### Enable Debug Logging
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Plugin specific debug
add_filter('bkgt_api_debug_enabled', '__return_true');
```

#### Monitor API Requests
```bash
# Check recent API logs
tail -f wp-content/debug.log | grep bkgt_api

# Monitor database queries
wp query monitor
```

#### Performance Profiling
```php
// Add to functions.php for profiling
add_action('bkgt_api_before_request', function() {
    global $api_start_time;
    $api_start_time = microtime(true);
});

add_action('bkgt_api_after_request', function() {
    global $api_start_time;
    $duration = microtime(true) - $api_start_time;
    error_log("API Request took: {$duration}s");
});
```

## SDKs and Libraries

### Official SDKs

#### JavaScript SDK
```bash
npm install @bkgt/api-client
```

```javascript
import { BKGTApi } from '@bkgt/api-client';

const api = new BKGTApi({
    baseURL: 'https://ledare.bkgt.se',
    apiKey: 'your-api-key'
});

// Auto-handles authentication and token refresh
const teams = await api.teams.list();
const newTeam = await api.teams.create({
    name: 'New Team',
    coach: 'Coach Name'
});
```

#### Python SDK
```bash
pip install bkgt-api
```

```python
from bkgt_api import BKGTApi

api = BKGTApi(
    base_url='https://ledare.bkgt.se',
    api_key='your-api-key'
)

# Auto-handles authentication
teams = api.teams.list()
new_team = api.teams.create({
    'name': 'New Team',
    'coach': 'Coach Name'
})
```

### Community Libraries

- **PHP SDK:** `composer require bkgt/api-php`
- **Java SDK:** Available on Maven Central
- **.NET SDK:** Available on NuGet
- **Go Client:** `go get github.com/bkgt/api-go`

## Migration Guide

### Upgrading from v1.0 to v2.0

#### Breaking Changes
- JWT token format changed (now includes user roles)
- Rate limiting now applies to all endpoints
- Document upload now requires `type` parameter

#### Migration Steps
1. Update client code to handle new JWT format
2. Implement proper error handling for rate limits
3. Add `type` parameter to document uploads
4. Test all API integrations

#### Backward Compatibility
- API v1 endpoints remain available until v3.0
- Use `?version=1` parameter to force v1 behavior

## Testing

### Equipment API Testing
A test script is provided to validate equipment API endpoints:

1. Access the test script at: `wp-content/plugins/bkgt-api/test-equipment-api.php`
2. Available tests:
   - `?test=preview` - Test the preview identifier endpoint
   - `?test=list` - Test equipment listing endpoint

**Note:** You'll need to replace `'test-token'` with a valid JWT token in the test script.

### Manual Testing with cURL
```bash
# Test preview identifier
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "https://your-site.com/wp-json/bkgt/v1/equipment/preview-identifier?manufacturer_id=1&item_type_id=1"

# Test equipment listing
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "https://your-site.com/wp-json/bkgt/v1/equipment"
```

## Changelog

### Version 2.0.0 (Current)
- **New Features:**
  - Webhook system for real-time updates
  - Enhanced security with request signing
  - Improved rate limiting with burst handling
  - Document versioning support
  - Advanced filtering and search
- **Breaking Changes:**
  - JWT token format updated
  - Rate limiting now applies to all endpoints
  - Document upload requires `type` parameter
- **Bug Fixes:**
  - Fixed pagination in large datasets
  - Improved error messages
  - Enhanced CORS handling

### Version 1.0.0
- Initial release
- JWT authentication
- Complete REST API endpoints
- Admin dashboard
- Security monitoring
- Rate limiting
- CORS support

## Changelog

### Version 1.0.0
- Initial release
- JWT authentication
- Complete REST API endpoints
- Admin dashboard
- Security monitoring
- Rate limiting
- CORS support

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please check:
1. WordPress admin > BKGT API > Settings
2. Plugin documentation
3. WordPress support forums