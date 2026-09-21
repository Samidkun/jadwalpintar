# System Architecture — JadwalPintar

## Layer Map

```
┌─────────────────────────────────────────────────┐
│                   Browser                        │
│   React 19 + TypeScript + Inertia.js Client      │
├─────────────────────────────────────────────────┤
│                 Inertia.js 2                     │
│          (server-driven SPA protocol)            │
├─────────────────────────────────────────────────┤
│              Laravel (latest)                    │
│  Route → Controller → Service → Repository       │
│                                                  │
│  ┌─────────────┐  ┌──────────────────────────┐  │
│  │ Auth (Breeze)│  │ Scheduling Engine        │  │
│  │ + Spatie     │  │ (pure PHP, queued job)   │  │
│  │ Permission   │  │ Greedy + Backtrack + MRV │  │
│  └─────────────┘  └──────────────────────────┘  │
├─────────────────────────────────────────────────┤
│              PostgreSQL 16+                      │
│         (single DB, all tables)                  │
└─────────────────────────────────────────────────┘
```

## Layer Responsibilities

### Frontend (React + Inertia)
- Component architecture: page components receive props from Inertia, shared layout
- State: server-driven (Inertia props), local React state for UI-only (modals, form draft)
- No client-side data fetching — all data comes from Inertia page props
- Scheduling grid: client-side rendering of server-computed schedule
- Form validation: client-side mirrors server-side (Laravel FormRequest rules shared via props)

### Backend (Laravel)
- Route -> Controller -> Service -> Model (no repository pattern, Eloquent is enough for T0)
- Validation: FormRequest classes at controller entry
- Transactions: Service layer wraps multi-model operations
- Auth: Laravel Breeze (session-based) + Spatie Permission (3 roles: admin, dosen, mahasiswa)
- Scheduling: SchedulingService runs as a queued Job, writes result to schedules + schedule_items tables
- Error shape: Inertia's built-in error bag (validation) + flash messages (business logic)

### Database (PostgreSQL)
- Single database, all tables
- Indexes on: foreign keys, unique constraints (kode_mk, nim, nidn), composite keys for schedule lookups
- Timezone: all timestamps UTC, rendered as WIB in frontend
- Money: not applicable (no financial module)
- Soft delete: on mahasiswa and dosen (data preservation)

## Interface Contract

### Inertia Page Props (the contract between backend and frontend)

Since Inertia doesn't have a separate API — the controller returns Inertia::render()
with typed props — the contract IS the TypeScript interface for each page's props.

All page props defined in: contracts/types.ts (TypeScript interfaces)
All form shapes defined in: contracts/forms.ts (TypeScript interfaces)

Enforcement: TypeScript compilation. If backend changes a prop shape, the TS page
component fails to compile.

### Key Type Definitions

```typescript
// Shared
interface PaginatedResponse<T> {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

// Master Data
interface Fakultas { id: number; nama: string; kode: string; }
interface Prodi { id: number; nama: string; kode: string; fakultas_id: number; fakultas?: Fakultas; }
interface MataKuliah {
  id: number; kode: string; nama: string; sks: number;
  semester: number; tipe: 'teori' | 'lab';
  prodi_id: number; prodi?: Prodi;
}
interface Dosen {
  id: number; user_id: number; nidn: string; nama: string;
  bidang: string; prodi_id: number;
  time_preferences?: DosenTimePreference[];
}
interface DosenTimePreference {
  id: number; dosen_id: number;
  hari: 'senin'|'selasa'|'rabu'|'kamis'|'jumat';
  slot_waktu_id: number;
  status: 'preferred' | 'avoid' | 'unavailable';
}
interface Ruangan {
  id: number; kode: string; nama: string;
  kapasitas: number; tipe: 'teori' | 'lab' | 'campuran';
}
interface SlotWaktu {
  id: number; jam_mulai: string; /* HH:MM */ jam_selesai: string;
  label: string; /* "08:00 - 09:40" */
}
interface TahunAkademik {
  id: number; tahun: string; /* "2025/2026" */
  semester: 'ganjil' | 'genap';
  aktif: boolean;
}

// Kelas (mata kuliah offering per semester)
interface Kelas {
  id: number; mata_kuliah_id: number; dosen_id: number;
  tahun_akademik_id: number;
  nama_kelas: string; /* "A", "B" */
  kapasitas: number;
  mata_kuliah?: MataKuliah; dosen?: Dosen;
}

// Mahasiswa
interface Mahasiswa {
  id: number; user_id: number; nim: string; nama: string;
  prodi_id: number; semester: number;
  status: 'aktif' | 'cuti' | 'lulus' | 'do';
  prodi?: Prodi;
}

// KRS
interface Krs {
  id: number; mahasiswa_id: number; tahun_akademik_id: number;
  status: 'draft' | 'submitted' | 'approved' | 'revision';
  catatan_dosen?: string;
  details?: KrsDetail[];
}
interface KrsDetail {
  id: number; krs_id: number; kelas_id: number;
  kelas?: Kelas;
}

// Scheduling
interface Schedule {
  id: number; tahun_akademik_id: number;
  status: 'draft' | 'published';
  created_at: string;
  items?: ScheduleItem[];
  conflicts?: ScheduleConflict[];
}
interface ScheduleItem {
  id: number; schedule_id: number; kelas_id: number;
  ruangan_id: number; slot_waktu_id: number;
  hari: 'senin'|'selasa'|'rabu'|'kamis'|'jumat';
  is_pinned: boolean;
  explanation?: string;
  kelas?: Kelas; ruangan?: Ruangan; slot_waktu?: SlotWaktu;
}
interface ScheduleConflict {
  id: number; schedule_id: number;
  kelas_id: number; type: string; description: string;
}

// Nilai
interface Nilai {
  id: number; kelas_id: number; mahasiswa_id: number;
  komponen: Record<string, number>; /* {"uts": 80, "uas": 75, "tugas": 85, "quiz": 90} */
  nilai_akhir: number;
  huruf: string; bobot: number;
}

// Quiz
interface Quiz {
  id: number; kelas_id: number; judul: string;
  durasi_menit: number | null; /* null = no timer */
  status: 'draft' | 'published' | 'closed';
  soal?: Soal[];
}
interface Soal {
  id: number; quiz_id: number; teks: string;
  tipe: 'pilgan' | 'isian';
  pilihan?: string[]; /* for pilgan */
  jawaban_benar: string;
  poin: number;
}
interface QuizAttempt {
  id: number; quiz_id: number; mahasiswa_id: number;
  jawaban: Record<number, string>; /* soal_id -> jawaban */
  skor: number; submitted_at: string;
}
```

### Auth Contract

- Login: POST /login (email, password) -> redirect to role-based dashboard
- Roles: admin, dosen, mahasiswa (Spatie Permission)
- Middleware: role:admin, role:dosen, role:mahasiswa on route groups
- Failed auth: redirect to /login with error flash

### Error Envelope

Inertia standard:
- Validation: 422 -> errors bag in page props
- Auth: 403 -> error page
- Not found: 404 -> error page
- Server: 500 -> error page
- Business logic: redirect back with flash('error', 'message')
