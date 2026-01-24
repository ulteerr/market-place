<?php

namespace Tests;

use App\Support\Testing\InteractsWithAuth;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithAuth;
}
