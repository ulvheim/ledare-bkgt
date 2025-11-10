# Comprehensive Equipment Search Implementation Guide

## Overview
This document provides detailed recommendations for implementing comprehensive search functionality across all equipment database columns in the BKGT Manager backend API.

## Current Issues Identified

### 1. Limited Search Scope
- **Current**: Search only works on "item identifiers or notes" according to API docs
- **Reality**: API responses don't include `notes` field, and `size` field (added in v2.0.0) is missing from responses
- **Impact**: Users cannot search for equipment by size designations like "TDJ"

### 2. Missing Fields in API Responses
- `size` field exists in database but not returned in API responses
- `notes` field not included in equipment list responses
- Limited search capability across available fields

## Recommended Implementation

### 1. Database-Level Full-Text Search

#### MySQL Full-Text Index Creation
```sql
-- Add full-text index on equipment table
ALTER TABLE equipment ADD FULLTEXT INDEX ft_equipment_search
(unique_identifier, title, storage_location, notes, size, condition_reason, sticker_code);

-- For MySQL 8.0+ with better performance
ALTER TABLE equipment ADD FULLTEXT INDEX ft_equipment_comprehensive
(unique_identifier, title, storage_location, notes, size, condition_reason, sticker_code)
WITH PARSER ngram;
```

#### PostgreSQL Full-Text Search (if applicable)
```sql
-- Add search vector column
ALTER TABLE equipment ADD COLUMN search_vector TSVECTOR;

-- Create index on search vector
CREATE INDEX idx_equipment_search_vector ON equipment USING gin(search_vector);

-- Update existing data
UPDATE equipment SET search_vector =
    to_tsvector('swedish', coalesce(unique_identifier, '') || ' ' ||
                          coalesce(title, '') || ' ' ||
                          coalesce(notes, '') || ' ' ||
                          coalesce(size, ''));

-- Trigger to maintain search vector
CREATE TRIGGER equipment_search_vector_update
    BEFORE INSERT OR UPDATE ON equipment
    FOR EACH ROW EXECUTE FUNCTION
    tsvector_update_trigger(search_vector, 'pg_catalog.swedish',
                           unique_identifier, title, notes, size);
```

### 2. Backend API Implementation

