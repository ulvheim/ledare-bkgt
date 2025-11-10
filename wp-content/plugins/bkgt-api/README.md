## 🚀 Quick Start for App Developers

### Authentication Setup

**Most API endpoints require authentication.** Choose one of the following methods:

#### Option 1: API Key Authentication (Recommended for mobile apps)
```javascript
// Include API key in all requests
const headers = {
  'X-API-Key': 'your_api_key_here'  // Replace with your actual API key
};

fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/teams', { headers })
  .then(response => response.json())
  .then(data => console.log(data));
```

#### Option 3: Service API Key Authentication (For internal/system operations)
```javascript
// Service API key for internal WordPress operations
const headers = {
  'X-API-Key': 'bkgt_svc_1a2b3c4d5e6f...'  // Service key with admin privileges
};

fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/health', { headers })
  .then(response => response.json())
  .then(data => console.log(data));
```

**Note:** Documentation endpoints (`/docs` and `/routes`) are public and do not require authentication.

### Testing Your Integration

Access the production test suite at:
**https://ledare.bkgt.se/wp-content/plugins/bkgt-api/test-production-api.php**

This will test all endpoints with your API key and confirm everything is working.

### Common Endpoints for Mobile Apps

#### Get API Documentation
```javascript
GET https://ledare.bkgt.se/wp-json/bkgt/v1/docs?format=html
// No authentication required - public endpoint
```

#### Get API Routes
```javascript
GET https://ledare.bkgt.se/wp-json/bkgt/v1/routes
// No authentication required - public endpoint
```

#### Get All Teams
```javascript
GET https://ledare.bkgt.se/wp-json/bkgt/v1/teams
Headers: { 'X-API-Key': 'your_api_key_here' }
```

#### Get Equipment Manufacturers
```javascript
GET https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/manufacturers
Headers: { 'X-API-Key': '047619e3c335576a70fcd1f9929883ca' }
```

#### Get System Statistics
```javascript
GET https://ledare.bkgt.se/wp-json/bkgt/v1/stats/overview
Headers: { 'X-API-Key': '047619e3c335576a70fcd1f9929883ca' }
```

### Error Handling

```javascript
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/teams', { headers })
  .then(response => {
    if (!response.ok) {
      if (response.status === 401) {
        // Authentication failed - check API key
        throw new Error('Authentication failed');
      }
      if (response.status === 429) {
        // Rate limited
        throw new Error('Too many requests');
      }
      throw new Error(`HTTP ${response.status}`);
    }
    return response.json();
  })
  .then(data => console.log(data))
  .catch(error => console.error('API Error:', error));
```

### Obtaining API Keys

**⚠️ Security Notice:** API keys are sensitive credentials and should never be hardcoded in documentation or shared publicly.

**For External Applications:**
- Contact the system administrator to obtain a valid API key
- API keys are managed through the WordPress admin interface
- Keys can have different permission levels and expiration dates

**For Development/Testing:**
- Use the API key provided by your system administrator
- Never commit API keys to version control
- Consider using environment variables or secure key management systems
- For local development, create a `.env` file (excluded from version control)

```javascript
// Example: Load from environment variable
const apiKey = process.env.BKGT_API_KEY;
const headers = {
  'X-API-Key': apiKey
};
```

All endpoints now require authentication - there are no public endpoints.

## 🏗️ API-First Architecture

This plugin implements a comprehensive API-First Architecture with the following key components:

### Service API Keys
- **Automatic Generation:** Service API keys are automatically generated on plugin activation
- **Controlled Rotation:** Keys are rotated every 30 days with a 24-hour transition period
- **Admin Management:** Service keys can be manually rotated and configured via WordPress admin
- **Full Access:** Service keys have administrator privileges for internal operations

### Service-to-Service Authentication
- **Internal API Calls:** WordPress components use service API keys for internal API communication
- **Secure Headers:** All internal API calls include proper authentication headers
- **Fallback Support:** Graceful fallback to direct database access if API calls fail

### Health Monitoring
- **System Health Endpoint:** `GET /bkgt/v1/health` provides system status and authentication type
- **Database Checks:** Validates database connectivity and response times
- **Service Validation:** Confirms API services are operational

### Admin Interface Migration
- **API-Based Operations:** Admin interfaces now use API calls instead of direct database queries
- **Consistent Access:** Ensures all data access goes through the same API layer
- **Improved Security:** Reduces direct database access and centralizes data validation

### Key Benefits
- **Consistency:** All data access (internal and external) uses the same API
- **Security:** Centralized authentication and authorization
- **Maintainability:** Single source of truth for data operations
- **Scalability:** API layer can be optimized independently of admin interfaces
- **Monitoring:** Comprehensive health checks and usage tracking

### Rate Limits

- **Authenticated requests:** 1000 per hour
- **Unauthenticated requests:** 100 per hour (will get 401 errors)

### Support

If you encounter issues:
1. Check the test suite: https://ledare.bkgt.se/wp-content/plugins/bkgt-api/test-production-api.php
2. Verify your API key is correct
3. Ensure you're using HTTPS
4. Check the error response for details

## Features

- **Self-Documenting API**: Dynamic documentation endpoints that automatically stay current
- **API Route Discovery**: Programmatically discover all available endpoints and their capabilities
- **JWT Authentication**: Secure token-based authentication with refresh tokens
- **API Key Management**: Generate and manage API keys for different applications
- **Rate Limiting**: Prevent abuse with configurable rate limits
- **Security Monitoring**: Comprehensive logging and threat detection
- **CORS Support**: Configurable cross-origin resource sharing
- **Input Validation**: Robust validation and sanitization
- **Admin Dashboard**: Complete admin interface for monitoring and management

## API Endpoints

### Documentation

#### GET `/wp-json/bkgt/v1/docs`
Get the complete API documentation.

**Authentication:** None required (public endpoint)
**Query Parameters:**
- `format` (string, optional): Response format - `html` (default), `markdown`, or `json`

**✨ Dynamic Feature:** This endpoint automatically serves the latest documentation from the README.md file. Any changes to the documentation are immediately reflected without requiring code updates.

**Response (200) - HTML format:**
```html
<!DOCTYPE html>
<html>
<head><title>BKGT API Documentation</title></head>
<body>
<h1>BKGT API Documentation</h1>
<!-- Complete documentation content with styling -->
</body>
</html>
```

**Response (200) - JSON format:**
```json
{
    "documentation": "# BKGT API Documentation\n\nComplete markdown content...",
    "format": "markdown",
    "last_updated": 1735689600
}
```

**Usage Examples:**
```javascript
// Get documentation as styled HTML (perfect for web browsers)
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/docs')
  .then(r => r.text())
  .then(html => document.body.innerHTML = html);

// Get documentation as JSON for programmatic processing
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/docs?format=json')
  .then(r => r.json())
  .then(data => console.log('Last updated:', new Date(data.last_updated * 1000)));

// Get raw markdown for custom processing
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/docs?format=markdown')
  .then(r => r.text())
  .then(markdown => console.log('Raw docs:', markdown));
```

