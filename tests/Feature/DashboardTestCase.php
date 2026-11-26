<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

abstract class DashboardTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->make(['id' => 1]));
    }
}
