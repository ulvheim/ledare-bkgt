#!/usr/bin/env python3
"""
SWE3 Document Discovery - Direct approach
Finds all PDF URLs in the SWE3 page HTML
"""

import requests
import re
import json
from urllib.parse import urlparse
from collections import defaultdict

def discover_pdfs():
    """Find all PDF URLs in SWE3 page"""
    url = 'https://amerikanskfotboll.swe3.se/information-verktyg/spelregler-tavlingsbestammelser/'
    
    try:
        print(f'Fetching {url}...')
        response = requests.get(url, timeout=10)
        response.raise_for_status()
        html = response.text
        
        # Find all PDF URLs
        pdf_pattern = r'https://[^"\'<>\s]+\.pdf'
        pdf_urls = set(re.findall(pdf_pattern, html))
        
        print(f'\n✓ Found {len(pdf_urls)} unique PDF URLs:')
        for pdf_url in sorted(pdf_urls):
            print(f'  {pdf_url}')
        
        # Find all WordPress uploads
        uploads_pattern = r'https://amerikanskfotboll\.swe3\.se/wp-content/uploads/[^"\'<>\s]+'
        uploads = set(re.findall(uploads_pattern, html))
        
        print(f'\n✓ Found {len(uploads)} WordPress uploads:')
        for upload in sorted(uploads)[:15]:
            print(f'  {upload}')
        
        # Analyze URL structure
        print(f'\n=== URL Analysis ===')
        year_months = defaultdict(list)
        for pdf_url in pdf_urls:
            # Extract year/month from URL like /2025/03/
            match = re.search(r'/(\d{4})/(\d{2})/', pdf_url)
            if match:
                year_month = f'{match.group(1)}/{match.group(2)}'
                year_months[year_month].append(pdf_url)
        
        print('PDFs by Year/Month:')
        for ym in sorted(year_months.keys(), reverse=True):
            print(f'  {ym}: {len(year_months[ym])} files')
        
        return {
            'success': True,
            'pdf_urls': list(pdf_urls),
            'uploads': list(uploads),
            'count': len(pdf_urls)
        }
        
    except Exception as e:
        print(f'Error: {e}')
        return {
            'success': False,
            'error': str(e)
        }

if __name__ == '__main__':
    result = discover_pdfs()
    print(f'\nJSON Output:')
    print(json.dumps(result, indent=2))
