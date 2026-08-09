    # Setup Script for MVP Search Engine Kampus

Write-Host "--- 1. Database Setup ---" -ForegroundColor Cyan
Write-Host "Pastikan MySQL (XAMPP) sudah menyala!" -ForegroundColor Yellow
# mysql -u root -e "source sql/schema.sql; source sql/seeder.sql;"
Write-Host "Silakan jalankan sql/schema.sql dan sql/seeder.sql di phpMyAdmin atau terminal MySQL Anda." -ForegroundColor Green

Write-Host "`n--- 2. Python Environment Setup ---" -ForegroundColor Cyan
pip install -r nlp/requirements.txt
python -m spacy download id_core_news_sm

Write-Host "`n--- 3. Folder Setup ---" -ForegroundColor Cyan
# Pastikan folder documents ada
if (!(Test-Path -Path "documents")) {
    New-Item -ItemType Directory -Path "documents"
}
Write-Host "Siap! Silakan masukkan file PDF/DOCX Anda ke folder /documents/" -ForegroundColor Green

Write-Host "`n--- 4. Final Steps ---" -ForegroundColor Cyan
Write-Host "1. Nyalakan XAMPP (Apache & MySQL)" -ForegroundColor White
Write-Host "2. Nyalakan Elasticsearch (Port 9200)" -ForegroundColor White
Write-Host "3. Jalankan NLP Service: cd nlp; python main.py" -ForegroundColor White
Write-Host "4. Buka di browser: http://localhost/search-engine/public/login.php" -ForegroundColor White

Write-Host "`nSetup MVP Selesai! GAS BRO! 🚀" -ForegroundColor Magenta
