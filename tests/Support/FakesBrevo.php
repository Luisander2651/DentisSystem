<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Email\Aplication\UseCases\SendResetPasswordEmailUseCase;
use App\Modules\Email\Infrastructure\ExternalApi\BrevoApi;
use Mockery;
use Mockery\MockInterface;

trait FakesBrevo
{
    /**
     * Override the contextual binding registered in AppServiceProvider
     * (SendResetPasswordEmailUseCase needs BrevoApi) with a mock, so the
     * real Brevo SDK client is never constructed/called during tests.
     *
     * @param  (callable(MockInterface): void)|null  $expectations
     */
    protected function fakeBrevo(?callable $expectations = null): MockInterface
    {
        $mock = Mockery::mock(BrevoApi::class);

        if ($expectations === null) {
            $mock->shouldReceive('sendEmail')->zeroOrMoreTimes();
        } else {
            $expectations($mock);
        }

        $this->app->when(SendResetPasswordEmailUseCase::class)
            ->needs(BrevoApi::class)
            ->give(fn () => $mock);

        return $mock;
    }
}
