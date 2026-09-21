<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AcademicMasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $dosenRole = Role::firstOrCreate(['name' => 'dosen']);
        $mahasiswaRole = Role::firstOrCreate(['name' => 'mahasiswa']);

        // 2. Admin User
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@kharisma.ac.id'],
            [
                'name' => 'Biro Akademik',
                'password' => Hash::make('password'),
            ]
        );
        $adminUser->assignRole($adminRole);

        // 3. Fakultas & Prodi
        $ftik = Fakultas::firstOrCreate(
            ['kode' => 'FTIK'],
            ['nama' => 'Fakultas Teknologi Informasi dan Komunikasi']
        );

        $feb = Fakultas::firstOrCreate(
            ['kode' => 'FEB'],
            ['nama' => 'Fakultas Ekonomi dan Bisnis']
        );

        $tif = Prodi::firstOrCreate(
            ['kode' => 'TIF'],
            ['nama' => 'Teknik Informatika', 'fakultas_id' => $ftik->id]
        );

        $si = Prodi::firstOrCreate(
            ['kode' => 'SI'],
            ['nama' => 'Sistem Informasi', 'fakultas_id' => $ftik->id]
        );

        $bd = Prodi::firstOrCreate(
            ['kode' => 'BD'],
            ['nama' => 'Bisnis Digital', 'fakultas_id' => $feb->id]
        );

        // 4. Tahun Akademik Aktif
        $ta = TahunAkademik::firstOrCreate(
            ['tahun' => '2025/2026', 'semester' => 'ganjil'],
            ['aktif' => true]
        );

        // 5. Ruangan
        $r301 = Ruangan::firstOrCreate(
            ['kode' => 'R301'],
            ['nama' => 'Gedung A • R.301', 'kapasitas' => 45, 'tipe' => 'teori']
        );
        $r302 = Ruangan::firstOrCreate(
            ['kode' => 'R302'],
            ['nama' => 'Gedung A • R.302', 'kapasitas' => 45, 'tipe' => 'teori']
        );
        $r304 = Ruangan::firstOrCreate(
            ['kode' => 'R304'],
            ['nama' => 'Gedung A • R.304', 'kapasitas' => 35, 'tipe' => 'teori']
        );
        $labKom1 = Ruangan::firstOrCreate(
            ['kode' => 'LABKOM01'],
            ['nama' => 'Lab Komputer 01', 'kapasitas' => 35, 'tipe' => 'lab']
        );
        $labJar = Ruangan::firstOrCreate(
            ['kode' => 'LABJAR'],
            ['nama' => 'Lab Jaringan Komputer', 'kapasitas' => 30, 'tipe' => 'lab']
        );

        // 6. Slot Waktu
        $s1 = SlotWaktu::firstOrCreate(
            ['jam_mulai' => '08:00', 'jam_selesai' => '10:30'],
            ['label' => 'Slot 1 (Pagi)']
        );
        $s2 = SlotWaktu::firstOrCreate(
            ['jam_mulai' => '10:45', 'jam_selesai' => '13:15'],
            ['label' => 'Slot 2 (Siang)']
        );
        $s3 = SlotWaktu::firstOrCreate(
            ['jam_mulai' => '13:30', 'jam_selesai' => '16:00'],
            ['label' => 'Slot 3 (Sore)']
        );
        $s4 = SlotWaktu::firstOrCreate(
            ['jam_mulai' => '16:15', 'jam_selesai' => '18:45'],
            ['label' => 'Slot 4 (Malam)']
        );

        // 7. Mata Kuliah
        $kalkulus = MataKuliah::firstOrCreate(
            ['kode' => 'TIF204'],
            ['nama' => 'Kalkulus Lanjut', 'sks' => 3, 'semester' => 3, 'tipe' => 'teori', 'prodi_id' => $tif->id]
        );
        $alpro = MataKuliah::firstOrCreate(
            ['kode' => 'SI102'],
            ['nama' => 'Algoritma & Pemrograman', 'sks' => 3, 'semester' => 1, 'tipe' => 'lab', 'prodi_id' => $si->id]
        );
        $basdat1 = MataKuliah::firstOrCreate(
            ['kode' => 'TIF201'],
            ['nama' => 'Basis Data I', 'sks' => 3, 'semester' => 2, 'tipe' => 'teori', 'prodi_id' => $tif->id]
        );
        $basdat2 = MataKuliah::firstOrCreate(
            ['kode' => 'TIF301'],
            ['nama' => 'Basis Data II', 'sks' => 3, 'semester' => 3, 'tipe' => 'teori', 'prodi_id' => $tif->id]
        );
        $basdat2->prasyarat()->syncWithoutDetaching([$basdat1->id]);

        $bisnis = MataKuliah::firstOrCreate(
            ['kode' => 'BD105'],
            ['nama' => 'Pengantar Bisnis Digital', 'sks' => 2, 'semester' => 1, 'tipe' => 'teori', 'prodi_id' => $bd->id]
        );
        $cyber = MataKuliah::firstOrCreate(
            ['kode' => 'TIF402'],
            ['nama' => 'Keamanan Siber Terapan', 'sks' => 3, 'semester' => 5, 'tipe' => 'lab', 'prodi_id' => $tif->id]
        );
        $ecom = MataKuliah::firstOrCreate(
            ['kode' => 'BD202'],
            ['nama' => 'Analitik E-Commerce', 'sks' => 2, 'semester' => 3, 'tipe' => 'teori', 'prodi_id' => $bd->id]
        );

        // 8. Dosen & User
        $uHendra = User::firstOrCreate(
            ['email' => 'hendra@kharisma.ac.id'],
            ['name' => 'Dr. Ir. Hendra Kusuma', 'password' => Hash::make('password')]
        );
        $uHendra->assignRole($dosenRole);
        $dHendra = Dosen::firstOrCreate(
            ['nidn' => '0412088501'],
            ['user_id' => $uHendra->id, 'nama' => 'Dr. Ir. Hendra Kusuma, M.T.', 'bidang' => 'Informatika & Siber', 'prodi_id' => $tif->id]
        );
        // Hendra prefers Senin pagi
        DosenTimePreference::firstOrCreate(
            ['dosen_id' => $dHendra->id, 'hari' => 'senin', 'slot_waktu_id' => $s1->id],
            ['status' => 'preferred']
        );

        $uZaki = User::firstOrCreate(
            ['email' => 'zaki@kharisma.ac.id'],
            ['name' => 'Ahmad Zaki, M.Kom.', 'password' => Hash::make('password')]
        );
        $uZaki->assignRole($dosenRole);
        $dZaki = Dosen::firstOrCreate(
            ['nidn' => '0412088502'],
            ['user_id' => $uZaki->id, 'nama' => 'Ahmad Zaki, M.Kom.', 'bidang' => 'Rekayasa Perangkat Lunak', 'prodi_id' => $si->id]
        );

        $uSiti = User::firstOrCreate(
            ['email' => 'siti@kharisma.ac.id'],
            ['name' => 'Siti Rahmawati, M.Cs.', 'password' => Hash::make('password')]
        );
        $uSiti->assignRole($dosenRole);
        $dSiti = Dosen::firstOrCreate(
            ['nidn' => '0412088503'],
            ['user_id' => $uSiti->id, 'nama' => 'Siti Rahmawati, M.Cs.', 'bidang' => 'Data Science', 'prodi_id' => $tif->id]
        );

        $uBambang = User::firstOrCreate(
            ['email' => 'bambang@kharisma.ac.id'],
            ['name' => 'Drs. Bambang Sudarsono, M.M.', 'password' => Hash::make('password')]
        );
        $uBambang->assignRole($dosenRole);
        $dBambang = Dosen::firstOrCreate(
            ['nidn' => '0412088504'],
            ['user_id' => $uBambang->id, 'nama' => 'Drs. Bambang Sudarsono, M.M.', 'bidang' => 'Manajemen Bisnis', 'prodi_id' => $bd->id]
        );
        // Bambang unavailable on Selasa
        DosenTimePreference::firstOrCreate(
            ['dosen_id' => $dBambang->id, 'hari' => 'selasa', 'slot_waktu_id' => $s1->id],
            ['status' => 'unavailable']
        );

        $uNadia = User::firstOrCreate(
            ['email' => 'nadia@kharisma.ac.id'],
            ['name' => 'Nadia Putri, M.M.', 'password' => Hash::make('password')]
        );
        $uNadia->assignRole($dosenRole);
        $dNadia = Dosen::firstOrCreate(
            ['nidn' => '0412088505'],
            ['user_id' => $uNadia->id, 'nama' => 'Nadia Putri, M.M.', 'bidang' => 'Digital Marketing', 'prodi_id' => $bd->id]
        );

        // 9. Kelas Offering
        Kelas::firstOrCreate(
            ['mata_kuliah_id' => $kalkulus->id, 'tahun_akademik_id' => $ta->id, 'nama_kelas' => 'A'],
            ['dosen_id' => $dHendra->id, 'kapasitas' => 38]
        );
        Kelas::firstOrCreate(
            ['mata_kuliah_id' => $alpro->id, 'tahun_akademik_id' => $ta->id, 'nama_kelas' => 'B'],
            ['dosen_id' => $dZaki->id, 'kapasitas' => 32]
        );
        Kelas::firstOrCreate(
            ['mata_kuliah_id' => $basdat2->id, 'tahun_akademik_id' => $ta->id, 'nama_kelas' => 'A'],
            ['dosen_id' => $dSiti->id, 'kapasitas' => 42]
        );
        Kelas::firstOrCreate(
            ['mata_kuliah_id' => $bisnis->id, 'tahun_akademik_id' => $ta->id, 'nama_kelas' => 'A'],
            ['dosen_id' => $dBambang->id, 'kapasitas' => 25]
        );
        Kelas::firstOrCreate(
            ['mata_kuliah_id' => $cyber->id, 'tahun_akademik_id' => $ta->id, 'nama_kelas' => 'A'],
            ['dosen_id' => $dHendra->id, 'kapasitas' => 28]
        );
        Kelas::firstOrCreate(
            ['mata_kuliah_id' => $ecom->id, 'tahun_akademik_id' => $ta->id, 'nama_kelas' => 'B'],
            ['dosen_id' => $dNadia->id, 'kapasitas' => 30]
        );

        // 10. Mahasiswa
        $uBudi = User::firstOrCreate(
            ['email' => 'budi@student.kharisma.ac.id'],
            ['name' => 'Budi Santoso', 'password' => Hash::make('password')]
        );
        $uBudi->assignRole($mahasiswaRole);
        Mahasiswa::firstOrCreate(
            ['nim' => '2201010042'],
            ['user_id' => $uBudi->id, 'nama' => 'Budi Santoso', 'prodi_id' => $tif->id, 'semester' => 3, 'status' => 'aktif', 'dosen_pa_id' => $dHendra->id]
        );

        $uAminah = User::firstOrCreate(
            ['email' => 'aminah@student.kharisma.ac.id'],
            ['name' => 'Siti Aminah', 'password' => Hash::make('password')]
        );
        $uAminah->assignRole($mahasiswaRole);
        Mahasiswa::firstOrCreate(
            ['nim' => '2201010043'],
            ['user_id' => $uAminah->id, 'nama' => 'Siti Aminah', 'prodi_id' => $si->id, 'semester' => 1, 'status' => 'aktif', 'dosen_pa_id' => $dZaki->id]
        );
    }
}
