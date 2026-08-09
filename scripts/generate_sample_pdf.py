from fpdf import FPDF
import os

def generate_sample_pdf(output_path):
    pdf = FPDF()
    pdf.add_page()
    
    # Title
    pdf.set_font("Helvetica", "B", 16)
    pdf.cell(0, 10, "Panduan Akademik Mahasiswa 2024", new_x="LMARGIN", new_y="NEXT", align="C")
    pdf.ln(10)
    
    # Section 1: UKT
    pdf.set_font("Helvetica", "B", 12)
    pdf.cell(0, 10, "1. Prosedur Pembayaran UKT", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("Helvetica", "", 10)
    pdf.multi_cell(0, 8, "Pembayaran UKT dilakukan setiap awal semester. Mahasiswa dapat membayar melalui Bank Mandiri, BNI, atau BRI dengan menggunakan Virtual Account yang tertera di portal masing-masing. Batas akhir pembayaran semester ganjil adalah 31 Agustus dan semester genap adalah 31 Januari.")
    pdf.ln(5)
    
    # Section 2: Graduation
    pdf.set_font("Helvetica", "B", 12)
    pdf.cell(0, 10, "2. Syarat Wisuda", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("Helvetica", "", 10)
    pdf.multi_cell(0, 8, "Untuk mengikuti wisuda, mahasiswa harus menyelesaikan minimal 144 SKS, memiliki skor TOEFL minimal 450, dan telah mengunggah skripsi ke repositori kampus. Pendaftaran wisuda dilakukan melalui website resmi biro akademik.")
    pdf.ln(5)

    # Section 3: Library
    pdf.set_font("Helvetica", "B", 12)
    pdf.cell(0, 10, "3. Peminjaman Buku Perpustakaan", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("Helvetica", "", 10)
    pdf.multi_cell(0, 8, "Mahasiswa dapat meminjam maksimal 3 buku dalam jangka waktu 2 minggu. Denda keterlambatan adalah Rp 1.000 per hari per buku.")
    
    # Save the PDF
    pdf.output(output_path)
    print(f"Successfully generated: {output_path}")

if __name__ == "__main__":
    doc_dir = os.path.join(os.path.dirname(__file__), "..", "documents")
    if not os.path.exists(doc_dir):
        os.makedirs(doc_dir)
    
    output_file = os.path.join(doc_dir, "Panduan_Akademik_2024.pdf")
    generate_sample_pdf(output_file)
