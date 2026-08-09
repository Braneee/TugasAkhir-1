import mysql.connector
from elasticsearch import Elasticsearch
from sentence_transformers import SentenceTransformer
import requests
from bs4 import BeautifulSoup
import time

# Configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'campus_search'
}
ES_URL = "http://localhost:9200"
INDEX_NAME = "campus_kb"

# Initialize
es = Elasticsearch([ES_URL])
model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')

def get_active_web_sources():
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM web_sources WHERE status = 'active'")
    sources = cursor.fetchall()
    cursor.close()
    conn.close()
    return sources

def crawl_and_index(source):
    print(f"Crawling: {source['site_name']} ({source['url']})...")
    try:
        response = requests.get(source['url'], timeout=10)
        if response.status_code == 200:
            soup = BeautifulSoup(response.text, 'html.parser')
            
            # Basic text extraction (we can refine this later)
            # Remove scripts and styles
            for script in soup(["script", "style"]):
                script.decompose()
            
            text = soup.get_text(separator=' ', strip=True)
            
            # Clean text (take first 5000 chars for now as a sample)
            clean_text = text[:5000] 
            
            if clean_text:
                print(f"Generating vector for {source['site_name']}...")
                vector = model.encode(clean_text).tolist()
                
                doc_body = {
                    "filename": source['site_name'],
                    "url": source['url'],
                    "content": clean_text,
                    "content_vector": vector,
                    "source_type": "web"
                }
                
                # Index to ES
                es.index(index=INDEX_NAME, id=f"web_{source['id']}", document=doc_body)
                print(f"Successfully indexed web source: {source['site_name']}")
                
                # Update last_sync in DB
                update_last_sync(source['id'])
    except Exception as e:
        print(f"Error crawling {source['url']}: {e}")

def update_last_sync(source_id):
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()
    cursor.execute("UPDATE web_sources SET last_sync = CURRENT_TIMESTAMP WHERE id = %s", (source_id,))
    conn.commit()
    cursor.close()
    conn.close()

if __name__ == "__main__":
    sources = get_active_web_sources()
    if not sources:
        print("No active web sources found.")
    else:
        for source in sources:
            crawl_and_index(source)
            time.sleep(2) # Be polite
