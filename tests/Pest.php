<?php

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

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Unit');

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

function createUser(string $role = 'ADMIN'): \App\Models\User
{
    return \App\Models\User::factory()->create(['role' => $role]);
}

function createClients(int $count = 1): \Illuminate\Database\Eloquent\Collection
{
    return \App\Models\Client::factory()->count($count)->create();
}

function createTransactions(int $count = 1): \Illuminate\Database\Eloquent\Collection
{
    $gateway = \App\Models\Gateway::factory()->create();

    return \App\Models\Transaction::factory()->count($count)->create([
        'gateway_id' => $gateway->id,
        'status' => 'completed',
    ]);
}

function createProducts(int $count = 1): \Illuminate\Database\Eloquent\Collection
{
    return \App\Models\Product::factory()->count($count)->create(['amount' => 999]);
}
