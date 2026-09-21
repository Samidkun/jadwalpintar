import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface Props {
  auth?: {
    user: any;
  };
}

export default function Welcome({ auth }: Props) {
  return (
    <>
      <Head title="SIAKAD & Timetable Studio" />
      <div className="min-h-screen bg-[#F5F5F7] text-[#1D1D1F] flex flex-col justify-between p-6">
        <header className="flex items-center justify-between max-w-5xl mx-auto w-full py-4">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-xl bg-black text-white font-bold text-sm flex items-center justify-center font-mono">
              JP
            </div>
            <div>
              <h1 className="font-bold text-sm leading-tight text-apple-text">JadwalPintar</h1>
              <p className="text-[11px] text-[#86868B] font-mono">SIAKAD & Timetable Studio</p>
            </div>
          </div>

          <div className="flex items-center gap-3 text-xs">
            <Link
              href="/login"
              className="px-3 py-1.5 rounded-lg bg-white border border-black/10 text-apple-text font-medium shadow-xs hover:bg-black/5 transition"
            >
              Masuk Portal
            </Link>
          </div>
        </header>

        <main className="max-w-3xl mx-auto w-full my-auto text-center py-16">
          <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-mono font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 mb-4">
            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            <span>Constraint-Based Academic Timetabling</span>
          </span>
          <h2 className="text-3xl md:text-4xl font-extrabold tracking-tight text-[#1D1D1F] leading-tight">
            Sistem Informasi Akademik dengan Engine Penjadwalan Bebas Bentrok.
          </h2>
          <p className="mt-4 text-sm text-[#86868B] max-w-xl mx-auto leading-relaxed">
            Otomatisasi pemetaan dosen, mata kuliah, ruangan kelas, dan preferensi waktu menggunakan Minimum Remaining Values (MRV) Backtracking Engine.
          </p>

          <div className="mt-8 flex items-center justify-center gap-3">
            <Link
              href="/admin/schedules"
              className="px-4 py-2 rounded-xl text-xs font-semibold bg-[#0071E3] text-white hover:bg-[#0077ED] shadow-sm transition active:scale-[0.98]"
            >
              Buka Studio Penjadwalan
            </Link>
            <Link
              href="/login"
              className="px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-black/10 text-[#1D1D1F] hover:bg-black/5 shadow-xs transition"
            >
              Demo Akun (Role Mahasiswa / Dosen / Admin)
            </Link>
          </div>
        </main>

        <footer className="text-center text-xs text-[#86868B] py-4">
          <p>© 2026 JadwalPintar • Institut Teknologi & Bisnis Nusatama</p>
        </footer>
      </div>
    </>
  );
}
