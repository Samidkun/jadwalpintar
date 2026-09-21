<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->string('judul');
            $table->integer('durasi_menit')->nullable();
            $table->boolean('acak_soal')->default(true);
            $table->boolean('acak_pilihan')->default(true);
            $table->boolean('satu_percobaan')->default(true);
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->text('teks');
            $table->string('gambar')->nullable();
            $table->enum('tipe', ['pilgan', 'isian'])->default('pilgan');
            $table->jsonb('pilihan')->nullable();
            $table->string('jawaban_benar');
            $table->integer('poin')->default(10);
            $table->integer('urutan')->default(1);
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->jsonb('jawaban')->nullable();
            $table->integer('skor')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('soal');
        Schema::dropIfExists('quizzes');
    }
};
