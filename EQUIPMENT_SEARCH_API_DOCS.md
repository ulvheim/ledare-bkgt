# BKGT Equipment Search API Documentation

## Overview

The BKGT Equipment Search API provides comprehensive search functionality across all equipment database fields, enabling users to find equipment by size designations, notes, storage locations, and other attributes.

## Base URL
```
https://ledare.bkgt.se/wp-json/bkgt/v1/
```

## Authentication
All API requests require authentication via Bearer token:
```
Authorization: Bearer YOUR_API_TOKEN
```

## Endpoints

### 1. Equipment Search

**Endpoint:** `GET /equipment`

Search for equipment with advanced filtering and search capabilities.

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `search` | string | - | Search term to find in equipment fields |
| `search_fields` | string | all fields | Comma-separated list of fields to search in |
| `search_operator` | string | "OR" | "AND" or "OR" for combining multiple search terms |
| `fuzzy` | boolean | false | Enable phonetic/fuzzy matching |
| `search_mode` | string | "partial" | "exact", "partial", or "fulltext" matching |
| `page` | integer | 1 | Page number for pagination |
| `per_page` | integer | 10 | Number of results per page (max 100) |
| `location_id` | integer | - | Filter by storage location |
| `condition` | string | - | Filter by condition status |

#### Search Fields

Available search fields:
- `unique_identifier` - Equipment ID (e.g., "0006-0005-00001")
- `title` - Equipment title
- `storage_location` - Where equipment is stored
- `notes` - Free text notes
- `size` - Size designation (TDJ, Large, etc.)
- `condition_reason` - Condition notes
- `sticker_code` - Physical labels/codes
- `manufacturer_name` - Manufacturer name
- `item_type_name` - Equipment type name

#### Examples

**Basic Search:**
```
GET /equipment?search=TDJ
```
Search for "TDJ" across all fields.

**Size-Specific Search:**
```
GET /equipment?search=TDJ&search_fields=size
```
Search only in the size field for "TDJ".

**Multi-Field Search with AND:**
```
GET /equipment?search=Warehouse&search_fields=storage_location,notes&search_operator=AND
```
Find equipment in "Warehouse" locations that also have "Warehouse" in notes.

**Fuzzy Search:**
```
GET /equipment?search=TDJ&fuzzy=true
```
Use phonetic matching for typos (e.g., "TDJ" might match "TDG").

**Exact Match:**
```
GET /equipment?search=TDJ&search_mode=exact&search_fields=size
```
Find equipment with exactly "TDJ" in the size field.

#### Response

```json
{
  "inventory_items": [
    {
      "id": 123,
      "unique_identifier": "0006-0005-00001",
      "title": "Equipment Title",
      "manufacturer_id": 1,
      "manufacturer_name": "Manufacturer Name",
      "item_type_id": 1,
      "item_type_name": "Equipment Type",
      "storage_location": "Warehouse A",
      "condition_status": "normal",
      "condition_reason": "New equipment",
      "size": "TDJ",
      "notes": "Additional notes",
      "sticker_code": "ABC123",
      "created_at": "2025-01-01 12:00:00",
      "updated_at": "2025-01-01 12:00:00",
      "assignee_type": null,
      "assignee_id": null
    }
  ],
  "total": 25,
  "page": 1,
  "per_page": 10,
  "total_pages": 3,
  "search": {
    "term": "TDJ",
    "fields_searched": ["size", "notes", "storage_location", "unique_identifier", "title", "condition_reason", "sticker_code", "manufacturer_name", "item_type_name"],
    "operator": "OR",
    "fuzzy": false,
    "mode": "partial",
    "total_matches": 25,
    "search_time_ms": 45
  }
}
```

### 2. Search Analytics

**Endpoint:** `GET /equipment/search-analytics`

Get analytics about search usage and popular search terms.

#### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `limit` | integer | 10 | Number of results to return (max 100) |
| `days` | integer | 30 | Number of days to look back |

#### Example

```
GET /equipment/search-analytics?limit=10&days=7
```

#### Response

```json
{
  "popular_searches": [
    {
      "search_term": "TDJ",
      "frequency": 45,
      "avg_results": 12.5,
      "avg_search_time": 35.2
    },
    {
      "search_term": "Warehouse",
      "frequency": 32,
      "avg_results": 28.3,
      "avg_search_time": 42.1
    }
  ],
  "period_days": 7,
  "limit": 10
}
```

## Error Responses

```json
{
  "code": "equipment_error",
  "message": "Error message",
  "data": {
    "status": 500
  }
}
```

## Rate Limiting

- 100 requests per minute per API token
- 1000 requests per hour per API token

## Best Practices

1. **Use specific search fields** when possible to improve performance
2. **Enable fuzzy search** for user-facing search where typos are expected
3. **Use pagination** for large result sets
4. **Monitor search analytics** to understand user behavior
5. **Cache frequent searches** on the frontend for better UX

## Field Updates

As of version 1.3.0, the following fields have been added to equipment responses:
- `size` - Size designation
- `condition_reason` - Detailed condition notes
- `sticker_code` - Physical labels/codes

These fields are now included in all equipment list and detail responses.

## Changelog

### v1.3.0 (November 2025)
- Added comprehensive search across all equipment fields
- Added `size`, `condition_reason`, and `sticker_code` fields to responses
- Added advanced search parameters (`search_fields`, `search_operator`, `fuzzy`, `search_mode`)
- Added search analytics and logging
- Added database indexes for search performance
- Added fuzzy search with SOUNDEX phonetic matching

### v1.2.0 (Previous)
- Basic search functionality
- Limited field search