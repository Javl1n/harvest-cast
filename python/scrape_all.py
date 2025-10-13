import sys
sys.path.append('/libs')

import requests
from bs4 import BeautifulSoup
import re
import pdfplumber
import io
import tempfile
import json
import os
from datetime import datetime

DA_URL = "https://www.da.gov.ph/price-monitoring/"
DA_HEADERS = {
     "User-Agent": (
          "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
          "AppleWebKit/537.36 (KHTML, like Gecko) "
          "Chrome/129.0.0.0 Safari/537.36"
     ),
     "Accept-Language": "en-US,en;q=0.9",
}

LARAVEL_HEADERS = {
    "Accept": "application/json",
    "Content-Type": "application/json",
}

def get_all_pdf_links():
    """
    Scrape the DA Price Monitoring page and get ALL 'Daily Price Index' PDF links.
    """
    response = requests.get(DA_URL, headers=DA_HEADERS)
    response.raise_for_status()

    soup = BeautifulSoup(response.text, "html.parser")

    # find the H3 with text containing 'Daily Price Index'
    header = soup.find("h3", string=lambda t: t and "Daily Price Index" in t)
    if not header:
        raise Exception("Couldn't find 'Daily Price Index' section.")

    # find the table immediately after that header
    table = header.find_next("table")
    if not table:
        raise Exception("Couldn't find the table below the header.")

    # get ALL <a> links inside table, not just the first one
    all_links = table.find_all("a", href=True)
    if not all_links:
        raise Exception("No PDF links found in table.")

    pdf_urls = [link["href"] for link in all_links]
    print(f"Found {len(pdf_urls)} PDFs:")
    for i, url in enumerate(pdf_urls, 1):
        print(f"  {i}. {url}")
    
    return pdf_urls

def download_pdf(url):
    """
    Download PDF file and save to a temporary location.
    """
    print(f"Downloading PDF: {url}")

    # make sure the directory exists
    save_dir = os.path.join(os.path.dirname(__file__), "pdfs")
    os.makedirs(save_dir, exist_ok=True)

    # extract filename from URL or create timestamp-based name
    url_filename = url.split("/")[-1] if "/" in url else None
    if url_filename and url_filename.endswith('.pdf'):
        filename = url_filename
    else:
        filename = f"daily_price_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
    
    save_path = os.path.join(save_dir, filename)

    # download
    response = requests.get(url)
    response.raise_for_status()

    with open(save_path, "wb") as f:
        f.write(response.content)

    print(f"Saved PDF to: {save_path}")
    return save_path

