#!/usr/bin/env python3
"""
SWE3 Document Scraper v2 - Using WordPress REST API
This is the correct approach - fetch page content via REST API and extract PDFs
"""

import requests
import re
import json
import sys
from typing import List, Dict

def scrape_via_rest_api(page_id: int = 5776) -> Dict:
    """
    Fetch SWE3 page via WordPress REST API and extract PDFs
    
    Args:
        page_id: WordPress page ID (default: 5776 for spelregler page)
    
    Returns:
        Dict with success status and documents
    """
    base_url = 'https://amerikanskfotboll.swe3.se'
    api_url = f'{base_url}/wp-json/wp/v2/pages/{page_id}'
    
    print(f'Fetching REST API: {api_url}')
    
    try:
        response = requests.get(api_url, timeout=10)
        response.raise_for_status()
        
        page_data = response.json()
        
        # Get the rendered HTML content
        content_html = page_data.get('content', {}).get('rendered', '')
        
        # Extract all PDF links
        pdf_pattern = r'href=["\']?([^"\'<>\s]*\.pdf)["\']?'
        pdf_matches = re.finditer(pdf_pattern, content_html, re.IGNORECASE)
        
        documents = []
        
        for match in pdf_matches:
            pdf_url = match.group(1)
            
            # Make absolute URL if relative
            if not pdf_url.startswith('http'):
                pdf_url = base_url + ('/' if not pdf_url.startswith('/') else '') + pdf_url
            
            # Extract title from nearby text (look backwards from PDF link)
            # Find the last <a> tag before this PDF link
            link_section = content_html[:match.start()]
            title_match = re.search(r'>([^<]{1,100}?)(?:</a>)?$', link_section)
            title = title_match.group(1).strip() if title_match else 'Document'
            
            documents.append({
                'url': pdf_url,
                'title': title[:100],  # Limit title length
                'type': 'pdf'
            })
        
        print(f'\nFound {len(documents)} PDFs:')
        for doc in documents:
            print(f'  - {doc["title"]}')
            print(f'    {doc["url"]}')
        
        return {
            'success': True,
            'page_id': page_id,
            'documents': documents,
            'count': len(documents),
            'page_title': page_data.get('title', {}).get('rendered', 'N/A')
        }
        
    except Exception as e:
        return {
            'success': False,
            'error': str(e),
            'page_id': page_id
        }

def main():
    """Main function"""
    result = scrape_via_rest_api()
    print(f'\n=== JSON Result ===')
    print(json.dumps(result, indent=2, ensure_ascii=False))
    
    # Exit with proper code
    sys.exit(0 if result.get('success') else 1)

if __name__ == '__main__':
    main()
