<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Nilai;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\SlotWaktu;
use App\Models\TahunAkademik;
use App\Models\User;
use Database\Seeders\AcademicMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KrsPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $mhsUser;
    private Mahasiswa $mhs;
    private Dosen $dosenPa;
    private TahunAkademik $ta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AcademicMasterSeeder::class);

        $this->mhsUser = User::where('email', 'budi@student.kharisma.ac.id')->firstOrFail();
        $this->mhs = Mahasiswa::where('user_id', $this->mhsUser->id)->firstOrFail();
        $this->dosenPa = $this->mhs->dosenPa;
        $this->ta = TahunAkademik::where('aktif', true)->firstOrFail();
    }

    public function test_rejects_krs_exceeding_max_sks_limit_ac4(): void
    {
        // Give previous semester grade resulting in IPS = 2.40 (max 18 SKS allowed)
        // Create previous semester
        $prevTa = TahunAkademik::create(['tahun' => '2024/2025', 'semester' => 'genap', 'aktif' => false]);
        $prodi = $this->mhs->prodi;

        $mkDummy = MataKuliah::create(['kode' => 'DUMMY01', 'nama' => 'Dummy Matkul', 'sks' => 3, 'semester' => 2, 'tipe' => 'teori', 'prodi_id' => $prodi->id]);
        $kelasDummy = Kelas::create(['mata_kuliah_id' => $mkDummy->id, 'dosen_id' => $this->dosenPa->id, 'tahun_akademik_id' => $prevTa->id, 'nama_kelas' => 'A', 'kapasitas' => 40]);

        Nilai::create([
            'kelas_id' => $kelasDummy->id,
            'mahasiswa_id' => $this->mhs->id,
            'komponen' => ['uas' => 60],
            'nilai_akhir' => 60,
            'huruf' => 'C',
            'bobot' => 2.00,
        ]);

        // Now student tries to submit classes totaling 21 SKS (exceeding 18 SKS limit)
        $kelasIds = [];
        for ($i = 1; $i <= 7; $i++) {
            $m = MataKuliah::create(['kode' => "TEST$i", 'nama' => "Test MK $i", 'sks' => 3, 'semester' => 3, 'tipe' => 'teori', 'prodi_id' => $prodi->id]);
            $k = Kelas::create(['mata_kuliah_id' => $m->id, 'dosen_id' => $this->dosenPa->id, 'tahun_akademik_id' => $this->ta->id, 'nama_kelas' => 'A', 'kapasitas' => 40]);
            $kelasIds[] = $k->id;
        }

        $response = $this->actingAs($this->mhsUser)->post('/mahasiswa/krs', [
            'kelas_ids' => $kelasIds, // 7 * 3 = 21 SKS
        ]);

        $response->assertSessionHasErrors('kelas_ids');
    }

    public function test_rejects_krs_with_schedule_time_collision_ac5(): void
    {
        $prodi = $this->mhs->prodi;
        $m1 = MataKuliah::create(['kode' => 'COL1', 'nama' => 'Matkul Clash 1', 'sks' => 3, 'semester' => 3, 'tipe' => 'teori', 'prodi_id' => $prodi->id]);
        $m2 = MataKuliah::create(['kode' => 'COL2', 'nama' => 'Matkul Clash 2', 'sks' => 3, 'semester' => 3, 'tipe' => 'teori', 'prodi_id' => $prodi->id]);

        $k1 = Kelas::create(['mata_kuliah_id' => $m1->id, 'dosen_id' => $this->dosenPa->id, 'tahun_akademik_id' => $this->ta->id, 'nama_kelas' => 'A', 'kapasitas' => 40]);
        $k2 = Kelas::create(['mata_kuliah_id' => $m2->id, 'dosen_id' => $this->dosenPa->id, 'tahun_akademik_id' => $this->ta->id, 'nama_kelas' => 'A', 'kapasitas' => 40]);

        $r1 = Ruangan::firstOrFail();
        $r2 = Ruangan::where('id', '!=', $r1->id)->firstOrFail();
        $slot = SlotWaktu::firstOrFail();

        $schedule = Schedule::create(['tahun_akademik_id' => $this->ta->id, 'status' => 'published']);
        // Both classes scheduled on Senin Slot 1
        ScheduleItem::create(['schedule_id' => $schedule->id, 'kelas_id' => $k1->id, 'ruangan_id' => $r1->id, 'slot_waktu_id' => $slot->id, 'hari' => 'senin']);
        ScheduleItem::create(['schedule_id' => $schedule->id, 'kelas_id' => $k2->id, 'ruangan_id' => $r2->id, 'slot_waktu_id' => $slot->id, 'hari' => 'senin']);

        $response = $this->actingAs($this->mhsUser)->post('/mahasiswa/krs', [
            'kelas_ids' => [$k1->id, $k2->id],
        ]);

        $response->assertSessionHasErrors('kelas_ids');
    }

    public function test_rejects_krs_when_prerequisite_not_passed_ac6(): void
    {
        // TIF301 (Basis Data II) requires TIF201 (Basis Data I)
        $basdat2 = MataKuliah::where('kode', 'TIF301')->firstOrFail();
        $kelasBasdat2 = Kelas::where('mata_kuliah_id', $basdat2->id)->firstOrFail();

        // Student has NOT passed TIF201
        $response = $this->actingAs($this->mhsUser)->post('/mahasiswa/krs', [
            'kelas_ids' => [$kelasBasdat2->id],
        ]);

        $response->assertSessionHasErrors('kelas_ids');
    }

    public function test_dosen_pa_can_approve_krs_ac7(): void
    {
        // Student submits valid KRS with Kalkulus Lanjut (no prerequisites)
        $kalkulus = MataKuliah::where('kode', 'TIF204')->firstOrFail();
        $kelasKalkulus = Kelas::where('mata_kuliah_id', $kalkulus->id)->firstOrFail();

        $this->actingAs($this->mhsUser)->post('/mahasiswa/krs', [
            'kelas_ids' => [$kelasKalkulus->id],
        ]);

        $krs = Krs::where('mahasiswa_id', $this->mhs->id)->firstOrFail();
        $this->assertEquals('submitted', $krs->status);

        // Dosen PA logs in and approves
        $dosenUser = $this->dosenPa->user;
        $response = $this->actingAs($dosenUser)->post("/dosen/krs/{$krs->id}/approve");
        $response->assertRedirect();

        $this->assertEquals('approved', $krs->fresh()->status);
    }
}
