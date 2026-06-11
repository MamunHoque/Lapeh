<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** Every standardized list page renders for an admin. */
    public function test_all_admin_list_pages_render(): void
    {
        $this->actingAs($this->admin());

        foreach ([
            'admin.orders', 'admin.senders', 'admin.drivers', 'admin.zones',
            'admin.payments', 'admin.ratings', 'admin.complaints', 'admin.users',
            'admin.sms', 'admin.activity-logs', 'admin.reports',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_csv_exports_stream(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.payments.export'))->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        foreach (['daily', 'drivers', 'senders'] as $type) {
            $this->get(route('admin.reports.export', $type))->assertOk();
        }
    }

    /** The shared toolbar (search + filter + clear) renders on list pages. */
    public function test_list_pages_show_filter_toolbar(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.drivers'))
            ->assertOk()
            ->assertSee('admin-toolbar', false)
            ->assertSee(__('admin.dr_today'));
    }

    /** Date-range presets and custom range filter without error. */
    public function test_date_range_filters_apply(): void
    {
        $this->actingAs($this->admin());

        foreach (['today', 'yesterday', '7d', 'month', 'year'] as $range) {
            $this->get(route('admin.orders', ['range' => $range]))->assertOk();
        }

        $this->get(route('admin.orders', [
            'range' => 'custom',
            'from' => now()->subDays(3)->toDateString(),
            'to' => now()->toDateString(),
        ]))->assertOk();
    }

    /** Search + status filters apply across pages. */
    public function test_search_and_status_filters_apply(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.orders', ['search' => 'LPH', 'status' => 'delivered']))->assertOk();
        $this->get(route('admin.drivers', ['search' => 'ali', 'status' => 'online']))->assertOk();
        $this->get(route('admin.payments', ['search' => 'x', 'status' => 'paid']))->assertOk();
        $this->get(route('admin.ratings', ['search' => 'x', 'status' => '5']))->assertOk();
        $this->get(route('admin.complaints', ['search' => 'x', 'type' => 'late']))->assertOk();
        $this->get(route('admin.sms', ['search' => 'x', 'status' => 'sent']))->assertOk();
    }
}
