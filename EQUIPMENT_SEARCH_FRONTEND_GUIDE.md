# Equipment Search Frontend Integration Guide

## Overview

This guide provides examples for integrating the enhanced equipment search functionality into your frontend application.

## Basic Search Implementation

### HTML Structure

```html
<div class="equipment-search">
    <div class="search-input-group">
        <input type="text" id="equipment-search" placeholder="Search equipment...">
        <button id="search-button">Search</button>
    </div>

    <div class="search-filters">
        <select id="search-fields" multiple>
            <option value="size">Size</option>
            <option value="notes">Notes</option>
            <option value="storage_location">Storage Location</option>
            <option value="unique_identifier">Equipment ID</option>
            <option value="manufacturer_name">Manufacturer</option>
            <option value="item_type_name">Type</option>
        </select>

        <select id="search-operator">
            <option value="OR">Any of these words</option>
            <option value="AND">All of these words</option>
        </select>

        <label>
            <input type="checkbox" id="fuzzy-search"> Fuzzy search
        </label>
    </div>

    <div id="search-results"></div>
</div>
```

### JavaScript Implementation

```javascript
class EquipmentSearch {
    constructor() {
        this.apiToken = 'YOUR_API_TOKEN';
        this.baseUrl = 'https://ledare.bkgt.se/wp-json/bkgt/v1';
        this.currentPage = 1;
        this.searchTimeout = null;

        this.init();
    }

    init() {
        // Bind events
        document.getElementById('equipment-search').addEventListener('input', this.debounceSearch.bind(this));
        document.getElementById('search-button').addEventListener('click', this.performSearch.bind(this));
        document.getElementById('search-fields').addEventListener('change', this.performSearch.bind(this));
        document.getElementById('search-operator').addEventListener('change', this.performSearch.bind(this));
        document.getElementById('fuzzy-search').addEventListener('change', this.performSearch.bind(this));
    }

    debounceSearch() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => this.performSearch(), 300);
    }

    async performSearch(page = 1) {
        const searchTerm = document.getElementById('equipment-search').value.trim();
        const searchFields = Array.from(document.getElementById('search-fields').selectedOptions)
            .map(option => option.value);
        const searchOperator = document.getElementById('search-operator').value;
        const fuzzy = document.getElementById('fuzzy-search').checked;

        if (!searchTerm && page === 1) {
            this.clearResults();
            return;
        }

        try {
            const params = new URLSearchParams({
                search: searchTerm,
                search_fields: searchFields.join(','),
                search_operator: searchOperator,
                fuzzy: fuzzy,
                page: page,
                per_page: 20
            });

            const response = await fetch(`${this.baseUrl}/equipment?${params}`, {
                headers: {
                    'Authorization': `Bearer ${this.apiToken}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            this.displayResults(data);

        } catch (error) {
            console.error('Search error:', error);
            this.showError('Search failed. Please try again.');
        }
    }

    displayResults(data) {
        const resultsContainer = document.getElementById('search-results');

        if (!data.inventory_items || data.inventory_items.length === 0) {
            resultsContainer.innerHTML = '<p>No equipment found matching your search.</p>';
            return;
        }

        let html = `
            <div class="search-summary">
                <p>Found ${data.total} equipment items (${data.search ? `searched in ${data.search.fields_searched.length} fields, took ${data.search.search_time_ms}ms` : ''})</p>
            </div>
            <div class="equipment-grid">
        `;

        data.inventory_items.forEach(item => {
            html += `
                <div class="equipment-card">
                    <h3>${this.escapeHtml(item.title)}</h3>
                    <p><strong>ID:</strong> ${this.escapeHtml(item.unique_identifier)}</p>
                    <p><strong>Size:</strong> ${this.escapeHtml(item.size || 'N/A')}</p>
                    <p><strong>Location:</strong> ${this.escapeHtml(item.storage_location || 'N/A')}</p>
                    <p><strong>Manufacturer:</strong> ${this.escapeHtml(item.manufacturer_name || 'N/A')}</p>
                    <p><strong>Type:</strong> ${this.escapeHtml(item.item_type_name || 'N/A')}</p>
                    ${item.notes ? `<p><strong>Notes:</strong> ${this.escapeHtml(item.notes)}</p>` : ''}
                    <p><strong>Condition:</strong> ${this.escapeHtml(item.condition_status)} ${item.condition_reason ? `(${this.escapeHtml(item.condition_reason)})` : ''}</p>
                </div>
            `;
        });

        html += '</div>';

        // Pagination
        if (data.total_pages > 1) {
            html += '<div class="pagination">';
            for (let i = 1; i <= Math.min(data.total_pages, 10); i++) {
                html += `<button class="page-btn ${i === data.page ? 'active' : ''}" data-page="${i}">${i}</button>`;
            }
            html += '</div>';
        }

        resultsContainer.innerHTML = html;

        // Bind pagination events
        document.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const page = parseInt(e.target.dataset.page);
                this.performSearch(page);
            });
        });
    }

    clearResults() {
        document.getElementById('search-results').innerHTML = '<p>Enter a search term to find equipment.</p>';
    }

    showError(message) {
        document.getElementById('search-results').innerHTML = `<p class="error">${this.escapeHtml(message)}</p>`;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize search when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new EquipmentSearch();
});
```

### CSS Styling

```css
.equipment-search {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.search-input-group {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

#equipment-search {
    flex: 1;
    padding: 10px;
    font-size: 16px;
    border: 2px solid #ddd;
    border-radius: 4px;
}

#search-button {
    padding: 10px 20px;
    background: #007cba;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

#search-button:hover {
    background: #005a87;
}

