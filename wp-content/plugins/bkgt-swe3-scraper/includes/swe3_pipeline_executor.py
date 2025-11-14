#!/usr/bin/env python3
"""
SWE3 Document Pipeline Executor
Fetches documents from SWE3, downloads them, and uploads to BKGT DMS
"""

import requests
import json
import sys
import os
import hashlib
import tempfile
from datetime import datetime

class SWE3_DMS_Pipeline:
    """Complete pipeline for SWE3 documents"""
    
    def __init__(self, dms_url):
        self.swe3_api = 'https://amerikanskfotboll.swe3.se/wp-json/wp/v2/media'
        self.dms_url = dms_url
        # Use admin-ajax endpoint instead of REST API to bypass nginx restrictions
        self.dms_endpoint = f'{dms_url}/wp-admin/admin-ajax.php?action=swe3_upload_document'
        self.temp_dir = tempfile.mkdtemp(prefix='swe3_')
        self.stats = {'fetched': 0, 'downloaded': 0, 'uploaded': 0, 'failed': 0}
        
    def fetch_all_documents(self):
        """Fetch all PDF documents from SWE3"""
        print(f"[FETCH] Getting documents from SWE3...")
        documents = {}
        page = 1
        
        while page <= 100:
            try:
                url = f"{self.swe3_api}?per_page=100&page={page}"
                response = requests.get(url, timeout=15)
                
                if response.status_code != 200:
                    break
                
                data = response.json()
                if not data:
                    break
                
                for item in data:
                    if item.get('mime_type') == 'application/pdf':
                        title = item.get('title', {})
                        if isinstance(title, dict):
                            title = title.get('rendered', '')
                        if not title:
                            title = item.get('slug', f'doc_{item["id"]}')
                        
                        doc_url = item.get('source_url', '')
                        if doc_url:
                            documents[doc_url] = {
                                'id': item.get('id'),
                                'title': title,
                                'url': doc_url,
                                'size': item.get('media_details', {}).get('filesize', 0),
                                'date': item.get('date')
                            }
                
                page += 1
                
            except Exception as e:
                print(f"[ERROR] Page {page}: {e}")
                break
        
        self.stats['fetched'] = len(documents)
        print(f"[FETCH] Found {len(documents)} documents")
        return documents
    
    def download_document(self, doc_url, filename):
        """Download PDF from SWE3"""
        try:
            response = requests.get(doc_url, timeout=30, stream=True)
            if response.status_code != 200:
                print(f"[DOWNLOAD] {filename[:40]}: Failed (HTTP {response.status_code})")
                return None
            
            file_path = os.path.join(self.temp_dir, filename)
            
            with open(file_path, 'wb') as f:
                for chunk in response.iter_content(chunk_size=8192):
                    if chunk:
                        f.write(chunk)
            
            print(f"[DOWNLOAD] {filename[:40]}: OK ({os.path.getsize(file_path):,} bytes)")
            self.stats['downloaded'] += 1
            return file_path
            
        except Exception as e:
            print(f"[DOWNLOAD] {filename[:40]}: Error - {e}")
            return None
    
    def upload_to_dms(self, file_path, metadata):
        """Upload document to DMS"""
        try:
            with open(file_path, 'rb') as f:
                files = {'file': f}
                data = {
                    'title': metadata['title'],
                    'url': metadata['url'],
                    'date': metadata.get('date', ''),
                    'size': metadata.get('size', 0),
                    'action': 'swe3_upload_document'
                }
                
                response = requests.post(
                    self.dms_endpoint,
                    data=data,
                    files=files,
                    timeout=60
                )
            
            if response.status_code in [200, 201]:
                try:
                    result = response.json()
                    if result.get('success') or result.get('data', {}).get('post_id'):
                        print(f"[UPLOAD] {metadata['title'][:40]}: OK (ID: {result.get('data', {}).get('post_id')})")
                        self.stats['uploaded'] += 1
                        return True
                except:
                    pass
            
            # Log the error response
            error_msg = response.text[:200] if response.text else f"HTTP {response.status_code}"
            print(f"[UPLOAD] {metadata['title'][:40]}: Failed (HTTP {response.status_code}) - {error_msg}")
            self.stats['failed'] += 1
            return False
            
        except Exception as e:
            print(f"[UPLOAD] {metadata['title'][:40]}: Error - {e}")
            self.stats['failed'] += 1
            return False
    
    def run(self):
        """Execute complete pipeline"""
        print("\n" + "="*60)
        print("SWE3 → BKGT DMS Pipeline")
        print("="*60 + "\n")
        
        # Fetch documents
        documents = self.fetch_all_documents()
        if not documents:
            print("[ERROR] No documents found!")
            return False
        
        print(f"\n[PIPELINE] Processing {len(documents)} documents...")
        
        # Process each document
        for i, (url, metadata) in enumerate(documents.items(), 1):
            filename = f"swe3_{metadata['id']}.pdf"
            
            file_path = self.download_document(url, filename)
            if file_path:
                self.upload_to_dms(file_path, metadata)
                try:
                    os.remove(file_path)
                except:
                    pass
        
        # Report results
        print(f"\n" + "="*60)
        print("Pipeline Complete")
        print("="*60)
        print(f"Fetched:   {self.stats['fetched']}")
        print(f"Downloaded: {self.stats['downloaded']}")
        print(f"Uploaded:   {self.stats['uploaded']}")
        print(f"Failed:     {self.stats['failed']}")
        print("="*60 + "\n")
        
        return self.stats['failed'] == 0

def main():
    """Main entry point"""
    # Detect DMS URL from environment or use default
    dms_url = os.environ.get('LEDARE_DMS_URL', 'https://ledare.bkgt.se')
    
    pipeline = SWE3_DMS_Pipeline(dms_url)
    success = pipeline.run()
    
    # Output JSON summary
    summary = {
        'success': success,
        'timestamp': datetime.utcnow().isoformat() + 'Z',
        'stats': pipeline.stats,
        'dms_url': dms_url
    }
    
    print("JSON Summary:")
    print(json.dumps(summary, indent=2))
    
    return 0 if success else 1

if __name__ == '__main__':
    sys.exit(main())
