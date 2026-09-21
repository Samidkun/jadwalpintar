<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Nilai;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Soal;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\GradingService;
use App\Services\QuizService;
use Database\Seeders\AcademicMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GradingAndQuizTest extends TestCase
{
    use RefreshDatabase;

    private User $dosenUser;
    private Dosen $dosen;
    private User $mhsUser1;
    private Mahasiswa $mhs1;
    private User $mhsUser2;
    private Mahasiswa $mhs2;
    private Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AcademicMasterSeeder::class);

        $this->dosenUser = User::where('email', 'hendra@kharisma.ac.id')->firstOrFail();
        $this->dosen = Dosen::where('user_id', $this->dosenUser->id)->firstOrFail();
        $this->kelas = Kelas::where('dosen_id', $this->dosen->id)->firstOrFail();

        $this->mhsUser1 = User::where('email', 'budi@student.kharisma.ac.id')->firstOrFail();
        $this->mhs1 = Mahasiswa::where('user_id', $this->mhsUser1->id)->firstOrFail();

        $this->mhsUser2 = User::where('email', 'aminah@student.kharisma.ac.id')->firstOrFail();
        $this->mhs2 = Mahasiswa::where('user_id', $this->mhsUser2->id)->firstOrFail();
    }

    public function test_automatic_grade_calculation_and_conversion_ac8(): void
    {
        $gradingService = new GradingService();

        // 85 on all components -> 85 total -> A / 4.00
        $resultA = $gradingService->calculateGrade([
            'tugas' => 85,
            'quiz' => 85,
            'uts' => 85,
            'uas' => 85,
        ]);

        $this->assertEquals(85.00, $resultA['nilai_akhir']);
        $this->assertEquals('A', $resultA['huruf']);
        $this->assertEquals(4.00, $resultA['bobot']);

        // Test B: 78 -> B / 3.00
        $resultB = $gradingService->calculateGrade([
            'tugas' => 80,
            'quiz' => 70,
            'uts' => 80,
            'uas' => 80,
        ]);
        $this->assertEquals(78.00, $resultB['nilai_akhir']);
        $this->assertEquals('B', $resultB['huruf']);
        $this->assertEquals(3.00, $resultB['bobot']);

        // Test Dosen inputting grade for student via controller/service
        $nilai = $gradingService->saveGrade($this->kelas->id, $this->mhs1->id, [
            'tugas' => 90,
            'quiz' => 80,
            'uts' => 85,
            'uas' => 90,
        ]);

        $this->assertDatabaseHas('nilai', [
            'kelas_id' => $this->kelas->id,
            'mahasiswa_id' => $this->mhs1->id,
            'huruf' => 'A',
            'bobot' => 4.00,
        ]);
    }

    public function test_dosen_can_create_quiz_with_image_upload_ac9(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('diagram-soal.png', 600, 400);

        $response = $this->actingAs($this->dosenUser)->post("/dosen/kelas/{$this->kelas->id}/quizzes", [
            'judul' => 'Kuis 1: Turunan Parsial',
            'durasi_menit' => 30,
            'soal' => [
                [
                    'teks' => 'Perhatikan grafik berikut. Berapakah gradien garis singgung di titik (2,3)?',
                    'gambar' => $image,
                    'tipe' => 'pilgan',
                    'pilihan' => ['1/2', '2', '3/2', '4'],
                    'jawaban_benar' => '2',
                    'poin' => 25,
                ],
                [
                    'teks' => 'Tentukan turunan pertama dari f(x) = 3x^2 + 5x.',
                    'tipe' => 'isian',
                    'jawaban_benar' => '6x + 5',
                    'poin' => 25,
                ],
            ],
        ]);

        $response->assertRedirect();

        $quiz = Quiz::where('kelas_id', $this->kelas->id)->firstOrFail();
        $this->assertEquals('Kuis 1: Turunan Parsial', $quiz->judul);
        $this->assertCount(2, $quiz->soal);

        $soalWithImage = $quiz->soal->firstWhere('tipe', 'pilgan');
        $this->assertNotNull($soalWithImage->gambar);
        Storage::disk('public')->assertExists($soalWithImage->gambar);
    }

    public function test_shuffles_questions_differently_per_student_ac10(): void
    {
        $quiz = Quiz::create([
            'kelas_id' => $this->kelas->id,
            'judul' => 'Quiz Shuffle Test',
            'durasi_menit' => 15,
            'acak_soal' => true,
            'status' => 'published',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            Soal::create([
                'quiz_id' => $quiz->id,
                'teks' => "Soal nomor $i",
                'tipe' => 'pilgan',
                'pilihan' => ['A', 'B', 'C', 'D'],
                'jawaban_benar' => 'A',
                'poin' => 10,
                'urutan' => $i,
            ]);
        }

        $quizService = new QuizService();
        $attempt1 = $quizService->startAttempt($quiz, $this->mhs1);
        $attempt2 = $quizService->startAttempt($quiz, $this->mhs2);

        $order1 = array_column($attempt1['soal'], 'id');
        $order2 = array_column($attempt2['soal'], 'id');

        // Shuffled order must not be identical for all 10 items
        $this->assertNotEquals($order1, $order2);
    }

    public function test_prevents_second_attempt_when_one_attempt_only_ac12(): void
    {
        $quiz = Quiz::create([
            'kelas_id' => $this->kelas->id,
            'judul' => 'One Shot Quiz',
            'satu_percobaan' => true,
            'status' => 'published',
        ]);

        $soal = Soal::create([
            'quiz_id' => $quiz->id,
            'teks' => '1 + 1 = ?',
            'tipe' => 'pilgan',
            'pilihan' => ['1', '2', '3', '4'],
            'jawaban_benar' => '2',
            'poin' => 100,
            'urutan' => 1,
        ]);

        $quizService = new QuizService();
        $quizService->startAttempt($quiz, $this->mhs1);
        $quizService->submitAttempt($quiz, $this->mhs1, [
            $soal->id => '2',
        ]);

        // Attempt second time should throw or be blocked
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $quizService->startAttempt($quiz, $this->mhs1);
    }

    public function test_auto_grading_calculates_correct_score_ac13(): void
    {
        $quiz = Quiz::create([
            'kelas_id' => $this->kelas->id,
            'judul' => 'Math Auto Grade Test',
            'status' => 'published',
        ]);

        // 5 questions, 20 points each = 100 points total
        $s1 = Soal::create(['quiz_id' => $quiz->id, 'teks' => 'Q1', 'tipe' => 'pilgan', 'pilihan' => ['A', 'B'], 'jawaban_benar' => 'A', 'poin' => 20, 'urutan' => 1]);
        $s2 = Soal::create(['quiz_id' => $quiz->id, 'teks' => 'Q2', 'tipe' => 'pilgan', 'pilihan' => ['A', 'B'], 'jawaban_benar' => 'B', 'poin' => 20, 'urutan' => 2]);
        $s3 = Soal::create(['quiz_id' => $quiz->id, 'teks' => 'Q3', 'tipe' => 'pilgan', 'pilihan' => ['A', 'B'], 'jawaban_benar' => 'A', 'poin' => 20, 'urutan' => 3]);
        $s4 = Soal::create(['quiz_id' => $quiz->id, 'teks' => 'Q4', 'tipe' => 'pilgan', 'pilihan' => ['A', 'B'], 'jawaban_benar' => 'B', 'poin' => 20, 'urutan' => 4]);
        $s5 = Soal::create(['quiz_id' => $quiz->id, 'teks' => 'Q5', 'tipe' => 'isian', 'jawaban_benar' => 'jakarta', 'poin' => 20, 'urutan' => 5]);

        $quizService = new QuizService();
        $quizService->startAttempt($quiz, $this->mhs1);

        // Student gets 4 questions right (Q1, Q2, Q3, Q5 correct; Q4 wrong) -> 80 points
        $attempt = $quizService->submitAttempt($quiz, $this->mhs1, [
            $s1->id => 'A', // correct (+20)
            $s2->id => 'B', // correct (+20)
            $s3->id => 'A', // correct (+20)
            $s4->id => 'A', // wrong (0)
            $s5->id => 'Jakarta', // correct case-insensitive (+20)
        ]);

        $this->assertEquals(80, $attempt->skor);
    }
}
