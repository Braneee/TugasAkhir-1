USE campus_search;

-- Clear tables first
DELETE FROM finance_data;
DELETE FROM academic_data;
DELETE FROM users;

-- Add Admin (Password: admin123)
INSERT INTO users (username, password, role, name, nim) 
VALUES ('admin', '$2y$10$LcwAZ9YUg9hsJTRGXafS/.zYczCAs1gjwZqGFcRqNsQwmtQt5p6cK', 'admin', 'Super Admin', NULL);

-- Add 20 Students (Password: password123)
INSERT INTO users (username, password, role, name, nim) VALUES 
('2024001', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Budi Santoso', '2024001'),
('2024002', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Siti Aminah', '2024002'),
('2024003', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Andi Wijaya', '2024003'),
('2024004', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Dewi Lestari', '2024004'),
('2024005', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Eko Prasetyo', '2024005'),
('2024006', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Fitriani', '2024006'),
('2024007', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Guntur Wibowo', '2024007'),
('2024008', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Hana Pertiwi', '2024008'),
('2024009', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Irfan Hakim', '2024009'),
('2024010', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Joko Susilo', '2024010'),
('2024011', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Kartika Sari', '2024011'),
('2024012', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Lukman Hakim', '2024012'),
('2024013', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Maya Indah', '2024013'),
('2024014', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Nanda Saputra', '2024014'),
('2024015', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Oki Setiawan', '2024015'),
('2024016', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Putri Utami', '2024016'),
('2024017', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Qori Ramadhan', '2024017'),
('2024018', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Rina Marlina', '2024018'),
('2024019', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Samsul Bahri', '2024019'),
('2024020', '$2y$10$NA8hTo1nCjs2eKGgs.J6KOvCDep.YgYTDdNZtErSRvsgLRQs4Bc/W', 'student', 'Tia Amelia', '2024020');

-- Add Sample Academic Data (NIM 2024001)
INSERT INTO academic_data (nim, mata_kuliah, nilai, semester) VALUES 
('2024001', 'Kalkulus I', 'A', 1),
('2024001', 'Fisika Dasar', 'B', 1),
('2024001', 'Pemrograman Dasar', 'A', 1),
('2024001', 'Kalkulus II', 'B+', 2),
('2024001', 'Struktur Data', 'A', 2);

-- Add Sample Finance Data (NIM 2024001)
INSERT INTO finance_data (nim, semester, bill, status) VALUES 
('2024001', 1, 5000000, 'lunas'),
('2024001', 2, 5000000, 'belum_lunas');

-- Add more sample data for other students to make it dynamic
INSERT INTO academic_data (nim, mata_kuliah, nilai, semester) VALUES 
('2024002', 'Kalkulus I', 'B', 1),
('2024002', 'Fisika Dasar', 'A', 1),
('2024003', 'Kalkulus I', 'C', 1);

INSERT INTO finance_data (nim, semester, bill, status) VALUES 
('2024002', 1, 5000000, 'lunas'),
('2024003', 1, 5000000, 'belum_lunas');
