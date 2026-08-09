import os
import fitz # PyMuPDF
import docx
from elasticsearch import Elasticsearch
from sentence_transformers import SentenceTransformer

# Connection setup
es = Elasticsearch(["http://localhost:9200"])
INDEX_NAME = "campus_kb"

# Load Sentence Transformer Model (Multilingual for Indonesian support)
print("Loading semantic model (this might take a while the first time)...")
model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')
print("Model loaded successfully.")

def extract_text_from_pdf(pdf_path):
    try:
        text = ""
        with fitz.open(pdf_path) as doc:
            for page in doc:
                text += page.get_text()
        return text
    except Exception as e:
        print(f"Error reading PDF {pdf_path}: {e}")
        return ""

def extract_text_from_docx(docx_path):
    try:
        doc = docx.Document(docx_path)
        text = "\n".join([para.text for para in doc.paragraphs])
        return text
    except Exception as e:
        print(f"Error reading DOCX {docx_path}: {e}")
        return ""

def index_all_documents(folder_path):
    # Create index with dense_vector mapping if it doesn't exist
    try:
        if not es.indices.exists(index=INDEX_NAME):
            mapping = {
                "mappings": {
                    "properties": {
                        "filename": {"type": "text"},
                        "content": {"type": "text"},
                        "url": {"type": "keyword"},
                        "source_type": {"type": "keyword"}, # 'file' atau 'web'
                        "content_vector": {
                            "type": "dense_vector",
                            "dims": 384,
                            "index": True,
                            "similarity": "cosine"
                        }
                    }
                }
            }
            es.indices.create(index=INDEX_NAME, body=mapping)
            print(f"Created index: {INDEX_NAME} with vector & metadata support")
    except Exception as e:
        print(f"Error checking/creating index: {e}")
        return

    # Scan folder
    if not os.path.exists(folder_path):
        print(f"Folder not found: {folder_path}")
        return

    for filename in os.listdir(folder_path):
        file_path = os.path.join(folder_path, filename)
        text = ""
        
        if filename.endswith(".pdf"):
            text = extract_text_from_pdf(file_path)
        elif filename.endswith(".docx"):
            text = extract_text_from_docx(file_path)
            
        if text:
            print(f"Generating vector for {filename}...")
            # Generate vector embedding for the content
            vector = model.encode(text).tolist()
            
            doc_body = {
                "filename": filename,
                "content": text,
                "content_vector": vector,
                "source_type": "file"
            }
            try:
                # Use filename as ID to overwrite if exists
                es.index(index=INDEX_NAME, id=filename, document=doc_body)
                print(f"Successfully indexed: {filename}")
            except Exception as e:
                print(f"Error indexing {filename}: {e}")

if __name__ == "__main__":
    # Path relative to script execution location
    DOC_FOLDER = os.path.join(os.path.dirname(__file__), "..", "documents")
    index_all_documents(DOC_FOLDER)
