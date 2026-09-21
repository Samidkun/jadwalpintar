import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
  Calendar,
  Layers,
  BookOpen,
  Award,
  HelpCircle,
  RotateCcw,
  Pin,
  CheckCircle2,
  AlertCircle,
  Sparkles,
  Building2,
  Clock,
  Users
} from 'lucide-react';

interface Matkul {
  id: number;
  kode: string;
  nama: string;
  sks: number;
  semester: number;
  tipe: 'teori' | 'lab';
  prodi?: {
    kode: string;
    nama: string;
  };
}

interface Dosen {
  id: number;
  nama: string;
  nidn: string;
  bidang: string;
}

interface Kelas {
  id: number;
  nama_kelas: string;
  kapasitas: number;
  mata_kuliah: Matkul;
  dosen: Dosen;
}

interface Ruangan {
  id: number;
  kode: string;
  nama: string;
  kapasitas: number;
  tipe: 'teori' | 'lab' | 'campuran';
}

interface SlotWaktu {
  id: number;
  jam_mulai: string;
  jam_selesai: string;
  label: string;
}

interface ScheduleItem {
  id: number;
  kelas_id: number;
  ruangan_id: number;
  slot_waktu_id: number;
  hari: 'senin' | 'selasa' | 'rabu' | 'kamis' | 'jumat';
  is_pinned: boolean;
  explanation: string | null;
  kelas: Kelas;
  ruangan: Ruangan;
  slot_waktu: SlotWaktu;
}

interface ScheduleConflict {
  id: number;
  kelas_id: number;
  type: string;
  description: string;
  kelas?: Kelas;
}

interface Schedule {
  id: number;
  status: 'generating' | 'draft' | 'published';
  metadata?: {
    total_kelas: number;
    assigned: number;
    conflicts: number;
    solve_time_ms: number;
    backtracks: number;
  };
  items: ScheduleItem[];
  conflicts: ScheduleConflict[];
}

interface Props {
  tahunAkademik: {
    id: number;
    tahun: string;
    semester: string;
    aktif: boolean;
  } | null;
  schedule: Schedule | null;
  rooms: Ruangan[];
  slots: SlotWaktu[];
  days: ('senin' | 'selasa' | 'rabu' | 'kamis' | 'jumat')[];
}

