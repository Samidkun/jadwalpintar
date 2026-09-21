<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use App\Services\KrsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KrsController extends Controller
{
    public function index(Request $request, KrsService $krsService): Response
    {
        $user = $request->user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->firstOrFail();
        $activeTa = TahunAkademik::where('aktif', true)->firstOrFail();

        $maxSks = $krsService->calculateMaxSks($mahasiswa);

        // Fetch classes offered this semester for student's prodi
        $availableClasses = Kelas::with(['mataKuliah.prasyarat', 'dosen'])
            ->where('tahun_akademik_id', $activeTa->id)
            ->whereHas('mataKuliah', fn ($q) => $q->where('prodi_id', $mahasiswa->prodi_id))
            ->get();

        $currentKrs = Krs::with(['details.kelas.mataKuliah', 'details.kelas.dosen'])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where('tahun_akademik_id', $activeTa->id)
            ->first();

        return Inertia::render('Mahasiswa/KrsPortal', [
            'mahasiswa' => $mahasiswa->load('prodi', 'dosenPa'),
            'tahunAkademik' => $activeTa,
            'maxSks' => $maxSks,
            'availableClasses' => $availableClasses,
            'currentKrs' => $currentKrs,
        ]);
    }

    public function store(Request $request, KrsService $krsService): RedirectResponse
    {
        $request->validate([
            'kelas_ids' => 'required|array|min:1',
            'kelas_ids.*' => 'integer|exists:kelas,id',
        ]);

        $user = $request->user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->firstOrFail();
        $activeTa = TahunAkademik::where('aktif', true)->firstOrFail();

        $krsService->validateAndSubmit($mahasiswa, $request->input('kelas_ids'), $activeTa);

        return redirect()->back()->with('success', 'Rencana studi berhasil diajukan ke Dosen Pembimbing Akademik.');
    }
}
