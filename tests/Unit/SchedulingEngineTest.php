<?php

namespace Tests\Unit;

use App\Models\Dosen;
use App\Models\DosenTimePreference;
use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\Schedule;
use App\Models\ScheduleConflict;
use App\Models\ScheduleItem;
use App\Models\SlotWaktu;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\SchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulingEngineTest extends TestCase
{
    use RefreshDatabase;

    private TahunAkademik $ta;
    private Ruangan $r1;
    private Ruangan $r2;
    private Ruangan $rLab;
    private SlotWaktu $s1;
    private SlotWaktu $s2;
    private Dosen $dosen1;
    private Dosen $dosen2;
    private Prodi $prodi;

    protected function setUp(): void
    {
        parent::setUp();

        $fakultas = Fakultas::create(['kode' => 'F1', 'nama' => 'Fakultas Teknik']);
        $this->prodi = Prodi::create(['kode' => 'TI', 'nama' => 'Teknik Informatika', 'fakultas_id' => $fakultas->id]);
        $this->ta = TahunAkademik::create(['tahun' => '2025/2026', 'semester' => 'ganjil', 'aktif' => true]);

        $this->r1 = Ruangan::create(['kode' => 'R101', 'nama' => 'Ruang 101', 'kapasitas' => 40, 'tipe' => 'teori']);
        $this->r2 = Ruangan::create(['kode' => 'R102', 'nama' => 'Ruang 102', 'kapasitas' => 30, 'tipe' => 'teori']);
        $this->rLab = Ruangan::create(['kode' => 'LAB1', 'nama' => 'Lab Komputer', 'kapasitas' => 35, 'tipe' => 'lab']);

        $this->s1 = SlotWaktu::create(['jam_mulai' => '08:00', 'jam_selesai' => '10:30', 'label' => 'Slot 1']);
        $this->s2 = SlotWaktu::create(['jam_mulai' => '10:45', 'jam_selesai' => '13:15', 'label' => 'Slot 2']);

        $u1 = User::create(['name' => 'Dosen 1', 'email' => 'd1@test.com', 'password' => bcrypt('password')]);
        $this->dosen1 = Dosen::create(['user_id' => $u1->id, 'nidn' => '11111', 'nama' => 'Dosen Satu', 'prodi_id' => $this->prodi->id]);

        $u2 = User::create(['name' => 'Dosen 2', 'email' => 'd2@test.com', 'password' => bcrypt('password')]);
        $this->dosen2 = Dosen::create(['user_id' => $u2->id, 'nidn' => '22222', 'nama' => 'Dosen Dua', 'prodi_id' => $this->prodi->id]);
    }

    public function test_can_solve_schedule_without_hard_constraint_violations_ac1(): void
    {
        $mkTeori = MataKuliah::create(['kode' => 'MK01', 'nama' => 'Teori Algoritma', 'sks' => 3, 'semester' => 1, 'tipe' => 'teori', 'prodi_id' => $this->prodi->id]);
        $mkLab = MataKuliah::create(['kode' => 'MK02', 'nama' => 'Praktikum Komputer', 'sks' => 3, 'semester' => 1, 'tipe' => 'lab', 'prodi_id' => $this->prodi->id]);

        $k1 = Kelas::create(['mata_kuliah_id' => $mkTeori->id, 'dosen_id' => $this->dosen1->id, 'tahun_akademik_id' => $this->ta->id, 'nama_kelas' => 'A', 'kapasitas' => 35]);
        $k2 = Kelas::create(['mata_kuliah_id' => $mkLab->id, 'dosen_id' => $this->dosen2->id, 'tahun_akademik_id' => $this->ta->id, 'nama_kelas' => 'A', 'kapasitas' => 30]);

        $service = new SchedulingService();
        $schedule = $service->generate($this->ta->id);

        $this->assertInstanceOf(Schedule::class, $schedule);
        $this->assertEquals('draft', $schedule->status);
        $this->assertCount(2, $schedule->items);
        $this->assertCount(0, $schedule->conflicts);

        // Verify HC-4: Lab matkul must be assigned to Lab room
        $labItem = $schedule->items->firstWhere('kelas_id', $k2->id);
        $this->assertNotNull($labItem);
        $this->assertEquals($this->rLab->id, $labItem->ruangan_id);

        // Verify explanation exists
        $this->assertNotEmpty($labItem->explanation);
    }

    public function test_pinned_class_remains_unmoved_ac3(): void
    {
        $mk = MataKuliah::create(['kode' => 'MK03', 'nama' => 'Kalkulus', 'sks' => 3, 'semester' => 1, 'tipe' => 'teori', 'prodi_id' => $this->prodi->id]);
        $k = Kelas::create(['mata_kuliah_id' => $mk->id, 'dosen_id' => $this->dosen1->id, 'tahun_akademik_id' => $this->ta->id, 'nama_kelas' => 'A', 'kapasitas' => 30]);

        $pinnedItem = [
            'kelas_id' => $k->id,
            'ruangan_id' => $this->r1->id,
            'slot_waktu_id' => $this->s1->id,
            'hari' => 'senin',
            'is_pinned' => true,
        ];

        $service = new SchedulingService();
        $schedule = $service->generate($this->ta->id, [$pinnedItem]);

        $item = $schedule->items->firstWhere('kelas_id', $k->id);
        $this->assertNotNull($item);
        $this->assertEquals('senin', $item->hari);
        $this->assertEquals($this->s1->id, $item->slot_waktu_id);
        $this->assertEquals($this->r1->id, $item->ruangan_id);
        $this->assertTrue($item->is_pinned);
    }

    public function test_unsatisfiable_constraints_record_conflicts_ac2(): void
    {
        // 1 single room, 1 single slot, 1 day, but 3 classes
        $mk1 = MataKuliah::create(['kode' => 'MK10', 'nama' => 'MK Sepuluh', 'sks' => 3, 'semester' => 1, 'tipe' => 'teori', 'prodi_id' => $this->prodi->id]);
        $mk2 = MataKuliah::create(['kode' => 'MK20', 'nama' => 'MK Dua Puluh', 'sks' => 3, 'semester' => 1, 'tipe' => 'teori', 'prodi_id' => $this->prodi->id]);

        // Dosen 1 teaches both, and Dosen 1 is unavailable on all slots except Senin Slot 1
        DosenTimePreference::create(['dosen_id' => $this->dosen1->id, 'hari' => 'selasa', 'slot_waktu_id' => $this->s1->id, 'status' => 'unavailable']);
        DosenTimePreference::create(['dosen_id' => $this->dosen1->id, 'hari' => 'selasa', 'slot_waktu_id' => $this->s2->id, 'status' => 'unavailable']);
        DosenTimePreference::create(['dosen_id' => $this->dosen1->id, 'hari' => 'rabu', 'slot_waktu_id' => $this->s1->id, 'status' => 'unavailable']);
        DosenTimePreference::create(['dosen_id' => $this->dosen1->id, 'hari' => 'rabu', 'slot_waktu_id' => $this->s2->id, 'status' => 'unavailable']);
        DosenTimePreference::create(['dosen_id' => $this->dosen1->id, 'hari' => 'kamis', 'slot_waktu_id' => $this->s1->id, 'status' => 'unavailable']);
        DosenTimePreference::create(['dosen_id' => $this->dosen1->id, 'hari' => 'kamis', 'slot_waktu_id' => $this->s2->id, 'status' => 'unavailable']);
        DosenTimePreference::create(['dosen_id' => $this->dosen1->id, 'hari' => 'jumat', 'slot_waktu_id' => $this->s1->id, 'status' => 'unavailable']);
        DosenTimePreference::create(['dosen_id' => $this->dosen1->id, 'hari' => 'jumat', 'slot_waktu_id' => $this->s2->id, 'status' => 'unavailable']);
        DosenTimePreference::create(['dosen_id' => $this->dosen1->id, 'hari' => 'senin', 'slot_waktu_id' => $this->s2->id, 'status' => 'unavailable']);
        // Only Senin Slot 1 is available for Dosen 1

        $k1 = Kelas::create(['mata_kuliah_id' => $mk1->id, 'dosen_id' => $this->dosen1->id, 'tahun_akademik_id' => $this->ta->id, 'nama_kelas' => 'A', 'kapasitas' => 30]);
        $k2 = Kelas::create(['mata_kuliah_id' => $mk2->id, 'dosen_id' => $this->dosen1->id, 'tahun_akademik_id' => $this->ta->id, 'nama_kelas' => 'B', 'kapasitas' => 30]);

        $service = new SchedulingService();
        $schedule = $service->generate($this->ta->id);

        // One should be placed, the other should be in conflicts
        $this->assertEquals(1, $schedule->items->count());
        $this->assertEquals(1, $schedule->conflicts->count());
        $this->assertStringContainsString('tidak menemukan slot', $schedule->conflicts->first()->description);
    }
}
