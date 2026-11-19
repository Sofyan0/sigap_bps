<?php

namespace Tests\Feature;

use Database\Seeders\EwsAssessmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EwsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_ews_page_displays_the_latest_assessment_from_database(): void
    {
        $this->seed(EwsAssessmentSeeder::class);

        $response = $this->get('/?tab=ews');

        $response->assertOk()
            ->assertSee('Risk Score TPID: 82 (Waspada)')
            ->assertSee('Cabe Rawit Merah')
            ->assertSee('22,4%')
            ->assertSee('Rp 62.000');
    }

    public function test_ews_page_explains_when_there_is_no_assessment(): void
    {
        $response = $this->get('/?tab=ews');

        $response->assertOk()
            ->assertSee('Belum ada data EWS untuk ditampilkan.')
            ->assertSee('Belum ada data');
    }
}
