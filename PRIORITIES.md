# BKGT Development Priorities

## 🚀 Current Status
- ✅ **Data Integrity**: Fixed team count discrepancy and removed fake teams
- ✅ **Core Functionality**: Document management, data scraping, and admin interfaces working
- 🔄 **API Development**: Planning secure REST API for mobile/desktop app integration

## 🎯 High Priority - Mobile/Desktop App API

### Overview
Create a comprehensive WordPress plugin that provides secure REST API endpoints for mobile and desktop applications to access BKGT features.

### Plugin Name: `bkgt-api`

### Core Features
1. **Authentication & Security**
   - JWT token-based authentication
   - WordPress user role integration
   - API key management for external apps
   - Rate limiting and request throttling

2. **Data Endpoints**
   - Teams management (CRUD operations)
   - Player management (CRUD operations)
   - Events/Matches management
   - Document management
   - Statistics and analytics

3. **User Management**
   - User registration and login
   - Profile management
   - Role-based access control
   - Session management

4. **Real-time Features**
   - Push notifications for updates
   - Live match updates
   - Document change notifications

### Technical Architecture

#### 1. Plugin Structure
```
bkgt-api/
├── bkgt-api.php (Main plugin file)
├── includes/
│   ├── class-bkgt-api.php (Core API class)
│   ├── class-bkgt-auth.php (Authentication handler)
│   ├── class-bkgt-endpoints.php (API endpoints)
│   ├── class-bkgt-security.php (Security features)
│   └── class-bkgt-notifications.php (Push notifications)
├── admin/
│   ├── class-bkgt-api-admin.php (Admin interface)
│   └── templates/ (Admin templates)
├── public/
│   └── js/ (Frontend scripts if needed)
└── languages/ (Translations)
```

#### 2. API Endpoints Structure
```
/wp-json/bkgt/v1/
├── auth/
│   ├── login
│   ├── register
│   ├── logout
│   ├── refresh-token
│   └── validate-token
├── teams/
│   ├── GET /teams (List teams)
│   ├── GET /teams/{id} (Get team details)
│   ├── POST /teams (Create team)
│   ├── PUT /teams/{id} (Update team)
│   └── DELETE /teams/{id} (Delete team)
├── players/
│   ├── GET /players (List players)
│   ├── GET /players/{id} (Get player details)
│   ├── POST /players (Create player)
│   └── PUT /players/{id} (Update player)
├── events/
│   ├── GET /events (List events)
│   ├── GET /events/{id} (Get event details)
│   ├── POST /events (Create event)
│   └── PUT /events/{id} (Update event)
├── documents/
│   ├── GET /documents (List documents)
│   ├── GET /documents/{id} (Get document)
│   ├── POST /documents (Upload document)
│   └── DELETE /documents/{id} (Delete document)
└── stats/
    ├── GET /stats/teams (Team statistics)
    ├── GET /stats/players (Player statistics)
    └── GET /stats/events (Event statistics)
```

#### 3. Authentication Flow
```
1. App requests login → POST /wp-json/bkgt/v1/auth/login
2. Server validates credentials
3. Server returns JWT access token + refresh token
4. App stores tokens securely
5. App includes Bearer token in subsequent requests
6. Server validates token on each request
7. Token refresh when needed
```

#### 4. Security Measures
- **JWT Tokens**: Short-lived access tokens (15 minutes) + refresh tokens (7 days)
- **API Keys**: For server-to-server communication
- **Rate Limiting**: 100 requests per minute per user/IP
- **CORS**: Configurable allowed origins
- **Input Validation**: Comprehensive sanitization
- **Audit Logging**: All API requests logged
- **HTTPS Only**: Force SSL for all API calls

#### 5. Mobile App Integration
- **iOS/Android SDK**: Provide client libraries
- **Offline Support**: Cache strategy for offline use
- **Background Sync**: Automatic data synchronization
- **Push Notifications**: Firebase/APNs integration

### Development Phases

