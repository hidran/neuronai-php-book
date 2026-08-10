<?php

declare(strict_types=1);

/*
 * Shared bootstrap for every runnable example in this repository.
 *
 * Each script under chapters/ChNN/run/ starts with:
 *
 *     require __DIR__ . '/../../../bootstrap.php';
 *
 * That gives it Composer's autoloader and the variables from .env.
 */

require_once __DIR__ . '/vendor/autoload.php';

if (is_file(__DIR__ . '/.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
}
