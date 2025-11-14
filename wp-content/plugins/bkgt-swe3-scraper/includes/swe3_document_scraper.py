#!/usr/bin/env python3
"""
SWE3 Document Scraper
Fetches and parses JavaScript-heavy SWE3 pages for document links
Falls back to requests library if Selenium/browser drivers unavailable
"""

import sys
import json
import time
import re
import urllib.parse
from typing import List, Dict, Optional

# Try Selenium first
HAS_SELENIUM = False
try:
    from selenium import webdriver
    from selenium.webdriver.common.by import By
    from selenium.webdriver.support.ui import WebDriverWait
    from selenium.webdriver.support import expected_conditions as EC
    from selenium.common.exceptions import TimeoutException, NoSuchElementException
    HAS_SELENIUM = True
except ImportError:
    pass

# Try webdriver-manager
HAS_DRIVER_MANAGER = False
if HAS_SELENIUM:
    try:
        from webdriver_manager.chrome import ChromeDriverManager
        from webdriver_manager.firefox import GeckoDriverManager
        from selenium.webdriver.chrome.service import Service as ChromeService
        from selenium.webdriver.firefox.service import Service as FirefoxService
        HAS_DRIVER_MANAGER = True
    except ImportError:
        pass

# Always available
try:
    import requests
    HAS_REQUESTS = True
except ImportError:
    HAS_REQUESTS = False

class SWE3DocumentScraper:
    """Scrapes documents from SWE3 website"""
    
    def __init__(self, headless: bool = True, timeout: int = 10):
        """
        Initialize Scraper
        
        Args:
            headless: Run browser in headless mode (if available)
            timeout: Wait timeout (seconds)
        """
        self.timeout = timeout
        self.driver = None
        self.headless = headless
        self.use_selenium = HAS_SELENIUM and HAS_DRIVER_MANAGER
        
    def setup_driver(self) -> bool:
        """
        Setup Chrome/Firefox WebDriver
        Returns True if successful, False otherwise
        """
        if not HAS_SELENIUM or not HAS_DRIVER_MANAGER:
            return False
            
        try:
            # Try Chrome with webdriver-manager
            try:
                options = webdriver.ChromeOptions()
                if self.headless:
                    options.add_argument('--headless=new')
                options.add_argument('--no-sandbox')
                options.add_argument('--disable-dev-shm-usage')
                options.add_argument('--disable-gpu')
                options.add_argument('--disable-extensions')
                options.add_argument('--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
                
                service = ChromeService(ChromeDriverManager().install())
                self.driver = webdriver.Chrome(service=service, options=options)
                return True
            except Exception as e:
                # Try Firefox
                options = webdriver.FirefoxOptions()
                if self.headless:
                    options.add_argument('--headless')
                options.add_argument('--disable-gpu')
                
                service = FirefoxService(GeckoDriverManager().install())
                self.driver = webdriver.Firefox(service=service, options=options)
                return True
        except Exception as e:
            return False
    
    def fetch_url(self, url: str) -> Optional[str]:
        """
        Fetch URL and wait for JavaScript to render
        
        Args:
            url: URL to fetch
            
        Returns:
            Page source HTML or None if error
        """
        if self.use_selenium and self.setup_driver():
            try:
                self.driver.get(url)
                
                # Wait for content
                wait = WebDriverWait(self.driver, self.timeout)
                try:
                    wait.until(EC.presence_of_all_elements_located((By.TAG_NAME, "a")))
                except TimeoutException:
                    pass
                
                time.sleep(1)
                return self.driver.page_source
            except Exception as e:
                return None
            finally:
                if self.driver:
                    self.driver.quit()
        
        # Fallback to requests library
        if HAS_REQUESTS:
            try:
                headers = {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                }
                response = requests.get(url, timeout=self.timeout, headers=headers)
                response.raise_for_status()
                return response.text
            except Exception as e:
                return None
        
        return None
    
    def extract_pdf_links(self, html: str) -> List[Dict[str, str]]:
        """
        Extract PDF download links from HTML
        
        Args:
            html: Page HTML source
            
        Returns:
            List of dicts with 'url' and 'title' keys
        """
        documents = []
        
        # Create a temporary driver instance to parse HTML
        # (using Selenium's HTML parser is overkill, use regex instead)
        import re
        
        # Pattern 1: Direct PDF links
        pdf_pattern = r'href=["\']?([^"\'>\s]+\.pdf)["\']?'
        for match in re.finditer(pdf_pattern, html, re.IGNORECASE):
            url = match.group(1)
            if url.startswith('http') or url.startswith('/'):
                documents.append({
                    'url': url,
                    'title': self._extract_title_from_url(url)
                })
        
        # Pattern 2: Links in table cells (common SWE3 structure)
        # <a href="...">Document Title</a>
        link_pattern = r'<a\s+(?:[^>]*?\s+)?href=["\']?([^"\'>\s]+)["\']?[^>]*>([^<]+)</a>'
        for match in re.finditer(link_pattern, html):
            url = match.group(1)
            title = match.group(2).strip()
            
            # Only include if it's a PDF or document-like URL
            if url.endswith('.pdf') or 'download' in url.lower() or 'document' in url.lower():
                if not any(d['url'] == url for d in documents):  # Avoid duplicates
                    documents.append({
                        'url': url,
                        'title': title if title else self._extract_title_from_url(url)
                    })
        
        return documents
    
    def _extract_title_from_url(self, url: str) -> str:
        """Extract human-readable title from URL"""
        import urllib.parse
        
        # Get filename from URL
        parsed = urllib.parse.urlparse(url)
        filename = parsed.path.split('/')[-1]
        
        # Remove extension and decode URL encoding
        title = urllib.parse.unquote(filename)
        if '.' in title:
            title = title.rsplit('.', 1)[0]
        
        return title or 'Document'
    
    def scrape(self, url: str) -> Dict[str, any]:
        """
        Main scraping function
        
        Args:
            url: URL to scrape
            
        Returns:
            Dict with 'success', 'documents', and optional 'error' keys
        """
        try:
            # Fetch and render page
            html = self.fetch_url(url)
            if not html:
                return {
                    'success': False,
                    'documents': [],
                    'error': 'Could not fetch URL'
                }
            
            # Extract links
            documents = self.extract_pdf_links(html)
            
            return {
                'success': True,
                'documents': documents,
                'count': len(documents),
                'method': 'selenium' if (self.use_selenium and self.driver) else 'requests'
            }
        except Exception as e:
            return {
                'success': False,
                'documents': [],
                'error': str(e)
            }

def main():
    """Command-line interface"""
    if len(sys.argv) < 2:
        print(json.dumps({
            'error': 'Usage: swe3_document_scraper.py <url> [timeout]'
        }))
        sys.exit(1)
    
    url = sys.argv[1]
    timeout = int(sys.argv[2]) if len(sys.argv) > 2 else 10
    
    scraper = SWE3DocumentScraper(headless=True, timeout=timeout)
    result = scraper.scrape(url)
    
    # Output JSON result
    print(json.dumps(result, indent=2))
    
    # Exit with proper code
    sys.exit(0 if result.get('success') else 1)

if __name__ == '__main__':
    main()
