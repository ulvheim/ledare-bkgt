#!/usr/bin/env python3
"""
SWE3 Document Scraper - FINAL VERSION
Fetches PDFs from WordPress Media Library via REST API
This is the correct approach - simple, reliable, and doesn't require browser rendering
"""

import requests
import json
import sys
from typing import List, Dict
from datetime import datetime

def scrape_swe3_documents() -> Dict:
    """
    Fetch all PDF documents from SWE3 WordPress Media Library
    
    Returns:
        Dict with success status, documents array, and metadata
    """
    base_url = 'https://amerikanskfotboll.swe3.se'
    api_url = f'{base_url}/wp-json/wp/v2/media'
    
    print(f'Fetching SWE3 media library: {api_url}')
    
    try:
        # Fetch all media items
        all_documents = []
        page = 1
        per_page = 100
        
        while True:
            url = f'{api_url}?per_page={per_page}&page={page}'
            print(f'  Fetching page {page}...')
            
            response = requests.get(url, timeout=10)
            response.raise_for_status()
            
            media_items = response.json()
            
            if not media_items:
                break
            
            # Filter for PDFs only
            for item in media_items:
                mime_type = item.get('mime_type', '').lower()
                
                if mime_type == 'application/pdf':
                    doc = {
                        'id': item.get('id'),
                        'title': item.get('title', {}).get('rendered', 'Document'),
                        'url': item.get('source_url', ''),
                        'mime_type': mime_type,
                        'date': item.get('date', ''),
                        'modified': item.get('modified', ''),
                        'size': item.get('media_details', {}).get('filesize', 0),
                    }
                    
                    # Normalize title (remove HTML entities)
                    if '&' in doc['title']:
                        import html
                        doc['title'] = html.unescape(doc['title'])
                    
                    all_documents.append(doc)
            
            # Check if there are more pages
            if len(media_items) < per_page:
                break
            
            page += 1
        
        print(f'\n✓ Found {len(all_documents)} PDF documents')
        
        # Sort by modified date (newest first)
        all_documents.sort(
            key=lambda x: x.get('modified', ''),
            reverse=True
        )
        
        return {
            'success': True,
            'count': len(all_documents),
            'documents': all_documents,
            'fetched_at': datetime.utcnow().isoformat(),
            'source': 'WordPress Media Library REST API',
        }
        
    except requests.exceptions.RequestException as e:
        return {
            'success': False,
            'error': f'HTTP Error: {str(e)}',
            'count': 0,
            'documents': [],
        }
    except Exception as e:
        return {
            'success': False,
            'error': f'Error: {str(e)}',
            'count': 0,
            'documents': [],
        }

def main():
    """Main function"""
    result = scrape_swe3_documents()
    
    # Print summary
    if result['success']:
        print(f'\nSummary:')
        for doc in result['documents'][:5]:
            print(f'  - {doc["title"]}')
            print(f'    {doc["url"]}')
        
        if len(result['documents']) > 5:
            print(f'  ... and {len(result["documents"]) - 5} more')
    else:
        print(f'\n✗ Error: {result["error"]}')
    
    # Print JSON output
    print(f'\n=== JSON Output ===')
    print(json.dumps(result, indent=2, ensure_ascii=False))
    
    # Exit with proper code
    sys.exit(0 if result['success'] else 1)

if __name__ == '__main__':
    main()
