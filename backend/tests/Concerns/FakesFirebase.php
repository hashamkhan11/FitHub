<?php

namespace Tests\Concerns;

use Kreait\Firebase\Contract\Messaging;
use Mockery;
use Mockery\MockInterface;

trait FakesFirebase
{
    protected function fakeFirebaseMessaging(): MockInterface
    {
        $messaging = Mockery::spy(Messaging::class);

        $this->app->instance('firebase.manager', new class($messaging)
        {
            public function __construct(private readonly Messaging $messaging) {}

            public function messaging(): Messaging
            {
                return $this->messaging;
            }
        });

        return $messaging;
    }
}
