<?php

declare(strict_types=1);

/*
 * Spec 015, CA3 and CA10 (P8, RD4.a, TM7): env() is only read inside config/, so production's
 * cached configuration (php artisan optimize) keeps every value.
 */

arch('application code never reads env() directly')
    ->expect('App')
    ->not->toUse('env');
