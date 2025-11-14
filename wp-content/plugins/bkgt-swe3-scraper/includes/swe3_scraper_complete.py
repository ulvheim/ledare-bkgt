#!/usr/bin/env python3
"""
Complete SWE3 Document Scraper
Fetches ALL documents from the WordPress REST API with proper pagination
"""

import requests
import json
import sys
from datetime import datetime

def scrape_all_documents():
    """Fetch all PDF documents from SWE3 WordPress REST API"""
    base_url = 'https://amerikanskfotboll.swe3.se/wp-json/wp/v2/media'
    documents = {}
    total_pages = 0
    total_items = 0
    
    print(f"Fetching SWE3 complete media library: {base_url}")
    
    # Paginate through all media
    for page in range(1, 100):  # Safety limit at 100 pages
        try:
            url = f"{base_url}?per_page=100&page={page}"
            response = requests.get(url, timeout=15)
            
            if response.status_code != 200:
                print(f"  Page {page}: Status {response.status_code} - stopping pagination")
                break
            
            data = response.json()
            
            # Check if we got results
            if not data or len(data) == 0:
                print(f"  Page {page}: No results - pagination complete")
                break
            
            page_pdfs = 0
            total_items += len(data)
            
            # Filter and process PDFs
            for item in data:
                if item.get('mime_type') == 'application/pdf':
                    doc_id = item.get('id')
                    
                    # Extract title
                    title = item.get('title', {})
                    if isinstance(title, dict):
                        title = title.get('rendered', '')
                    if not title:
                        title = item.get('slug', f'Document {doc_id}')
                    
                    # Get file info
                    url = item.get('source_url', '')
                    media_details = item.get('media_details', {})
                    file_size = media_details.get('filesize', 0)
                    
                    # Store by URL as key to avoid duplicates
                    documents[url] = {
                        'id': doc_id,
                        'title': title,
                        'url': url,
                        'mime_type': item.get('mime_type'),
                        'date': item.get('date'),
                        'modified': item.get('modified'),
                        'size': file_size
                    }
                    page_pdfs += 1
            
            print(f"  Page {page}: Found {page_pdfs} PDFs (total items: {len(data)})")
            total_pages = page
            
        except requests.exceptions.RequestException as e:
            print(f"  Page {page}: Error - {e}")
            continue
        except json.JSONDecodeError as e:
            print(f"  Page {page}: JSON decode error - {e}")
            continue
    
    return documents, total_pages, total_items

def main():
    """Main entry point"""
    try:
        # Fetch all documents
        documents, pages_fetched, total_items = scrape_all_documents()
        
        # Convert to sorted list
        doc_list = sorted(documents.values(), key=lambda x: x.get('date', ''), reverse=True)
        
        print(f"\n✓ Found {len(documents)} unique PDF documents")
        print(f"  Fetched {pages_fetched} pages with {total_items} total media items")
        
        # Output JSON
        output = {
            'success': True,
            'count': len(documents),
            'pages_fetched': pages_fetched,
            'total_media_items': total_items,
            'fetched_at': datetime.utcnow().isoformat() + 'Z',
            'source': 'WordPress Media Library REST API (Complete Pagination)',
            'documents': doc_list
        }
        
        print(f"\n=== JSON Output ===")
        print(json.dumps(output, indent=2, ensure_ascii=False))
        
        return 0
        
    except Exception as e:
        error_output = {
            'success': False,
            'error': str(e),
            'fetched_at': datetime.utcnow().isoformat() + 'Z'
        }
        print(json.dumps(error_output, indent=2), file=sys.stderr)
        return 1

if __name__ == '__main__':
    sys.exit(main())