def extract_date_from_pdf(pdf_path):
    """
    Extract date from PDF content or filename.
    Returns date in YYYY-MM-DD format or None if not found.
    """
    print(f"🔍 Attempting to extract date from: {pdf_path}")
    
    try:
        # First try to extract from PDF content
        with pdfplumber.open(pdf_path) as pdf:
            for page_num, page in enumerate(pdf.pages[:2]):  # Check first 2 pages
                text = page.extract_text()
                if not text:
                    continue
                
                print(f"📄 Checking page {page_num + 1} for date patterns...")
                
                # Look for common date patterns in the first few lines
                lines = text.split("\n")[:15]  # Check first 15 lines
                
                for line_num, line in enumerate(lines):
                    line = line.strip()
                    if not line:
                        continue
                    
                    print(f"   Line {line_num + 1}: {line[:80]}...")  # Debug: show first 80 chars
                    
                    # Pattern: "October 13, 2025" or "October 13 2025"
                    date_match = re.search(r'(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{1,2}),?\s+(\d{4})', line, re.IGNORECASE)
                    if date_match:
                        month_name, day, year = date_match.groups()
                        month_num = {
                            'january': '01', 'february': '02', 'march': '03', 'april': '04',
                            'may': '05', 'june': '06', 'july': '07', 'august': '08',
                            'september': '09', 'october': '10', 'november': '11', 'december': '12'
                        }.get(month_name.lower())
                        
                        if month_num:
                            extracted_date = f"{year}-{month_num}-{day.zfill(2)}"
                            print(f"✅ Found date pattern (Month DD, YYYY): {extracted_date}")
                            return extracted_date
                    
                    # Pattern: "13/10/2025" or "10/13/2025" 
                    date_match = re.search(r'(\d{1,2})/(\d{1,2})/(\d{4})', line)
                    if date_match:
                        part1, part2, year = date_match.groups()
                        # Try both MM/DD and DD/MM formats
                        if int(part1) <= 12 and int(part2) <= 31:  # MM/DD format
                            extracted_date = f"{year}-{part1.zfill(2)}-{part2.zfill(2)}"
                            print(f"✅ Found date pattern (MM/DD/YYYY): {extracted_date}")
                            return extracted_date
                        elif int(part2) <= 12 and int(part1) <= 31:  # DD/MM format
                            extracted_date = f"{year}-{part2.zfill(2)}-{part1.zfill(2)}"
                            print(f"✅ Found date pattern (DD/MM/YYYY): {extracted_date}")
                            return extracted_date
                    
                    # Pattern: "2025-10-13"
                    date_match = re.search(r'(\d{4})-(\d{1,2})-(\d{1,2})', line)
                    if date_match:
                        year, month, day = date_match.groups()
                        extracted_date = f"{year}-{month.zfill(2)}-{day.zfill(2)}"
                        print(f"✅ Found date pattern (YYYY-MM-DD): {extracted_date}")
                        return extracted_date
                    
                    # Pattern: "13 October 2025" or "13 Oct 2025"
                    date_match = re.search(r'(\d{1,2})\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})', line, re.IGNORECASE)
                    if date_match:
                        day, month_name, year = date_match.groups()
                        month_num = {
                            'jan': '01', 'january': '01', 'feb': '02', 'february': '02',
                            'mar': '03', 'march': '03', 'apr': '04', 'april': '04',
                            'may': '05', 'jun': '06', 'june': '06', 'jul': '07', 'july': '07',
                            'aug': '08', 'august': '08', 'sep': '09', 'september': '09',
                            'oct': '10', 'october': '10', 'nov': '11', 'november': '11',
                            'dec': '12', 'december': '12'
                        }.get(month_name.lower())
                        
                        if month_num:
                            extracted_date = f"{year}-{month_num}-{day.zfill(2)}"
                            print(f"✅ Found date pattern (DD Month YYYY): {extracted_date}")
                            return extracted_date
        
        # If no date found in content, try filename
        filename = os.path.basename(pdf_path)
        print(f"🔍 Trying to extract date from filename: {filename}")
        
        # Pattern in filename: "2025-10-13" or "20251013"
        date_match = re.search(r'(\d{4})-?(\d{2})-?(\d{2})', filename)
        if date_match:
            year, month, day = date_match.groups()
            extracted_date = f"{year}-{month}-{day}"
            print(f"✅ Found date in filename: {extracted_date}")
            return extracted_date
        
        print(f"❌ Warning: Could not extract date from {pdf_path}")
        return None
        
    except Exception as e:
        print(f"❌ Error extracting date from {pdf_path}: {e}")
        return None

