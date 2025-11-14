#!/usr/bin/env python3
"""
SWE3 - Find PDF loading mechanism
Check for JavaScript data, React props, or Ajax endpoints that fetch PDFs
"""

import requests
import re
import json

def find_pdf_loading():
    """Find how PDFs are loaded dynamically"""
    url = 'https://amerikanskfotboll.swe3.se/information-verktyg/spelregler-tavlingsbestammelser/'
    
    print(f'Analyzing {url}...\n')
    
    response = requests.get(url, timeout=10)
    html = response.text
    
    # Look for window.* or window.DATA patterns
    window_vars = re.findall(r'window\.(\w+)\s*=\s*(?:{[^}]+}|\[[^\]]+\]|"[^"]*")', html)
    if window_vars:
        print(f'Window variables: {window_vars}')
    
    # Look for data JSON in script tags
    json_pattern = r'<script[^>]*type=["\']application/json["\'][^>]*>([^<]+)</script>'
    json_blocks = re.findall(json_pattern, html, re.DOTALL)
    
    if json_blocks:
        print(f'\nFound {len(json_blocks)} JSON script blocks:')
        for i, block in enumerate(json_blocks[:2]):
            print(f'\n--- Block {i+1} (first 300 chars) ---')
            try:
                data = json.loads(block)
                print(json.dumps(data, indent=2, ensure_ascii=False)[:300])
            except:
                print(block[:300])
    
    # Look for document/posts data structures
    post_pattern = r'(?://\s*)?var\s+(?:posts?|documents?|content)\s*=\s*(\[.*?\])\s*;'
    posts = re.findall(post_pattern, html, re.DOTALL)
    
    if posts:
        print(f'\nFound {len(posts)} post/document arrays')
    
    # Look specifically for table rows or list items that might be PDF references
    link_pattern = r'<(?:tr|li|div)[^>]*>(?:.*?<a[^>]*href=["\']([^"\']+\.pdf)["\'][^>]*>([^<]*)</a>)'
    links = re.findall(link_pattern, html, re.DOTALL)
    
    if links:
        print(f'\nFound PDF links in table/list: {len(links)}')
        for url, title in links[:5]:
            print(f'  {title}: {url}')
    
    # Check for any href with PDF pattern
    all_href = re.findall(r'href=["\']([^"\']+)["\']', html)
    pdf_hrefs = [h for h in all_href if '.pdf' in h.lower()]
    
    print(f'\nAll href attributes with ".pdf": {len(pdf_hrefs)}')
    for href in pdf_hrefs[:10]:
        print(f'  {href}')
    
    # Check source size and structure
    print(f'\n=== Page Analysis ===')
    print(f'HTML size: {len(html)} bytes')
    print(f'Script tags: {len(re.findall(r"<script", html))}')
    print(f'Style tags: {len(re.findall(r"<style", html))}')
    
    # Look for known SWE3 WordPress patterns
    print(f'\n=== Looking for SWE3 Upload Paths ===')
    upload_pattern = r'https://amerikanskfotboll\.swe3\.se/wp-content/uploads/[^"\'<>\s]*'
    uploads = set(re.findall(upload_pattern, html))
    
    for upload in sorted(uploads)[:20]:
        print(f'  {upload}')

find_pdf_loading()
