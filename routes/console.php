<?php

use Illuminate\Support\Facades\Schedule;

// Schedule runs only when SIGAP_SYNC_ENABLED=true; command itself also checks this flag.
Schedule::command('sigap:sync-central')->dailyAt('06:00')->withoutOverlapping();
