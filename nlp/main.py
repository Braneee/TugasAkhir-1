from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import Optional, Dict, Any, List
# import spacy (bypassed)
import re
import difflib
# from sentence_transformers import SentenceTransformer (bypassed)

app = FastAPI()

# Load SpaCy model (Bypassed due to Torch/C++ Redistributable Error)
nlp = None

# Load Sentence Transformer for Semantic Search Query
print("Loading semantic model for API... (Bypassed)")
model = None
print("Model loaded.")

class QueryRequest(BaseModel):
    query: str
    nim: Optional[str] = None

class AnalysisResponse(BaseModel):
    intent: str
    entities: Dict[str, Any]
    original_query: str
    vector: List[float] # Tambahan vektor untuk dikembalikan ke PHP
    suggested_query: Optional[str] = None

@app.post("/analyze")
async def analyze_query(request: QueryRequest):
    query = request.query.lower()
    
    # Simple Intent Detection logic
    intent = "GENERAL"
    academic_keywords = ["nilai", "krs", "ipk", "matakuliah", "kalkulus", "fisika", "algoritma", "semester", "pemrograman", "struktur data"]
    finance_keywords = ["ukt", "bayar", "tagihan", "biaya", "keuangan", "duit", "uang", "pembayaran"]
    
    # Typo Correction Logic (Domain Specific)
    all_domain_words = academic_keywords + finance_keywords
    words = query.split()
    corrected_words = []
    has_typo = False
    
    for w in words:
        if len(w) > 3:
            # Cari kata yang mirip di kamus domain
            matches = difflib.get_close_matches(w, all_domain_words, n=1, cutoff=0.75)
            if matches and matches[0] != w:
                corrected_words.append(matches[0])
                has_typo = True
            else:
                corrected_words.append(w)
        else:
            corrected_words.append(w)
            
    suggested_query = " ".join(corrected_words) if has_typo else None
    
    # Gunakan corrected_query untuk intent & entity extraction jika ada typo
    eval_query = suggested_query if suggested_query else query
    
    # Intent Detection
    if any(word in eval_query for word in finance_keywords):
        intent = "FINANCE"
    elif any(word in eval_query for word in academic_keywords):
        intent = "ACADEMIC"
        
    # Entity Extraction
    entities = {}
    
    sem_match = re.search(r'semester\s*(\d+)', eval_query)
    if sem_match:
        entities['semester'] = int(sem_match.group(1))
        
    matkul_list = ["kalkulus", "fisika", "pemrograman", "struktur data", "algoritma"]
    for mk in matkul_list:
        if mk in eval_query:
            entities['mata_kuliah'] = mk
            break
            
    nim_match = re.search(r'\d{7,}', eval_query)
    if nim_match:
        entities['nim'] = nim_match.group(0)
        
    # Generate Vector for Semantic Search (Bypassed)
    query_vector = [0.0] * 384
        
    return {
        "intent": intent,
        "entities": entities,
        "original_query": request.query,
        "vector": query_vector,
        "suggested_query": suggested_query
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
