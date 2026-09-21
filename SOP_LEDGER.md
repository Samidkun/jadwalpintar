# SOP Execution Ledger — JadwalPintar

Status: Phase 2 In Progress
Tier: T0 (Personal / Portfolio)
Ceremony: L (Full)
Drift Contract: Spec-Anchored (`HANDOFF.md`)

## Acceptance Criteria Tracker

- [x] **AC-1**: WHEN admin menjalankan solver penjadwalan, THE system SHALL mengalokasikan kelas ke slot & ruangan tanpa melanggar Hard Constraints (HC1-HC5).
  - Verify: Feature Test: Test solver dengan 30 kelas dummy menghasilkan 0 room & dosen overlap.
  - Status: VERIFIED (tests/Feature/SchedulingIntegrationTest.php, 70 assertions, 0 collisions)

- [x] **AC-2**: IF terdapat kelas yang tidak menemukan slot bebas bentrok, THEN THE system SHALL mencatatnya pada `schedule_conflicts` dan menampilkan radar bentrok ke admin.
  - Verify: Unit Test: Injeksi kasus over-capacity/over-constraint, assert conflict record tercipta.
  - Status: VERIFIED (tests/Unit/SchedulingEngineTest.php::test_unsatisfiable_constraints_record_conflicts_ac2)

- [x] **AC-3**: WHILE kelas memiliki flag `is_pinned = true`, THE solver SHALL mempertahankan ruangan dan slot waktu tersebut tanpa memindahkannya.
  - Verify: Unit Test: Pin satu kelas, jalankan solver, assert slot tetap sama.
  - Status: VERIFIED (tests/Unit/SchedulingEngineTest.php::test_pinned_class_remains_unmoved_ac3)

- [x] **AC-4**: WHEN mahasiswa memilih matkul pada periode KRS, THE system SHALL memvalidasi total SKS tidak melebihi batas SKS berdasarkan IPS semester sebelumnya.
  - Verify: Feature Test: Coba ambil 24 SKS saat batas IPS hanya mengizinkan 20 SKS, assert validasi 422.
  - Status: VERIFIED (tests/Feature/KrsPortalTest.php::test_rejects_krs_exceeding_max_sks_limit_ac4)

- [x] **AC-5**: IF mahasiswa memilih dua kelas dengan jadwal waktu yang beririsan, THEN THE system SHALL menolak pengajuan dan menampilkan pesan bentrok jadwal.
  - Verify: Feature Test: Submit 2 kelas di slot yang sama, assert error bentrok.
  - Status: VERIFIED (tests/Feature/KrsPortalTest.php::test_rejects_krs_with_schedule_time_collision_ac5)

- [x] **AC-6**: WHEN mahasiswa belum lulus mata kuliah prasyarat, THE system SHALL melarang pemilihan mata kuliah lanjutan tersebut pada formulir KRS.
  - Verify: Feature Test: Submit matkul prasyarat belum diambil, assert rejection.
  - Status: VERIFIED (tests/Feature/KrsPortalTest.php::test_rejects_krs_when_prerequisite_not_passed_ac6)

- [x] **AC-7**: WHEN dosen Pembimbing Akademik menyetujui (approve) KRS mahasiswa, THE status KRS SHALL berubah menjadi `approved` dan kelas resmi terdaftar.
  - Verify: Feature Test: Dosen approve KRS, assert status update & record terdaftar.
  - Status: VERIFIED (tests/Feature/KrsPortalTest.php::test_dosen_pa_can_approve_krs_ac7)

- [ ] **AC-8**: WHEN dosen menginput komponen nilai mahasiswa (tugas, quiz, uts, uas), THE system SHALL menghitung nilai akhir, huruf mutu (A-E), dan bobot (0.00-4.00) secara otomatis.
  - Verify: Unit Test: Injeksi angka 85, assert huruf 'A' dan bobot 4.00.
  - Status: PENDING

- [ ] **AC-9**: WHEN dosen membuat soal quiz tipe pilihan ganda, THE system SHALL mengizinkan lampiran 1 gambar (maks 2MB) dan menyimpan path gambar di database.
  - Verify: Feature Test: Upload fixture image di endpoint store soal, assert storage link valid.
  - Status: PENDING

- [ ] **AC-10**: WHILE mahasiswa sedang mengerjakan quiz, THE system SHALL menyajikan soal dalam urutan teracak dan pilihan opsi teracak per mahasiswa.
  - Verify: Unit Test: 2 attempt dari 2 mahasiswa berbeda menghasilkan urutan soal yang berbeda.
  - Status: PENDING

- [ ] **AC-11**: WHEN waktu timer per soal habis (timeout), THE system SHALL secara otomatis merekam respon saat itu dan melanjutkan ke nomor berikutnya.
  - Verify: E2E / Component Test: Simulasi countdown 0, assert trigger auto-advance.
  - Status: PENDING

- [ ] **AC-12**: IF mahasiswa mencoba melakukan attempt kedua pada quiz bertipe satu percobaan, THEN THE system SHALL menolak akses ke soal.
  - Verify: Feature Test: Ambil quiz yang statusnya sudah submitted, assert 403 / redirect.
  - Status: PENDING

- [ ] **AC-13**: WHEN mahasiswa menyelesaikan quiz pilihan ganda, THE system SHALL menghitung skor secara otomatis berdasarkan kunci jawaban.
  - Verify: Unit Test: Jawab 4 dari 5 soal benar @20 poin, assert skor = 80.
  - Status: PENDING

- [x] **AC-14**: WHEN admin mengeklik blok jadwal pada timetable grid, THE system SHALL menampilkan popover penjelasan algoritma (alasan penempatan dosen, kapasitas, preferensi).
  - Verify: Component Test: Click card, assert modal/popover explanation tampil.
  - Status: VERIFIED (resources/js/Pages/Admin/ScheduleWorkspace.tsx, Audit Trail inspector sheet, tested in ScheduleControllerTest)
