<?php

declare(strict_types=1);

/*
 * Fix 2026-11-16 (docs/changes/2026-11-16-fix-memoria-suite-paralela.md): each worker of the
 * parallel suite grows to about 120 MB, so PHP's default 128 MB made the suite fail at random.
 * phpunit.xml gives the tests 512 MB.
 */

it('runs the tests with at least 512 MB of memory', function () {
    $limit = ini_parse_quantity((string) ini_get('memory_limit'));

    expect($limit === -1 || $limit >= 512 * 1024 * 1024)->toBeTrue("memory_limit is {$limit} bytes");
});
