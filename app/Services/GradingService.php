<?php

namespace App\Services;

use App\Models\Nilai;

class GradingService
{
    private const WEIGHTS = [
        'tugas' => 0.20,
        'quiz' => 0.20,
        'uts' => 0.30,
        'uas' => 0.30,
    ];

    public function calculateGrade(array $komponen): array
    {
        $totalWeight = 0;
        $weightedScore = 0;

        foreach (self::WEIGHTS as $key => $weight) {
            if (isset($komponen[$key])) {
                $totalWeight += $weight;
                $weightedScore += ($komponen[$key] * $weight);
            }
        }

        // Normalize if total weight differs slightly
        $nilaiAkhir = $totalWeight > 0 ? round($weightedScore / $totalWeight, 2) : 0.00;

        if ($nilaiAkhir >= 85.00) {
            $huruf = 'A';
            $bobot = 4.00;
        } elseif ($nilaiAkhir >= 75.00) {
            $huruf = 'B';
            $bobot = 3.00;
        } elseif ($nilaiAkhir >= 60.00) {
            $huruf = 'C';
            $bobot = 2.00;
        } elseif ($nilaiAkhir >= 50.00) {
            $huruf = 'D';
            $bobot = 1.00;
        } else {
            $huruf = 'E';
            $bobot = 0.00;
        }

        return [
            'nilai_akhir' => $nilaiAkhir,
            'huruf' => $huruf,
            'bobot' => $bobot,
        ];
    }

    public function saveGrade(int $kelasId, int $mahasiswaId, array $komponen): Nilai
    {
        $calc = $this->calculateGrade($komponen);

        return Nilai::updateOrCreate(
            [
                'kelas_id' => $kelasId,
                'mahasiswa_id' => $mahasiswaId,
            ],
            [
                'komponen' => $komponen,
                'nilai_akhir' => $calc['nilai_akhir'],
                'huruf' => $calc['huruf'],
                'bobot' => $calc['bobot'],
            ]
        );
    }
}
