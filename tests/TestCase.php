<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    // Kepakai otomatis sama RefreshDatabase -- DatabaseSeeder jalan sekali abis migrate:fresh, bukan tiap test.
    protected $seed = true;
}
