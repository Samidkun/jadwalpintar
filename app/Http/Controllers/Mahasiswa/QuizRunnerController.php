<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizRunnerController extends Controller
{
    public function show(Request $request, Quiz $quiz, QuizService $service): Response
    {
        $user = $request->user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->firstOrFail();

        $sessionData = $service->startAttempt($quiz, $mahasiswa);

        return Inertia::render('Mahasiswa/QuizRunner', [
            'quiz' => $sessionData['quiz'],
            'soal' => $sessionData['soal'],
            'attempt' => $sessionData['attempt'],
            'mahasiswa' => $mahasiswa,
        ]);
    }

    public function submit(Request $request, Quiz $quiz, QuizService $service): RedirectResponse
    {
        $request->validate([
            'jawaban' => 'required|array',
        ]);

        $user = $request->user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->firstOrFail();

        $attempt = $service->submitAttempt($quiz, $mahasiswa, $request->input('jawaban'));

        return redirect()->back()->with('success', sprintf('Kuis berhasil diselesaikan! Skor Anda: %d.', $attempt->skor));
    }
}