def parse_pdf_to_json(pdf_path):
    """
    Robust parser for DA Daily Price Index PDFs.
    - Skips column header blocks that contain 'PRICE', 'UNIT', etc.
    - Detects category headings that are real section titles (e.g. 'FISH PRODUCTS').
    - Accumulates multi-line commodity descriptions until a price token is found,
      then emits a record.
    Returns: list of dicts: { category, commodity, price }
    """
    header_keywords = {
        'PRICE','UNIT','RETAIL','PREVAILING','COMMODITY','SPECIFICATION',
        'DEPARTMENT','DAILY','INDEX','NCR','PAGE','NOTE','(P/UNIT)','P/UNIT'
    }
    data = []
    current_category = None
    buffer = []

    # read all text lines from pdf
    lines = []
    with pdfplumber.open(pdf_path) as pdf:
        for page in pdf.pages:
            text = page.extract_text()
            if not text:
                continue
            for raw in text.split("\n"):
                line = raw.strip()
                if not line:
                    continue
                # skip obvious footers/headers
                if re.match(r'Page \d+', line) or line.startswith("Department of Agriculture") or line.startswith("DAILY PRICE INDEX") or line.startswith("("):
                    continue
                lines.append(line)

    i = 0
    while i < len(lines):
        line = lines[i]

        # If this is an uppercase line, it may be a column header block (skip) or a real category
        if line.isupper():
            # if it contains header keywords -> skip the entire consecutive uppercase block
            if any(k in line for k in header_keywords):
                j = i
                while j < len(lines) and lines[j].isupper():
                    j += 1
                i = j
                buffer = []
                continue
            # otherwise treat as category (avoid parentheses/digits)
            if not re.search(r'[\d\(\)]', line):
                current_category = line.title()
                i += 1
                buffer = []
                continue

        # Accumulate lines until we see a price token at the end
        buffer.append(line)
        m = re.search(r"([0-9]+(?:\.[0-9]+)?|n/a|N/A)$", line)
        if m:
            price_str = m.group(1)
            # Remove price from last buffered line, join previous buffer parts
            last = buffer[-1]
            last_wo_price = re.sub(r"([0-9]+(?:\.[0-9]+)?|n/a|N/A)$", "", last).strip()
            parts = [b for b in buffer[:-1]] + [last_wo_price]
            commodity = " ".join(parts).strip(" ,;-")
            commodity = re.sub(r"\s+", " ", commodity)
            price = None if price_str.lower() == "n/a" else float(price_str)
            data.append({
                "category": current_category,
                "commodity": commodity,
                "price": price
            })
            buffer = []
        i += 1

    print(f"Extracted {len(data)} entries from {pdf_path}")
    # print(data)

    return data

def post_to_server(data, date):
    """
    Send JSON data to your server endpoint.
    """
    print(f"Posting {len(data)} entries to server with date: {date}...")
    SERVER_ENDPOINT = "http://localhost:8000/api/crops/data"
    payload = {
        "data": data,
        "date": date
    }
    response = requests.post(SERVER_ENDPOINT, json=payload, headers=LARAVEL_HEADERS)
    print(f"Server response: {response.status_code}")
    try:
        print("Response JSON:", response.json())
    except Exception:
        print("Response text:", response.text)


def main():
    try:
        # Get all PDF links instead of just the latest
        pdf_links = get_all_pdf_links()
        
        all_data = []
        
        # Process each PDF
        for i, pdf_link in enumerate(pdf_links, 1):
            print(f"\n--- Processing PDF {i}/{len(pdf_links)} ---")
            try:
                pdf_path = download_pdf(pdf_link)
                
                # Extract date from PDF before parsing data
                pdf_date = extract_date_from_pdf(pdf_path)
                if pdf_date:
                    print(f"Extracted date: {pdf_date}")
                else:
                    # Fallback to current date if extraction fails
                    pdf_date = datetime.now().strftime('%Y-%m-%d')
                    print(f"Using current date as fallback: {pdf_date}")
                
                json_data = parse_pdf_to_json(pdf_path)
                
                # Delete the PDF file immediately after parsing
                try:
                    os.remove(pdf_path)
                    print(f"Cleaned up: {pdf_path}")
                except Exception as e:
                    print(f"Warning: Could not remove {pdf_path}: {e}")
                
                # Post each PDF's data separately to the server with extracted date
                if json_data:
                    post_to_server(json_data, pdf_date)
                    all_data.extend(json_data)
                else:
                    print(f"No data extracted from {pdf_path}")
                    
            except Exception as e:
                print(f"❌ Error processing PDF {i} ({pdf_link}): {e}")
                continue
        
        print(f"\n✅ Done successfully. Processed {len(pdf_links)} PDFs with {len(all_data)} total entries.")
        
    except Exception as e:
        print("❌ Error:", e)


if __name__ == "__main__":
    main()