#### GET `/wp-json/bkgt/v1/routes`
Get information about all available API routes.

**Authentication:** None required (public endpoint)
**Query Parameters:**
- `namespace` (string, optional): API namespace to filter by (default: "bkgt/v1")
- `detailed` (boolean, optional): Include detailed route configuration (default: false)

**✨ Dynamic Feature:** This endpoint automatically discovers all registered API routes in real-time. New endpoints from plugin updates are immediately available without documentation updates.

**Response (200) - Simple mode:**
```json
{
    "namespace": "bkgt/v1",
    "routes": {
        "/bkgt/v1/teams": ["GET"],
        "/bkgt/v1/equipment": ["GET", "POST"],
        "/bkgt/v1/docs": ["GET"],
        "/bkgt/v1/routes": ["GET"]
    },
    "total_routes": 45,
    "detailed": false,
    "generated_at": "2025-11-09 12:00:00"
}
```

**Response (200) - Detailed mode (`?detailed=true`):**
```json
{
    "namespace": "bkgt/v1",
    "routes": {
        "/bkgt/v1/teams": {
            "methods": ["GET"],
            "args": {
                "page": {"type": "integer", "default": 1},
                "per_page": {"type": "integer", "default": 10}
            },
            "permission_callback": ["BKGT_API_Endpoints", "validate_api_key"]
        }
    },
    "total_routes": 45,
    "detailed": true,
    "generated_at": "2025-11-09 12:00:00"
}
```

**Usage Examples:**
```javascript
// Discover all available endpoints
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/routes')
  .then(r => r.json())
  .then(data => {
    console.log(`${data.total_routes} endpoints available`);
    Object.keys(data.routes).forEach(route => {
      console.log(`- ${route}: ${data.routes[route].join(', ')}`);
    });
  });

// Get detailed route information for development
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/routes?detailed=true')
  .then(r => r.json())
  .then(data => {
    // Use detailed route information for API client generation
    console.log('Detailed route configs:', data.routes);
  });

// Check for specific endpoint availability
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/routes')
  .then(r => r.json())
  .then(data => {
    const hasEquipmentEndpoint = Object.keys(data.routes).some(route =>
      route.includes('/equipment')
    );
    console.log('Equipment endpoints available:', hasEquipmentEndpoint);
  });
```

**🔄 Auto-Discovery Benefits:**
- **Always Current:** Routes are discovered dynamically from WordPress REST API registry
- **Plugin Agnostic:** Works with any plugin that registers REST routes
- **Real-time Updates:** Changes are reflected immediately when plugins are activated/deactivated
- **Development Aid:** Perfect for building dynamic API clients or documentation tools

### Dynamic Documentation Benefits

The self-documenting API system provides several key advantages:

#### 🚀 **Always Up-to-Date**
- Documentation automatically reflects code changes
- No manual synchronization required between code and docs
- Eliminates documentation drift over time

#### 🔧 **Developer Experience**
- **API Discovery:** Instantly see all available endpoints
- **Interactive Docs:** Browse documentation directly in browsers
- **Multiple Formats:** Choose HTML for reading, JSON for processing, Markdown for editing
- **No Authentication:** Public access for easy sharing and reference

#### 🛠️ **Integration Ready**
- **Machine Readable:** JSON responses perfect for API client generation
- **CI/CD Friendly:** Automated testing can verify endpoint availability
- **Third-party Tools:** Easy integration with API gateways, documentation tools, and monitoring systems

#### 📈 **Maintenance Free**
- **Zero Maintenance:** Documentation updates automatically with code changes
- **Plugin Compatible:** Works with any WordPress plugin that registers REST routes
- **Version Agnostic:** Automatically adapts to API versioning changes

### Usage Examples

#### Building an API Client
```javascript
// Discover available endpoints
const routes = await fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/routes?detailed=true')
  .then(r => r.json());

// Generate client methods dynamically
const apiClient = {};
Object.entries(routes.routes).forEach(([route, config]) => {
  const endpoint = route.replace('/bkgt/v1/', '');
  apiClient[endpoint] = (params) => {
    return fetch(`https://ledare.bkgt.se${route}`, {
      method: config.methods[0], // Use first available method
      headers: { 'Authorization': 'Bearer ' + apiKey },
      body: JSON.stringify(params)
    });
  };
});
```

#### Documentation Integration
```javascript
// Embed live documentation in your application
const docsContainer = document.getElementById('api-docs');
const docs = await fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/docs')
  .then(r => r.text());
docsContainer.innerHTML = docs;
```

#### Monitoring and Health Checks
```javascript
// Verify API availability
const healthCheck = async () => {
  try {
    const routes = await fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/routes')
      .then(r => r.json());
    return routes.total_routes > 0;
  } catch (error) {
    return false;
  }
};
```

### Troubleshooting

#### Common Issues

**Documentation not loading:**
- Ensure the `bkgt-api` plugin is activated
- Check that the README.md file exists in the plugin directory
- Verify WordPress REST API is enabled

**Routes not showing:**
- Confirm plugins registering routes are active
- Check for PHP errors in server logs
- Ensure proper WordPress REST API initialization

**Authentication issues:**
- Verify API key/JWT token format
- Check token expiration
- Confirm user has appropriate permissions

**CORS errors:**
- Ensure proper CORS headers are set for cross-origin requests
- Check server configuration for API access

#### Debug Mode
Enable WordPress debug mode to see detailed error information:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

#### Testing Endpoints
Use tools like Postman, curl, or browser dev tools to test endpoints:
```bash
# Test documentation endpoint
curl -X GET "https://ledare.bkgt.se/wp-json/bkgt/v1/docs"

# Test routes discovery
curl -X GET "https://ledare.bkgt.se/wp-json/bkgt/v1/routes?detailed=true"
```

## Changelog

### v2.5.0 - Identifier Resolution for Equipment Endpoints (2025-11-10)
- 🆔 **Implemented identifier resolution system**
  - Equipment endpoints now accept both numeric IDs and unique identifiers
  - URL pattern updated from `/equipment/{id}` to `/equipment/{identifier}`
  - Supports unique identifier format: `####-####-#####` (manufacturer-itemtype-sequential)
  - Maintains full backward compatibility with existing numeric ID usage
- 🔄 **Enhanced API stability for integrations**
  - Eliminates downstream integration issues caused by volatile database IDs
  - Provides stable, meaningful identifiers for external system integration
  - Automatic resolution between identifiers and internal database IDs
- 📚 **Updated endpoint documentation**
  - All equipment CRUD endpoints now document identifier support
  - Added examples showing both ID types in API calls
  - Clarified parameter naming from `{id}` to `{identifier}` for clarity