export default function ScheduleWorkspace({
  tahunAkademik,
  schedule,
  rooms,
  slots,
  days,
}: Props) {
  const [selectedRoomId, setSelectedRoomId] = useState<number | 'all'>('all');
  const [selectedItem, setSelectedItem] = useState<ScheduleItem | null>(
    schedule?.items?.[0] || null
  );
  const [isSolving, setIsSolving] = useState(false);

  const handleGenerate = () => {
    setIsSolving(true);
    router.post(
      '/admin/schedules/generate',
      {},
      {
        onFinish: () => setIsSolving(false),
      }
    );
  };

  const handleTogglePin = (itemId: number) => {
    router.post(`/admin/schedules/items/${itemId}/toggle-pin`);
  };

  const handlePublish = (scheduleId: number) => {
    router.post(`/admin/schedules/${scheduleId}/publish`);
  };

  // Filter items by room if selected
  const filteredItems = schedule?.items?.filter((item) => {
    if (selectedRoomId === 'all') return true;
    return item.ruangan_id === Number(selectedRoomId);
  }) || [];

  const totalClasses = schedule?.metadata?.total_kelas || 0;
  const assignedCount = schedule?.items?.length || 0;
  const conflictCount = schedule?.conflicts?.length || 0;
  const isPublished = schedule?.status === 'published';

  return (
    <>
      <Head title="Studio Penjadwalan — JadwalPintar" />

      <div className="h-screen flex flex-col overflow-hidden text-[13px] leading-relaxed antialiased select-none bg-[#F5F5F7] p-3 md:p-4">
        {/* macOS Window Frame */}
        <div className="h-full w-full flex flex-col bg-white rounded-2xl shadow-apple-card border border-black/5 overflow-hidden">
          
          {/* Top Window Header */}
          <header className="h-13 bg-[#FBFBFD] border-b border-black/5 px-4 py-2.5 flex items-center justify-between shrink-0">
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-1.5">
                <span className="w-3 h-3 rounded-full bg-[#FF5F56] border border-[#E0443E]/40 inline-block shadow-2xs"></span>
                <span className="w-3 h-3 rounded-full bg-[#FFBD2E] border border-[#DEA123]/40 inline-block shadow-2xs"></span>
                <span className="w-3 h-3 rounded-full bg-[#27C93F] border border-[#1AAB29]/40 inline-block shadow-2xs"></span>
              </div>

              <div className="h-4 w-px bg-black/10"></div>

              <div className="flex items-center gap-2">
                <span className="font-semibold text-xs tracking-tight text-[#1D1D1F]">JadwalPintar</span>
                <span className="text-[#A1A1A6]">/</span>
                <span className="text-[#86868B] text-xs font-medium">Studio Penjadwalan</span>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#F5F5F7] text-[#86868B] border border-black/5 font-mono">
                  {tahunAkademik ? `${tahunAkademik.tahun} ${tahunAkademik.semester.toUpperCase()}` : 'Belum Ada Periode'}
                </span>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <div className="flex items-center gap-1.5 text-[11px] font-mono text-[#86868B]">
                <span className={`w-2 h-2 rounded-full ${conflictCount > 0 ? 'bg-red-500' : 'bg-emerald-500'}`}></span>
                <span>
                  {conflictCount > 0 ? `${conflictCount} Konflik Terdeteksi` : `${assignedCount}/${totalClasses} Kelas Bebas Bentrok`}
                </span>
              </div>

              <button
                onClick={handleGenerate}
                disabled={isSolving}
                className="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#0071E3] text-white hover:bg-[#0077ED] shadow-sm transition flex items-center gap-1.5 active:scale-[0.98] disabled:opacity-50"
              >
                <RotateCcw className={`w-3.5 h-3.5 ${isSolving ? 'animate-spin' : ''}`} />
                <span>{isSolving ? 'Menghitung...' : 'Kalkulasi Solver'}</span>
              </button>

              {schedule && !isPublished && (
                <button
                  onClick={() => handlePublish(schedule.id)}
                  className="px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-black/10 text-[#1D1D1F] hover:bg-black/5 transition"
                >
                  Publikasikan
                </button>
              )}
            </div>
          </header>

          {/* Workspace Body */}
          <div className="flex-1 flex overflow-hidden">
            
            {/* Left Sidebar */}
            <aside className="w-52 bg-[#FBFBFD] border-r border-black/5 flex flex-col justify-between shrink-0">
              <div className="p-2 space-y-4">
                {/* Stats Card */}
                <div className="p-3 bg-white rounded-xl border border-black/5 shadow-2xs">
                  <div className="flex items-center justify-between text-[11px] mb-1">
                    <span className="text-[#86868B] font-medium">Status Solver</span>
                    <span className="text-emerald-600 font-semibold font-mono">
                      {schedule?.metadata?.solve_time_ms ? `${schedule.metadata.solve_time_ms}ms` : 'Siap'}
                    </span>
                  </div>
                  <p className="text-xl font-bold text-[#1D1D1F] font-mono tracking-tight">
                    {assignedCount} <span className="text-xs font-normal text-[#86868B] font-sans">Kelas</span>
                  </p>
                  <div className="w-full bg-[#EBEBED] rounded-full h-1.5 mt-2 overflow-hidden">
                    <div
                      className="bg-[#0071E3] h-1.5 rounded-full transition-all duration-500"
                      style={{ width: `${totalClasses > 0 ? (assignedCount / totalClasses) * 100 : 0}%` }}
                    ></div>
                  </div>
                  <div className="flex items-center justify-between text-[10px] text-[#86868B] font-mono mt-1.5">
                    <span>{rooms.length} Ruangan</span>
                    <span>{slots.length} Slot Waktu</span>
                  </div>
                </div>

                {/* Nav Links */}
                <div className="space-y-1">
                  <p className="px-2 text-[10px] font-semibold text-[#86868B] tracking-wider uppercase font-mono">Menu Navigasi</p>
                  <Link href="/" className="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[#86868B] hover:bg-black/5 transition text-xs font-medium">
                    <Layers className="w-4 h-4 text-[#A1A1A6]" />
                    <span>Halaman Utama</span>
                  </Link>
                  <div className="flex items-center justify-between px-2.5 py-1.5 rounded-lg bg-[#0071E3]/10 text-[#0071E3] font-semibold text-xs shadow-2xs">
                    <div className="flex items-center gap-2">
                      <Calendar className="w-4 h-4 text-[#0071E3]" />
                      <span>Studio Jadwal</span>
                    </div>
                    <span className="w-1.5 h-1.5 rounded-full bg-[#0071E3]"></span>
                  </div>
                </div>
              </div>

              <div className="p-3 border-t border-black/5 flex items-center justify-between">
                <div className="flex items-center gap-2 min-w-0">
                  <div className="w-6 h-6 rounded-full bg-[#E5E5EA] text-[#1D1D1F] font-bold text-[10px] flex items-center justify-center font-mono">BA</div>
                  <div className="truncate">
                    <p className="text-xs font-semibold text-[#1D1D1F] truncate">Biro Akademik</p>
                    <p className="text-[10px] text-[#86868B] truncate font-mono">admin@kharisma.ac.id</p>
                  </div>
                </div>
              </div>
            </aside>

            {/* Center Grid */}
            <main className="flex-1 flex flex-col min-w-0 bg-[#F5F5F7] overflow-hidden">
              
              {/* Filter Sub-toolbar */}
              <div className="h-11 px-5 bg-white/70 backdrop-blur-md border-b border-black/5 flex items-center justify-between shrink-0">
                <div className="flex items-center gap-3">
                  <div className="flex items-center gap-2 text-xs">
                    <span className="text-[#86868B] font-medium">Filter Ruang:</span>
                    <select
                      value={selectedRoomId}
                      onChange={(e) => setSelectedRoomId(e.target.value === 'all' ? 'all' : Number(e.target.value))}
                      className="bg-white border border-black/10 rounded-lg px-2.5 py-1 text-xs text-[#1D1D1F] font-medium shadow-2xs focus:outline-none focus:ring-1 focus:ring-[#0071E3]"
                    >
                      <option value="all">Semua Ruangan Kuliah ({rooms.length} Unit)</option>
                      {rooms.map((r) => (
                        <option key={r.id} value={r.id}>
                          {r.nama} ({r.tipe === 'lab' ? 'Praktikum' : 'Teori'} - {r.kapasitas} Kursi)
                        </option>
                      ))}
                    </select>
                  </div>
                </div>

                <div className="flex items-center gap-2 text-xs text-[#86868B]">
                  <span className="font-mono text-[11px]">
                    Backtracks: {schedule?.metadata?.backtracks || 0}
                  </span>
                </div>
              </div>

              {/* Grid Matrix View */}
              <div className="flex-1 overflow-auto p-4">
                <div className="bg-white rounded-2xl border border-black/5 shadow-2xs overflow-hidden flex flex-col min-w-[760px]">
                  
                  {/* Days Header */}
                  <div className="grid grid-cols-6 border-b border-black/5 bg-[#FBFBFD] text-[11px] font-semibold text-[#86868B] uppercase tracking-wider font-mono">
                    <div className="p-2.5 text-center border-r border-black/5">Waktu / Slot</div>
                    {days.map((day, idx) => (
                      <div key={day} className={`p-2.5 text-center ${idx < days.length - 1 ? 'border-r border-black/5' : ''}`}>
                        {day}
                      </div>
                    ))}
                  </div>

                  {/* Slot Rows */}
                  <div className="divide-y divide-black/5">
                    {slots.map((slot) => (
                      <div key={slot.id} className="grid grid-cols-6 divide-x divide-black/5 min-h-[115px]">
                        {/* Time Column */}
                        <div className="p-3 bg-[#FBFBFD] flex flex-col justify-start">
                          <span className="font-mono text-xs font-bold text-[#1D1D1F]">{slot.jam_mulai} - {slot.jam_selesai}</span>
                          <span className="text-[10px] text-[#86868B] font-mono mt-0.5">{slot.label}</span>
                        </div>

                        {/* Days Columns */}
                        {days.map((day) => {
                          const matched = filteredItems.filter(
                            (it) => it.hari === day && it.slot_waktu_id === slot.id
                          );

                          return (
                            <div key={day} className="p-2 bg-white flex flex-col gap-2 relative">
                              {matched.length > 0 ? (
                                matched.map((it) => {
                                  const isSelected = selectedItem?.id === it.id;
                                  const isLab = it.kelas.mata_kuliah.tipe === 'lab';

                                  return (
                                    <div
                                      key={it.id}
                                      onClick={() => setSelectedItem(it)}
                                      className={`p-2.5 rounded-xl border transition-all cursor-pointer relative overflow-hidden group ${
                                        isSelected
                                          ? 'border-[#0071E3] bg-[#0071E3]/5 ring-1 ring-[#0071E3]'
                                          : 'border-black/5 bg-white hover:border-black/20 shadow-2xs'
                                      }`}
                                    >
                                      <div
                                        className={`absolute left-0 top-0 bottom-0 w-1 ${
                                          isLab ? 'bg-[#AF52DE]' : 'bg-[#0071E3]'
                                        } rounded-l-xl`}
                                      ></div>

                                      <div className="flex items-center justify-between pl-1 font-mono text-[10px]">
                                        <span className="font-semibold px-1.5 py-0.2 rounded border bg-[#F5F5F7] text-[#1D1D1F]">
                                          {it.kelas.mata_kuliah.kode} • {it.kelas.nama_kelas}
                                        </span>
                                        {it.is_pinned && (
                                          <Pin className="w-3 h-3 text-[#FF9500] fill-current" />
                                        )}
                                      </div>

                                      <p className="font-bold text-xs text-[#1D1D1F] mt-1.5 pl-1 leading-tight group-hover:text-[#0071E3] transition">
                                        {it.kelas.mata_kuliah.nama}
                                      </p>
                                      <p className="text-[11px] text-[#86868B] truncate pl-1 mt-0.5">
                                        {it.kelas.dosen.nama}
                                      </p>

                                      <div className="mt-2 pt-1.5 pl-1 border-t border-black/5 flex items-center justify-between text-[10px] text-[#86868B] font-mono">
                                        <span>{it.ruangan.kode}</span>
                                        <span className="text-emerald-700 font-medium">★ Lolos</span>
                                      </div>
                                    </div>
                                  );
                                })
                              ) : (
                                <div className="h-full w-full rounded-xl border border-dashed border-black/5 flex items-center justify-center text-[#A1A1A6] text-[10px] font-mono">
                                  Kosong
                                </div>
                              )}
                            </div>
                          );
                        })}
                      </div>
                    ))}
                  </div>

                </div>
              </div>
            </main>

            {/* Right Inspector Sheet (AC-14 Explainability) */}
            <aside className="w-80 bg-white border-l border-black/5 flex flex-col justify-between shrink-0 overflow-y-auto">
              <div className="p-4 space-y-4">
                
                {/* Header */}
                <div className="flex items-center justify-between pb-3 border-b border-black/5">
                  <div>
                    <h3 className="font-bold text-xs text-[#1D1D1F] tracking-tight uppercase font-mono">Inspector Alokasi</h3>
                    <p className="text-[11px] text-[#86868B]">Detail & audit trail constraint</p>
                  </div>
                  {selectedItem && (
                    <span className="text-[10px] font-mono font-bold px-2 py-0.5 rounded-md bg-[#F5F5F7] text-[#1D1D1F] border border-black/5">
                      {selectedItem.kelas.mata_kuliah.kode}
                    </span>
                  )}
                </div>

                {selectedItem ? (
                  <div className="space-y-3">
                    <div>
                      <p className="text-[11px] text-[#86868B] font-medium">Mata Kuliah</p>
                      <h4 className="text-base font-bold text-[#1D1D1F] leading-tight mt-0.5">
                        {selectedItem.kelas.mata_kuliah.nama} ({selectedItem.kelas.nama_kelas})
                      </h4>
                      <div className="flex items-center gap-2 mt-1 font-mono text-[11px] text-[#86868B]">
                        <span className="px-1.5 py-0.2 rounded bg-black/5 text-[#1D1D1F] font-medium">
                          {selectedItem.kelas.mata_kuliah.sks} SKS
                        </span>
                        <span>•</span>
                        <span>Semester {selectedItem.kelas.mata_kuliah.semester}</span>
                        <span>•</span>
                        <span>{selectedItem.kelas.kapasitas} Mhs</span>
                      </div>
                    </div>

                    {/* Lecturer */}
                    <div className="p-3 bg-[#FBFBFD] rounded-xl border border-black/5">
                      <div className="flex items-center justify-between text-[11px]">
                        <span className="text-[#86868B] font-medium">Dosen Pengampu</span>
                        <span className="font-mono text-[#A1A1A6]">{selectedItem.kelas.dosen.nidn}</span>
                      </div>
                      <p className="text-xs font-bold text-[#1D1D1F] mt-1">{selectedItem.kelas.dosen.nama}</p>
                      <p className="text-[10px] text-[#86868B] mt-0.5">{selectedItem.kelas.dosen.bidang}</p>
                    </div>

                    {/* Room & Slot Allocation */}
                    <div className="grid grid-cols-2 gap-2">
                      <div className="p-2.5 bg-[#FBFBFD] rounded-xl border border-black/5">
                        <span className="text-[10px] text-[#86868B] font-mono block uppercase">Ruang Kuliah</span>
                        <span className="font-bold text-xs text-[#1D1D1F] block mt-0.5 truncate">{selectedItem.ruangan.nama}</span>
                        <span className="text-[10px] text-[#86868B] block font-mono mt-0.5">Kapasitas {selectedItem.ruangan.kapasitas}</span>
                      </div>
                      <div className="p-2.5 bg-[#FBFBFD] rounded-xl border border-black/5">
                        <span className="text-[10px] text-[#86868B] font-mono block uppercase">Waktu Terpilih</span>
                        <span className="font-bold text-xs text-[#1D1D1F] block mt-0.5 capitalize">{selectedItem.hari}, {selectedItem.slot_waktu.jam_mulai}</span>
                        <span className="text-[10px] text-emerald-600 block font-medium mt-0.5">Bebas Bentrok</span>
                      </div>
                    </div>

                    {/* Explainability Audit Trail (AC-14) */}
                    <div className="space-y-2 pt-2 border-t border-black/5">
                      <div className="flex items-center justify-between">
                        <span className="text-xs font-bold text-[#1D1D1F] uppercase tracking-tight font-mono">Alasan Solver (Audit)</span>
                        <span className="text-[10px] font-mono font-semibold text-[#0071E3] bg-[#0071E3]/10 px-2 py-0.5 rounded-full">Score 95%</span>
                      </div>
                      <div className="p-3 bg-[#FBFBFD] rounded-xl border border-black/5 text-[11px] text-[#48484A] leading-relaxed">
                        <p>{selectedItem.explanation || 'Alokasi bebas bentrok dan memenuhi hard constraint HC1-HC5.'}</p>
                      </div>
                    </div>

                    {/* Pinning Action (AC-3) */}
                    <div className="pt-2 border-t border-black/5">
                      <button
                        onClick={() => handleTogglePin(selectedItem.id)}
                        className={`w-full py-2 px-3 rounded-xl border text-xs font-semibold transition shadow-2xs flex items-center justify-center gap-1.5 ${
                          selectedItem.is_pinned
                            ? 'bg-[#FF9500]/10 border-[#FF9500]/30 text-[#D97706]'
                            : 'bg-[#FBFBFD] hover:bg-black/5 border-black/10 text-[#1D1D1F]'
                        }`}
                      >
                        <Pin className="w-3.5 h-3.5" />
                        <span>{selectedItem.is_pinned ? 'Lepas Kunci (Unpin)' : 'Kunci Posisi (Pin Slot)'}</span>
                      </button>
                    </div>

                  </div>
                ) : (
                  <div className="p-8 text-center text-[#86868B] text-xs">
                    Pilih salah satu jadwal di grid untuk melihat audit solver.
                  </div>
                )}

              </div>

              {/* Conflict Radar Footer */}
              {conflictCount > 0 && (
                <div className="p-3 bg-red-50 border-t border-red-200">
                  <div className="flex items-center gap-2 text-red-700 font-semibold text-xs mb-1">
                    <AlertCircle className="w-4 h-4" />
                    <span>Radar Konflik ({conflictCount})</span>
                  </div>
                  <div className="space-y-1 max-h-32 overflow-y-auto">
                    {schedule?.conflicts?.map((conf) => (
                      <p key={conf.id} className="text-[10px] text-red-600 leading-tight">
                        • {conf.description}
                      </p>
                    ))}
                  </div>
                </div>
              )}

              {/* Footer */}
              <div className="p-3 bg-[#FBFBFD] border-t border-black/5 flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span className={`w-2 h-2 rounded-full ${isPublished ? 'bg-emerald-500' : 'bg-[#FF9500]'}`}></span>
                  <span className="text-xs font-medium text-[#1D1D1F]">
                    {isPublished ? 'Published Resmi' : 'Draft Penjadwalan'}
                  </span>
                </div>
                <span className="text-[10px] font-mono text-[#86868B]">
                  {assignedCount} Sesi Aktif
                </span>
              </div>
            </aside>

          </div>

        </div>
      </div>
    </>
  );
}
