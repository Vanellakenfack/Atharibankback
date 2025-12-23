<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase; // Importez le trait
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase; // Ajoutez-le ici

    // ... autres méthodes (createApplication, etc.)
}
