<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $currentDb = DB::connection()->getDatabaseName();
            if ($currentDb !== 'smartspace_test') {
                throw new \RuntimeException(
                    "BAHAYA: Tes berjalan pada database '$currentDb' (bukan 'smartspace_test'). " .
                    "Tes dihentikan untuk melindungi data asli."
                );
            }
        } catch (\Throwable $e) {
            echo "SETUP ERROR: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
