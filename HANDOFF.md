# HANDOFF.md — Planning → Execution Contract

> Produced by `sop-planning` (P6), consumed by `sop-execution` (E0).
> Frozen at handoff; changes are amendments recorded in §12.

---

## 1. Identity

| Field | Value |
|-------|-------|
| Project | JadwalPintar (SIAKAD + Smart Scheduling + Mini Quiz) |
| Tier | T0 (Personal / Portfolio) |
| Ceremony level | L (Full — new repo, multi-layer, algorithm, DB schema) |
| Spec level | spec-anchored (spec + code in same repo/commit) |
| Team size | solo |
| Stack | Laravel 12 + Inertia.js 2 + React 19 + TypeScript + PostgreSQL 16+ + TailwindCSS 4 |
| Constitution | `/mnt/data/01_Projects/Porto/jadwalpintar/CONSTITUTION.md` |
| Handoff date | 2026-09-21 |
| Frozen by | Samid & Hermes Agent |

---

## 2. Goal & Scope

- **Goal:** Membangun Sistem Informasi Akademik (SIAKAD) modern dengan constraint-based automatic timetable scheduler (MRV Backtracking) dan integrated media-capable mini quiz engine untuk portofolio teknis level enterprise.
- **In scope:**
  1. Master Data Akademik (Fakultas, Prodi, Mata Kuliah, Dosen, Ruangan, Slot Waktu, Tahun Akademik).
  2. Smart Scheduling Engine (MRV Greedy + Backtracking, Hard Constraints HC1-HC5, Soft Constraints SC1-SC4, Explainability popover, manual pin & conflict radar).
  3. Portal Mahasiswa: Pengisian KRS dengan validasi batas SKS berbasis IPS dan anti-bentrok jadwal, Transkrip Akademik Kumulatif & KHS.
  4. Portal Dosen: Verifikasi/Approval KRS bimbingan, Input Nilai Komponen (UTS, UAS, Tugas, Quiz), Pembuatan Mini Quiz dengan dukungan upload gambar.
  5. Mini Quiz Engine: Pengacakan urutan soal & pilihan ganda, timer per soal, proteksi copy-paste dasar, auto-grading instan.
- **Out of scope (deliberate):**
  - LMS kompleks (forum diskusi, video conference, submission tugas file besar).
  - Presensi kehadiran berbasis RFID/QR.
  - Modul keuangan & pembayaran SPP gateway.
  - Pendaftaran Mahasiswa Baru (PMB).
  - Skripsi & Kerja Praktik tracking.
  - Integrasi PDDikti Feeder.
- **Definition of done (T0):**
  - Semua AC-1 s.d. AC-14 lolos verifikasi automated / unit / feature test.
  - Clean linting, typecheck TypeScript lolos tanpa warning.
  - Secret scanner bersih, tidak ada kredensial bocor.
  - Mockup visual ditransplantasikan 1:1 ke komponen React Inertia.

---

## 2b. Boundaries

- **Always:** Validasi semua payload request di FormRequest backend; sanitasi input; pastikan constraint solver deterministik dan tidak looping tak terhingga (max backtrack depth limit).
- **Ask first:** Penambahan library solver pihak ketiga (Z3/CP-SAT/Python binding) atau modifikasi drastis skema database di luar ERD.
- **Never:** Menulis password plain text; melakukan hardcode token rahasia di source code; membiarkan solver menghasilkan jadwal yang melanggar Hard Constraint (HC1-HC5).

---

## 2c. Capability Map

| Module id | Responsibility | Depends on |
|-----------|----------------|------------|
| `master-data` | Fakultas, Prodi, MK, Dosen, Ruangan, Slot Waktu | — |
| `scheduling-engine` | Constraint solver (MRV), alokasi jadwal, deteksi bentrok | `master-data` |
| `krs-portal` | Pengisian KRS mahasiswa, validasi SKS & prasyarat, approval dosen PA | `master-data`, `scheduling-engine` |
| `grading-transkrip`| Input nilai dosen, kalkulasi nilai akhir/huruf/bobot, KHS, transkrip | `krs-portal` |
| `mini-quiz` | Pembuatan quiz (teks + gambar), pengerjaan acak + timer, auto-grading | `master-data` |