#### Phase 1: Foundation (Week 1-2)
- [ ] Create plugin structure and basic setup
- [ ] Implement JWT authentication system
- [ ] Set up basic API infrastructure
- [ ] Create authentication endpoints
- [ ] Add rate limiting and security measures

#### Phase 2: Core Endpoints (Week 3-4)
- [ ] Implement teams CRUD endpoints
- [ ] Implement players CRUD endpoints
- [ ] Add proper error handling and validation
- [ ] Create comprehensive API documentation

#### Phase 3: Advanced Features (Week 5-6)
- [ ] Add events and documents endpoints
- [ ] Implement statistics endpoints
- [ ] Add push notification system
- [ ] Create admin interface for API management

#### Phase 4: Testing & Optimization (Week 7-8)
- [ ] Comprehensive testing (unit, integration, security)
- [ ] Performance optimization
- [ ] Create mobile app integration guides
- [ ] Documentation and deployment

### Dependencies
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.7+
- JWT PHP library
- CORS handling library

### Success Metrics
- ✅ All CRUD operations working via API
- ✅ Secure authentication system
- ✅ Comprehensive API documentation
- ✅ Mobile app integration tested
- ✅ Performance benchmarks met (response time < 200ms)

### Risk Mitigation
- **Security**: Regular security audits and penetration testing
- **Performance**: Load testing and optimization
- **Compatibility**: Test with various WordPress versions and plugins
- **Documentation**: Keep API docs updated with each change

---

## ✅ COMPLETED - API-First Architecture Implementation

### Overview
Successfully transitioned all user-facing WordPress admin interfaces to use the REST API instead of direct database connections. This ensures consistency, security, and maintainability across the entire system.

### ✅ Completed Implementation
- **Consistency**: Single source of truth for all data operations
- **Security**: Centralized authentication and validation
- **Maintainability**: Business logic changes in one place
- **Testing**: Comprehensive API testing covers all use cases
- **Future-Proofing**: Same API serves web admin, mobile apps, and third-party integrations

### Implementation Strategy

#### Phase 1: Service Account & Authentication (Week 1)
**Goal**: Establish secure internal API communication

**Tasks:**
- [ ] Create dedicated service API key for internal system use
- [ ] Implement service-to-service authentication mechanism
- [ ] Set up monthly key rotation process and documentation
- [ ] Create admin interface for key management
- [ ] Add health checks for API key validation

**Technical Details:**
- Service key stored in `wp_options` table
- Separate from user API keys for security isolation
- Monthly manual rotation (not automated) for reliability
- Grace period for key transitions

#### Phase 2: Admin Interface Migration (Week 2-3)
**Goal**: Convert WordPress admin pages to use API endpoints

**Tasks:**
- [ ] Migrate equipment management admin pages to API calls
- [ ] Update team/player management interfaces
- [ ] Convert document management to API-based
- [ ] Implement proper error handling for API failures
- [ ] Add loading states and user feedback

**Migration Pattern:**
```php
// Before: Direct database access
$items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkgt_inventory_items");

// After: API-based access
$response = wp_remote_get(rest_url('bkgt/v1/equipment'), array(
    'headers' => array(
        'Authorization' => 'Bearer ' . get_service_jwt_token(),
        'X-API-Key' => get_option('bkgt_service_api_key')
    )
));
$items = json_decode($response['body'])->equipment;
```

#### Phase 3: Caching & Performance Optimization (Week 4)
**Goal**: Ensure API-based system performs as well as direct database access

**Tasks:**
- [ ] Implement aggressive API response caching (Redis/WordPress transients)
- [ ] Add request deduplication to prevent duplicate API calls
- [ ] Optimize batch operations for bulk data loading
- [ ] Implement HTTP/2 multiplexing for concurrent requests
- [ ] Add fallback mechanisms for API unavailability

**Caching Strategy:**
- API responses cached for 5-15 minutes
- User-specific data cached per session
- Static reference data (manufacturers, types) cached indefinitely
- Cache invalidation on data changes

