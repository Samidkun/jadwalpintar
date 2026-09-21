import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
  BookOpen,
  CheckCircle2,
  Clock,
  AlertCircle,
  Send,
  Layers,
  Calendar,
  Award
} from 'lucide-react';

interface Matkul {
  id: number;
  kode: string;
  nama: string;
  sks: number;
  semester: number;
  tipe: 'teori' | 'lab';
}

interface Dosen {
  id: number;
  nama: string;
}

interface Kelas {
  id: number;
  nama_kelas: string;
  kapasitas: number;
  mata_kuliah: Matkul;
  dosen: Dosen;
}

interface Props {
  mahasiswa: {
    id: number;
    nama: string;
    nim: string;
    semester: number;
    prodi?: { nama: string };
    dosen_pa?: { nama: string };
  };
  tahunAkademik: {
    tahun: string;
    semester: string;
  };
  maxSks: number;
  availableClasses: Kelas[];
  currentKrs: {
    id: number;
    status: 'draft' | 'submitted' | 'approved' | 'revision';
    catatan_dosen?: string | null;
    details: {
      kelas_id: number;
      kelas: Kelas;
    }[];
  } | null;
  errors?: Record<string, string>;
}

export default function KrsPortal({
  mahasiswa,
  tahunAkademik,
  maxSks,
  availableClasses,
  currentKrs,
  errors = {},
}: Props) {
  const initialSelected = currentKrs?.details?.map((d) => d.kelas_id) || [];
  const [selectedIds, setSelectedIds] = useState<number[]>(initialSelected);

  const toggleSelect = (id: number) => {
    if (currentKrs?.status === 'approved') return;
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id]
    );
  };

  const selectedClasses = availableClasses.filter((k) => selectedIds.includes(k.id));
  const currentTotalSks = selectedClasses.reduce((sum, k) => sum + k.mata_kuliah.sks, 0);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    router.post('/mahasiswa/krs', {
      kelas_ids: selectedIds,
    });
  };

  const isLocked = currentKrs?.status === 'approved';

  return (
    <>
      <Head title="Pengisian KRS Mahasiswa — JadwalPintar" />

      <div className="min-h-screen bg-[#F5F5F7] text-[#1D1D1F] p-4 md:p-6 flex flex-col justify-between antialiased">
        <div className="max-w-5xl mx-auto w-full space-y-6">
          
          {/* Header */}
          <header className="flex items-center justify-between pb-4 border-b border-black/5">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-xl bg-black text-white font-bold text-xs flex items-center justify-center font-mono">
                JP
              </div>
              <div>
                <h1 className="font-bold text-base text-[#1D1D1F]">Kartu Rencana Studi (KRS)</h1>
                <p className="text-xs text-[#86868B] font-mono">
                  {mahasiswa.nama} • {mahasiswa.nim} • {mahasiswa.prodi?.nama}
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Link
                href="/admin/schedules"
                className="px-3 py-1.5 rounded-lg bg-white border border-black/10 text-xs font-semibold text-[#1D1D1F] shadow-2xs hover:bg-black/5 transition"
              >
                Kembali ke Studio
              </Link>
            </div>
          </header>

          {/* SKS Summary Banner */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="p-4 bg-white rounded-2xl border border-black/5 shadow-apple-sm">
              <p className="text-xs text-[#86868B] font-medium">Batas Hak SKS Anda</p>
              <p className="text-2xl font-bold font-mono text-[#1D1D1F] mt-1">{maxSks} <span className="text-xs font-sans font-normal text-[#86868B]">SKS</span></p>
              <p className="text-[11px] text-[#86868B] mt-1">Dihitung otomatis dari IPS semester sebelumnya.</p>
            </div>

            <div className="p-4 bg-white rounded-2xl border border-black/5 shadow-apple-sm">
              <p className="text-xs text-[#86868B] font-medium">Total SKS Terpilih</p>
              <p className={`text-2xl font-bold font-mono mt-1 ${currentTotalSks > maxSks ? 'text-red-600' : 'text-[#0071E3]'}`}>
                {currentTotalSks} <span className="text-xs font-sans font-normal text-[#86868B]">/ {maxSks} SKS</span>
              </p>
              <p className="text-[11px] text-[#86868B] mt-1">
                {currentTotalSks > maxSks ? '⚠️ Melebihi kuota hak SKS' : 'Sisa kuota: ' + (maxSks - currentTotalSks) + ' SKS'}
              </p>
            </div>

            <div className="p-4 bg-white rounded-2xl border border-black/5 shadow-apple-sm">
              <p className="text-xs text-[#86868B] font-medium">Status Pengajuan</p>
              <div className="mt-2 flex items-center gap-2">
                <span className={`w-2.5 h-2.5 rounded-full ${
                  currentKrs?.status === 'approved' ? 'bg-emerald-500' :
                  currentKrs?.status === 'submitted' ? 'bg-[#0071E3]' :
                  currentKrs?.status === 'revision' ? 'bg-red-500' : 'bg-[#FF9500]'
                }`}></span>
                <span className="text-xs font-bold font-mono uppercase tracking-wide">
                  {currentKrs?.status || 'Belum Mengajukan'}
                </span>
              </div>
              <p className="text-[11px] text-[#86868B] mt-1">
                PA: {mahasiswa.dosen_pa?.nama || 'Belum Ditentukan'}
              </p>
            </div>
          </div>

          {/* Validation Error Banner if Any */}
          {errors.kelas_ids && (
            <div className="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 flex items-start gap-2.5">
              <AlertCircle className="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
              <div>
                <p className="font-bold">Gagal Mengajukan KRS</p>
                <p className="mt-0.5">{errors.kelas_ids}</p>
              </div>
            </div>
          )}

          {/* Revision Note Banner if Any */}
          {currentKrs?.status === 'revision' && currentKrs.catatan_dosen && (
            <div className="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-2.5">
              <AlertCircle className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
              <div>
                <p className="font-bold">Catatan Revisi dari Dosen Pembimbing</p>
                <p className="mt-0.5">{currentKrs.catatan_dosen}</p>
              </div>
            </div>
          )}

          {/* Available Classes Selection Table */}
          <div className="bg-white rounded-2xl border border-black/5 shadow-apple-sm overflow-hidden">
            <div className="p-4 border-b border-black/5 flex items-center justify-between">
              <div>
                <h3 className="font-bold text-xs uppercase tracking-wider text-[#1D1D1F] font-mono">Daftar Mata Kuliah Ditawarkan</h3>
                <p className="text-[11px] text-[#86868B]">Pilih mata kuliah yang ingin Anda ambil pada semester {tahunAkademik.tahun} ({tahunAkademik.semester})</p>
              </div>
            </div>

            <div className="divide-y divide-black/5">
              {availableClasses.map((kelas) => {
                const isChecked = selectedIds.includes(kelas.id);
                return (
                  <div
                    key={kelas.id}
                    onClick={() => toggleSelect(kelas.id)}
                    className={`p-3.5 flex items-center justify-between cursor-pointer transition ${
                      isChecked ? 'bg-[#0071E3]/5' : 'hover:bg-black/[0.02]'
                    } ${isLocked ? 'cursor-not-allowed opacity-75' : ''}`}
                  >
                    <div className="flex items-center gap-3">
                      <input
                        type="checkbox"
                        checked={isChecked}
                        disabled={isLocked}
                        onChange={() => {}}
                        className="w-4 h-4 rounded text-[#0071E3] focus:ring-0 border-black/20"
                      />
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-mono text-xs font-bold text-[#1D1D1F]">{kelas.mata_kuliah.kode}</span>
                          <span className="text-xs font-medium text-[#1D1D1F]">{kelas.mata_kuliah.nama}</span>
                          <span className="px-1.5 py-0.2 rounded text-[10px] font-mono bg-black/5 text-[#86868B]">
                            Kelas {kelas.nama_kelas}
                          </span>
                        </div>
                        <p className="text-[11px] text-[#86868B] mt-0.5">
                          Dosen: {kelas.dosen.nama} • {kelas.mata_kuliah.tipe === 'lab' ? 'Praktikum' : 'Teori'}
                        </p>
                      </div>
                    </div>

                    <div className="text-right">
                      <span className="font-mono text-xs font-bold text-[#1D1D1F]">{kelas.mata_kuliah.sks} SKS</span>
                      <p className="text-[10px] text-[#86868B]">Sem. {kelas.mata_kuliah.semester}</p>
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Form Footer Action */}
            <div className="p-4 bg-[#FBFBFD] border-t border-black/5 flex items-center justify-between">
              <div className="text-xs text-[#86868B]">
                <span>{selectedIds.length} mata kuliah terpilih ({currentTotalSks} SKS)</span>
              </div>

              {!isLocked && (
                <button
                  onClick={handleSubmit}
                  disabled={currentTotalSks > maxSks || selectedIds.length === 0}
                  className="px-4 py-2 rounded-xl text-xs font-semibold bg-[#0071E3] text-white hover:bg-[#0077ED] shadow-sm transition active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5"
                >
                  <Send className="w-3.5 h-3.5" />
                  <span>Ajukan KRS ke Dosen PA</span>
                </button>
              )}
            </div>

          </div>

        </div>
      </div>
    </>
  );
}