Build order: `master-data` → `scheduling-engine` → `krs-portal` → `grading-transkrip` → `mini-quiz`.

---

## 3. Prompt-Roast Result

- Score: 9/12 (Goal: 2, Context: 2, Scope: 2, Success: 1, Constraints: 1, Ambiguity: 1).
- Gaps addressed:
  - Algoritma scheduling: ditetapkan pure PHP (Greedy + MRV Backtracking, batasan runtime < 5 detik untuk 500 kelas).
  - Gambar pada mini quiz: diakomodasi via upload disk lokal (max 2MB, resize client/server).
  - Anti-cheat quiz: speed-bump model (acak soal + pilihan, timer per-soal, disabled copy).
- False premise checked: Bersih (project baru dari scratch di bawah `/mnt/data/01_Projects/Porto/jadwalpintar`).
- **Clarify gate (P1.5):** PASSED.

---

## 4. Design Contract (UI)

- **`DESIGN.md` path:** `/mnt/data/01_Projects/Porto/jadwalpintar/DESIGN.md`
- **Visual Anchor:** Apple macOS / Craft Pro Application (subtle warm-neutral canvas `#F5F5F7`, elevated pure white cards, continuous curvature `rounded-2xl`, hairline borders `rgba(0,0,0,0.07)`, segmented pill controls, SF-style typography).
- **Archetype:** Workspace (Scheduler) + Dense Ledger (Nilai/Transkrip) + Guided Flow (KRS & Quiz).
- **Visual Mockup Spike (P2.1):** `/mnt/data/01_Projects/Porto/jadwalpintar/preview/mockup.html`
- **Signature Bet:** Interactive Timetable Workspace dengan Explainability Inspector Sheet (admin tahu *mengapa* sistem menempatkan matkul tersebut di slot tertentu).
- **Anti-Slop Compliance:** No AI-purple gradients, SF-style Lucide icons, sentence case, no bounce hover, tactile segmented controls.

---

## 5. Acceptance Criteria (EARS, Binary-Testable)

