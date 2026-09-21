<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\DosenTimePreference;
use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\SlotWaktu;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_academic_master_data_hierarchy(): void
    {
        $fakultas = Fakultas::create([
            'kode' => 'FTIK',
            'nama' => 'Fakultas Teknologi Informasi dan Komunikasi',
        ]);

        $prodi = Prodi::create([
            'kode' => 'TIF',
            'nama' => 'Teknik Informatika',
            'fakultas_id' => $fakultas->id,
        ]);

        $tahunAkademik = TahunAkademik::create([
            'tahun' => '2025/2026',
            'semester' => 'ganjil',
            'aktif' => true,
        ]);

        $mk1 = MataKuliah::create([
            'kode' => 'TIF101',
            'nama' => 'Algoritma Pemrograman',
            'sks' => 3,
            'semester' => 1,
            'tipe' => 'lab',
            'prodi_id' => $prodi->id,
        ]);

        $mk2 = MataKuliah::create([
            'kode' => 'TIF201',
            'nama' => 'Struktur Data',
            'sks' => 3,
            'semester' => 2,
            'tipe' => 'lab',
            'prodi_id' => $prodi->id,
        ]);

        // Prerequisite: TIF201 requires TIF101
        $mk2->prasyarat()->attach($mk1->id);

        $this->assertEquals('Fakultas Teknologi Informasi dan Komunikasi', $prodi->fakultas->nama);
        $this->assertTrue($mk2->prasyarat->contains($mk1));

        $userDosen = User::create([
            'name' => 'Dr. Ir. Hendra Kusuma',
            'email' => 'hendra@kharisma.ac.id',
            'password' => bcrypt('secret123'),
        ]);

        $dosen = Dosen::create([
            'user_id' => $userDosen->id,
            'nidn' => '0412088501',
            'nama' => 'Dr. Ir. Hendra Kusuma, M.T.',
            'bidang' => 'Kecerdasan Buatan',
            'prodi_id' => $prodi->id,
        ]);

        $slot = SlotWaktu::create([
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
            'label' => 'Slot 1 (Pagi)',
        ]);

        $pref = DosenTimePreference::create([
            'dosen_id' => $dosen->id,
            'hari' => 'senin',
            'slot_waktu_id' => $slot->id,
            'status' => 'preferred',
        ]);

        $this->assertEquals('preferred', $dosen->timePreferences->first()->status);

        $ruangan = Ruangan::create([
            'kode' => 'R301',
            'nama' => 'Gedung A • R.301',
            'kapasitas' => 45,
            'tipe' => 'teori',
        ]);

        $kelas = Kelas::create([
            'mata_kuliah_id' => $mk1->id,
            'dosen_id' => $dosen->id,
            'tahun_akademik_id' => $tahunAkademik->id,
            'nama_kelas' => 'A',
            'kapasitas' => 40,
        ]);

        $this->assertEquals('TIF101', $kelas->mataKuliah->kode);
        $this->assertEquals('0412088501', $kelas->dosen->nidn);

        $userMhs = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@student.kharisma.ac.id',
            'password' => bcrypt('secret123'),
        ]);

        $mahasiswa = Mahasiswa::create([
            'user_id' => $userMhs->id,
            'nim' => '2201010042',
            'nama' => 'Budi Santoso',
            'prodi_id' => $prodi->id,
            'semester' => 3,
            'status' => 'aktif',
            'dosen_pa_id' => $dosen->id,
        ]);

        $this->assertEquals('2201010042', $mahasiswa->nim);
        $this->assertEquals($dosen->id, $mahasiswa->dosenPa->id);
    }
}