#### Phase 4: Shortcodes & Public Pages (Week 5)
**Goal**: Update public-facing components to use API where appropriate

**Tasks:**
- [ ] Audit existing shortcodes for data access patterns
- [ ] Convert dynamic data loading to API calls
- [ ] Maintain direct database access for static content
- [ ] Implement lazy loading for performance-critical pages

**Decision Criteria:**
- Use API: Dynamic data, user-specific content, real-time updates
- Keep Direct: Static content, high-frequency operations, menu generation

#### Phase 5: Testing & Monitoring (Week 6)
**Goal**: Ensure system reliability and performance

**Tasks:**
- [ ] Comprehensive integration testing
- [ ] Performance benchmarking (API vs direct database)
- [ ] Load testing with concurrent users
- [ ] API monitoring and alerting setup
- [ ] Documentation updates for developers

### Technical Architecture

#### Service Authentication
```php
function get_service_auth_headers() {
    return array(
        'Authorization' => 'Bearer ' . generate_service_jwt(),
        'X-API-Key' => get_option('bkgt_service_api_key'),
        'X-Service-Call' => 'true' // Identify internal calls
    );
}
```

#### Error Handling & Fallbacks
```php
function api_call_with_fallback($endpoint, $fallback_callback) {
    try {
        $response = wp_remote_get(rest_url($endpoint), array(
            'headers' => get_service_auth_headers(),
            'timeout' => 5
        ));
        
        if (is_wp_error($response) || $response['response']['code'] !== 200) {
            // Log error and use fallback
            error_log('API call failed, using fallback: ' . $endpoint);
            return $fallback_callback();
        }
        
        return json_decode($response['body']);
    } catch (Exception $e) {
        error_log('API exception, using fallback: ' . $e->getMessage());
        return $fallback_callback();
    }
}
```

#### Caching Implementation
```php
function get_cached_api_response($endpoint, $ttl = 300) {
    $cache_key = 'bkgt_api_' . md5($endpoint);
    
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    $response = wp_remote_get(rest_url($endpoint), array(
        'headers' => get_service_auth_headers()
    ));
    
    if (!is_wp_error($response) && $response['response']['code'] === 200) {
        $data = json_decode($response['body']);
        set_transient($cache_key, $data, $ttl);
        return $data;
    }
    
    return false;
}
```

### Security Considerations

#### Service Account Security
- **Isolation**: Service key separate from user keys
- **Limited Scope**: Service key restricted to necessary endpoints
- **IP Restrictions**: Optional IP whitelisting for internal calls
- **Audit Logging**: All service API calls logged separately

#### Key Rotation Process
1. **Generation**: Create new service key via admin interface
2. **Testing**: Verify new key works with all endpoints
3. **Transition**: Update system to use new key
4. **Grace Period**: Keep old key active for 24 hours
5. **Deactivation**: Remove old key and log rotation event

### Success Metrics
- ✅ **Performance**: API-based pages load within 10% of direct database performance
- ✅ **Reliability**: < 0.1% API call failures under normal operation
- ✅ **Security**: All data access properly authenticated and authorized
- ✅ **Maintainability**: Single source of truth for business logic
- ✅ **Developer Experience**: Clear API contracts and comprehensive documentation

### Risk Mitigation
- **Fallback Mechanisms**: Direct database access available if API fails
- **Gradual Migration**: Phase-by-phase rollout allows for issues to be caught early
- **Performance Monitoring**: Real-time monitoring of API response times
- **Rollback Plan**: Ability to revert individual components to direct database access
- **Documentation**: Comprehensive guides for maintaining API-first architecture

---

## � SWE3 Document Integration

### Overview
Implement automated scraping and curation of official SWE3 documents (rules, regulations, competition guidelines) from https://amerikanskfotboll.swe3.se/information-verktyg/spelregler-tavlingsbestammelser/ with daily updates and DMS integration.

