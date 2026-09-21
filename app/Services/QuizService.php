<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Soal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuizService
{
    public function createQuiz(int $kelasId, array $data): Quiz
    {
        return DB::transaction(function () use ($kelasId, $data) {
            $quiz = Quiz::create([
                'kelas_id' => $kelasId,
                'judul' => $data['judul'],
                'durasi_menit' => $data['durasi_menit'] ?? null,
                'acak_soal' => $data['acak_soal'] ?? true,
                'acak_pilihan' => $data['acak_pilihan'] ?? true,
                'satu_percobaan' => $data['satu_percobaan'] ?? true,
                'status' => $data['status'] ?? 'published',
            ]);

            if (! empty($data['soal'])) {
                foreach ($data['soal'] as $index => $s) {
                    $gambarPath = null;
                    if (isset($s['gambar']) && $s['gambar'] instanceof UploadedFile) {
                        $gambarPath = $s['gambar']->store('quizzes', 'public');
                    }

                    Soal::create([
                        'quiz_id' => $quiz->id,
                        'teks' => $s['teks'],
                        'gambar' => $gambarPath,
                        'tipe' => $s['tipe'],
                        'pilihan' => $s['pilihan'] ?? null,
                        'jawaban_benar' => $s['jawaban_benar'],
                        'poin' => $s['poin'] ?? 10,
                        'urutan' => $index + 1,
                    ]);
                }
            }

            return $quiz->load('soal');
        });
    }

    public function startAttempt(Quiz $quiz, Mahasiswa $mahasiswa): array
    {
        // 1. Check one attempt rule (AC-12)
        $existing = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereNotNull('submitted_at')
            ->first();

        if ($quiz->satu_percobaan && $existing) {
            throw new AuthorizationException('Anda sudah mengerjakan kuis ini (Aturan Satu Percobaan).');
        }

        $attempt = QuizAttempt::firstOrCreate(
            [
                'quiz_id' => $quiz->id,
                'mahasiswa_id' => $mahasiswa->id,
            ],
            [
                'started_at' => now(),
                'jawaban' => [],
                'skor' => 0,
            ]
        );

        $soalList = $quiz->soal()->get()->toArray();

        // 2. Shuffle questions per student (AC-10)
        if ($quiz->acak_soal) {
            // Seed with student id + quiz id to be uniquely shuffled per student
            $seed = crc32("{$quiz->id}_{$mahasiswa->id}");
            mt_srand($seed);

            for ($i = count($soalList) - 1; $i > 0; $i--) {
                $j = mt_rand(0, $i);
                $temp = $soalList[$i];
                $soalList[$i] = $soalList[$j];
                $soalList[$j] = $temp;
            }
        }

        // 3. Shuffle choices per student and hide answer keys
        foreach ($soalList as &$s) {
            unset($s['jawaban_benar']); // Never send answer key to client!

            if ($quiz->acak_pilihan && ! empty($s['pilihan']) && is_array($s['pilihan'])) {
                shuffle($s['pilihan']);
            }
        }

        return [
            'attempt' => $attempt,
            'quiz' => [
                'id' => $quiz->id,
                'judul' => $quiz->judul,
                'durasi_menit' => $quiz->durasi_menit,
            ],
            'soal' => $soalList,
        ];
    }

    public function submitAttempt(Quiz $quiz, Mahasiswa $mahasiswa, array $jawaban): QuizAttempt
    {
        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->firstOrFail();

        $soalList = $quiz->soal()->get();
        $totalSkor = 0;

        // Auto-grading (AC-13)
        foreach ($soalList as $soal) {
            $studentAnswer = $jawaban[$soal->id] ?? null;
            if ($studentAnswer === null) {
                continue;
            }

            if ($soal->tipe === 'pilgan') {
                if (trim((string) $studentAnswer) === trim((string) $soal->jawaban_benar)) {
                    $totalSkor += $soal->poin;
                }
            } elseif ($soal->tipe === 'isian') {
                if (strtolower(trim((string) $studentAnswer)) === strtolower(trim((string) $soal->jawaban_benar))) {
                    $totalSkor += $soal->poin;
                }
            }
        }

        $attempt->update([
            'jawaban' => $jawaban,
            'skor' => $totalSkor,
            'submitted_at' => now(),
        ]);

        return $attempt;
    }
}
