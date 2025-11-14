#!/usr/bin/env python3
"""
SWE3 - Check for custom endpoints or alternative API routes
"""

import requests
import json

endpoints_to_try = [
    # Standard WordPress REST API routes
    '/wp-json/',
    '/wp-json/wp/v2/pages',
    '/wp-json/wp/v2/posts',
    '/wp-json/wp/v2/media',
    '/wp-json/wp/v2/attachments',
    
    # Custom post types (common SWE3 patterns)
    '/wp-json/wp/v2/documents',
    '/wp-json/wp/v2/pdfs',
    '/wp-json/wp/v2/rules',
    '/wp-json/wp/v2/spelregler',
    
    # Custom endpoints (SWE3 might have)
    '/wp-json/swe3/v1/documents',
    '/wp-json/amerikanskfotboll/v1/documents',
    '/wp-json/api/documents',
    
    # ACF (Advanced Custom Fields) endpoints
    '/wp-json/acf/v3/pages/5776',
]

base_url = 'https://amerikanskfotboll.swe3.se'

print('Testing endpoints for PDF or document data:\n')

for endpoint in endpoints_to_try:
    url = base_url + endpoint
    try:
        response = requests.get(url, timeout=5)
        
        # Check if response has content
        is_json = 'json' in response.headers.get('content-type', '')
        size = len(response.text)
        status = response.status_code
        
        if status == 200:
            marker = '✓'
        elif status == 404:
            marker = '✗'
        else:
            marker = '⚠'
        
        print(f'{marker} {status} {endpoint}')
        
        # If it's JSON and has data, show sample
        if is_json and status == 200 and size > 100:
            try:
                data = response.json()
                if isinstance(data, dict):
                    keys = list(data.keys())[:5]
                    print(f'    Keys: {keys}')
                elif isinstance(data, list):
                    print(f'    Array with {len(data)} items')
            except:
                pass
    
    except Exception as e:
        print(f'✗ {endpoint} (Error: {str(e)[:40]})')

# Also check the page attachments
print(f'\n\nChecking page 5776 for attached media:')
url = 'https://amerikanskfotboll.swe3.se/wp-json/wp/v2/pages/5776?_embed'
try:
    response = requests.get(url, timeout=5)
    if response.status_code == 200:
        data = response.json()
        if '_embedded' in data:
            print('✓ Page has embedded data')
            print(f'  Embedded keys: {list(data["_embedded"].keys())}')
except:
    pass
