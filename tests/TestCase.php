<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Statistik dashboard, peta, dan badge sidebar memakai cache —
        // kosongkan antar test agar tidak ada data ter-cache dari test sebelumnya.
        Cache::flush();
    }
}