.search-filters {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

#search-fields {
    min-width: 200px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

#search-operator {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.equipment-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.equipment-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.equipment-card h3 {
    margin-top: 0;
    color: #007cba;
}

.equipment-card p {
    margin: 5px 0;
    font-size: 14px;
}

.search-summary {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
}

.page-btn {
    padding: 8px 12px;
    border: 1px solid #ddd;
    background: white;
    cursor: pointer;
    border-radius: 4px;
}

.page-btn.active {
    background: #007cba;
    color: white;
    border-color: #007cba;
}

.page-btn:hover:not(.active) {
    background: #f8f9fa;
}

.error {
    color: #dc3545;
    background: #f8d7da;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #f5c6cb;
}
```

## Advanced Features

### Search Suggestions (Future Enhancement)

```javascript
// Add to EquipmentSearch class
async getSearchSuggestions(query) {
    if (query.length < 2) return [];

    try {
        const response = await fetch(`${this.baseUrl}/equipment/search-analytics?limit=5&days=30`, {
            headers: {
                'Authorization': `Bearer ${this.apiToken}`
            }
        });

        const data = await response.json();
        return data.popular_searches
            .filter(item => item.search_term.toLowerCase().includes(query.toLowerCase()))
            .map(item => item.search_term);
    } catch (error) {
        console.error('Suggestions error:', error);
        return [];
    }
}
```

### Search Analytics Integration

```javascript
// Track search usage
trackSearch(searchTerm, resultsCount, searchTime) {
    // Send analytics data to your tracking system
    console.log(`Search: "${searchTerm}" returned ${resultsCount} results in ${searchTime}ms`);
}
```

## Error Handling

```javascript
// Enhanced error handling
async performSearch(page = 1) {
    // ... existing code ...

    try {
        // ... existing fetch code ...

        if (response.status === 401) {
            throw new Error('Authentication required. Please log in again.');
        } else if (response.status === 403) {
            throw new Error('Access denied. Insufficient permissions.');
        } else if (response.status === 429) {
            throw new Error('Too many requests. Please wait and try again.');
        } else if (!response.ok) {
            throw new Error(`Server error: ${response.status}`);
        }

        // ... rest of existing code ...

    } catch (error) {
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            this.showError('Network error. Please check your connection.');
        } else {
            this.showError(error.message);
        }
    }
}
```

## Performance Tips

1. **Debounce search input** to avoid excessive API calls
2. **Cache frequent searches** in localStorage
3. **Use pagination** for large result sets
4. **Implement loading states** for better UX
5. **Handle network errors gracefully**

## Testing

Test the following scenarios:
- Search by size (e.g., "TDJ")
- Search by notes
- Multi-field search with AND/OR
- Fuzzy search for typos
- Pagination
- Empty results
- Network errors