### v2.4.0 - Advanced Equipment Features (2025-11-10)
- 🔍 **Implemented equipment search functionality**
  - `GET /wp-json/bkgt/v1/equipment/search` endpoint
  - Multi-field search across title, identifier, manufacturer, type, assignee
  - Configurable result fields and limits
  - Optimized database queries for performance
- 📦 **Implemented bulk equipment operations**
  - `POST /wp-json/bkgt/v1/equipment/bulk` endpoint
  - Bulk delete and export operations
  - Proper error handling and transaction safety
  - CSV export functionality for selected items
- 🔧 **Enhanced class loading system**
  - Automatic loading of bkgt-inventory classes in API context
  - Proper dependency management between plugins
  - Error handling for missing plugin dependencies
- 📚 **Updated documentation**
  - Complete API reference for new endpoints
  - Usage examples and response formats
  - Field specifications and validation rules

### v2.3.0 - Equipment Database Schema Enhancement (2025-11-10)
- 🗄️ **Added comprehensive equipment fields to database**
  - `location_id` (integer): Equipment storage location reference
  - `purchase_date` (date): Date equipment was purchased (YYYY-MM-DD format)
  - `purchase_price` (decimal): Equipment purchase cost
  - `warranty_expiry` (date): Warranty expiration date (YYYY-MM-DD format)
  - `size` (varchar): Equipment size specification
- 🔧 **Fixed critical equipment API bugs**
  - Resolved HTTP 500 errors on PUT/DELETE operations
  - Added missing database columns causing update failures
  - Enhanced error handling and validation
- 📋 **Updated API documentation**
  - Added new fields to equipment CRUD examples
  - Improved field validation documentation
  - Enhanced error response details

### v2.2.0 - Equipment Title Simplification (2025-11-09)
- 🗑️ **Removed title parameter from equipment API**
  - **BREAKING CHANGE**: `title` field no longer accepted in equipment creation/update
  - Title is now automatically generated from `unique_identifier`
  - Unique identifier serves as the equipment identifier and display title
- 🔄 **Simplified equipment data model**
  - Equipment identification now uses unique identifier exclusively
  - Consistent behavior between admin interface and API
  - Reduced API complexity by eliminating redundant title field

### v2.1.0 - Dynamic Documentation (2025-11-09)
- ✨ **Added self-documenting API endpoints**
  - `/docs` endpoint with HTML, JSON, and Markdown formats
  - `/routes` endpoint for automatic API discovery
- 🔄 **Dynamic documentation system**
  - Auto-generates documentation from README.md
  - Real-time route discovery from WordPress REST API
  - No manual updates required when endpoints change
- 📚 **Enhanced developer experience**
  - Public access to documentation (no auth required)
  - Multiple output formats for different use cases
  - Integration-ready JSON responses

### v2.0.0 - Equipment API Enhancement (2025-11-08)
- ➕ **Added size field to equipment API**
  - Database schema updated with size column
  - CRUD operations support size parameter
  - Frontend compatibility ensured
- 🔧 **API endpoint improvements**
  - Enhanced error handling and validation
  - Consistent response formats
  - Better parameter documentation

### v1.0.0 - Initial Release (2025-11-01)
- 🚀 **Core API functionality**
  - JWT and API key authentication
  - Teams and equipment management
  - WordPress REST API integration
- 📖 **Basic documentation**
  - API endpoint reference
  - Authentication guide
  - Usage examples

### Authentication

### Equipment

> **Important:** Equipment CRUD operations (CREATE, UPDATE, DELETE) are implemented in the `bkgt-inventory` plugin, not this `bkgt-api` plugin. This plugin only provides READ operations and reference data (manufacturers, types, locations, analytics).
>
> **Plugin Separation:**
> - **bkgt-api**: Read-only equipment endpoints, manufacturers, types, locations, analytics
> - **bkgt-inventory**: Full equipment CRUD operations, assignments, bulk operations, comprehensive database schema
>
> For equipment CRUD operations, use the `bkgt-inventory` plugin endpoints:
> - `POST /wp-json/bkgt/v1/equipment` - Create equipment (bkgt-inventory plugin)
> - `PUT /wp-json/bkgt/v1/equipment/{id}` - Update equipment (bkgt-inventory plugin) ✅ **FIXED**
> - `DELETE /wp-json/bkgt/v1/equipment/{id}` - Delete equipment (bkgt-inventory plugin) ✅ **FIXED**
> - `POST /wp-json/bkgt/v1/equipment/bulk` - Bulk operations ✅ **IMPLEMENTED**
> - `GET /wp-json/bkgt/v1/equipment/search` - Search functionality ✅ **IMPLEMENTED**

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
Get all equipment items with optional filtering.

**Authentication:** JWT Bearer token or API Key
**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 10, max: 100)
- `manufacturer_id` (integer): Filter by manufacturer
- `item_type_id` (integer): Filter by item type
- `location_id` (integer): Filter by location
- `condition_status` (string): Filter by condition (normal, needs_repair, repaired, reported_lost, scrapped)
- `assignment_status` (string): Filter by assignment status (assigned, available, overdue)
- `search` (string): Search in item identifiers or notes
- `orderby` (string): Sort field (id, unique_identifier, created_date, updated_date)
- `order` (string): Sort order (asc, desc)

