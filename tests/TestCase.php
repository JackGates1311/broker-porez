<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Baza\PripremaBaze;
use Tests\Feature\Concerns\TestBaza;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Šema se pravi pre nego što Laravel otvori konekciju (DatabaseTransactions u TestBaza).
        if (in_array(TestBaza::class, class_uses_recursive($this), true)) {
            PripremaBaze::jednom(dirname(__DIR__));
        }

        parent::setUp();
    }
}
