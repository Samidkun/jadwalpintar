<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fakultas', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('prodi', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->foreignId('fakultas_id')->constrained('fakultas')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('tahun_akademik', function (Blueprint $table) {
            $table->id();
            $table->string('tahun'); // e.g. "2025/2026"
            $table->enum('semester', ['ganjil', 'genap']);
            $table->boolean('aktif')->default(false);
            $table->timestamps();
        });

        Schema::create('mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->integer('sks');
            $table->integer('semester');
            $table->enum('tipe', ['teori', 'lab'])->default('teori');
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('prasyarat_mk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah')->cascadeOnDelete();
            $table->foreignId('prasyarat_id')->constrained('mata_kuliah')->cascadeOnDelete();
            $table->unique(['mata_kuliah_id', 'prasyarat_id']);
        });

        Schema::create('dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nidn')->unique();
            $table->string('nama');
            $table->string('bidang')->nullable();
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('slot_waktu', function (Blueprint $table) {
            $table->id();
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('dosen_time_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat']);
            $table->foreignId('slot_waktu_id')->constrained('slot_waktu')->cascadeOnDelete();
            $table->enum('status', ['preferred', 'avoid', 'unavailable'])->default('preferred');
            $table->timestamps();
            $table->unique(['dosen_id', 'hari', 'slot_waktu_id']);
        });

        Schema::create('ruangan', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->integer('kapasitas');
            $table->enum('tipe', ['teori', 'lab', 'campuran'])->default('teori');
            $table->timestamps();
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('dosen')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik')->cascadeOnDelete();
            $table->string('nama_kelas'); // 'A', 'B', 'C'
            $table->integer('kapasitas')->default(40);
            $table->timestamps();
            $table->unique(['mata_kuliah_id', 'tahun_akademik_id', 'nama_kelas']);
        });

        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nim')->unique();
            $table->string('nama');
            $table->foreignId('prodi_id')->constrained('prodi')->cascadeOnDelete();
            $table->integer('semester')->default(1);
            $table->enum('status', ['aktif', 'cuti', 'lulus', 'do'])->default('aktif');
            $table->foreignId('dosen_pa_id')->nullable()->constrained('dosen')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('ruangan');
        Schema::dropIfExists('dosen_time_preferences');
        Schema::dropIfExists('slot_waktu');
        Schema::dropIfExists('dosen');
        Schema::dropIfExists('prasyarat_mk');
        Schema::dropIfExists('mata_kuliah');
        Schema::dropIfExists('tahun_akademik');
        Schema::dropIfExists('prodi');
        Schema::dropIfExists('fakultas');
    }
};
