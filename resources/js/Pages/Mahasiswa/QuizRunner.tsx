import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Clock, AlertTriangle, CheckCircle, ArrowRight, ShieldAlert } from 'lucide-react';

interface Soal {
  id: number;
  teks: string;
  gambar?: string | null;
  tipe: 'pilgan' | 'isian';
  pilihan?: string[] | null;
  poin: number;
}

interface Props {
  quiz: {
    id: number;
    judul: string;
    durasi_menit: number | null;
  };
  soal: Soal[];
  attempt: {
    id: number;
    skor: number;
    submitted_at: string | null;
  };
  mahasiswa: {
    nama: string;
    nim: string;
  };
}

export default function QuizRunner({ quiz, soal, attempt, mahasiswa }: Props) {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [jawaban, setJawaban] = useState<Record<number, string>>({});
  const [currentAnswer, setCurrentAnswer] = useState('');
  
  // 60 seconds per question speed-bump timer
  const [timer, setTimer] = useState<number>(60);
  const [isFinished, setIsFinished] = useState(Boolean(attempt.submitted_at));
  const [finalScore, setFinalScore] = useState<number>(attempt.skor || 0);

  const activeSoal = soal[currentIndex];

  // Timer countdown effect per question (AC-11)
  useEffect(() => {
    if (isFinished || !activeSoal) return;

    setTimer(60); // Reset to 60s for new question

    const interval = setInterval(() => {
      setTimer((prev) => {
        if (prev <= 1) {
          // Timeout reached: auto advance (AC-11)
          handleNext(true);
          return 60;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(interval);
  }, [currentIndex, isFinished]);

  const handleNext = (autoAdvance = false) => {
    if (!activeSoal) return;

    const updated = {
      ...jawaban,
      [activeSoal.id]: autoAdvance ? (currentAnswer || '') : currentAnswer,
    };
    setJawaban(updated);
    setCurrentAnswer('');

    if (currentIndex < soal.length - 1) {
      setCurrentIndex((prev) => prev + 1);
    } else {
      // Last question reached: submit
      submitQuiz(updated);
    }
  };

  const submitQuiz = (finalJawaban: Record<number, string>) => {
    router.post(
      `/mahasiswa/quizzes/${quiz.id}/submit`,
      { jawaban: finalJawaban },
      {
        onSuccess: (page: any) => {
          setIsFinished(true);
          // If flash or props update
          if (page.props?.attempt?.skor !== undefined) {
            setFinalScore(page.props.attempt.skor);
          }
        },
      }
    );
  };

  return (
    <>
      <Head title={`Pengerjaan Kuis: ${quiz.judul}`} />

      <div
        onCopy={(e) => e.preventDefault()}
        onContextMenu={(e) => e.preventDefault()}
        className="min-h-screen bg-[#F5F5F7] text-[#1D1D1F] p-4 md:p-6 flex flex-col justify-between select-none antialiased"
      >
        <div className="max-w-2xl mx-auto w-full space-y-6">
          
          {/* Header */}
          <header className="flex items-center justify-between pb-4 border-b border-black/5">
            <div>
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-[#0071E3] animate-ping"></span>
                <h1 className="font-bold text-sm text-[#1D1D1F]">{quiz.judul}</h1>
              </div>
              <p className="text-[11px] text-[#86868B] font-mono mt-0.5">
                Peserta: {mahasiswa.nama} ({mahasiswa.nim})
              </p>
            </div>

            {!isFinished && (
              <div className="flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-black/10 font-mono text-xs font-bold shadow-2xs">
                <Clock className={`w-3.5 h-3.5 ${timer < 15 ? 'text-red-500 animate-pulse' : 'text-[#86868B]'}`} />
                <span className={timer < 15 ? 'text-red-600' : 'text-[#1D1D1F]'}>{timer}s</span>
              </div>
            )}
          </header>

          {/* Body */}
          {isFinished ? (
            <div className="p-8 bg-white rounded-2xl border border-black/5 shadow-apple-sm text-center space-y-4">
              <div className="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto border border-emerald-100">
                <CheckCircle className="w-6 h-6" />
              </div>
              <h2 className="text-xl font-bold text-[#1D1D1F]">Kuis Selesai!</h2>
              <p className="text-xs text-[#86868B] max-w-sm mx-auto">
                Jawaban Anda telah terekam di sistem akademik. Hasil evaluasi otomatis telah tersimpan.
              </p>

              <div className="p-4 bg-[#FBFBFD] rounded-xl border border-black/5 inline-block font-mono">
                <span className="text-xs text-[#86868B] block">SKOR PEROLEHAN</span>
                <span className="text-3xl font-extrabold text-[#0071E3]">{finalScore} / 100</span>
              </div>

              <div className="pt-4">
                <Link
                  href="/mahasiswa/krs"
                  className="px-4 py-2 rounded-xl bg-[#0071E3] text-white text-xs font-semibold hover:bg-[#0077ED] transition"
                >
                  Kembali ke Portal Akademik
                </Link>
              </div>
            </div>
          ) : (
            <div className="p-6 bg-white rounded-2xl border border-black/5 shadow-apple-sm space-y-5">
              
              {/* Question Index Progress */}
              <div className="flex items-center justify-between text-xs font-mono text-[#86868B] border-b border-black/5 pb-3">
                <span>Soal {currentIndex + 1} dari {soal.length}</span>
                <span className="text-emerald-700 font-bold">{activeSoal.poin} Poin</span>
              </div>

              {/* Media Image Attachment if available (AC-9) */}
              {activeSoal.gambar && (
                <div className="rounded-xl overflow-hidden border border-black/5 bg-[#FBFBFD] flex justify-center p-2">
                  <img
                    src={`/storage/${activeSoal.gambar}`}
                    alt="Lampiran Soal"
                    className="max-h-64 object-contain rounded-lg"
                  />
                </div>
              )}

              {/* Question Text */}
              <div className="text-sm font-semibold text-[#1D1D1F] leading-relaxed">
                {activeSoal.teks}
              </div>

              {/* Answer Input */}
              {activeSoal.tipe === 'pilgan' && activeSoal.pilihan ? (
                <div className="space-y-2 pt-2">
                  {activeSoal.pilihan.map((opsi, idx) => (
                    <div
                      key={idx}
                      onClick={() => setCurrentAnswer(opsi)}
                      className={`p-3 rounded-xl border text-xs cursor-pointer transition flex items-center gap-3 ${
                        currentAnswer === opsi
                          ? 'border-[#0071E3] bg-[#0071E3]/5 text-[#0071E3] font-semibold'
                          : 'border-black/5 bg-[#FBFBFD] hover:bg-black/[0.03] text-[#1D1D1F]'
                      }`}
                    >
                      <span className="w-5 h-5 rounded-full border border-black/20 flex items-center justify-center font-mono text-[10px]">
                        {String.fromCharCode(65 + idx)}
                      </span>
                      <span>{opsi}</span>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="pt-2">
                  <label className="text-xs font-medium text-[#86868B] block mb-1">Ketikkan Jawaban Anda:</label>
                  <input
                    type="text"
                    value={currentAnswer}
                    onChange={(e) => setCurrentAnswer(e.target.value)}
                    placeholder="Jawaban singkat..."
                    className="w-full text-xs p-3 rounded-xl border border-black/10 bg-[#FBFBFD] focus:outline-none focus:ring-1 focus:ring-[#0071E3]"
                  />
                </div>
              )}

              {/* Anti-cheat speed-bump notice */}
              <div className="flex items-center gap-1.5 text-[11px] text-[#86868B] pt-2">
                <ShieldAlert className="w-3.5 h-3.5 text-[#FF9500]" />
                <span>Timer per soal aktif. Copy-paste dinonaktifkan untuk menjaga integritas kuis.</span>
              </div>

              {/* Action Button */}
              <div className="pt-3 border-t border-black/5 flex justify-end">
                <button
                  type="button"
                  onClick={() => handleNext(false)}
                  className="px-4 py-2 rounded-xl text-xs font-semibold bg-[#0071E3] text-white hover:bg-[#0077ED] transition flex items-center gap-1.5 active:scale-[0.98]"
                >
                  <span>{currentIndex === soal.length - 1 ? 'Selesaikan Kuis' : 'Soal Berikutnya'}</span>
                  <ArrowRight className="w-3.5 h-3.5" />
                </button>
              </div>

            </div>
          )}

        </div>
      </div>
    </>
  );
}
