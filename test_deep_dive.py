#!/usr/bin/env python3
"""
SWE3 - Deep dive into available content
Check posts, media, and look for PDF-related content
"""

import requests
import json
import re

base_url = 'https://amerikanskfotboll.swe3.se'

print('=== Checking Posts ===')
url = base_url + '/wp-json/wp/v2/posts?per_page=100'
response = requests.get(url)
posts = response.json()

print(f'Found {len(posts)} posts')
pdf_posts = [p for p in posts if '.pdf' in p.get('content', {}).get('rendered', '').lower()]
print(f'Posts with PDF links: {len(pdf_posts)}')

if pdf_posts:
    for post in pdf_posts[:3]:
        print(f'\nPost: {post["title"]["rendered"]}')
        content = post.get('content', {}).get('rendered', '')
        pdfs = re.findall(r'https://[^"\'<>\s]+\.pdf', content)
        print(f'  PDFs: {pdfs}')

print('\n=== Checking Media ===')
url = base_url + '/wp-json/wp/v2/media?per_page=100'
response = requests.get(url)
media = response.json()

print(f'Found {len(media)} media items')
pdfs = [m for m in media if m.get('mime_type', '').lower() == 'application/pdf']
print(f'PDF media items: {len(pdfs)}')

for pdf_media in pdfs[:10]:
    print(f'\n  Title: {pdf_media["title"]["rendered"]}')
    print(f'  Source: {pdf_media.get("source_url", "N/A")}')

print('\n=== Checking REST API Index ===')
url = base_url + '/wp-json/'
response = requests.get(url)
root = response.json()

namespaces = root.get('namespaces', [])
print(f'Available namespaces: {len(namespaces)}')
for ns in namespaces:
    print(f'  - {ns}')

routes = root.get('routes', {})
custom_routes = [r for r in routes.keys() if 'document' in r.lower() or 'pdf' in r.lower() or 'file' in r.lower()]
if custom_routes:
    print(f'\nRoutes mentioning documents/pdfs/files:')
    for route in custom_routes:
        print(f'  - {route}')
else:
    print('\nNo custom document/pdf/file routes found')

print('\n=== Getting Detailed Page Info ===')
url = base_url + '/wp-json/wp/v2/pages/5776?_embed=true'
response = requests.get(url)
page = response.json()

print(f'Page: {page["title"]["rendered"]}')
print(f'Modified: {page.get("modified", "N/A")}')
print(f'Content length: {len(page.get("content", {}).get("rendered", ""))} chars')

# Check for block data
if 'blocks' in page:
    print(f'Blocks: {len(page["blocks"])}')
    for block in page.get('blocks', [])[:3]:
        print(f'  - {block.get("blockName", "unknown")}')
