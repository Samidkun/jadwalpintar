<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\Nilai;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KrsService
{
    public function calculateMaxSks(Mahasiswa $mahasiswa): int
    {
        // Fetch all grades for this student
        $allNilai = Nilai::with('kelas.mataKuliah')
            ->where('mahasiswa_id', $mahasiswa->id)
            ->get();

        if ($allNilai->isEmpty()) {
            return 20; // Default freshman / first semester allowance
        }

        $totalSks = 0;
        $totalPoin = 0;

        foreach ($allNilai as $n) {
            $sks = $n->kelas->mataKuliah->sks;
            $totalSks += $sks;
            $totalPoin += ($sks * (float) $n->bobot);
        }

        $ips = $totalSks > 0 ? round($totalPoin / $totalSks, 2) : 0.00;

        if ($ips >= 3.00) {
            return 24;
        } elseif ($ips >= 2.50) {
            return 20;
        } elseif ($ips >= 2.00) {
            return 18;
        }

        return 15;
    }

    public function validateAndSubmit(Mahasiswa $mahasiswa, array $kelasIds, TahunAkademik $ta): Krs
    {
        if (empty($kelasIds)) {
            throw ValidationException::withMessages([
                'kelas_ids' => 'Pilih minimal satu mata kuliah untuk mengisi KRS.',
            ]);
        }

        $selectedClasses = Kelas::with('mataKuliah.prasyarat')
            ->whereIn('id', $kelasIds)
            ->get();

        // 1. Validate SKS Limit (AC-4)
        $maxSks = $this->calculateMaxSks($mahasiswa);
        $totalSks = $selectedClasses->sum(fn ($k) => $k->mataKuliah->sks);

        if ($totalSks > $maxSks) {
            throw ValidationException::withMessages([
                'kelas_ids' => sprintf(
                    'Total SKS yang dipilih (%d SKS) melebihi batas hak SKS Anda (%d SKS).',
                    $totalSks,
                    $maxSks
                ),
            ]);
        }

        // 2. Validate Prerequisites (AC-6)
        $passedMkIds = Nilai::where('mahasiswa_id', $mahasiswa->id)
            ->where('huruf', '!=', 'E')
            ->where('nilai_akhir', '>=', 55)
            ->join('kelas', 'nilai.kelas_id', '=', 'kelas.id')
            ->pluck('kelas.mata_kuliah_id')
            ->toArray();

        foreach ($selectedClasses as $kelas) {
            foreach ($kelas->mataKuliah->prasyarat as $prasyarat) {
                if (! in_array($prasyarat->id, $passedMkIds)) {
                    throw ValidationException::withMessages([
                        'kelas_ids' => sprintf(
                            'Anda belum lulus mata kuliah prasyarat "%s (%s)" untuk mengambil mata kuliah "%s".',
                            $prasyarat->nama,
                            $prasyarat->kode,
                            $kelas->mataKuliah->nama
                        ),
                    ]);
                }
            }
        }

        // 3. Validate Schedule Collisions (AC-5)
        $latestSchedule = Schedule::where('tahun_akademik_id', $ta->id)->latest()->first();
        if ($latestSchedule) {
            $scheduleItems = ScheduleItem::where('schedule_id', $latestSchedule->id)
                ->whereIn('kelas_id', $kelasIds)
                ->get();

            $occupiedSlots = [];
            foreach ($scheduleItems as $item) {
                $slotKey = "{$item->hari}_{$item->slot_waktu_id}";
                if (isset($occupiedSlots[$slotKey])) {
                    $clashClass = $selectedClasses->firstWhere('id', $item->kelas_id);
                    $existingClass = $selectedClasses->firstWhere('id', $occupiedSlots[$slotKey]);

                    throw ValidationException::withMessages([
                        'kelas_ids' => sprintf(
                            'Jadwal bentrok pada hari %s: kelas "%s" bertabrakan dengan kelas "%s". Silakan pilih kelas paralel lain.',
                            ucfirst($item->hari),
                            $clashClass?->mataKuliah->nama,
                            $existingClass?->mataKuliah->nama
                        ),
                    ]);
                }
                $occupiedSlots[$slotKey] = $item->kelas_id;
            }
        }

        // 4. Save KRS (AC-4, AC-5, AC-6 valid)
        return DB::transaction(function () use ($mahasiswa, $ta, $kelasIds) {
            $krs = Krs::updateOrCreate(
                [
                    'mahasiswa_id' => $mahasiswa->id,
                    'tahun_akademik_id' => $ta->id,
                ],
                [
                    'status' => 'submitted',
                    'catatan_dosen' => null,
                ]
            );

            $krs->details()->delete();

            $details = array_map(fn ($id) => [
                'krs_id' => $krs->id,
                'kelas_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ], $kelasIds);

            KrsDetail::insert($details);

            return $krs;
        });
    }

    public function approve(Krs $krs, Dosen $dosen, ?string $catatan = null): void
    {
        $krs->update([
            'status' => 'approved',
            'catatan_dosen' => $catatan,
        ]);
    }
}