**Response (200):**
```json
{
    "equipment": [
        {
            "id": 1,
            "unique_identifier": "0001-0001-00001",
            "manufacturer_id": 1,
            "manufacturer_name": "Schutt",
            "item_type_id": 1,
            "item_type_name": "Helmet",
            "location_id": 1,
            "location_name": "Storage Room A",
            "condition_status": "normal",
            "assignment_status": "available",
            "purchase_date": "2024-01-01",
            "purchase_price": 299.99,
            "warranty_expiry": "2026-01-01",
            "notes": "Brand new helmet",
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

#### GET `/wp-json/bkgt/v1/equipment/{identifier}`
Get specific equipment item details.

**Authentication:** JWT Bearer token or API Key
**URL Parameters:**
- `identifier` (string/integer): Equipment item ID (numeric) or unique identifier (format: ####-####-#####)

**Examples:**
- `GET /wp-json/bkgt/v1/equipment/123` - Get equipment by numeric ID
- `GET /wp-json/bkgt/v1/equipment/0001-0001-00001` - Get equipment by unique identifier

**Response (200):**

**Response (200):**
```json
{
    "id": 1,
    "unique_identifier": "0001-0001-00001",
    "manufacturer_id": 1,
    "manufacturer_name": "Schutt",
    "item_type_id": 1,
    "item_type_name": "Helmet",
    "location_id": 1,
    "location_name": "Storage Room A",
    "condition_status": "normal",
    "assignment_status": "available",
    "purchase_date": "2024-01-01",
    "purchase_price": 299.99,
    "warranty_expiry": "2026-01-01",
    "notes": "Brand new helmet",
    "assignment_history": [
        {
            "assignment_id": 1,
            "assignee_type": "individual",
            "assignee_id": 25,
            "assignee_name": "John Doe",
            "assigned_date": "2024-02-01T00:00:00Z",
            "due_date": "2024-06-01",
            "return_date": "2024-05-15T00:00:00Z",
            "condition_on_return": "normal"
        }
    ],
    "created_date": "2024-01-01T00:00:00Z",
    "updated_date": "2024-01-01T00:00:00Z"
}
```

### 🔧 Equipment CRUD Operations (bkgt-inventory Plugin)

**Important:** Equipment creation, updates, and deletion are handled by the `bkgt-inventory` plugin. Use these endpoints for managing equipment inventory:

#### Creating Equipment

**Endpoint:** `POST /wp-json/bkgt/v1/equipment`  
**Plugin:** bkgt-inventory  
**Authentication:** JWT Bearer token or API Key

```javascript
const equipmentData = {
    "manufacturer_id": 1,         // Required: Get from /equipment/manufacturers
    "item_type_id": 1,            // Required: Get from /equipment/types
    "size": "Large",              // Optional: Equipment size (S, M, L, XL, etc.)
    "storage_location": "Storage Room A", // Optional: Storage location
    "location_id": 1,             // Optional: Location ID from /locations
    "purchase_date": "2024-01-15", // Optional: Purchase date (YYYY-MM-DD)
    "purchase_price": 299.99,     // Optional: Purchase price (numeric)
    "warranty_expiry": "2026-01-15", // Optional: Warranty expiry (YYYY-MM-DD)
    "condition_status": "normal", // Optional: normal, needs_repair, repaired, reported_lost, scrapped
    "notes": "New helmet for spring season"
};

fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/equipment', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your_api_key_here'
    },
    body: JSON.stringify(equipmentData)
})
.then(response => response.json())
.then(data => {
    console.log('Equipment created:', data);
    // Response includes auto-generated unique_identifier (used as title)
});
```

**Response (201):**
```json
{
    "id": 123,
    "unique_identifier": "0001-0001-00005",
    "title": "0001-0001-00005",
    "size": "Large",
    "manufacturer_id": 1,
    "item_type_id": 1,
    "storage_location": "Storage Room A",
    "location_id": 1,
    "purchase_date": "2024-01-15",
    "purchase_price": 299.99,
    "warranty_expiry": "2026-01-15",
    "condition_status": "normal",
    "notes": "New helmet for spring season",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
}
```

**Note:** The `title` field is automatically generated from the `unique_identifier` and cannot be manually specified.

#### Field Validation

The equipment API includes validation for data integrity:

- **`purchase_date`**: Must be in `YYYY-MM-DD` format (e.g., "2024-01-15")
- **`warranty_expiry`**: Must be in `YYYY-MM-DD` format (e.g., "2026-01-15") 
- **`purchase_price`**: Must be a valid numeric value (e.g., 299.99)
- **`location_id`**: Must reference an existing location ID
- **`manufacturer_id`**: Must reference an existing manufacturer ID
- **`item_type_id`**: Must reference an existing item type ID

Invalid data will result in a `400 Bad Request` response with validation error details.

#### Updating Equipment

**Endpoint:** `PUT /wp-json/bkgt/v1/equipment/{identifier}`  
**Plugin:** bkgt-inventory  
**Authentication:** JWT Bearer token or API Key

```javascript
const updateData = {
    "size": "Medium",           // Update equipment size
    "location_id": 2,           // Update storage location
    "purchase_price": 279.99,   // Update purchase price
    "warranty_expiry": "2026-06-15", // Update warranty date
    "condition_status": "needs_repair",
    "notes": "Crack in helmet shell - needs repair"
};

// Update by numeric ID
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/123', {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your_api_key_here'
    },
    body: JSON.stringify(updateData)
})
.then(response => response.json())
.then(data => {
    console.log('Equipment updated:', data);
});

// Update by unique identifier
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/0001-0001-00001', {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your_api_key_here'
    },
    body: JSON.stringify(updateData)
})
.then(response => response.json())
.then(data => {
    console.log('Equipment updated:', data);
});
```

#### Deleting Equipment

**Endpoint:** `DELETE /wp-json/bkgt/v1/equipment/{identifier}`  
**Plugin:** bkgt-inventory  
**Authentication:** JWT Bearer token

```javascript
// Delete by numeric ID
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/123', {
    method: 'DELETE',
    headers: {
        'X-API-Key': 'your_api_key_here'
    }
})
.then(response => {
    if (response.status === 204) {
        console.log('Equipment deleted successfully');
    } else {
        return response.json();
    }
});

// Delete by unique identifier
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/0001-0001-00001', {
    method: 'DELETE',
    headers: {
        'X-API-Key': 'your_api_key_here'
    }
})
.then(response => {
    if (response.status === 204) {
        console.log('Equipment deleted successfully');
    } else {
        return response.json();
    }
});
```

#### Bulk Equipment Operations

**Endpoint:** `POST /wp-json/bkgt/v1/equipment/bulk`  
**Plugin:** bkgt-api (with bkgt-inventory classes)  
**Authentication:** JWT Bearer token or API Key

```javascript
const bulkData = {
    "operation": "delete",  // delete, export
    "item_ids": [1, 2, 3, 4, 5]
};

fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/bulk', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your_api_key_here'
    },
    body: JSON.stringify(bulkData)
})
.then(response => response.json())
.then(data => {
    console.log('Bulk operation completed:', data);
});
```

**Bulk Operations:**
- `delete`: Delete multiple equipment items
- `export`: Export multiple equipment items to CSV format

**Response for delete operation:**
```json
{
    "message": "Bulk delete completed. 3 items deleted.",
    "deleted_count": 3,
    "errors": []
}
```

**Response for export operation:**
```json
{
    "message": "Bulk export completed.",
    "item_count": 5,
    "csv_data": [
        ["Unik Identifierare", "Artikelnamn", "Tillverkare", ...],
        ["0001-0001-00001", "Fotboll", "Nike", ...],
        ...
    ]
}
```

#### Equipment Search

**Endpoint:** `GET /wp-json/bkgt/v1/equipment/search`  
**Plugin:** bkgt-api (with bkgt-inventory classes)  
**Authentication:** JWT Bearer token or API Key

Search equipment across multiple fields including title, unique identifier, serial number, manufacturer, item type, and assignee name.

**Query Parameters:**
- `q` (string, required): Search query
- `limit` (integer, optional): Maximum results to return (default: 20, max: 100)
- `fields` (array, optional): Fields to include in results (default: ['id', 'unique_identifier', 'title'])

**Example Request:**
```javascript
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/search?q=football&limit=10&fields[]=id&fields[]=title&fields[]=manufacturer_name', {
    headers: {
        'X-API-Key': 'your_api_key_here'
    }
})
.then(response => response.json())
.then(data => {
    console.log('Search results:', data.results);
});
```

**Response (200):**
```json
{
    "query": "football",
    "results": [
        {
            "id": 1,
            "unique_identifier": "0001-0001-00001",
            "title": "Fotboll Nike",
            "manufacturer_name": "Nike"
        },
        {
            "id": 5,
            "unique_identifier": "0001-0002-00005",
            "title": "Träningsfotboll",
            "manufacturer_name": "Adidas"
        }
    ],
    "total": 2,
    "limit": 10,
    "fields": ["id", "unique_identifier", "title", "manufacturer_name"]
}
```

**Available Fields:**
- `id`: Equipment ID
- `unique_identifier`: Unique identifier
- `title`: Equipment title
- `manufacturer_name`: Manufacturer name
- `item_type_name`: Item type name
- `condition_status`: Condition status
- `assignee_name`: Current assignee name

#### Equipment Assignment Management

**Assign Equipment:** `POST /wp-json/bkgt/v1/equipment/{identifier}/assignment`  
**Plugin:** bkgt-inventory

```javascript
const assignmentData = {
    "assignment_type": "individual",  // individual, team, club
    "assignee_id": 25,               // User ID for individual, Team ID for team
    "due_date": "2024-06-30",
    "notes": "Assigned for spring training"
};

// Assign by numeric ID
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/123/assignment', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your_api_key_here'
    },
    body: JSON.stringify(assignmentData)
});

// Assign by unique identifier
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/0001-0001-00001/assignment', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your_api_key_here'
    },
    body: JSON.stringify(assignmentData)
});
```

**Unassign Equipment:** `DELETE /wp-json/bkgt/v1/equipment/{identifier}/assignment`  
**Plugin:** bkgt-inventory

```javascript
const returnData = {
    "return_date": "2024-05-15",
    "condition_status": "normal",
    "notes": "Returned in good condition"
};

// Unassign by numeric ID
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/123/assignment', {
    method: 'DELETE',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your_api_key_here'
    },
    body: JSON.stringify(returnData)
});