| Id | Pattern | EARS Statement | Verify |
|---|---|---|---|
| **AC-1** | event | WHEN admin menjalankan solver penjadwalan, THE system SHALL mengalokasikan kelas ke slot & ruangan tanpa melanggar Hard Constraints (HC1-HC5). | Feature Test: Test solver dengan 30 kelas dummy menghasilkan 0 room & dosen overlap. |
| **AC-2** | unwanted | IF terdapat kelas yang tidak menemukan slot bebas bentrok, THEN THE system SHALL mencatatnya pada `schedule_conflicts` dan menampilkan radar bentrok ke admin. | Unit Test: Injeksi kasus over-capacity/over-constraint, assert conflict record tercipta. |
| **AC-3** | state | WHILE kelas memiliki flag `is_pinned = true`, THE solver SHALL mempertahankan ruangan dan slot waktu tersebut tanpa memindahkannya. | Unit Test: Pin satu kelas, jalankan solver, assert slot tetap sama. |
| **AC-4** | event | WHEN mahasiswa memilih matkul pada periode KRS, THE system SHALL memvalidasi total SKS tidak melebihi batas SKS berdasarkan IPS semester sebelumnya. | Feature Test: Coba ambil 24 SKS saat batas IPS hanya mengizinkan 20 SKS, assert validasi 422. |
| **AC-5** | unwanted | IF mahasiswa memilih dua kelas dengan jadwal waktu yang beririsan, THEN THE system SHALL menolak pengajuan dan menampilkan pesan bentrok jadwal. | Feature Test: Submit 2 kelas di slot yang sama, assert error bentrok. |
| **AC-6** | event | WHEN mahasiswa belum lulus mata kuliah prasyarat, THE system SHALL melarang pemilihan mata kuliah lanjutan tersebut pada formulir KRS. | Feature Test: Submit matkul prasyarat belum diambil, assert rejection. |
| **AC-7** | event | WHEN dosen Pembimbing Akademik menyetujui (approve) KRS mahasiswa, THE status KRS SHALL berubah menjadi `approved` dan kelas resmi terdaftar. | Feature Test: Dosen approve KRS, assert status update & record terdaftar. |
| **AC-8** | event | WHEN dosen menginput komponen nilai mahasiswa (tugas, quiz, uts, uas), THE system SHALL menghitung nilai akhir, huruf mutu (A-E), dan bobot (0.00-4.00) secara otomatis. | Unit Test: Injeksi angka 85, assert huruf 'A' dan bobot 4.00. |
| **AC-9** | event | WHEN dosen membuat soal quiz tipe pilihan ganda, THE system SHALL mengizinkan lampiran 1 gambar (maks 2MB) dan menyimpan path gambar di database. | Feature Test: Upload fixture image di endpoint store soal, assert storage link valid. |
| **AC-10** | state | WHILE mahasiswa sedang mengerjakan quiz, THE system SHALL menyajikan soal dalam urutan teracak dan pilihan opsi teracak per mahasiswa. | Unit Test: 2 attempt dari 2 mahasiswa berbeda menghasilkan urutan soal yang berbeda. |
| **AC-11** | event | WHEN waktu timer per soal habis (timeout), THE system SHALL secara otomatis merekam respon saat itu dan melanjutkan ke nomor berikutnya. | E2E / Component Test: Simulasi countdown 0, assert trigger auto-advance. |
| **AC-12** | unwanted | IF mahasiswa mencoba melakukan attempt kedua pada quiz bertipe satu percobaan, THEN THE system SHALL menolak akses ke soal. | Feature Test: Ambil quiz yang statusnya sudah submitted, assert 403 / redirect. |
| **AC-13** | event | WHEN mahasiswa menyelesaikan quiz pilihan ganda, THE system SHALL menghitung skor secara otomatis berdasarkan kunci jawaban. | Unit Test: Jawab 4 dari 5 soal benar @20 poin, assert skor = 80. |
| **AC-14** | event | WHEN admin mengeklik blok jadwal pada timetable grid, THE system SHALL menampilkan popover penjelasan algoritma (alasan penempatan dosen, kapasitas, preferensi). | Component Test: Click card, assert modal/popover explanation tampil. |

---

## 5b. Drift Contract

- Spec path: `/mnt/data/01_Projects/Porto/jadwalpintar/HANDOFF.md`
- Code changes harus selalu sinkron dengan EARS criteria AC-1 s.d. AC-14. Perubahan logika tanpa update HANDOFF.md dianggap drift defect.

---

## 6. Feature Completeness (Decided Exclusions)

- Excluded: Multi-tenant SaaS (fokus single-institution clean deployment).
- Excluded: Live proctoring / webcam surveillance (speed-bump anti-cheat model sudah cukup untuk T0).
- Excluded: Payment gateway & SPP invoicing.
- Security: Role-based authorization via Spatie Permission + strict form request validation + secure file upload mime-type whitelist.

---

## 7. Interfaces & File Paths

- Contracts: `/mnt/data/01_Projects/Porto/jadwalpintar/contracts/types.ts`
- Diagrams: `/mnt/data/01_Projects/Porto/jadwalpintar/docs/diagrams/all-diagrams.md`
- Architecture: `/mnt/data/01_Projects/Porto/jadwalpintar/docs/architecture.md`
- Mockup: `/mnt/data/01_Projects/Porto/jadwalpintar/preview/mockup.html`

---

## 7b. Interface Contracts

- **Layers & Responsibilities:**
  - Frontend: React 19 + Inertia 2 client components, consuming typed page props.
  - Backend: Laravel 12 controllers and FormRequests, orchestrating domain services.
  - Database: PostgreSQL 16+ relational schema with foreign keys and strict constraints.
