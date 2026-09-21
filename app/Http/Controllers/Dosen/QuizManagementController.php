<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Quiz;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizManagementController extends Controller
{
    public function create(Kelas $kelas): Response
    {
        return Inertia::render('Dosen/QuizCreate', [
            'kelas' => $kelas->load('mataKuliah', 'dosen'),
        ]);
    }

    public function store(Request $request, Kelas $kelas, QuizService $service): RedirectResponse
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'durasi_menit' => 'nullable|integer|min:1',
            'acak_soal' => 'boolean',
            'acak_pilihan' => 'boolean',
            'satu_percobaan' => 'boolean',
            'soal' => 'required|array|min:1',
            'soal.*.teks' => 'required|string',
            'soal.*.gambar' => 'nullable|image|max:2048',
            'soal.*.tipe' => 'required|in:pilgan,isian',
            'soal.*.pilihan' => 'nullable|array',
            'soal.*.jawaban_benar' => 'required|string',
            'soal.*.poin' => 'required|integer|min:1',
        ]);

        $service->createQuiz($kelas->id, $validated);

        return redirect()->back()->with('success', 'Kuis baru berhasil dibuat dan dipublikasikan.');
    }
}