### Key Documents Identified
1. **Tävlingsbestämmelser 2026** (Competition Regulations 2026)
   - URL: https://amerikanskfotboll.swe3.se/wp-content/uploads/sites/4/2025/11/Tavlingsbestammelser-Amerikansk-fotboll-2026.pdf
   - Current version with 2026 updates

2. **Additional Documents** (12+ files on rules page)
   - Game rules, referee guidelines, competition formats
   - Development series modifications
   - Easy Football instructions

### Technical Implementation

#### 1. SWE3 Scraper Plugin Structure
```
wp-content/plugins/bkgt-swe3-scraper/
├── bkgt-swe3-scraper.php (Main plugin file)
├── includes/
│   ├── class-bkgt-swe3-scraper.php (Core scraper)
│   ├── class-bkgt-swe3-parser.php (HTML/PDF parser)
│   ├── class-bkgt-swe3-scheduler.php (WP Cron integration)
│   └── class-bkgt-swe3-dms-integration.php (DMS integration)
├── admin/
│   ├── class-bkgt-swe3-admin.php (Admin interface)
│   └── templates/ (Admin templates)
└── logs/ (Scraping logs and error reports)
```

#### 2. Document Processing Flow
```
1. Daily Cron Job → Check SWE3 rules page
2. Parse HTML → Extract document links and metadata
3. Download PDFs → Store locally with versioning
4. Extract metadata → Title, date, version, type
5. DMS Integration → Create/update documents with "SWE3-" prefix
6. Permission Setup → Make viewable by all users
7. Notification → Alert admins of updates
8. Logging → Record all operations for audit
```

#### 3. DMS Integration Details
- **Document Naming**: "SWE3-[Document Type]-[Year]-[Version]"
- **Categories**: "SWE3 Official Documents", "Competition Rules", "Game Regulations"
- **Permissions**: Public read access for all authenticated users
- **Versioning**: Automatic version control for document updates
- **Metadata**: Include SWE3 publication date, document type, applicable season

#### 4. Scraping Strategy
- **Frequency**: Daily at 02:00 (low traffic time)
- **Error Handling**: Retry logic with exponential backoff
- **Change Detection**: MD5 hash comparison to detect updates
- **Rate Limiting**: Respectful crawling with delays
- **User Agent**: Identify as BKGT system for transparency

#### 5. Data Storage
- **Local Storage**: wp-content/uploads/swe3-documents/
- **Database Tracking**: Custom table for document metadata
- **Version History**: Full version control with change logs
- **Backup**: Automatic backups before updates

### Development Phases

#### Phase 1: Foundation (1-2 days)
- Plugin structure and core classes
- Basic HTML scraping from rules page
- PDF download and storage functionality
- Database schema for SWE3 documents

#### Phase 2: DMS Integration (2-3 days)
- Document upload to DMS with SWE3 prefix
- Permission management (public access)
- Metadata extraction and storage
- Version control implementation

#### Phase 3: Automation & Monitoring (1-2 days)
- WP Cron scheduling for daily updates
- Change detection and update notifications
- Error handling and retry logic
- Admin dashboard for monitoring

#### Phase 4: Testing & Deployment (1 day)
- Comprehensive testing of scraping logic
- Error scenario testing
- Performance optimization
- Production deployment

### Success Metrics
- **Document Coverage**: 100% of available SWE3 documents scraped
- **Update Accuracy**: <24 hour delay for new SWE3 publications
- **System Reliability**: 99% successful daily scrapes
- **User Access**: All documents viewable by authenticated users
- **Version Control**: Complete history of document changes

### Risk Mitigation
- **Rate Limiting**: Implement delays to avoid overwhelming SWE3 servers
- **Fallback Strategy**: Manual document upload if scraping fails
- **Legal Compliance**: Ensure fair use and proper attribution
- **Change Monitoring**: Alert system for SWE3 website structure changes

---

## �📋 Future Priorities

### Medium Priority
- Enhanced analytics dashboard
- Advanced reporting features
- Integration with external systems

### Low Priority
- Multi-language support
- Advanced user permissions
- Third-party integrations