- **Boundary Contract(s):**
  - Inertia server-side render returning typed props defined in `/mnt/data/01_Projects/Porto/jadwalpintar/contracts/types.ts`.
  - Form endpoints: POST `/admin/schedules/generate`, POST `/mahasiswa/krs`, POST `/dosen/nilai`, POST `/dosen/quizzes`.
- **Shared Types:** All TypeScript interfaces mirrored in `contracts/types.ts`.
- **Error Envelope:** Laravel FormRequest validation bag (HTTP 422) + Inertia flash messages (`success`, `error`).
- **Auth:** Laravel Breeze session cookie + Spatie Permission (roles: `admin`, `dosen`, `mahasiswa`).
- **Enforcement:** TypeScript build-time typecheck (`tsc --noEmit`) and PHPUnit feature tests.

---

## 8. Constraints (Verbatim)

- PHP: ^8.2 (CachyOS Host PHP 8.4 / Laravel 12 ready).
- Frontend: Inertia.js 2 + React 19 + TypeScript + TailwindCSS.
- Database: PostgreSQL 16+.
- Pure PHP Constraint Solver (No external binary like Z3/Python solver needed).

---

## 9. Plan Artifact

- Multi-task execution with clear phase order:
  - Task 1: Scaffolding Factory & Migrations + Seeders Master Data.
  - Task 2: Pure PHP MRV Constraint Scheduling Engine + Unit Tests.
  - Task 3: Interactive Timetable Grid & Solver Management (Admin).
  - Task 4: Portal Mahasiswa (KRS, SKS Validation, Schedule View, Transkrip).
  - Task 5: Portal Dosen (KRS Approval, Grade Entry, Quiz Creator with Image).
  - Task 6: Student Mini Quiz Runner (Timer, Shuffle, Auto-grade).

---

## 10. Factory Bootstrap

- Directory: `/mnt/data/01_Projects/Porto/jadwalpintar`
- Script: `bash /home/samid/.hermes/skills/software-development/project-bootstrap/scripts/bootstrap.sh /mnt/data/01_Projects/Porto/jadwalpintar t0`
- Pending: Dieksekusi saat inisiasi project di Phase 2.

---

## 11. Accepted Risks

| Risk | Why Accepted | Owner |
|---|---|---|
| Solver execution timeout jika >1000 kelas | Kapasitas kampus target adalah skala menengah (100-400 kelas); solver dibatasi max iteration 5000 | Samid |
| Client-side copy-paste anti-cheat bypassable | Level T0 tidak mewajibkan secure lockdown browser; speed-bump timer sudah efektif membatasi waktu curang | Samid |

---

## 12. Amendments (Changelog)

| Date | Change | Reason | Re-approved by |
|---|---|---|---|
| 2026-09-21 | Added image upload to Soal | Memfasilitasi soal diagram/grafik matematika & sains | Samid |
| 2026-09-21 | Added speed-bump anti-cheat | Mengurangi potensi mahasiswa copas soal ke AI | Samid |

---

## 13. P7 Readiness Gate (Audit Checklist)

- [x] Tier + team + ceremony level written (T0, Solo, L-Ceremony)
- [x] Spec level declared (spec-anchored)
- [x] Constitution present (`CONSTITUTION.md`)
- [x] Boundaries recorded (Always / Ask first / Never)
- [x] Capability map approved
- [x] Prompt-roast scored (9/12) & gaps answered
- [x] False premise checked against repo (clean)
- [x] Assumptions surfaced & locked
- [x] Clarify gate passed (P1.5)
- [x] Design contract present (`DESIGN.md`)
- [x] Standalone Mockup generated (`preview/mockup.html`) with 5-state toggle
- [x] Acceptance criteria EARS-shaped with Verify instructions (AC-1 s.d. AC-14)
- [x] Diagrams as code complete (ERD, Flow, Sequence, Architecture)
- [x] No TBD/TODO anywhere
- [x] Accepted risks documented

**FROZEN:** [x]  ·  **Handoff Path:** `/mnt/data/01_Projects/Porto/jadwalpintar/HANDOFF.md`
