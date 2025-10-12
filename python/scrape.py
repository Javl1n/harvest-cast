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

def get_latest_pdf_link():
    """
    Scrape the DA Price Monitoring page and get the latest 'Daily Price Index' PDF link.
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

    # get first <a> link inside table (latest)
    first_link = table.find("a", href=True)
    if not first_link:
        raise Exception("No PDF links found in table.")

    pdf_url = first_link["href"]
    print(f"Found latest PDF: {pdf_url}")
    return pdf_url

def download_pdf(url):
    """
    Download PDF file and save to a temporary location.
    """
    print("Downloading PDF...")

    # make sure the directory exists
    save_dir = os.path.join(os.path.dirname(__file__), "pdfs")
    os.makedirs(save_dir, exist_ok=True)

    # name file by date or timestamp
    filename = f"daily_price_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
    save_path = os.path.join(save_dir, filename)

    # download
    response = requests.get(url)
    response.raise_for_status()

    with open(save_path, "wb") as f:
        f.write(response.content)

    print(f"Saved PDF to: {save_path}")
    return save_path

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

    print(f"Extracted {len(data)} entries.")
    # print(data)

    return data

def post_to_server(data):
    """
    Send JSON data to your server endpoint.
    """
    print(f"Posting {len(data)} entries to server...")
    SERVER_ENDPOINT = "http://localhost:8000/api/crops/data"
    response = requests.post(SERVER_ENDPOINT, json={"data": data}, headers=LARAVEL_HEADERS)
    print(f"Server response: {response.status_code}")
    try:
        print("Response JSON:", response.json())
    except Exception:
        print("Response text:", response.text)


def main():
    try:
        pdf_link = get_latest_pdf_link()
        pdf_path = download_pdf(pdf_link)
        json_data = parse_pdf_to_json(pdf_path)
        post_to_server(json_data)
        os.remove(pdf_path)
        print("✅ Done successfully.")
    except Exception as e:
        print("❌ Error:", e)


if __name__ == "__main__":
    main()