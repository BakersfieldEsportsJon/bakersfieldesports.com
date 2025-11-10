import xml.etree.ElementTree as ET
import re
from urllib.parse import urlparse
import sys

def validate_sitemap(sitemap_path):
    print("\nValidating sitemap.xml...")
    try:
        # Parse XML
        tree = ET.parse(sitemap_path)
        root = tree.getroot()
        
        # Track URLs for uniqueness
        urls = set()
        
        # Validate each URL entry
        for url in root.findall(".//{http://www.sitemaps.org/schemas/sitemap/0.9}url"):
            loc = url.find(".//{http://www.sitemaps.org/schemas/sitemap/0.9}loc")
            if loc is not None:
                url_str = loc.text
                
                # Check for duplicate URLs
                if url_str in urls:
                    print(f"WARNING: Duplicate URL found: {url_str}")
                urls.add(url_str)
                
                # Validate URL format
                parsed = urlparse(url_str)
                if not all([parsed.scheme, parsed.netloc]):
                    print(f"ERROR: Invalid URL format: {url_str}")
                
                # Check domain
                if "bakersfieldesports.com" not in parsed.netloc:
                    print(f"WARNING: URL not on main domain: {url_str}")
            
            # Validate lastmod format
            lastmod = url.find(".//{http://www.sitemaps.org/schemas/sitemap/0.9}lastmod")
            if lastmod is not None:
                if not re.match(r'^\d{4}-\d{2}-\d{2}$', lastmod.text):
                    print(f"WARNING: Invalid lastmod format for {url_str}: {lastmod.text}")
            
            # Validate priority
            priority = url.find(".//{http://www.sitemaps.org/schemas/sitemap/0.9}priority")
            if priority is not None:
                try:
                    p = float(priority.text)
                    if not 0 <= p <= 1:
                        print(f"WARNING: Priority out of range for {url_str}: {p}")
                except ValueError:
                    print(f"ERROR: Invalid priority format for {url_str}: {priority.text}")
        
        print(f"Found {len(urls)} unique URLs in sitemap")
        print("XML validation successful!")
        return True
        
    except ET.ParseError as e:
        print(f"ERROR: XML parsing failed: {e}")
        return False
    except Exception as e:
        print(f"ERROR: Validation failed: {e}")
        return False

def validate_robots(robots_path):
    print("\nValidating robots.txt...")
    try:
        with open(robots_path, 'r') as f:
            content = f.read()
            
        # Check for required sections
        required_sections = ['User-agent: *', 'Allow:', 'Disallow:', 'Sitemap:']
        for section in required_sections:
            if section not in content:
                print(f"WARNING: Missing required section: {section}")
        
        # Validate sitemap URL
        sitemap_matches = re.findall(r'Sitemap: (.*)', content)
        for sitemap in sitemap_matches:
            parsed = urlparse(sitemap)
            if not all([parsed.scheme, parsed.netloc]):
                print(f"ERROR: Invalid sitemap URL: {sitemap}")
        
        # Check for valid crawl delays
        crawl_delays = re.findall(r'Crawl-delay: (\d+)', content)
        for delay in crawl_delays:
            if int(delay) > 100:
                print(f"WARNING: High crawl delay value: {delay}")
        
        print("Robots.txt validation successful!")
        return True
        
    except Exception as e:
        print(f"ERROR: Validation failed: {e}")
        return False

if __name__ == "__main__":
    sitemap_valid = validate_sitemap('sitemap.xml')
    robots_valid = validate_robots('robots.txt')
    
    if sitemap_valid and robots_valid:
        print("\nAll validations passed successfully!")
    else:
        print("\nValidation completed with warnings/errors. Please review the output above.")
        sys.exit(1)
