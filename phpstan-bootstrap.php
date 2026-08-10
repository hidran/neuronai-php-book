<?php

declare(strict_types=1);

/*
 * PHPStan bootstrap.
 *
 * The Laravel chapters (17-23) reference application classes that only exist
 * inside a real Laravel project - App\Models\User, App\Models\Tenant and so on.
 * Rather than vendoring a whole Laravel skeleton into this repository, the
 * examples declare those classes in chapters/Support/. This file exists so
 * PHPStan loads Composer's autoloader the same way the runnable scripts do.
 */

require_once __DIR__ . '/vendor/autoload.php';