#### Enhanced Equipment Controller
```php
<?php
class BKGT_Equipment_Controller extends WP_REST_Controller {

    public function get_equipment($request) {
        $search = $request->get_param('search');
        $search_fields = $request->get_param('search_fields');
        $search_operator = $request->get_param('search_operator') ?: 'OR';
        $fuzzy = $request->get_param('fuzzy') ?: false;

        $args = array(
            'post_type' => 'equipment',
            'posts_per_page' => min($request->get_param('per_page') ?: 10, 100),
            'paged' => $request->get_param('page') ?: 1,
            'meta_query' => array()
        );

        // Add search functionality
        if (!empty($search)) {
            $search_conditions = $this->build_search_conditions($search, $search_fields, $search_operator, $fuzzy);
            if (!empty($search_conditions)) {
                $args['meta_query'][] = $search_conditions;
            }
        }

        // Add other filters (manufacturer_id, item_type_id, etc.)
        $this->add_standard_filters($args, $request);

        $query = new WP_Query($args);
        $equipment = array();

        foreach ($query->posts as $post) {
            $equipment[] = $this->format_equipment_item($post);
        }

        return array(
            'equipment' => $equipment,
            'total' => $query->found_posts,
            'page' => $request->get_param('page') ?: 1,
            'per_page' => count($equipment),
            'total_pages' => $query->max_num_pages,
            'search' => !empty($search) ? array(
                'term' => $search,
                'fields_searched' => $search_fields ?: $this->get_default_search_fields(),
                'total_matches' => $query->found_posts
            ) : null
        );
    }

    private function build_search_conditions($search, $search_fields, $operator, $fuzzy) {
        $fields = !empty($search_fields) ?
            explode(',', $search_fields) :
            $this->get_default_search_fields();

        $conditions = array('relation' => strtoupper($operator));

        foreach ($fields as $field) {
            $field = trim($field);

            if ($fuzzy) {
                // Fuzzy search using SOUNDEX or similar
                $conditions[] = array(
                    'key' => $field,
                    'value' => $search,
                    'compare' => 'RLIKE',
                    'type' => 'CHAR'
                );
            } else {
                // Standard LIKE search
                $conditions[] = array(
                    'key' => $field,
                    'value' => '%' . $wpdb->esc_like($search) . '%',
                    'compare' => 'LIKE'
                );
            }
        }

        return $conditions;
    }

    private function get_default_search_fields() {
        return array(
            'unique_identifier',
            'title',
            'storage_location',
            'notes',
            'size',
            'condition_reason',
            'sticker_code',
            'manufacturer_name',
            'item_type_name'
        );
    }

    private function format_equipment_item($post) {
        // Ensure all searchable fields are included in response
        return array(
            'id' => $post->ID,
            'unique_identifier' => get_post_meta($post->ID, 'unique_identifier', true),
            'title' => $post->post_title,
            'manufacturer_id' => get_post_meta($post->ID, 'manufacturer_id', true),
            'manufacturer_name' => $this->get_manufacturer_name(get_post_meta($post->ID, 'manufacturer_id', true)),
            'item_type_id' => get_post_meta($post->ID, 'item_type_id', true),
            'item_type_name' => $this->get_item_type_name(get_post_meta($post->ID, 'item_type_id', true)),
            'storage_location' => get_post_meta($post->ID, 'storage_location', true),
            'condition_status' => get_post_meta($post->ID, 'condition_status', true),
            'condition_reason' => get_post_meta($post->ID, 'condition_reason', true),
            'size' => get_post_meta($post->ID, 'size', true), // Include size field
            'notes' => get_post_meta($post->ID, 'notes', true), // Include notes field
            'sticker_code' => get_post_meta($post->ID, 'sticker_code', true),
            'created_date' => $post->post_date,
            'updated_date' => $post->post_modified
        );
    }
}
?>
```

### 3. Enhanced API Parameters

#### Query Parameters
```
GET /wp-json/bkgt/v1/equipment?search=TDJ&search_fields=size,notes&search_operator=AND&fuzzy=true

Parameters:
- search (string): Search term
- search_fields (string): Comma-separated list of fields (optional)
- search_operator (string): 'AND' or 'OR' for multiple terms (default: OR)
- fuzzy (boolean): Enable fuzzy matching (default: false)
- search_mode (string): 'exact', 'partial', 'fulltext' (default: partial)
```

### 4. Searchable Fields Matrix

#### Core Equipment Fields
- **unique_identifier**: Equipment ID (e.g., "0006-0005-00001")
- **title**: Display title
- **storage_location**: Where equipment is stored
- **notes**: Free text notes
- **size**: Size designation (TDJ, Large, etc.)
- **condition_reason**: Condition notes
- **sticker_code**: Physical labels/codes

#### Related Fields (from joined tables)
- **manufacturer_name**: From manufacturers table
- **item_type_name**: From equipment_types table
- **location_name**: From locations table

### 5. Performance Optimizations

#### Database Indexing Strategy
```sql
-- Composite indexes for common search patterns
CREATE INDEX idx_equipment_search_core ON equipment(unique_identifier, title, size);
CREATE INDEX idx_equipment_search_text ON equipment(notes, storage_location, condition_reason);

-- Individual field indexes for filtered searches
CREATE INDEX idx_equipment_size ON equipment(size);
CREATE INDEX idx_equipment_notes ON equipment(notes);
CREATE INDEX idx_equipment_storage ON equipment(storage_location);
```

