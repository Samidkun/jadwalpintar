<?php

namespace Tests\Feature;

use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\MataKuliah;
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

class ScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AcademicMasterSeeder::class);
        $this->admin = User::where('email', 'admin@kharisma.ac.id')->firstOrFail();
    }

    public function test_admin_can_view_schedule_workspace(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/schedules');
        $response->assertStatus(200);
    }

    public function test_admin_can_generate_schedule(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/schedules/generate');
        $response->assertRedirect('/admin/schedules');

        $this->assertDatabaseHas('schedules', [
            'status' => 'draft',
        ]);

        $this->assertGreaterThan(0, ScheduleItem::count());
    }

    public function test_admin_can_toggle_pin_on_schedule_item_ac3(): void
    {
        $this->actingAs($this->admin)->post('/admin/schedules/generate');
        $item = ScheduleItem::firstOrFail();

        $response = $this->actingAs($this->admin)->post("/admin/schedules/items/{$item->id}/toggle-pin");
        $response->assertRedirect('/admin/schedules');

        $this->assertTrue($item->fresh()->is_pinned);
    }

    public function test_admin_can_publish_schedule(): void
    {
        $this->actingAs($this->admin)->post('/admin/schedules/generate');
        $schedule = Schedule::firstOrFail();

        $response = $this->actingAs($this->admin)->post("/admin/schedules/{$schedule->id}/publish");
        $response->assertRedirect('/admin/schedules');

        $this->assertEquals('published', $schedule->fresh()->status);
    }
}
