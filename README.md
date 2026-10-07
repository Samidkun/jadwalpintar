# 🎓 JadwalPintar — Enterprise SIAKAD & Smart Timetable Solver

> **Sistem Informasi Akademik (SIAKAD) Modern** dilengkapi dengan **Constraint-Based Automatic Timetable Scheduler (MRV Backtracking)** dan **Media-Capable Interactive Mini Quiz Engine**.  
> Dibangun dengan arsitektur modular monolith enterprise: **Laravel 12 + Inertia.js 2 + React 19 + TypeScript + PostgreSQL + TailwindCSS 4**.



<p align="center">
  <img src="docs/screenshots/preview.png" alt="Application Preview" width="100%" style="border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.1);" />
</p>

---

## 🚀 Fitur Unggulan

### 1. 🧠 Smart Timetable Scheduling Engine (MRV Backtrack Solver)
- **Constraint Satisfaction Problem (CSP) Solver:**
  - **Hard Constraints (HC1–HC5):** Menjamin 0% jadwal bentrok antar dosen, ruangan, kapasitas kelas, dan slot waktu.
  - **Soft Constraints (SC1–SC4):** Mengoptimalkan distribusi hari mengajar dosen, preferensi waktu, dan efisiensi ruang kuliah.
  - **Explainability Radar:** Setiap konflik jadwal dijelaskan secara transparan dengan audit popover dan saran pemecahan manual pin.

### 2. 📋 Portal Akademik & KRS Anti-Bentrok
- **Validasi SKS Dinamis:** Batas pengambilan SKS otomatis disesuaikan dengan Indeks Prestasi Semester (IPS) sebelumnya.
- **Deteksi Bentrok Instan:** Memvalidasi jadwal mata kuliah saat pengisian KRS secara real-time sebelum submit.
- **Approval Dosen PA:** Alur persetujuan Kartu Rencana Studi (KRS) 1-klik untuk dosen pembimbing akademik.
- **KHS & Transkrip Kumulatif:** Perhitungan IPK, IPS, dan rekap nilai otomatis dengan grade konversi standar Dikti.

### 3. 📝 Media-Capable Mini Quiz Engine
- **Soal Bergambar & Acak:** Mendukung upload gambar ilustrasi soal, pengacakan urutan soal dan opsi jawaban per mahasiswa.
- **Timer & Anti-Cheat:** Penghitung waktu mundur otomatis per sesi, auto-submit saat timeout, dan deteksi berpindah tab dasar.
- **Auto-Grading & Analisis:** Penilaian instan pasca submission dengan integrasi langsung ke komponen penilaian akademik dosen.

### 4. 🎨 Apple/Craft Design System (Anti-Slop UI)
- Warm neutral canvas `#F5F5F7`, kartu elevated putih dengan hairline borders `rgba(0,0,0,0.07)`, typography tabular mono, dan micro-interactions presisi.

---

## 🛠️ Tech Stack & Arsitektur

- **Backend:** Laravel 12.x (PHP 8.2+)
- **Frontend:** React 19 + Inertia.js 2.x + TypeScript
- **Styling:** Tailwind CSS 4.x
- **Database:** PostgreSQL 16+ / 18
- **Testing:** PHPUnit 11 (20 Feature & Unit Tests, 129 assertions green)

---

## 🏁 Memulai (Local Setup)

### 1. Kloning & Dependensi
```bash
git clone <repo-url> jadwalpintar
cd jadwalpintar

# Install PHP dependencies
composer install

# Install JS dependencies
npm install
```

### 2. Konfigurasi Environment
```bash
cp .env.example .env
php artisan key:generate
```
Sesuaikan konfigurasi database PostgreSQL di file `.env`.

### 3. Migrasi & Seeder Data
```bash
php artisan migrate --seed
```

### 4. Menjalankan Server
```bash
# Terminal 1: Backend
php artisan serve

# Terminal 2: Frontend Vite
npm run dev
```

### 5. Menjalankan Pengujian (Test Suite)
```bash
# Menjalankan PHPUnit
php artisan test

# Typecheck TypeScript
npm run typecheck
```

---

## 📜 Lisensi
MIT License © 2026 Samid & JadwalPintar Contributors.