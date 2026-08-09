# 🏗️ Arsitektur & Infrastruktur CUAN Search Engine (MVP)

Dokumen ini disusun khusus sebagai referensi untuk membuat **Gambar Arsitektur / Topologi Infrastruktur** dari sistem CUAN Search Engine.

---

## 1. Komponen Utama Sistem (The Nodes)

Sistem ini menggunakan pendekatan **Microservices/Hybrid Architecture** yang terdiri dari 4 komponen utama:

### A. Web Frontend & Orchestrator (Aplikasi Utama)
- **Teknologi:** PHP Native (PHP 8.x) + Bootstrap 5 (UI)
- **Port Default:** 80 / 443 (atau 8080 untuk local dev)
- **Peran:**
  - Menerima request pencarian dari User (Mahasiswa/Admin/Guest).
  - Menangani Autentikasi & Session (Login/Logout).
  - Berfungsi sebagai **Orchestrator** (penghubung) yang mengatur komunikasi ke NLP Service, MySQL, dan Elasticsearch.
  - Merender hasil akhir (HTML) ke browser.

### B. The Brain / NLP Service (Mesin Cerdas)
- **Teknologi:** Python 3.x + FastAPI
- **Library Utama:** `spaCy` (NLP), `SentenceTransformers` (Vektor Model), `difflib` (Spell-Checker)
- **Port Default:** 8000
- **Endpoint Utama:** `POST /analyze`
- **Peran:**
  - Menerima teks pertanyaan dari PHP.
  - Melakukan **Auto-Correction / Spell Checking** (memperbaiki typo).
  - **Intent Detection**: Menentukan apakah user bertanya soal Akademik, Keuangan, atau Umum.
  - **Entity Extraction**: Mengambil data spesifik (misal: "Kalkulus", "Semester 2").
  - **Vector Embedding**: Mengubah teks menjadi angka (vektor 384 dimensi) untuk *Semantic Search*.

### C. Structured Database (Data Privat & Relasional)
- **Teknologi:** MySQL / MariaDB (via XAMPP/VPS)
- **Port Default:** 3306
- **Peran:**
  - Menyimpan data terstruktur: Users (Mahasiswa/Admin), Nilai Akademik, Tagihan Keuangan (UKT), dan Log Riwayat Pencarian.
  - Diakses langsung oleh PHP menggunakan PDO.

### D. Unstructured Database / Vector Engine (Knowledgebase)
- **Teknologi:** Elasticsearch 8.x
- **Port Default:** 9200
- **Index:** `campus_kb` (dengan mapping `dense_vector`)
- **Peran:**
  - Menyimpan isi dokumen panduan kampus (PDF, DOCX).
  - Menjalankan **Hybrid Search**: Kombinasi *Fuzzy Search* (pencocokan kata/typo) dan *kNN Semantic Search* (pencocokan makna/vektor).
  - Diakses oleh PHP via cURL REST API.

---

## 2. Alur Komunikasi & Data Flow (The Arrows)

Saat menggambar diagram, lo bisa bikin tanda panah (alur data) berdasarkan skenario berikut:

### 🔄 A. Skenario Pencarian User (Search Flow)
1. **User (Browser)** mengirim keyword pencarian ke **PHP (Web Server)**.
2. **PHP** melakukan *HTTP POST (JSON)* ke **Python NLP Service** (Port 8000).
3. **Python NLP** mengembalikan respon (Intent, Entities, Vektor, Typo Correction) ke **PHP**.
4. Berdasarkan Intent:
   - Jika intent = `ACADEMIC` / `FINANCE`: **PHP** melakukan query SQL (`SELECT`) ke **MySQL** (Port 3306).
5. Secara paralel, **PHP** juga melakukan *HTTP POST (JSON)* ke **Elasticsearch** (Port 9200) dengan mengirimkan kata kunci + Vektor untuk mencari dokumen Knowledgebase.
6. **Elasticsearch** mengembalikan hasil dokumen (Hits & Similarity Score) ke **PHP**.
7. **PHP** menggabungkan data dari MySQL dan Elasticsearch, lalu mengirimkan tampilan HTML ke **User (Browser)**.

### 🔄 B. Skenario Indexing Dokumen (Sync Flow)
1. **Admin** menekan tombol "Sync Knowledgebase" di Dashboard PHP (atau via Terminal).
2. Menjalankan script **Python Indexer (`scripts/indexer.py`)**.
3. Script membaca file mentah (PDF/DOCX) dari folder `/documents/`.
4. Script memanggil Model AI (SentenceTransformers) untuk membuat Vektor Teks.
5. Script melakukan *HTTP POST/PUT* langsung ke **Elasticsearch** (Port 9200) untuk memasukkan dokumen ke dalam index `campus_kb`.

---

## 3. Protokol Keamanan & Jaringan
- Komunikasi antar internal server (PHP ↔ Python ↔ Elasticsearch ↔ MySQL) dilakukan via **Localhost (127.0.0.1)** menggunakan HTTP biasa (REST) tanpa otentikasi rumit (karena berada di environment VPS yang sama).
- Akses eksternal dari Internet HANYA dibuka untuk Web Server PHP (Port 80/443). Port 8000, 9200, dan 3306 **ditutup dari publik** (di-block oleh Firewall/UFW).

---

## 4. Tips Desain Diagram
Buat bikin gambar infrastrukturnya makin keren, lo bisa pake susunan layout ini:

- **Layer 1 (Client):** Icon User / Browser / Laptop.
- **Layer 2 (Gateway/Frontend):** Kotak "PHP Web Server (Port 80)".
- **Layer 3 (Backend Services):** 
  - Kotak "Python NLP API (FastAPI - Port 8000)" (Garis panah bolak-balik dari PHP).
- **Layer 4 (Databases):** 
  - Kotak "MySQL DB (Port 3306)" (Garis panah bolak-balik dari PHP).
  - Kotak "Elasticsearch Vector DB (Port 9200)" (Garis panah bolak-balik dari PHP & dari Python Indexer).
- **Storage:** Icon Folder `/documents/` yang terhubung ke Python Indexer.

Semoga dokumentasi ini bikin gampang pas nggambar diagramnya! 🚀