#### Query Optimization
```php
// Use prepared statements and limit results
$args['posts_per_page'] = min($request->get_param('per_page') ?: 10, 100);
$args['no_found_rows'] = false; // For accurate pagination

// Add search result caching
$cache_key = 'equipment_search_' . md5(serialize($args));
$cached_result = wp_cache_get($cache_key, 'equipment');
if ($cached_result !== false) {
    return $cached_result;
}
// ... perform query ...
wp_cache_set($cache_key, $result, 'equipment', 300); // Cache for 5 minutes
```

### 6. Advanced Search Features

#### Fuzzy Search Implementation
```php
private function fuzzy_search($search_term, $fields) {
    global $wpdb;

    $search_term = $wpdb->esc_like($search_term);
    $conditions = array();

    foreach ($fields as $field) {
        // SOUNDEX for phonetic matching
        $conditions[] = $wpdb->prepare(
            "SOUNDEX({$field}) = SOUNDEX(%s)",
            $search_term
        );

        // Levenshtein distance for edit distance (requires MySQL 8.0+)
        $conditions[] = $wpdb->prepare(
            "LEVENSHTEIN({$field}, %s) <= 2",
            $search_term
        );
    }

    return '(' . implode(' OR ', $conditions) . ')';
}
```

#### Search Result Highlighting
```php
private function highlight_search_terms($text, $search_term) {
    if (empty($search_term)) return $text;

    return preg_replace(
        '/(' . preg_quote($search_term, '/') . ')/iu',
        '<mark>$1</mark>',
        $text
    );
}
```

### 7. Search Analytics & Monitoring

#### Search Logging
```php
private function log_search_query($search_term, $results_count, $user_id = null) {
    global $wpdb;

    $wpdb->insert('search_logs', array(
        'search_term' => $search_term,
        'results_count' => $results_count,
        'user_id' => $user_id,
        'search_fields' => $search_fields,
        'timestamp' => current_time('mysql'),
        'ip_address' => $_SERVER['REMOTE_ADDR']
    ));
}
```

#### Popular Searches Dashboard
```php
public function get_popular_searches($limit = 10) {
    global $wpdb;

    return $wpdb->get_results($wpdb->prepare("
        SELECT search_term, COUNT(*) as frequency,
               AVG(results_count) as avg_results
        FROM search_logs
        WHERE timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY search_term
        ORDER BY frequency DESC
        LIMIT %d
    ", $limit));
}
```

### 8. API Response Enhancement

#### Include Search Metadata
```json
{
    "equipment": [...],
    "total": 25,
    "page": 1,
    "per_page": 10,
    "total_pages": 3,
    "search": {
        "term": "TDJ",
        "fields_searched": ["size", "notes", "storage_location"],
        "operator": "OR",
        "fuzzy": false,
        "total_matches": 25,
        "search_time_ms": 45
    }
}
```

### 9. Testing Strategy

#### Unit Tests
```php
class EquipmentSearchTest extends WP_UnitTestCase {
    public function test_search_by_size() {
        // Create equipment with size "TDJ"
        $equipment_id = $this->create_equipment_with_size('TDJ');

        // Test search returns correct results
        $request = new WP_REST_Request('GET', '/bkgt/v1/equipment');
        $request->set_param('search', 'TDJ');

        $response = $this->server->dispatch($request);
        $data = $response->get_data();

        $this->assertEquals(200, $response->get_status());
        $this->assertGreaterThan(0, count($data['equipment']));
        $this->assertEquals('TDJ', $data['equipment'][0]['size']);
    }

    public function test_fuzzy_search() {
        // Test fuzzy matching for typos
        $request = new WP_REST_Request('GET', '/bkgt/v1/equipment');
        $request->set_param('search', 'TDJ');
        $request->set_param('fuzzy', true);

        $response = $this->server->dispatch($request);
        // Assert fuzzy matching works
    }

    public function test_field_specific_search() {
        // Test searching only in specific fields
        $request = new WP_REST_Request('GET', '/bkgt/v1/equipment');
        $request->set_param('search', 'TDJ');
        $request->set_param('search_fields', 'size');

        $response = $this->server->dispatch($request);
        // Assert only size field was searched
    }
}
```

