#!/usr/bin/env python3
"""
SWE3 Page Analysis - Find how PDFs are loaded
Examine page source for API calls, data attributes, or JavaScript patterns
"""

import requests
import re
import json

def analyze_page():
    """Analyze SWE3 page to find PDF loading mechanism"""
    url = 'https://amerikanskfotboll.swe3.se/information-verktyg/spelregler-tavlingsbestammelser/'
    
    print(f'Fetching {url}...')
    response = requests.get(url, timeout=10)
    html = response.text
    
    # Look for API endpoints
    api_pattern = r'(?:/api/|/wp-json/|/rest/)[^"\'<>\s]*'
    api_endpoints = set(re.findall(api_pattern, html))
    
    print(f'\n=== API Endpoints ===')
    if api_endpoints:
        for api in sorted(api_endpoints)[:20]:
            print(f'  {api}')
    else:
        print('  None found')
    
    # Look for JavaScript data attributes
    data_attrs = re.findall(r'data-[a-z-]+=["\']([^"\']+)["\']', html, re.IGNORECASE)
    print(f'\n=== Data Attributes ({len(data_attrs)} found) ===')
    for attr in sorted(set(data_attrs))[:10]:
        if len(attr) < 100:
            print(f'  {attr}')
    
    # Look for JSON in script tags
    script_pattern = r'<script[^>]*>([^<]{100,}?{[^<]{50,}?}[^<]{100,}?)</script>'
    scripts = re.findall(script_pattern, html, re.DOTALL)
    
    print(f'\n=== JavaScript Objects ({len(scripts)} script blocks found) ===')
    
    # Try to find specific patterns
    post_id_pattern = r'post["\']?\s*:\s*(?:"(\d+)"|(\d+))'
    post_ids = re.findall(post_id_pattern, html)
    
    if post_ids:
        print(f'Post IDs: {set([p[0] or p[1] for p in post_ids])}')
    
    # Look for REST API nonce
    nonce_pattern = r'_wpnonce["\']?\s*:\s*["\']([^"\']+)["\']'
    nonces = re.findall(nonce_pattern, html)
    
    if nonces:
        print(f'Nonces found: {len(set(nonces))}')
    
    # Check for form submissions or AJAX calls
    ajax_pattern = r'wp\.ajax\.|jQuery\.ajax|fetch\(["\']([^"\']+)'
    ajax_calls = re.findall(ajax_pattern, html)
    
    if ajax_calls:
        print(f'AJAX endpoints: {set(ajax_calls)}')
    
    # Look for download/document links
    link_patterns = [
        (r'href=["\']([^"\']*\.pdf[^"\']*)["\']', 'PDF links'),
        (r'href=["\']([^"\']*downloads?[^"\']*)["\']', 'Download links'),
        (r'href=["\']([^"\']*documents?[^"\']*)["\']', 'Document links'),
    ]
    
    print(f'\n=== Content Links ===')
    for pattern, label in link_patterns:
        links = re.findall(pattern, html, re.IGNORECASE)
        if links:
            print(f'{label}: {len(set(links))}')
            for link in sorted(set(links))[:3]:
                print(f'  {link[:80]}')
    
    # Look for post content or document container
    container_pattern = r'<div[^>]*class=["\']([^"\']*(?:post|content|document|document-list)[^"\']*)["\']'
    containers = re.findall(container_pattern, html)
    
    if containers:
        print(f'\n=== Containers ({len(set(containers))} found) ===')
        for cont in sorted(set(containers))[:5]:
            print(f'  {cont}')
    
    # Save first 2000 chars for inspection
    with open('/tmp/swe3_page_sample.html', 'w') as f:
        f.write(html[:3000])
    
    return {
        'success': True,
        'page_size': len(html),
        'api_endpoints': len(api_endpoints),
        'has_nonces': len(nonces) > 0,
        'message': 'See /tmp/swe3_page_sample.html for first 3000 chars'
    }

if __name__ == '__main__':
    result = analyze_page()
    print(json.dumps(result, indent=2))
