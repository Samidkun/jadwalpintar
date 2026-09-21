<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik')->cascadeOnDelete();
            $table->enum('status', ['generating', 'draft', 'published'])->default('generating');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('ruangan_id')->constrained('ruangan')->cascadeOnDelete();
            $table->foreignId('slot_waktu_id')->constrained('slot_waktu')->cascadeOnDelete();
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat']);
            $table->boolean('is_pinned')->default(false);
            $table->text('explanation')->nullable();
            $table->timestamps();

            $table->unique(['schedule_id', 'ruangan_id', 'hari', 'slot_waktu_id'], 'uq_schedule_room_time');
        });

        Schema::create('schedule_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->string('type');
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_conflicts');
        Schema::dropIfExists('schedule_items');
        Schema::dropIfExists('schedules');
    }
};
