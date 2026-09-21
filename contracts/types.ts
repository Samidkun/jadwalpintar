// contracts/types.ts — Interfaces between backend & frontend for JadwalPintar

export type Role = 'admin' | 'dosen' | 'mahasiswa';
export type Hari = 'senin' | 'selasa' | 'rabu' | 'kamis' | 'jumat';
export type SemesterType = 'ganjil' | 'genap';
export type RuanganType = 'teori' | 'lab' | 'campuran';
export type MatkulType = 'teori' | 'lab';
export type KrsStatus = 'draft' | 'submitted' | 'approved' | 'revision';
export type ScheduleStatus = 'generating' | 'draft' | 'published';
export type QuizStatus = 'draft' | 'published' | 'closed';
export type SoalType = 'pilgan' | 'isian';
export type MahasiswaStatus = 'aktif' | 'cuti' | 'lulus' | 'do';
export type PreferenceStatus = 'preferred' | 'avoid' | 'unavailable';

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
}

export interface Fakultas {
  id: number;
  kode: string;
  nama: string;
}

export interface Prodi {
  id: number;
  kode: string;
  nama: string;
  fakultas_id: number;
  fakultas?: Fakultas;
}

export interface TahunAkademik {
  id: number;
  tahun: string; // "2025/2026"
  semester: SemesterType;
  aktif: boolean;
}

export interface MataKuliah {
  id: number;
  kode: string;
  nama: string;
  sks: number;
  semester: number;
  tipe: MatkulType;
  prodi_id: number;
  prodi?: Prodi;
  prasyarat?: MataKuliah[];
}

export interface Dosen {
  id: number;
  user_id: number;
  nidn: string;
  nama: string;
  bidang: string;
  prodi_id: number;
  prodi?: Prodi;
  time_preferences?: DosenTimePreference[];
}

export interface DosenTimePreference {
  id: number;
  dosen_id: number;
  hari: Hari;
  slot_waktu_id: number;
  status: PreferenceStatus;
}

export interface Ruangan {
  id: number;
  kode: string;
  nama: string;
  kapasitas: number;
  tipe: RuanganType;
}

export interface SlotWaktu {
  id: number;
  jam_mulai: string; // "08:00"
  jam_selesai: string; // "09:40"
  label: string;
}

export interface Kelas {
  id: number;
  mata_kuliah_id: number;
  dosen_id: number;
  tahun_akademik_id: number;
  nama_kelas: string;
  kapasitas: number;
  mata_kuliah?: MataKuliah;
  dosen?: Dosen;
}

export interface Mahasiswa {
  id: number;
  user_id: number;
  nim: string;
  nama: string;
  prodi_id: number;
  semester: number;
  status: MahasiswaStatus;
  dosen_pa_id?: number | null;
  prodi?: Prodi;
  dosen_pa?: Dosen;
}

export interface Krs {
  id: number;
  mahasiswa_id: number;
  tahun_akademik_id: number;
  status: KrsStatus;
  catatan_dosen?: string | null;
  details?: KrsDetail[];
  total_sks?: number;
}

export interface KrsDetail {
  id: number;
  krs_id: number;
  kelas_id: number;
  kelas?: Kelas;
}

export interface Schedule {
  id: number;
  tahun_akademik_id: number;
  status: ScheduleStatus;
  metadata?: {
    total_kelas: number;
    assigned: number;
    conflicts: number;
    solve_time_ms: number;
  };
  items?: ScheduleItem[];
  conflicts?: ScheduleConflict[];
}

export interface ScheduleItem {
  id: number;
  schedule_id: number;
  kelas_id: number;
  ruangan_id: number;
  slot_waktu_id: number;
  hari: Hari;
  is_pinned: boolean;
  explanation?: string | null;
  kelas?: Kelas;
  ruangan?: Ruangan;
  slot_waktu?: SlotWaktu;
}

export interface ScheduleConflict {
  id: number;
  schedule_id: number;
  kelas_id: number;
  type: string;
  description: string;
  kelas?: Kelas;
}

export interface Nilai {
  id: number;
  kelas_id: number;
  mahasiswa_id: number;
  komponen: Record<string, number>; // { tugas: 80, quiz: 85, uts: 75, uas: 80 }
  nilai_akhir: number;
  huruf: string;
  bobot: number;
  mahasiswa?: Mahasiswa;
}

export interface Quiz {
  id: number;
  kelas_id: number;
  judul: string;
  durasi_menit: number | null;
  acak_soal: boolean;
  acak_pilihan: boolean;
  satu_percobaan: boolean;
  status: QuizStatus;
  soal?: Soal[];
}

export interface Soal {
  id: number;
  quiz_id: number;
  teks: string;
  gambar?: string | null; // path to uploaded media
  tipe: SoalType;
  pilihan?: string[] | null;
  jawaban_benar: string;
  poin: number;
  urutan: number;
}

export interface QuizAttempt {
  id: number;
  quiz_id: number;
  mahasiswa_id: number;
  jawaban: Record<number, string>; // soal_id -> answer
  skor: number;
  started_at: string;
  submitted_at: string;
}
