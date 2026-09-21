<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ruangan;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\SlotWaktu;
use App\Models\TahunAkademik;
use App\Services\SchedulingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $activeTa = TahunAkademik::where('aktif', true)->first();

        $schedule = null;
        if ($activeTa) {
            $schedule = Schedule::with([
                'items.kelas.mataKuliah.prodi',
                'items.kelas.dosen',
                'items.ruangan',
                'items.slotWaktu',
                'conflicts.kelas.mataKuliah',
            ])
            ->where('tahun_akademik_id', $activeTa->id)
            ->latest()
            ->first();
        }

        $rooms = Ruangan::orderBy('kode')->get();
        $slots = SlotWaktu::orderBy('jam_mulai')->get();

        return Inertia::render('Admin/ScheduleWorkspace', [
            'tahunAkademik' => $activeTa,
            'schedule' => $schedule,
            'rooms' => $rooms,
            'slots' => $slots,
            'days' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat'],
        ]);
    }

    public function generate(Request $request, SchedulingService $service): RedirectResponse
    {
        $activeTa = TahunAkademik::where('aktif', true)->firstOrFail();

        // Preserve currently pinned items from the latest schedule if available
        $pinnedItems = [];
        $latestSchedule = Schedule::where('tahun_akademik_id', $activeTa->id)->latest()->first();
        if ($latestSchedule) {
            $pinned = $latestSchedule->items()->where('is_pinned', true)->get();
            foreach ($pinned as $p) {
                $pinnedItems[] = [
                    'kelas_id' => $p->kelas_id,
                    'ruangan_id' => $p->ruangan_id,
                    'slot_waktu_id' => $p->slot_waktu_id,
                    'hari' => $p->hari,
                    'is_pinned' => true,
                ];
            }
        }

        $schedule = $service->generate($activeTa->id, $pinnedItems);

        return redirect()->route('admin.schedules.index')
            ->with('success', sprintf(
                'Jadwal perkuliahan berhasil dihitung ulang (%d kelas dialokasikan, %d bentrok) dalam %sms.',
                $schedule->metadata['assigned'] ?? 0,
                $schedule->metadata['conflicts'] ?? 0,
                $schedule->metadata['solve_time_ms'] ?? 0
            ));
    }

    public function togglePin(ScheduleItem $item): RedirectResponse
    {
        $item->update([
            'is_pinned' => ! $item->is_pinned,
            'explanation' => ! $item->is_pinned ? 'Jadwal dikunci secara manual oleh administrator (Pinned).' : null,
        ]);

        return redirect()->route('admin.schedules.index')->with('success', 'Status kunci jadwal berhasil diperbarui.');
    }

    public function publish(Schedule $schedule): RedirectResponse
    {
        $schedule->update(['status' => 'published']);

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal resmi berhasil dipublikasikan untuk seluruh sivitas akademika.');
    }
}
