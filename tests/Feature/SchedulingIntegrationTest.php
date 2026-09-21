<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\DosenTimePreference;
use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\Schedule;
use App\Models\SlotWaktu;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\SchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stress_solve_30_classes_has_zero_collisions_ac1(): void
    {
        $fakultas = Fakultas::create(['kode' => 'FTIK', 'nama' => 'Fakultas Teknologi Informasi']);
        $prodi1 = Prodi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'fakultas_id' => $fakultas->id]);
        $prodi2 = Prodi::create(['kode' => 'SI', 'nama' => 'Sistem Informasi', 'fakultas_id' => $fakultas->id]);

        $ta = TahunAkademik::create(['tahun' => '2025/2026', 'semester' => 'ganjil', 'aktif' => true]);

        // 6 Rooms (4 theory + 2 labs)
        $rooms = [];
        for ($r = 1; $r <= 4; $r++) {
            $rooms[] = Ruangan::create(['kode' => "R$r", 'nama' => "Ruang Teori $r", 'kapasitas' => 45, 'tipe' => 'teori']);
        }
        for ($l = 1; $l <= 2; $l++) {
            $rooms[] = Ruangan::create(['kode' => "LAB$l", 'nama' => "Lab Komputer $l", 'kapasitas' => 35, 'tipe' => 'lab']);
        }

        // 4 Slots
        $slots = [];
        $timePairs = [
            ['08:00', '10:30'],
            ['10:45', '13:15'],
            ['13:30', '16:00'],
            ['16:15', '18:45'],
        ];
        foreach ($timePairs as $idx => $t) {
            $slots[] = SlotWaktu::create([
                'jam_mulai' => $t[0],
                'jam_selesai' => $t[1],
                'label' => 'Slot '.($idx + 1),
            ]);
        }

        // 10 Dosen
        $dosens = [];
        for ($d = 1; $d <= 10; $d++) {
            $u = User::create(['name' => "Dosen $d", 'email' => "dosen$d@kharisma.ac.id", 'password' => bcrypt('password')]);
            $dosens[] = Dosen::create([
                'user_id' => $u->id,
                'nidn' => "NIDN$d",
                'nama' => "Dosen $d, M.Kom.",
                'prodi_id' => ($d % 2 === 0) ? $prodi1->id : $prodi2->id,
            ]);
        }

        // Inject some realistic time preferences (e.g. Dosen 1 avoids sore/malam)
        DosenTimePreference::create(['dosen_id' => $dosens[0]->id, 'hari' => 'jumat', 'slot_waktu_id' => $slots[3]->id, 'status' => 'unavailable']);
        DosenTimePreference::create(['dosen_id' => $dosens[1]->id, 'hari' => 'senin', 'slot_waktu_id' => $slots[0]->id, 'status' => 'preferred']);

        // 30 Classes (22 theory + 8 lab)
        for ($c = 1; $c <= 30; $c++) {
            $isLab = ($c % 4 === 0);
            $mk = MataKuliah::create([
                'kode' => "MK$c",
                'nama' => "Mata Kuliah $c",
                'sks' => 3,
                'semester' => ($c % 6) + 1,
                'tipe' => $isLab ? 'lab' : 'teori',
                'prodi_id' => ($c % 2 === 0) ? $prodi1->id : $prodi2->id,
            ]);

            $dosen = $dosens[($c - 1) % count($dosens)];

            Kelas::create([
                'mata_kuliah_id' => $mk->id,
                'dosen_id' => $dosen->id,
                'tahun_akademik_id' => $ta->id,
                'nama_kelas' => 'A',
                'kapasitas' => $isLab ? 30 : 40,
            ]);
        }

        $service = new SchedulingService();
        $schedule = $service->generate($ta->id);

        $this->assertInstanceOf(Schedule::class, $schedule);
        $this->assertEquals(30, $schedule->items->count());
        $this->assertEquals(0, $schedule->conflicts->count());

        // Hard Constraint Verification:
        // 1. No two classes share the same room at the same day & slot (HC-2)
        $roomOccupancy = [];
        foreach ($schedule->items as $item) {
            $key = "{$item->ruangan_id}_{$item->hari}_{$item->slot_waktu_id}";
            $this->assertArrayNotHasKey($key, $roomOccupancy, "Double booked room detected: $key");
            $roomOccupancy[$key] = $item->id;
        }

        // 2. No lecturer teaches two classes at the same day & slot (HC-1)
        $dosenOccupancy = [];
        foreach ($schedule->items as $item) {
            $dosenId = $item->kelas->dosen_id;
            $key = "{$dosenId}_{$item->hari}_{$item->slot_waktu_id}";
            $this->assertArrayNotHasKey($key, $dosenOccupancy, "Lecturer double booking detected: $key");
            $dosenOccupancy[$key] = $item->id;
        }

        // 3. Lab matkul allocated to lab room (HC-4)
        foreach ($schedule->items as $item) {
            if ($item->kelas->mataKuliah->tipe === 'lab') {
                $this->assertContains($item->ruangan->tipe, ['lab', 'campuran']);
            }
        }
    }
}
