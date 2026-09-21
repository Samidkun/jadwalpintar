<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Krs;
use App\Models\TahunAkademik;
use App\Services\KrsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KrsApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $dosen = Dosen::where('user_id', $user->id)->firstOrFail();
        $activeTa = TahunAkademik::where('aktif', true)->firstOrFail();

        $krsList = Krs::with(['mahasiswa.prodi', 'details.kelas.mataKuliah'])
            ->where('tahun_akademik_id', $activeTa->id)
            ->whereHas('mahasiswa', fn ($q) => $q->where('dosen_pa_id', $dosen->id))
            ->get();

        return Inertia::render('Dosen/KrsApprovalList', [
            'dosen' => $dosen,
            'tahunAkademik' => $activeTa,
            'krsList' => $krsList,
        ]);
    }

    public function approve(Request $request, Krs $krs, KrsService $service): RedirectResponse
    {
        $user = $request->user();
        $dosen = Dosen::where('user_id', $user->id)->firstOrFail();

        $service->approve($krs, $dosen, $request->input('catatan'));

        return redirect()->back()->with('success', sprintf('KRS mahasiswa %s berhasil disetujui.', $krs->mahasiswa->nama));
    }

    public function revise(Request $request, Krs $krs): RedirectResponse
    {
        $request->validate([
            'catatan' => 'required|string|max:500',
        ]);

        $krs->update([
            'status' => 'revision',
            'catatan_dosen' => $request->input('catatan'),
        ]);

        return redirect()->back()->with('success', sprintf('Catatan revisi dikirimkan ke mahasiswa %s.', $krs->mahasiswa->nama));
    }
}
