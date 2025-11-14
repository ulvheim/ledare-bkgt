#!/usr/bin/env node

/**
 * SWE3 Scraper - Headless Browser Script
 * 
 * Uses Puppeteer to render JavaScript and extract document links.
 * Outputs JSON with all discovered documents.
 * 
 * Usage: node scrape-with-browser.js <url>
 * 
 * Dependencies:
 * - npm install puppeteer
 * 
 * Output:
 * JSON array with document objects:
 * [
 *   {
 *     "title": "Document Title",
 *     "url": "https://...",
 *     "type": "pdf|document"
 *   }
 * ]
 */

const puppeteer = require('puppeteer');
const url = process.argv[2];

if (!url) {
  console.error('Usage: node scrape-with-browser.js <url>');
  process.exit(1);
}

async function scrapeWithBrowser() {
  let browser;
  try {
    // Launch browser with optimizations for server environment
    browser = await puppeteer.launch({
      headless: true,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        '--disable-extensions',
        '--disable-sync'
      ]
    });

    const page = await browser.newPage();
    
    // Set viewport and user agent
    await page.setViewport({ width: 1920, height: 1080 });
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    // Navigate to page with wait for network idle
    console.error(`[*] Navigating to ${url}...`, {
      timeout: 30000,
      waitUntil: 'networkidle2'
    });

    await page.goto(url, {
      timeout: 30000,
      waitUntil: 'networkidle2'
    });

    // Wait a bit for any lazy-loaded content
    await page.waitForTimeout(2000);

    // Extract all links from the rendered page
    const documents = await page.evaluate(() => {
      const docs = [];
      
      // Find all links (both <a> tags and data attributes)
      const links = document.querySelectorAll('a[href*=".pdf"], a[href*=".PDF"], [data-download], [data-file]');
      
      links.forEach(link => {
        let href = link.getAttribute('href') || 
                   link.getAttribute('data-download') || 
                   link.getAttribute('data-file') ||
                   link.getAttribute('data-url');
        
        let title = link.textContent.trim() || 
                    link.getAttribute('title') ||
                    link.getAttribute('aria-label') ||
                    href;

        if (href && (href.includes('.pdf') || href.includes('.PDF'))) {
          // Make absolute URLs
          if (href.startsWith('/')) {
            href = new URL(href, window.location.origin).href;
          } else if (!href.startsWith('http')) {
            href = new URL(href, window.location.href).href;
          }

          docs.push({
            title: title.substring(0, 200),
            url: href,
            type: 'pdf'
          });
        }
      });

      // Also check for iframe sources that might be PDFs
      const iframes = document.querySelectorAll('iframe[src*=".pdf"], iframe[src*=".PDF"]');
      iframes.forEach(iframe => {
        let src = iframe.getAttribute('src');
        if (src) {
          if (src.startsWith('/')) {
            src = new URL(src, window.location.origin).href;
          } else if (!src.startsWith('http')) {
            src = new URL(src, window.location.href).href;
          }

          docs.push({
            title: iframe.getAttribute('title') || 'Embedded Document',
            url: src,
            type: 'pdf'
          });
        }
      });

      // Remove duplicates
      const unique = [];
      const seen = new Set();
      
      docs.forEach(doc => {
        if (!seen.has(doc.url)) {
          seen.add(doc.url);
          unique.push(doc);
        }
      });

      return unique;
    });

    console.error(`[+] Found ${documents.length} documents`);
    
    // Output as JSON to stdout
    console.log(JSON.stringify(documents, null, 2));

    await browser.close();
    process.exit(0);

  } catch (error) {
    console.error(`[!] Error: ${error.message}`);
    if (browser) {
      await browser.close();
    }
    process.exit(1);
  }
}

scrapeWithBrowser();
