#!/usr/bin/env python3
"""
Find all PDFs from SWE3 site - including those not in REST API
"""
import requests
import json
import sys
from urllib.parse import urljoin

def find_pdfs_from_api():
    """Get PDFs from WordPress REST API with full pagination"""
    base_url = 'https://amerikanskfotboll.swe3.se/wp-json/wp/v2/media'
    all_files = {}
    
    for page in range(1, 20):  # Check up to 20 pages (2000 items)
        try:
            resp = requests.get(f'{base_url}?per_page=100&page={page}', timeout=10)
            if resp.status_code != 200:
                print(f"[API] Page {page}: Status {resp.status_code}", file=sys.stderr)
                break
            
            data = resp.json()
            if not data:
                print(f"[API] Page {page}: No more data", file=sys.stderr)
                break
            
            page_count = 0
            for item in data:
                if item.get('mime_type') == 'application/pdf':
                    title = item.get('title', {})
                    if isinstance(title, dict):
                        title = title.get('rendered', item.get('slug', ''))
                    
                    all_files[item['source_url']] = {
                        'id': item.get('id'),
                        'title': title,
                        'url': item.get('source_url', ''),
                        'size': item.get('media_details', {}).get('filesize', 0),
                        'date': item.get('date'),
                        'source': 'REST API'
                    }
                    page_count += 1
            
            if page_count == 0:
                print(f"[API] Page {page}: No PDFs on this page", file=sys.stderr)
                if page > 1:
                    break
            else:
                print(f"[API] Page {page}: Found {page_count} PDFs", file=sys.stderr)
                
        except Exception as e:
            print(f"[API] Error on page {page}: {e}", file=sys.stderr)
            break
    
    return all_files

def find_pdfs_via_sitemap():
    """Try to get PDFs from sitemap or XML"""
    try:
        resp = requests.get('https://amerikanskfotboll.swe3.se/sitemap.xml', timeout=10)
        if resp.status_code == 200:
            import re
            urls = re.findall(r'<loc>(.*?\.pdf)</loc>', resp.text, re.IGNORECASE)
            print(f"[SITEMAP] Found {len(urls)} PDF URLs", file=sys.stderr)
            return urls
    except:
        pass
    return []

def find_pdfs_via_crawl():
    """Crawl common pages for PDF links"""
    urls_found = set()
    common_pages = [
        '/regler/',
        '/dokument/',
        '/downloads/',
        '/resources/',
        '/tavlingsbestammelser/',
        '/'
    ]
    
    for page_path in common_pages:
        try:
            url = f'https://amerikanskfotboll.swe3.se{page_path}'
            resp = requests.get(url, timeout=10)
            if resp.status_code == 200:
                import re
                pdfs = re.findall(r'https://[^"\s]*\.pdf', resp.text, re.IGNORECASE)
                if pdfs:
                    print(f"[CRAWL] {page_path}: Found {len(pdfs)} PDFs", file=sys.stderr)
                    urls_found.update(pdfs)
        except Exception as e:
            print(f"[CRAWL] Error on {page_path}: {e}", file=sys.stderr)
    
    return list(urls_found)

if __name__ == '__main__':
    print("=== Searching for all PDFs ===\n", file=sys.stderr)
    
    all_pdfs = {}
    
    # 1. Get from REST API
    api_pdfs = find_pdfs_from_api()
    all_pdfs.update(api_pdfs)
    print(f"[API] Total: {len(api_pdfs)} PDFs\n", file=sys.stderr)
    
    # 2. Try sitemap
    sitemap_urls = find_pdfs_via_sitemap()
    for url in sitemap_urls:
        if url not in all_pdfs:
            all_pdfs[url] = {
                'title': url.split('/')[-1],
                'url': url,
                'source': 'Sitemap'
            }
    
    # 3. Try crawling common pages
    crawl_urls = find_pdfs_via_crawl()
    for url in crawl_urls:
        if url not in all_pdfs:
            all_pdfs[url] = {
                'title': url.split('/')[-1],
                'url': url,
                'source': 'Crawl'
            }
    
    print(f"\n=== Results ===", file=sys.stderr)
    print(f"Total unique PDFs: {len(all_pdfs)}", file=sys.stderr)
    print(f"From REST API: {len([p for p in all_pdfs.values() if p.get('source') == 'REST API'])}", file=sys.stderr)
    print(f"From other sources: {len([p for p in all_pdfs.values() if p.get('source') != 'REST API'])}", file=sys.stderr)
    print(f"\n=== PDF List ===\n", file=sys.stderr)
    
    # Output JSON
    output = {
        'total_count': len(all_pdfs),
        'documents': sorted(list(all_pdfs.values()), key=lambda x: x.get('title', ''))
    }
    print(json.dumps(output, indent=2, ensure_ascii=False))
