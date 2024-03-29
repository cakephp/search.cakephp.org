<?php
declare(strict_types=1);

/**
 * Test runner bootstrap.
 *
 * Add additional configuration/setup your application needs when running
 * unit tests in this file.
 */
require dirname(__DIR__) . '/config/bootstrap.php';

// dummy default connection to make the fixture manager happy
\Cake\Datasource\ConnectionManager::setConfig('default', []);

// disable logging in tests to avoid polluting the CLI output
\Cake\Log\Log::drop('debug');
\Cake\Log\Log::drop('error');