### 10. Migration & Deployment

#### Database Migration Script
```php
function upgrade_equipment_search() {
    global $wpdb;

    // Add full-text indexes
    $wpdb->query("ALTER TABLE {$wpdb->prefix}equipment ADD FULLTEXT INDEX ft_search (unique_identifier, title, notes, size)");

    // Add individual field indexes
    $wpdb->query("CREATE INDEX idx_equipment_size ON {$wpdb->prefix}equipment(size)");
    $wpdb->query("CREATE INDEX idx_equipment_notes ON {$wpdb->prefix}equipment(notes)");

    // Populate search vectors for existing data (PostgreSQL)
    if ($wpdb->db_server_info() === 'PostgreSQL') {
        $wpdb->query("UPDATE {$wpdb->prefix}equipment SET search_vector = to_tsvector('swedish', COALESCE(unique_identifier,'') || ' ' || COALESCE(title,'') || ' ' || COALESCE(notes,'') || ' ' || COALESCE(size,''))");
    }

    // Update version
    update_option('bkgt_equipment_search_version', '1.0.0');
}
```

### 11. Frontend Integration Updates

#### Enhanced Search UI
```typescript
// Add to EquipmentParams interface
export interface EquipmentParams extends PaginatedParams {
  search?: string
  search_fields?: string  // "size,notes,storage_location"
  search_operator?: 'AND' | 'OR'
  fuzzy?: boolean
  search_mode?: 'exact' | 'partial' | 'fulltext'
}

// Search component enhancement
const [searchOptions, setSearchOptions] = useState({
  fields: ['size', 'notes', 'storage_location', 'unique_identifier'],
  operator: 'OR' as 'AND' | 'OR',
  fuzzy: false
})
```

## Implementation Priority

### Phase 1: Core Search (High Priority)
1. ✅ Include `size` and `notes` fields in API responses
2. ✅ Implement basic search across all text fields
3. ✅ Add database indexes for performance

### Phase 2: Enhanced Features (Medium Priority)
1. 🔄 Fuzzy search implementation
2. 🔄 Field-specific search
3. 🔄 Search analytics and logging

### Phase 3: Advanced Features (Low Priority)
1. 🔄 Full-text search with ranking
2. 🔄 Search result highlighting
3. 🔄 Search suggestions/autocomplete

## Testing Checklist

### Functional Tests
- [ ] Search by size (e.g., "TDJ") returns correct results
- [ ] Search by notes returns equipment with matching notes
- [ ] Search by unique identifier works
- [ ] Search across multiple fields works
- [ ] Field-specific search limits results correctly
- [ ] Fuzzy search handles typos
- [ ] Search with AND/OR operators works correctly

### Performance Tests
- [ ] Search queries complete within 500ms
- [ ] Large result sets are properly paginated
- [ ] Database indexes are utilized (check EXPLAIN plans)
- [ ] Memory usage remains reasonable

### Integration Tests
- [ ] Frontend search input works with new parameters
- [ ] Search results display correctly
- [ ] Search metadata is shown to users
- [ ] Error handling works for invalid search parameters

## API Documentation Updates

Update the API documentation to reflect:
- New `size` field in equipment responses
- New `notes` field in equipment responses
- Enhanced search parameters (`search_fields`, `search_operator`, `fuzzy`)
- Search metadata in response
- Examples showing size-based searches

## Conclusion

Implementing comprehensive search across all equipment database columns will significantly improve the user experience by allowing searches for size designations like "TDJ", notes, storage locations, and other relevant fields. The implementation should be done in phases, starting with core functionality and gradually adding advanced features.

The key changes required:
1. Include missing fields (`size`, `notes`) in API responses
2. Implement search across all relevant text fields
3. Add appropriate database indexes for performance
4. Update API documentation
5. Test thoroughly across all search scenarios</content>
<parameter name="filePath">c:\Users\Olheim\Desktop\GH\bkgt-manager\Comprehensive_Equipment_Search_Implementation.md