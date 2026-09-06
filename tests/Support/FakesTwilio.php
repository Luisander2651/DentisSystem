<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\whatsApp\Infrastructure\ExternalApi\TwilioConection;
use Mockery\MockInterface;

trait FakesTwilio
{
    /**
     * Bind a mock of TwilioConection into the container. Never calls the real Twilio API.
     *
     * @param  (callable(MockInterface): void)|null  $expectations
     */
    protected function fakeTwilio(?callable $expectations = null): MockInterface
    {
        return $this->mock(TwilioConection::class, function (MockInterface $mock) use ($expectations) {
            if ($expectations === null) {
                $mock->shouldReceive('sendTemplate')->zeroOrMoreTimes();

                return;
            }

            $expectations($mock);
        });
    }
}
