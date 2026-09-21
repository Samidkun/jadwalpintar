<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Ruangan;
use App\Models\Schedule;
use App\Models\ScheduleConflict;
use App\Models\ScheduleItem;
use App\Models\SlotWaktu;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    private const DAYS = ['senin', 'selasa', 'rabu', 'kamis', 'jumat'];
    private const MAX_BACKTRACK_ITERATIONS = 3000;

    private array $occupiedRooms = [];
    private array $occupiedDosens = [];
    private array $dayCounts = [];
    private int $iterationCount = 0;

    public function generate(int $tahunAkademikId, array $pinnedItems = []): Schedule
    {
        $startTime = microtime(true);

        $classes = Kelas::with(['mataKuliah', 'dosen.timePreferences'])
            ->where('tahun_akademik_id', $tahunAkademikId)
            ->get();

        $rooms = Ruangan::all();
        $slots = SlotWaktu::orderBy('jam_mulai')->get();

        // Initialize tracking state
        $this->occupiedRooms = [];
        $this->occupiedDosens = [];
        $this->dayCounts = array_fill_keys(self::DAYS, 0);
        $this->iterationCount = 0;

        return DB::transaction(function () use ($tahunAkademikId, $classes, $rooms, $slots, $pinnedItems, $startTime) {
            $schedule = Schedule::create([
                'tahun_akademik_id' => $tahunAkademikId,
                'status' => 'draft',
                'metadata' => [
                    'total_kelas' => $classes->count(),
                    'solve_time_ms' => 0,
                    'backtracks' => 0,
                ],
            ]);

            $assignedItems = [];
            $conflicts = [];
            $unassignedClasses = [];

            // 1. Process Pinned Items First (AC-3)
            $pinnedClassIds = [];
            foreach ($pinnedItems as $pin) {
                $kelas = $classes->firstWhere('id', $pin['kelas_id']);
                if (! $kelas) {
                    continue;
                }

                $pinnedClassIds[] = $kelas->id;
                $this->assignSlot(
                    $pin['ruangan_id'],
                    $kelas->dosen_id,
                    $pin['hari'],
                    $pin['slot_waktu_id'],
                    $kelas->id
                );

                $assignedItems[] = [
                    'schedule_id' => $schedule->id,
                    'kelas_id' => $kelas->id,
                    'ruangan_id' => $pin['ruangan_id'],
                    'slot_waktu_id' => $pin['slot_waktu_id'],
                    'hari' => $pin['hari'],
                    'is_pinned' => true,
                    'explanation' => 'Jadwal dikunci secara manual oleh administrator (Pinned).',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Filter out already pinned classes
            foreach ($classes as $kelas) {
                if (! in_array($kelas->id, $pinnedClassIds)) {
                    $unassignedClasses[] = $kelas;
                }
            }

            // 2. Solve unassigned classes using MRV Backtracking
            $solvedAssignments = [];
            $failedClasses = [];

            $this->solveBacktrack(
                $unassignedClasses,
                $rooms,
                $slots,
                $solvedAssignments,
                $failedClasses
            );

            // 3. Format assigned items and generate explanation
            foreach ($solvedAssignments as $assignment) {
                $assignedItems[] = [
                    'schedule_id' => $schedule->id,
                    'kelas_id' => $assignment['kelas']->id,
                    'ruangan_id' => $assignment['room']->id,
                    'slot_waktu_id' => $assignment['slot']->id,
                    'hari' => $assignment['day'],
                    'is_pinned' => false,
                    'explanation' => $this->generateExplanation($assignment),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 4. Format conflicts (AC-2)
            foreach ($failedClasses as $failed) {
                $conflicts[] = [
                    'schedule_id' => $schedule->id,
                    'kelas_id' => $failed['kelas']->id,
                    'type' => $failed['type'],
                    'description' => $failed['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 5. Bulk insert
            if (! empty($assignedItems)) {
                ScheduleItem::insert($assignedItems);
            }

            if (! empty($conflicts)) {
                ScheduleConflict::insert($conflicts);
            }

            $elapsedMs = round((microtime(true) - $startTime) * 1000, 2);

            $schedule->update([
                'metadata' => [
                    'total_kelas' => $classes->count(),
                    'assigned' => count($assignedItems),
                    'conflicts' => count($conflicts),
                    'solve_time_ms' => $elapsedMs,
                    'backtracks' => $this->iterationCount,
                ],
            ]);

            return $schedule->load(['items.kelas.mataKuliah', 'items.kelas.dosen', 'items.ruangan', 'items.slotWaktu', 'conflicts.kelas.mataKuliah']);
        });
    }

    private function solveBacktrack(
        array $remainingClasses,
        Collection $rooms,
        Collection $slots,
        array &$solvedAssignments,
        array &$failedClasses
    ): bool {
        if (empty($remainingClasses)) {
            return true;
        }

        // Apply MRV: compute candidate domain size for each remaining class
        $domains = [];
        foreach ($remainingClasses as $idx => $kelas) {
            $candidates = $this->getCandidatesForClass($kelas, $rooms, $slots);
            $domains[$idx] = [
                'kelas' => $kelas,
                'candidates' => $candidates,
                'count' => count($candidates),
            ];
        }

        // Sort by MRV: smallest candidate domain first
        usort($domains, fn ($a, $b) => $a['count'] <=> $b['count']);

        $current = array_shift($domains);
        $kelas = $current['kelas'];
        $candidates = $current['candidates'];

        // If domain is empty, this class cannot be scheduled without conflict
        if (empty($candidates)) {
            $failedClasses[] = [
                'kelas' => $kelas,
                'type' => 'NO_SLOT_AVAILABLE',
                'description' => sprintf(
                    'Kelas %s (%s) tidak menemukan slot bebas bentrok karena preferensi dosen dan keterbatasan kapasitas ruangan.',
                    $kelas->mataKuliah->nama,
                    $kelas->nama_kelas
                ),
            ];

            // Continue solving the rest
            $remaining = array_column($domains, 'kelas');
            return $this->solveBacktrack($remaining, $rooms, $slots, $solvedAssignments, $failedClasses);
        }

        // Sort candidates by soft constraint score (highest score first)
        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        $nextRemaining = array_column($domains, 'kelas');

        foreach ($candidates as $candidate) {
            $this->iterationCount++;
            if ($this->iterationCount > self::MAX_BACKTRACK_ITERATIONS) {
                // Safeguard against infinite backtrack
                break;
            }

            // Assign
            $this->assignSlot($candidate['room']->id, $kelas->dosen_id, $candidate['day'], $candidate['slot']->id, $kelas->id);
            $solvedAssignments[] = [
                'kelas' => $kelas,
                'room' => $candidate['room'],
                'day' => $candidate['day'],
                'slot' => $candidate['slot'],
                'score' => $candidate['score'],
                'score_details' => $candidate['score_details'],
            ];

            $success = $this->solveBacktrack($nextRemaining, $rooms, $slots, $solvedAssignments, $failedClasses);
            if ($success) {
                return true;
            }

            // Backtrack
            array_pop($solvedAssignments);
            $this->unassignSlot($candidate['room']->id, $kelas->dosen_id, $candidate['day'], $candidate['slot']->id);
        }

        // If all candidates failed in this branch, register conflict
        $failedClasses[] = [
            'kelas' => $kelas,
            'type' => 'BACKTRACK_EXHAUSTED',
            'description' => sprintf(
                'Kelas %s (%s) gagal dialokasikan setelah penelusuran alternatif jadwal.',
                $kelas->mataKuliah->nama,
                $kelas->nama_kelas
            ),
        ];

        return $this->solveBacktrack($nextRemaining, $rooms, $slots, $solvedAssignments, $failedClasses);
    }

    private function getCandidatesForClass(Kelas $kelas, Collection $rooms, Collection $slots): array
    {
        $candidates = [];
        $dosenPrefs = $kelas->dosen->timePreferences ?? collect();

        foreach ($rooms as $room) {
            // HC-3: Room capacity >= class capacity
            if ($room->kapasitas < $kelas->kapasitas) {
                continue;
            }

            // HC-4: Lab matkul must be assigned to lab or campuran room
            if ($kelas->mataKuliah->tipe === 'lab' && ! in_array($room->tipe, ['lab', 'campuran'])) {
                continue;
            }

            foreach (self::DAYS as $day) {
                foreach ($slots as $slot) {
                    // HC-1: Dosen not busy
                    if ($this->isDosenOccupied($kelas->dosen_id, $day, $slot->id)) {
                        continue;
                    }

                    // HC-2: Room not busy
                    if ($this->isRoomOccupied($room->id, $day, $slot->id)) {
                        continue;
                    }

                    // HC-5: Dosen unavailable
                    $pref = $dosenPrefs->first(fn ($p) => $p->hari === $day && $p->slot_waktu_id === $slot->id);
                    if ($pref && $pref->status === 'unavailable') {
                        continue;
                    }

                    // Calculate Soft Constraints Score
                    $score = 50; // base score
                    $details = [];

                    // SC-1: Dosen preference
                    if ($pref) {
                        if ($pref->status === 'preferred') {
                            $score += 20;
                            $details[] = 'Preferensi waktu dosen dipenuhi (+20)';
                        } elseif ($pref->status === 'avoid') {
                            $score -= 20;
                            $details[] = 'Menghindari jam kurang disukai dosen (-20)';
                        }
                    }

                    // SC-2: Right-sized room (penalize excessive capacity waste)
                    $excess = $room->kapasitas - $kelas->kapasitas;
                    if ($excess <= 8) {
                        $score += 15;
                        $details[] = 'Ukuran ruangan tepat sesuai kapasitas (+15)';
                    } elseif ($excess > 25) {
                        $score -= 10;
                    }

                    // SC-3: Day balance
                    $currentDayLoad = $this->dayCounts[$day] ?? 0;
                    if ($currentDayLoad < 3) {
                        $score += 5;
                    }

                    $candidates[] = [
                        'room' => $room,
                        'day' => $day,
                        'slot' => $slot,
                        'score' => $score,
                        'score_details' => $details,
                    ];
                }
            }
        }

        return $candidates;
    }

    private function isRoomOccupied(int $roomId, string $day, int $slotId): bool
    {
        return isset($this->occupiedRooms[$roomId][$day][$slotId]);
    }

    private function isDosenOccupied(int $dosenId, string $day, int $slotId): bool
    {
        return isset($this->occupiedDosens[$dosenId][$day][$slotId]);
    }

    private function assignSlot(int $roomId, int $dosenId, string $day, int $slotId, int $kelasId): void
    {
        $this->occupiedRooms[$roomId][$day][$slotId] = $kelasId;
        $this->occupiedDosens[$dosenId][$day][$slotId] = $kelasId;
        $this->dayCounts[$day] = ($this->dayCounts[$day] ?? 0) + 1;
    }

    private function unassignSlot(int $roomId, int $dosenId, string $day, int $slotId): void
    {
        unset($this->occupiedRooms[$roomId][$day][$slotId]);
        unset($this->occupiedDosens[$dosenId][$day][$slotId]);
        if (isset($this->dayCounts[$day]) && $this->dayCounts[$day] > 0) {
            $this->dayCounts[$day]--;
        }
    }

    private function generateExplanation(array $assignment): string
    {
        $room = $assignment['room'];
        $kelas = $assignment['kelas'];
        $day = ucfirst($assignment['day']);
        $slot = $assignment['slot'];
        $details = $assignment['score_details'];

        $reasons = [
            sprintf('Ditempatkan di hari %s %s pada %s.', $day, $slot->label, $room->nama),
            sprintf('Kapasitas ruangan (%d) memenuhi kuota mahasiswa (%d).', $room->kapasitas, $kelas->kapasitas),
        ];

        if (! empty($details)) {
            $reasons[] = implode(' ', $details);
        }

        return implode(' ', $reasons);
    }
}
