<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Modules test base classes
|--------------------------------------------------------------------------
|
| Files under tests/Modules/<Module>/{Unit,Integration} declare their own base
| explicitly at the top of each test file, e.g.:
|   uses(Tests\TestCase::class);                                  // plain
|   uses(Tests\Modules\<Module>\Integration\<Module>TestCase::class); // custom, extends Tests\TestCase
| This is intentional: a global pest()->extend(...)->in('Modules') here would
| conflict with any file that also declares its own uses() (Pest does not
| allow binding the same test file to two different base classes), and each
| module may need a different domain-specific base (factories, acting-as
| helpers) rather than one shared one.
|
| Each parallel process gets its own test database (Laravel's ParallelTesting
| appends a token to DB_DATABASE, e.g. dentissa_testing_test_1). Integration
| test files opt into a real database via Illuminate\Foundation\Testing\RefreshDatabase
| (either directly, or inherited from a custom base TestCase). Unit tests
| (Value Objects, Entities) stay framework-light and skip it.
|
*/

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
