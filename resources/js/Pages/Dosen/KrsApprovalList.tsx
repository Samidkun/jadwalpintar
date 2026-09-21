import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, XCircle, AlertCircle, Clock, UserCheck } from 'lucide-react';

interface KrsItem {
  id: number;
  status: 'draft' | 'submitted' | 'approved' | 'revision';
  catatan_dosen?: string | null;
  mahasiswa: {
    id: number;
    nama: string;
    nim: string;
    prodi?: { nama: string };
  };
  details: {
    kelas: {
      nama_kelas: string;
      mata_kuliah: {
        kode: string;
        nama: string;
        sks: number;
      };
    };
  }[];
}

interface Props {
  dosen: {
    id: number;
    nama: string;
    nidn: string;
  };
  tahunAkademik: {
    tahun: string;
    semester: string;
  };
  krsList: KrsItem[];
}

export default function KrsApprovalList({ dosen, tahunAkademik, krsList }: Props) {
  const [revisionNote, setRevisionNote] = useState<Record<number, string>>({});
  const [activeRevisionId, setActiveRevisionId] = useState<number | null>(null);

  const handleApprove = (krsId: number) => {
    router.post(`/dosen/krs/${krsId}/approve`);
  };

  const handleRevise = (krsId: number) => {
    const note = revisionNote[krsId];
    if (!note) return;
    router.post(`/dosen/krs/${krsId}/revise`, {
      catatan: note,
    }, {
      onSuccess: () => setActiveRevisionId(null),
    });
  };

  return (
    <>
      <Head title="Verifikasi KRS Bimbingan — JadwalPintar" />

      <div className="min-h-screen bg-[#F5F5F7] text-[#1D1D1F] p-4 md:p-6 flex flex-col justify-between antialiased">
        <div className="max-w-5xl mx-auto w-full space-y-6">
          
          {/* Header */}
          <header className="flex items-center justify-between pb-4 border-b border-black/5">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-xl bg-black text-white font-bold text-xs flex items-center justify-center font-mono">
                JP
              </div>
              <div>
                <h1 className="font-bold text-base text-[#1D1D1F]">Verifikasi KRS Mahasiswa Bimbingan</h1>
                <p className="text-xs text-[#86868B] font-mono">
                  {dosen.nama} • NIDN {dosen.nidn} • Semester {tahunAkademik.tahun} ({tahunAkademik.semester})
                </p>
              </div>
            </div>

            <Link
              href="/admin/schedules"
              className="px-3 py-1.5 rounded-lg bg-white border border-black/10 text-xs font-semibold text-[#1D1D1F] shadow-2xs hover:bg-black/5 transition"
            >
              Kembali ke Studio
            </Link>
          </header>

          {/* List of KRS */}
          <div className="bg-white rounded-2xl border border-black/5 shadow-apple-sm overflow-hidden">
            <div className="p-4 border-b border-black/5 flex items-center justify-between">
              <div>
                <h3 className="font-bold text-xs uppercase tracking-wider text-[#1D1D1F] font-mono">Antrean Pengajuan Mahasiswa</h3>
                <p className="text-[11px] text-[#86868B]">Periksa kesesuaian rencana studi mahasiswa bimbingan akademik Anda.</p>
              </div>
              <span className="font-mono text-xs text-[#86868B] font-medium">
                Total {krsList.length} Mahasiswa
              </span>
            </div>

            <div className="divide-y divide-black/5">
              {krsList.length > 0 ? (
                krsList.map((krs) => {
                  const totalSks = krs.details.reduce((sum, d) => sum + d.kelas.mata_kuliah.sks, 0);

                  return (
                    <div key={krs.id} className="p-4 space-y-3">
                      <div className="flex items-start justify-between">
                        <div>
                          <div className="flex items-center gap-2">
                            <h4 className="font-bold text-sm text-[#1D1D1F]">{krs.mahasiswa.nama}</h4>
                            <span className="font-mono text-xs text-[#86868B] bg-black/5 px-2 py-0.5 rounded">
                              {krs.mahasiswa.nim}
                            </span>
                            <span className={`text-[10px] font-mono font-bold uppercase px-2 py-0.5 rounded-full ${
                              krs.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' :
                              krs.status === 'submitted' ? 'bg-blue-50 text-blue-700 border border-blue-200' :
                              krs.status === 'revision' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-gray-100 text-gray-700'
                            }`}>
                              {krs.status}
                            </span>
                          </div>
                          <p className="text-xs text-[#86868B] mt-0.5">{krs.mahasiswa.prodi?.nama} • Total {totalSks} SKS</p>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex items-center gap-2">
                          {krs.status !== 'approved' && (
                            <>
                              <button
                                onClick={() => handleApprove(krs.id)}
                                className="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-2xs transition flex items-center gap-1"
                              >
                                <CheckCircle2 className="w-3.5 h-3.5" />
                                <span>Setujui (Approve)</span>
                              </button>
                              <button
                                onClick={() => setActiveRevisionId(activeRevisionId === krs.id ? null : krs.id)}
                                className="px-3 py-1.5 rounded-lg bg-white border border-black/10 hover:bg-black/5 text-red-600 text-xs font-semibold shadow-2xs transition flex items-center gap-1"
                              >
                                <XCircle className="w-3.5 h-3.5" />
                                <span>Revisi</span>
                              </button>
                            </>
                          )}
                        </div>
                      </div>

                      {/* Course list pills */}
                      <div className="flex flex-wrap gap-1.5 pt-1">
                        {krs.details.map((d, idx) => (
                          <span
                            key={idx}
                            className="px-2 py-1 rounded-md bg-[#F5F5F7] border border-black/5 text-[11px] font-mono text-[#1D1D1F]"
                          >
                            {d.kelas.mata_kuliah.kode} • {d.kelas.mata_kuliah.nama} ({d.kelas.mata_kuliah.sks} SKS)
                          </span>
                        ))}
                      </div>

                      {/* Revision Input if open */}
                      {activeRevisionId === krs.id && (
                        <div className="p-3 bg-red-50/50 rounded-xl border border-red-200 space-y-2 mt-2">
                          <p className="text-xs font-bold text-red-900">Tulis Catatan Revisi:</p>
                          <textarea
                            value={revisionNote[krs.id] || ''}
                            onChange={(e) => setRevisionNote({ ...revisionNote, [krs.id]: e.target.value })}
                            placeholder="Contoh: Silakan ganti kelas Algoritma ke paralel B agar tidak bentrok..."
                            className="w-full text-xs p-2 rounded-lg border border-red-300 focus:outline-none focus:ring-1 focus:ring-red-500 bg-white text-[#1D1D1F]"
                            rows={2}
                          ></textarea>
                          <div className="flex justify-end gap-2">
                            <button
                              onClick={() => setActiveRevisionId(null)}
                              className="px-2.5 py-1 text-xs text-[#86868B] hover:text-[#1D1D1F]"
                            >
                              Batal
                            </button>
                            <button
                              onClick={() => handleRevise(krs.id)}
                              className="px-3 py-1 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700"
                            >
                              Kirim Revisi
                            </button>
                          </div>
                        </div>
                      )}

                    </div>
                  );
                })
              ) : (
                <div className="p-12 text-center text-xs text-[#86868B]">
                  Tidak ada pengajuan KRS mahasiswa bimbingan saat ini.
                </div>
              )}
            </div>
          </div>

        </div>
      </div>
    </>
  );
}
