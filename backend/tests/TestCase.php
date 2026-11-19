<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        putenv('DB_DATABASE=accommity_test_db');
        $_ENV['DB_DATABASE'] = 'accommity_test_db';
        $_SERVER['DB_DATABASE'] = 'accommity_test_db';

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $app['config']->set('database.connections.pgsql.database', 'accommity_test_db');

        return $app;
    }
}
