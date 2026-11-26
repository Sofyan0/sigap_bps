<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardNavigationTest extends DashboardTestCase
{
    use RefreshDatabase;

    public function test_master_data_and_excel_entry_is_not_shown_in_dashboard_navigation(): void
    {
        $this->get('/?tab=beranda')
            ->assertOk()
            ->assertDontSee('Master Data &amp; Excel', false)
            ->assertSee('Pengaturan Akun');
    }
}