// Unassign by unique identifier
fetch('https://ledare.bkgt.se/wp-json/bkgt/v1/equipment/0001-0001-00001/assignment', {
    method: 'DELETE',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'your_api_key_here'
    },
    body: JSON.stringify(returnData)
});
```

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

#### POST `/wp-json/bkgt/v1/equipment/manufacturers`
Create a new manufacturer.

**Authentication:** JWT Bearer token or API Key  
**Request Body:**
```json
{
    "name": "New Manufacturer Name",
    "description": "Optional description",
    "website": "https://manufacturer.com",
    "contact_email": "contact@manufacturer.com"
}
```

**Response (201):**
```json
{
    "message": "Manufacturer created successfully.",
    "manufacturer": {
        "id": 123,
        "manufacturer_id": "MFG-A1B2C3D4",
        "name": "New Manufacturer Name",
        "contact_info": {
            "description": "Optional description",
            "website": "https://manufacturer.com",
            "email": "contact@manufacturer.com"
        }
    }
}
```

#### PUT `/wp-json/bkgt/v1/equipment/manufacturers/{id}`
Update an existing manufacturer.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Manufacturer ID

**Request Body:**
```json
{
    "name": "Updated Manufacturer Name",
    "description": "Updated description",
    "website": "https://updated-manufacturer.com",
    "contact_email": "updated@manufacturer.com"
}
```

**Response (200):**
```json
{
    "message": "Manufacturer updated successfully."
}
```

#### DELETE `/wp-json/bkgt/v1/equipment/manufacturers/{id}`
Delete a manufacturer.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Manufacturer ID

**Response (200):**
```json
{
    "message": "Manufacturer deleted successfully."
}
```

**Error (409):** If manufacturer is assigned to equipment items
```json
{
    "code": "manufacturer_in_use",
    "message": "Cannot delete manufacturer that is assigned to equipment items.",
    "data": { "status": 409 }
}
```

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

#### POST `/wp-json/bkgt/v1/equipment/types`
Create a new equipment type.

**Authentication:** JWT Bearer token or API Key  
**Request Body:**
```json
{
    "name": "New Equipment Type",
    "description": "Description of the equipment type",
    "custom_fields": {
        "size": "string",
        "certification": "string",
        "weight": "number"
    }
}
```

**Response (201):**
```json
{
    "message": "Item type created successfully.",
    "type": {
        "id": 123,
        "item_type_id": "TYPE-A1B2C3D4",
        "name": "New Equipment Type",
        "description": "Description of the equipment type",
        "custom_fields": {
            "size": "string",
            "certification": "string",
            "weight": "number"
        }
    }
}
```

#### PUT `/wp-json/bkgt/v1/equipment/types/{id}`
Update an existing equipment type.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Equipment type ID

**Request Body:**
```json
{
    "name": "Updated Equipment Type",
    "description": "Updated description",
    "custom_fields": {
        "size": "string",
        "certification": "string",
        "color": "string"
    }
}
```

**Response (200):**
```json
{
    "message": "Item type updated successfully."
}
```

#### DELETE `/wp-json/bkgt/v1/equipment/types/{id}`
Delete an equipment type.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Equipment type ID

**Response (200):**
```json
{
    "message": "Item type deleted successfully."
}
```

**Error (409):** If item type is assigned to equipment items
```json
{
    "code": "item_type_in_use",
    "message": "Cannot delete item type that is assigned to equipment items.",
    "data": { "status": 409 }
}
```

### Equipment Assignments

> **Important:** Equipment assignment endpoints are implemented in the `bkgt-inventory` plugin, not this `bkgt-api` plugin. The endpoints documented below are handled by the bkgt-inventory plugin.

#### GET `/wp-json/bkgt/v1/equipment/{identifier}/assignment`
Get current assignment details for equipment item.

**Plugin:** bkgt-inventory
**Authentication:** JWT Bearer token or API Key
**URL Parameters:**
- `identifier` (string/integer): Equipment item ID (numeric) or unique identifier (format: ####-####-#####)

**Examples:**
- `GET /wp-json/bkgt/v1/equipment/123/assignment` - Get assignment by numeric ID
- `GET /wp-json/bkgt/v1/equipment/0001-0001-00001/assignment` - Get assignment by unique identifier

**Response (200):**
```json
{
    "assignment": {
        "type": "individual",
        "id": 25,
        "name": "John Doe",
        "assigned_date": "2024-01-15T10:30:00Z",
        "due_date": "2024-06-30"
    }
}
```

#### POST `/wp-json/bkgt/v1/equipment/{identifier}/assignment`
Assign equipment to a user, team, or club.

**Plugin:** bkgt-inventory
**Authentication:** JWT Bearer token
**URL Parameters:**
- `identifier` (string/integer): Equipment item ID (numeric) or unique identifier (format: ####-####-#####)

**Examples:**
- `POST /wp-json/bkgt/v1/equipment/123/assignment` - Assign by numeric ID
- `POST /wp-json/bkgt/v1/equipment/0001-0001-00001/assignment` - Assign by unique identifier

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

**Response (200):**
```json
{
    "message": "Equipment assigned successfully."
}
```

#### DELETE `/wp-json/bkgt/v1/equipment/{identifier}/assignment`
Unassign equipment from current assignment.

**Plugin:** bkgt-inventory
**Authentication:** JWT Bearer token
**URL Parameters:**
- `identifier` (string/integer): Equipment item ID (numeric) or unique identifier (format: ####-####-#####)

**Examples:**
- `DELETE /wp-json/bkgt/v1/equipment/123/assignment` - Unassign by numeric ID
- `DELETE /wp-json/bkgt/v1/equipment/0001-0001-00001/assignment` - Unassign by unique identifier

**Request Body (optional):**
```json
{
    "return_date": "2024-01-20",
    "condition_status": "normal",
    "notes": "Returned in good condition"
}
```

**Response (200):**
```json
{
    "message": "Equipment unassigned successfully."
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

#### POST `/wp-json/bkgt/v1/equipment/locations`
Create a new storage location.

**Authentication:** JWT Bearer token or API Key  
**Request Body:**
```json
{
    "name": "New Storage Location",
    "location_type": "storage",
    "address": "456 Oak St, Stockholm",
    "contact_person": "John Doe",
    "contact_phone": "+46 70 987 6543",
    "contact_email": "john.doe@bkgt.se",
    "capacity": 150
}
```

**Response (201):**
```json
{
    "message": "Location created successfully.",
    "location": {
        "id": 123,
        "name": "New Storage Location",
        "slug": "new-storage-location",
        "location_type": "storage",
        "address": "456 Oak St, Stockholm",
        "contact_person": "John Doe",
        "contact_phone": "+46 70 987 6543",
        "contact_email": "john.doe@bkgt.se",
        "capacity": 150,
        "is_active": true
    }
}
```

#### PUT `/wp-json/bkgt/v1/equipment/locations/{id}`
Update an existing location.

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Location ID

**Request Body:**
```json
{
    "name": "Updated Storage Location",
    "location_type": "warehouse",
    "address": "789 Pine St, Stockholm",
    "contact_person": "Jane Smith",
    "contact_phone": "+46 70 555 1234",
    "contact_email": "jane.smith@bkgt.se",
    "capacity": 300
}
```

**Response (200):**
```json
{
    "message": "Location updated successfully."
}
```

#### DELETE `/wp-json/bkgt/v1/equipment/locations/{id}`
Delete a location (soft delete - marks as inactive).

**Authentication:** JWT Bearer token or API Key  
**URL Parameters:**
- `id` (integer): Location ID

**Response (200):**
```json
{
    "message": "Location deleted successfully."
}
```

**Error (409):** If location contains equipment items
```json
{
    "code": "location_in_use",
    "message": "Cannot delete location that contains equipment items.",
    "data": { "status": 409 }
}
```

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

> **Note:** Teams are atomic reference data automatically scraped from svenskalag.se. They cannot be created, updated, or deleted through the API. Teams serve as foreign keys for other data entities like players, events, and equipment assignments.

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
            "source_id": "P2013",
            "source_url": "https://www.svenskalag.se/bkgt/teams/p2013",
            "category": "senior",
            "season": "2024",
            "coach": "John Doe",
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
    "source_id": "P2013",
    "source_url": "https://www.svenskalag.se/bkgt/teams/p2013",
    "category": "senior",
    "season": "2024",
    "coach": "John Doe",
    "players_count": 15,
    "created_date": "2020-01-01T00:00:00Z",
    "updated_date": "2024-01-01T00:00:00Z"
}
```

### Players

> **Note:** Players are atomic reference data automatically scraped from svenskalag.se. They cannot be created, updated, or deleted through the API. Player data serves as reference information for statistics, events, and other team-related activities.

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
            "source_id": "P12345",
            "source_url": "https://www.svenskalag.se/bkgt/players/p12345",
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

### User Management

#### GET `/wp-json/bkgt/v1/admin/users`
Get all users with optional filtering (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 20, max: 100)
- `search` (string): Search in username, email, or display name
- `role` (string): Filter by role
- `orderby` (string): Sort field (ID, user_login, user_email, user_registered, display_name)
- `order` (string): Sort order (asc, desc)

**Response (200):**
```json
{
    "success": true,
    "data": {
        "users": [
            {
                "id": 1,
                "username": "admin",
                "email": "admin@example.com",
                "first_name": "Admin",
                "last_name": "User",
                "display_name": "Admin User",
                "roles": ["administrator"],
                "capabilities": ["manage_options", "edit_users"],
                "registered_date": "2024-01-01T00:00:00Z",
                "last_login": "2024-12-01T10:30:00Z"
            }
        ],
        "pagination": {
            "page": 1,
            "per_page": 20,
            "total": 1,
            "total_pages": 1
        }
    }
}
```

#### GET `/wp-json/bkgt/v1/admin/users/{id}`
Get specific user details (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**URL Parameters:**
- `id` (integer): User ID

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "username": "admin",
        "email": "admin@example.com",
        "first_name": "Admin",
        "last_name": "User",
        "display_name": "Admin User",
        "roles": ["administrator"],
        "capabilities": ["manage_options", "edit_users"],
        "registered_date": "2024-01-01T00:00:00Z",
        "last_login": "2024-12-01T10:30:00Z"
    }
}
```

