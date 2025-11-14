#!/usr/bin/env python3
"""Quick test of SWE3 upload to DMS"""

import requests
import os

# Get a real PDF URL from SWE3
api_url = 'https://amerikanskfotboll.swe3.se/wp-json/wp/v2/media?per_page=1&mime_type=application/pdf'
api_response = requests.get(api_url, timeout=30)

if api_response.status_code != 200:
    print(f'Failed to get API data: {api_response.status_code}')
    exit(1)

data = api_response.json()
if not data:
    print('No documents found')
    exit(1)

doc = data[0]
pdf_url = doc.get('source_url', '')
title_obj = doc.get('title', {})

if isinstance(title_obj, dict):
    title = title_obj.get('rendered', f"doc_{doc.get('id', 'unknown')}")
else:
    title = str(title_obj)

print(f'Found document: {title[:50]}')
print(f'PDF URL: {pdf_url}')

# Download the PDF
pdf_response = requests.get(pdf_url, timeout=30)
if pdf_response.status_code != 200:
    print(f'Failed to download PDF: {pdf_response.status_code}')
    exit(1)

temp_file = '/tmp/test_real_doc.pdf'
with open(temp_file, 'wb') as f:
    f.write(pdf_response.content)

file_size = os.path.getsize(temp_file)
print(f'Downloaded: {file_size} bytes')

# Now upload to DMS
with open(temp_file, 'rb') as f:
    upload_response = requests.post(
        'https://ledare.bkgt.se/wp-admin/admin-ajax.php',
        data={
            'action': 'swe3_upload_document',
            'title': title,
            'url': pdf_url,
            'date': doc.get('date', ''),
            'size': file_size
        },
        files={'file': f},
        timeout=60
    )
    
    print(f'Upload response status: {upload_response.status_code}')
    print(f'Upload response:\n{upload_response.text[:1000]}')

# Clean up
os.remove(temp_file)
