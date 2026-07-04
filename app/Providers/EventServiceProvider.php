<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Modules\Appointments\Domain\Events\ScheduledAppointment;
use App\Modules\whatsApp\Infrastructure\Listeners\CreatedAppointmentListener;

use App\Modules\Auth\Domain\Events\SendEmailForChangePasswordEvent;
use App\Modules\Email\Infrastructure\Listeners\SendPasswordResetListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ScheduledAppointment::class => [
            CreatedAppointmentListener::class,
        ],

        SendEmailForChangePasswordEvent::class => [
            SendPasswordResetListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