#### POST `/wp-json/bkgt/v1/admin/users`
Create a new user (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**Request Body:**
```json
{
    "username": "newuser",
    "email": "newuser@example.com",
    "password": "securepassword123",
    "first_name": "New",
    "last_name": "User",
    "display_name": "New User",
    "role": "editor"
}
```

**Response (201):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "username": "newuser",
        "email": "newuser@example.com",
        "first_name": "New",
        "last_name": "User",
        "display_name": "New User",
        "roles": ["editor"],
        "capabilities": ["edit_posts", "edit_pages"],
        "registered_date": "2024-12-01T12:00:00Z"
    },
    "message": "User created successfully"
}
```

#### PUT `/wp-json/bkgt/v1/admin/users/{id}`
Update user information (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**URL Parameters:**
- `id` (integer): User ID

**Request Body:**
```json
{
    "email": "updated@example.com",
    "first_name": "Updated",
    "last_name": "Name",
    "display_name": "Updated Name",
    "role": "author"
}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "username": "newuser",
        "email": "updated@example.com",
        "first_name": "Updated",
        "last_name": "Name",
        "display_name": "Updated Name",
        "roles": ["author"],
        "capabilities": ["edit_posts", "publish_posts"]
    },
    "message": "User updated successfully"
}
```

#### DELETE `/wp-json/bkgt/v1/admin/users/{id}`
Delete a user (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**URL Parameters:**
- `id` (integer): User ID

**Query Parameters:**
- `reassign` (integer, optional): User ID to reassign content to

**Response (200):**
```json
{
    "success": true,
    "message": "User deleted successfully"
}
```

### Role Management

#### GET `/wp-json/bkgt/v1/admin/roles`
Get all roles and their capabilities (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  

**Response (200):**
```json
{
    "success": true,
    "data": {
        "administrator": {
            "name": "Administrator",
            "capabilities": {
                "manage_options": true,
                "edit_users": true,
                "delete_users": true
            },
            "user_count": 1
        },
        "editor": {
            "name": "Editor",
            "capabilities": {
                "edit_posts": true,
                "publish_posts": true,
                "edit_pages": true
            },
            "user_count": 3
        }
    }
}
```

#### GET `/wp-json/bkgt/v1/admin/roles/{role}`
Get specific role details (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**URL Parameters:**
- `role` (string): Role slug

**Response (200):**
```json
{
    "success": true,
    "data": {
        "role": "editor",
        "name": "Editor",
        "capabilities": {
            "edit_posts": true,
            "publish_posts": true,
            "edit_pages": true
        },
        "user_count": 3
    }
}
```

#### POST `/wp-json/bkgt/v1/admin/roles`
Create a new role (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**Request Body:**
```json
{
    "role": "coach",
    "display_name": "Coach",
    "capabilities": {
        "read": true,
        "edit_posts": true,
        "upload_files": true
    }
}
```

**Response (201):**
```json
{
    "success": true,
    "data": {
        "role": "coach",
        "name": "Coach",
        "capabilities": {
            "read": true,
            "edit_posts": true,
            "upload_files": true
        }
    },
    "message": "Role created successfully"
}
```

#### PUT `/wp-json/bkgt/v1/admin/roles/{role}`
Update role capabilities (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**URL Parameters:**
- `role` (string): Role slug

**Request Body:**
```json
{
    "display_name": "Team Coach",
    "capabilities": {
        "read": true,
        "edit_posts": true,
        "upload_files": true,
        "manage_categories": true
    }
}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "role": "coach",
        "name": "Team Coach",
        "capabilities": {
            "read": true,
            "edit_posts": true,
            "upload_files": true,
            "manage_categories": true
        }
    },
    "message": "Role updated successfully"
}
```

#### DELETE `/wp-json/bkgt/v1/admin/roles/{role}`
Delete a role (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**URL Parameters:**
- `role` (string): Role slug

**Note:** Cannot delete core WordPress roles (administrator, editor, author, contributor, subscriber).

**Response (200):**
```json
{
    "success": true,
    "message": "Role deleted successfully"
}
```

### User-Role Assignment

#### GET `/wp-json/bkgt/v1/admin/users/{user_id}/roles`
Get user roles (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**URL Parameters:**
- `user_id` (integer): User ID

**Response (200):**
```json
{
    "success": true,
    "data": {
        "administrator": {
            "name": "Administrator",
            "capabilities": {
                "manage_options": true,
                "edit_users": true
            }
        }
    }
}
```

#### POST `/wp-json/bkgt/v1/admin/users/{user_id}/roles`
Add role to user (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**URL Parameters:**
- `user_id` (integer): User ID

**Request Body:**
```json
{
    "role": "editor"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Role added to user successfully"
}
```

#### DELETE `/wp-json/bkgt/v1/admin/users/{user_id}/roles/{role}`
Remove role from user (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**URL Parameters:**
- `user_id` (integer): User ID
- `role` (string): Role slug

**Response (200):**
```json
{
    "success": true,
        "message": "Role removed from user successfully"
}
```

### Teams Management

#### POST `/wp-json/bkgt/v1/admin/teams/clear-repopulate`
Clear all teams and repopulate from svenskalag.se (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  

**Description:** This endpoint clears all existing teams from the database and repopulates them by scraping the latest data from svenskalag.se. Teams are treated as atomic reference data and cannot be manually created, updated, or deleted through the API.

**Response (200):**
```json
{
    "success": true,
    "message": "Teams cleared and repopulated successfully. Removed 15 teams, added 12 teams.",
    "data": {
        "teams_cleared": 15,
        "teams_added": 12,
        "sample_teams": [
            {
                "name": "BKGT Herr A",
                "source_id": "P2013",
                "source_url": "https://www.svenskalag.se/bkgt/teams/p2013"
            }
        ]
    }
}
```

### Players Management

#### POST `/wp-json/bkgt/v1/admin/players/clear-repopulate`
Clear all players and repopulate from svenskalag.se (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  

**Description:** This endpoint clears all existing players from the database and repopulates them by scraping the latest data from svenskalag.se. Players are treated as atomic reference data and cannot be manually created, updated, or deleted through the API.

**Response (200):**
```json
{
    "success": true,
    "message": "Players cleared and repopulated successfully. Removed 45 players, added 38 players.",
    "data": {
        "players_cleared": 45,
        "players_added": 38,
        "sample_players": [
            {
                "first_name": "John",
                "last_name": "Doe",
                "team_id": 1
            }
        ]
    }
}
```

### BKGT Dashboard

#### GET `/wp-json/bkgt/v1/admin/dashboard`
Get comprehensive BKGT system dashboard information (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  

**Response (200):**
```json
{
    "success": true,
    "data": {
        "system_info": {
            "wordpress_version": "6.4.1",
            "php_version": "8.1.12",
            "bkgt_core_version": "1.0.0",
            "server_software": "Apache/2.4.54",
            "database_version": "10.6.12-MariaDB",
            "memory_limit": "256M",
            "max_execution_time": "30",
            "upload_max_filesize": "64M",
            "post_max_size": "64M",
            "debug_mode": false,
            "debug_log": false
        },
        "plugins": {
            "bkgt-core": {
                "name": "BKGT Core",
                "version": "1.0.0",
                "active": true,
                "path": "bkgt-core/bkgt-core.php"
            },
            "bkgt-api": {
                "name": "BKGT API",
                "version": "1.0.0",
                "active": true,
                "path": "bkgt-api/bkgt-api.php"
            }
        },
        "system_status": {
            "database_connection": {
                "status": "healthy",
                "message": "Database connection is healthy"
            },
            "file_permissions": {
                "status": "healthy",
                "message": "File permissions are correct"
            },
            "memory_usage": {
                "used": "45.2 MB",
                "limit": "256M",
                "percentage": 17.7
            },
            "disk_space": {
                "free": "15.3 GB",
                "total": "50 GB",
                "percentage": 69.4
            }
        },
        "recent_activity": {
            "api_requests_last_24h": 1250,
            "recent_requests": [
                {
                    "endpoint": "/teams",
                    "method": "GET",
                    "response_code": 200,
                    "created_at": "2024-12-01 14:30:25"
                }
            ]
        },
        "generated_at": "2024-12-01 15:00:00"
    }
}
```

### BKGT Error Logs

#### GET `/wp-json/bkgt/v1/admin/error-logs`
Get recent error logs with optional filtering (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  
**Query Parameters:**
- `limit` (integer): Number of logs to retrieve (default: 50, max: 500)
- `level` (string): Filter by log level (critical, error, warning, info, debug)
- `start_date` (string): Filter logs from this date (YYYY-MM-DD)
- `end_date` (string): Filter logs until this date (YYYY-MM-DD)

**Response (200):**
```json
{
    "success": true,
    "data": {
        "logs": [
            {
                "timestamp": "2024-12-01 14:30:25",
                "level": "error",
                "message": "Database connection failed",
                "user": "admin",
                "context": {
                    "operation": "user_update",
                    "user_id": 123
                }
            }
        ],
        "total": 1,
        "limit": 50
    }
}
```

#### DELETE `/wp-json/bkgt/v1/admin/error-logs`
Clear all error logs (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  

**Response (200):**
```json
{
    "success": true,
    "message": "Error logs cleared successfully"
}
```

#### GET `/wp-json/bkgt/v1/admin/error-statistics`
Get error statistics overview (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  

**Response (200):**
```json
{
    "success": true,
    "data": {
        "total_errors": 25,
        "critical": 2,
        "errors": 15,
        "warnings": 8,
        "by_type": {
            "database": 10,
            "api": 8,
            "filesystem": 7
        }
    }
}
```

#### GET `/wp-json/bkgt/v1/admin/system-health`
Get comprehensive system health check (Admin only).

**Authentication:** JWT Bearer token (admin role) or API Key with admin permissions  

**Response (200):**
```json
{
    "success": true,
    "data": {
        "status": "healthy",
        "checks": {
            "database": {
                "status": "healthy",
                "message": "Database connection is healthy"
            },
            "filesystem": {
                "status": "healthy",
                "message": "File permissions are correct"
            },
            "memory": {
                "status": "healthy",
                "message": "Memory usage is normal",
                "details": {
                    "used": "45.2 MB",
                    "limit": "256M",
                    "percentage": 17.7
                }
            },
            "disk_space": {
                "status": "warning",
                "message": "Low disk space available",
                "details": {
                    "free": "2.1 GB",
                    "total": "50 GB",
                    "percentage": 95.8
                }
            }
        },
        "timestamp": "2024-12-01 15:00:00"
    }
}
```

## AuthenticationThe BKGT API supports two authentication methods: API Key authentication and JWT token authentication. API Key authentication is simpler for server-to-server communication, while JWT authentication is better for user sessions.

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

### Version 1.0.0 (Current)
- **New Features:**
  - JWT authentication
  - Complete REST API endpoints
  - Admin dashboard
  - Security monitoring
  - Rate limiting
  - CORS support
- **Breaking Changes:**
  - All endpoints now require authentication
- **Bug Fixes:**
  - Fixed database query issues in players and events endpoints
  - Resolved duplicate method declaration errors
- **Security:**
  - API key and JWT token authentication
  - Request logging and monitoring

### Version 1.0.0
- Initial release
- JWT authentication
- Complete REST API endpoints
- Admin dashboard
- Security monitoring
- Rate limiting
- CORS support

## Changelog

### Version 1.2.0 (Current)
- **Major Architecture Update: API-First Implementation**
  - Service API key system with automatic rotation
  - Service-to-service authentication for internal operations
  - Admin interface migration to API-based operations
  - Health monitoring and system status endpoints
- **New Components:**
  - `BKGT_API_Service_Client` - Service client for internal API calls
  - `BKGT_API_Service_Admin` - Admin interface for service key management
  - Service authentication with virtual admin user
  - Automatic key rotation with transition periods
- **Security Enhancements:**
  - Centralized authentication for all data access
  - Reduced direct database queries in admin interfaces
  - Consistent API access patterns across all components
- **API Endpoints Added:**
  - `GET /health` - System health and authentication status
- **Admin Features:**
  - Service API key management interface
  - Manual key rotation capabilities
  - Key rotation interval configuration
  - Service key testing and validation

### Version 1.1.0
- **New Features:**
  - Equipment assignment management API endpoints
  - Enhanced assignment tracking and history
  - Improved equipment filtering and search
- **API Endpoints Added:**
  - `GET /equipment/{id}/assignment` - Get assignment details
  - `POST /equipment/{id}/assignment` - Assign equipment
  - `DELETE /equipment/{id}/assignment` - Unassign equipment
- **Note:** Equipment CRUD and bulk operations moved to `bkgt-inventory` plugin
- **Documentation Updates:**
  - Complete API reference for assignment endpoints
  - Updated examples and usage patterns

### Version 1.0.0
- Initial release
- JWT authentication
- Complete REST API endpoints
- Admin dashboard
- Security monitoring
- Rate limiting
- CORS support

## 🔄 Auto-Update API

The BKGT API includes comprehensive auto-update functionality for the BKGT Manager desktop application. This allows seamless updates without user intervention.

### Update Endpoints

#### Get Latest Version Information
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

#### Download Update Package
```http
GET /wp-json/bkgt/v1/updates/download/{version}/{platform}
Headers:
  X-API-Key: your_api_key_here
```

**Response:** Binary update package with appropriate headers.

#### Check Version Compatibility
```http
GET /wp-json/bkgt/v1/updates/compatibility/{current_version}
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

#### Report Update Status
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
  "status": "completed",
  "error_message": null,
  "install_time_seconds": 45
}
```

### Admin Update Management

#### Upload Update Package (Admin Only)
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

#### List Updates (Admin Only)
```http
GET /wp-json/bkgt/v1/updates/admin/list?page=1&per_page=20
Headers:
  X-API-Key: your_admin_api_key_here
```

#### Deactivate Update (Admin Only)
```http
DELETE /wp-json/bkgt/v1/updates/{version}
Headers:
  X-API-Key: your_admin_api_key_here
```

### Update API Features

- **Secure Downloads:** SHA256 hash verification for all packages
- **Platform Support:** Windows, macOS (Intel/Apple Silicon), Linux
- **Version Management:** Semantic versioning with compatibility checking
- **Analytics:** Update adoption and failure tracking
- **Admin Interface:** Complete update management through WordPress admin

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please check:
1. WordPress admin > BKGT API > Settings
2. Plugin documentation
3. WordPress support forums