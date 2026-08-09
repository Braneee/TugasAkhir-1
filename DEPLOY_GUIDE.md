# Panduan Deployment VPS - MVP Search Engine Kampus

Project ini menggunakan arsitektur hybrid (PHP + Python + Elasticsearch).

## 1. Persyaratan Sistem (Requirements)
- **Web Server:** Apache (dengan mod_rewrite aktif untuk .htaccess)
- **PHP:** v8.1 atau lebih tinggi
- **Python:** v3.9 atau lebih tinggi
- **Database:** MySQL / MariaDB
- **Search Engine:** Elasticsearch v8.x (Wajib 8.x untuk dukungan kNN Semantic Search)

## 2. Struktur Folder
- `public/` -> Root folder untuk Web (arahkan DocumentRoot Apache ke sini)
- `nlp/` -> Service NLP (FastAPI + Sentence Transformers)
- `documents/` -> Folder penyimpanan file PDF/DOCX Knowledgebase
- `scripts/` -> Script utility (Indexer & Seeder)
- `sql/` -> File skema database

## 3. Langkah Instalasi VPS

### A. Database (MySQL)
1. Buat database: `CREATE DATABASE campus_search;`
2. Import skema: `mysql -u root -p campus_search < sql/schema.sql`
3. Import data: `mysql -u root -p campus_search < sql/seeder.sql`
4. Sesuaikan kredensial di `public/api/config.php`.

### B. NLP Service (Python)
1. Masuk ke folder nlp: `cd nlp`
2. Install dependencies: `pip install -r requirements.txt`
3. Download model bahasa: `python -m spacy download id_core_news_sm`
   *(Catatan: Download model pertama kali mungkin memakan waktu karena akan mengunduh model Semantic Search HuggingFace sebesar ~400MB)*
4. Jalankan service (Gunakan PM2 atau Systemd):
   `uvicorn main:app --host 0.0.0.0 --port 8000`

### C. Web Server (Apache)
1. Pastikan `AllowOverride All` aktif di konfigurasi virtual host Apache agar `.htaccess` berfungsi.
2. Link login sekarang menjadi: `http://domain-anda.com/login` (tanpa .php).

### D. Knowledgebase (Elasticsearch)
1. Install & Jalankan Elasticsearch.
2. Matikan security di `elasticsearch.yml` (`xpack.security.enabled: false`) untuk kemudahan MVP.
3. Jalankan indexer awal: `python scripts/indexer.py`

## 4. Keamanan Tambahan
- Pastikan Port 8000 (NLP) dan 9200 (Elasticsearch) tidak dibuka untuk umum (Firewall).
- Gunakan SSL (Certbot/Let's Encrypt) untuk akses HTTPS.
