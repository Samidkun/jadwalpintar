import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Plus, Trash2, Image as ImageIcon, CheckCircle, ArrowLeft } from 'lucide-react';

interface Props {
  kelas: {
    id: number;
    nama_kelas: string;
    mata_kuliah: {
      kode: string;
      nama: string;
    };
    dosen: {
      nama: string;
    };
  };
}

interface SoalForm {
  teks: string;
  gambar: File | null;
  tipe: 'pilgan' | 'isian';
  pilihan: string[];
  jawaban_benar: string;
  poin: number;
}

export default function QuizCreate({ kelas }: Props) {
  const [judul, setJudul] = useState('');
  const [durasiMenit, setDurasiMenit] = useState<number>(15);
  const [soalList, setSoalList] = useState<SoalForm[]>([
    {
      teks: '',
      gambar: null,
      tipe: 'pilgan',
      pilihan: ['', '', '', ''],
      jawaban_benar: '',
      poin: 25,
    },
  ]);

  const addSoal = (tipe: 'pilgan' | 'isian') => {
    setSoalList((prev) => [
      ...prev,
      {
        teks: '',
        gambar: null,
        tipe,
        pilihan: tipe === 'pilgan' ? ['', '', '', ''] : [],
        jawaban_benar: '',
        poin: 25,
      },
    ]);
  };

  const removeSoal = (index: number) => {
    setSoalList((prev) => prev.filter((_, idx) => idx !== index));
  };

  const handlePilihanChange = (soalIdx: number, pilIdx: number, val: string) => {
    setSoalList((prev) => {
      const copy = [...prev];
      copy[soalIdx].pilihan[pilIdx] = val;
      return copy;
    });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const formData = new FormData();
    formData.append('judul', judul);
    formData.append('durasi_menit', String(durasiMenit));
    formData.append('acak_soal', '1');
    formData.append('acak_pilihan', '1');
    formData.append('satu_percobaan', '1');

    soalList.forEach((soal, sIdx) => {
      formData.append(`soal[${sIdx}][teks]`, soal.teks);
      formData.append(`soal[${sIdx}][tipe]`, soal.tipe);
      formData.append(`soal[${sIdx}][jawaban_benar]`, soal.jawaban_benar);
      formData.append(`soal[${sIdx}][poin]`, String(soal.poin));

      if (soal.gambar) {
        formData.append(`soal[${sIdx}][gambar]`, soal.gambar);
      }

      if (soal.tipe === 'pilgan') {
        soal.pilihan.forEach((pil, pIdx) => {
          formData.append(`soal[${sIdx}][pilihan][${pIdx}]`, pil);
        });
      }
    });

    router.post(`/dosen/kelas/${kelas.id}/quizzes`, formData);
  };

  return (
    <>
      <Head title="Buat Mini Quiz — JadwalPintar" />

      <div className="min-h-screen bg-[#F5F5F7] text-[#1D1D1F] p-4 md:p-6 antialiased">
        <div className="max-w-4xl mx-auto w-full space-y-6">
          
          <header className="flex items-center justify-between pb-4 border-b border-black/5">
            <div className="flex items-center gap-3">
              <Link href="/admin/schedules" className="p-2 rounded-lg bg-white border border-black/10 hover:bg-black/5 transition">
                <ArrowLeft className="w-4 h-4 text-[#1D1D1F]" />
              </Link>
              <div>
                <h1 className="font-bold text-base text-[#1D1D1F]">Buat Mini Quiz Baru</h1>
                <p className="text-xs text-[#86868B] font-mono">
                  {kelas.mata_kuliah.kode} • {kelas.mata_kuliah.nama} (Kelas {kelas.nama_kelas})
                </p>
              </div>
            </div>
          </header>

          <form onSubmit={handleSubmit} className="space-y-6">
            {/* General Info */}
            <div className="p-5 bg-white rounded-2xl border border-black/5 shadow-apple-sm space-y-4">
              <h3 className="font-bold text-xs uppercase tracking-wider text-[#1D1D1F] font-mono">Informasi Kuis</h3>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="text-xs font-semibold text-[#1D1D1F] block mb-1">Judul Kuis</label>
                  <input
                    type="text"
                    required
                    value={judul}
                    onChange={(e) => setJudul(e.target.value)}
                    placeholder="Contoh: Kuis 1: Konsep Dasar Relasi"
                    className="w-full text-xs p-2.5 rounded-xl border border-black/10 focus:outline-none focus:ring-1 focus:ring-[#0071E3] bg-[#FBFBFD]"
                  />
                </div>
                <div>
                  <label className="text-xs font-semibold text-[#1D1D1F] block mb-1">Durasi Pengerjaan (Menit)</label>
                  <input
                    type="number"
                    min={1}
                    required
                    value={durasiMenit}
                    onChange={(e) => setDurasiMenit(Number(e.target.value))}
                    className="w-full text-xs p-2.5 rounded-xl border border-black/10 focus:outline-none focus:ring-1 focus:ring-[#0071E3] bg-[#FBFBFD]"
                  />
                </div>
              </div>
            </div>

            {/* Questions List */}
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="font-bold text-xs uppercase tracking-wider text-[#1D1D1F] font-mono">Daftar Soal ({soalList.length})</h3>
                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    onClick={() => addSoal('pilgan')}
                    className="px-3 py-1.5 rounded-lg bg-white border border-black/10 text-xs font-semibold hover:bg-black/5 transition flex items-center gap-1.5"
                  >
                    <Plus className="w-3.5 h-3.5" />
                    <span>+ Soal Pilihan Ganda</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => addSoal('isian')}
                    className="px-3 py-1.5 rounded-lg bg-white border border-black/10 text-xs font-semibold hover:bg-black/5 transition flex items-center gap-1.5"
                  >
                    <Plus className="w-3.5 h-3.5" />
                    <span>+ Soal Isian Singkat</span>
                  </button>
                </div>
              </div>

              {soalList.map((soal, sIdx) => (
                <div key={sIdx} className="p-5 bg-white rounded-2xl border border-black/5 shadow-apple-sm space-y-4 relative">
                  <div className="flex items-center justify-between">
                    <span className="font-mono text-xs font-bold text-[#1D1D1F] px-2 py-0.5 rounded bg-black/5">
                      Nomor {sIdx + 1} • {soal.tipe === 'pilgan' ? 'Pilihan Ganda' : 'Isian Singkat'}
                    </span>
                    {soalList.length > 1 && (
                      <button
                        type="button"
                        onClick={() => removeSoal(sIdx)}
                        className="text-red-500 hover:text-red-700 p-1"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    )}
                  </div>

                  {/* Question Text */}
                  <div>
                    <label className="text-xs font-medium text-[#86868B] block mb-1">Teks Pertanyaan</label>
                    <textarea
                      required
                      value={soal.teks}
                      onChange={(e) => {
                        const copy = [...soalList];
                        copy[sIdx].teks = e.target.value;
                        setSoalList(copy);
                      }}
                      placeholder="Ketikkan butir soal di sini..."
                      rows={3}
                      className="w-full text-xs p-2.5 rounded-xl border border-black/10 focus:outline-none focus:ring-1 focus:ring-[#0071E3] bg-[#FBFBFD]"
                    ></textarea>
                  </div>

                  {/* Image Upload (AC-9) */}
                  <div>
                    <label className="text-xs font-medium text-[#86868B] block mb-1">Lampiran Gambar (Opsional, Diagram/Rumus maks 2MB)</label>
                    <div className="flex items-center gap-3">
                      <input
                        type="file"
                        accept="image/*"
                        onChange={(e) => {
                          const file = e.target.files?.[0] || null;
                          const copy = [...soalList];
                          copy[sIdx].gambar = file;
                          setSoalList(copy);
                        }}
                        className="text-xs text-[#86868B]"
                      />
                      {soal.gambar && (
                        <span className="text-[11px] font-mono text-emerald-600 flex items-center gap-1">
                          <CheckCircle className="w-3.5 h-3.5" />
                          <span>{soal.gambar.name}</span>
                        </span>
                      )}
                    </div>
                  </div>

                  {/* Options for Pilgan */}
                  {soal.tipe === 'pilgan' && (
                    <div className="space-y-2">
                      <label className="text-xs font-medium text-[#86868B] block">Opsi Pilihan Jawaban</label>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                        {soal.pilihan.map((pil, pIdx) => (
                          <div key={pIdx} className="flex items-center gap-2">
                            <span className="font-mono text-xs font-bold text-[#86868B] w-4">
                              {String.fromCharCode(65 + pIdx)}.
                            </span>
                            <input
                              type="text"
                              required
                              value={pil}
                              onChange={(e) => handlePilihanChange(sIdx, pIdx, e.target.value)}
                              placeholder={`Pilihan ${String.fromCharCode(65 + pIdx)}`}
                              className="flex-1 text-xs p-2 rounded-lg border border-black/10 bg-[#FBFBFD] focus:outline-none focus:ring-1 focus:ring-[#0071E3]"
                            />
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Correct Answer */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-black/5">
                    <div>
                      <label className="text-xs font-medium text-emerald-700 block mb-1 font-bold">Kunci Jawaban Benar</label>
                      <input
                        type="text"
                        required
                        value={soal.jawaban_benar}
                        onChange={(e) => {
                          const copy = [...soalList];
                          copy[sIdx].jawaban_benar = e.target.value;
                          setSoalList(copy);
                        }}
                        placeholder={soal.tipe === 'pilgan' ? 'Ketikkan teks opsi yang tepat persis' : 'Jawaban isian singkat'}
                        className="w-full text-xs p-2 rounded-lg border border-emerald-300 bg-emerald-50/40 text-emerald-900 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                      />
                    </div>
                    <div>
                      <label className="text-xs font-medium text-[#86868B] block mb-1">Bobot Poin</label>
                      <input
                        type="number"
                        min={1}
                        required
                        value={soal.poin}
                        onChange={(e) => {
                          const copy = [...soalList];
                          copy[sIdx].poin = Number(e.target.value);
                          setSoalList(copy);
                        }}
                        className="w-full text-xs p-2 rounded-lg border border-black/10 bg-[#FBFBFD] focus:outline-none focus:ring-1 focus:ring-[#0071E3]"
                      />
                    </div>
                  </div>

                </div>
              ))}
            </div>

            <div className="flex justify-end gap-3 pt-4">
              <Link
                href="/admin/schedules"
                className="px-4 py-2 rounded-xl text-xs font-medium text-[#86868B] hover:text-[#1D1D1F]"
              >
                Batal
              </Link>
              <button
                type="submit"
                className="px-5 py-2.5 rounded-xl text-xs font-semibold bg-[#0071E3] text-white hover:bg-[#0077ED] shadow-sm transition active:scale-[0.98]"
              >
                Simpan & Publikasikan Kuis
              </button>
            </div>
          </form>

        </div>
      </div>
    </>
  );
}
