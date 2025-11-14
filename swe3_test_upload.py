#!/usr/bin/env python3
"""Test SWE3 upload endpoint"""

import requests
import sys

url = 'https://ledare.bkgt.se/wp-json/bkgt/v1/swe3-upload-document'

# Create a test file
with open('/tmp/test_upload.pdf', 'w') as f:
    f.write('Test PDF content')

# Prepare the upload
with open('/tmp/test_upload.pdf', 'rb') as f:
    files = {'file': f}
    data = {
        'title': 'Test Document',
        'url': 'https://example.com/test.pdf',
        'date': '2025-01-01',
        'size': 18
    }
    
    print(f"POST {url}")
    print(f"Data: {data}")
    print()
    
    try:
        response = requests.post(url, files=files, data=data, timeout=10)
        print(f"Status: {response.status_code}")
        print(f"Headers: {dict(response.headers)}")
        print(f"Body: {response.text[:500]}")
    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)
