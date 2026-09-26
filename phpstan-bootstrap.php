<?php

declare(strict_types=1);

/*
 * PHPStan bootstrap.
 *
 * The Laravel chapters (17-20) reference application classes that only exist
 * inside a real Laravel project - App\Models\User, App\Models\Tenant and so on
 * - so their listings are not shipped here. Chapters 21-23 ship only the
 * framework-level parts. This file exists so PHPStan loads Composer's
 * autoloader the same way the runnable scripts do.
 */

require_once __DIR__ . '/vendor/autoload.php';
