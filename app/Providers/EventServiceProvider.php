<?php

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // \App\Events\TaskCompletedEvent::class => [
        //     \App\Listeners\TaskCompletedListener::class,
        // ],
        // \App\Events\ReopenProjectItemEvent::class => [
        //     \App\Listeners\MilestoneReopenListener::class,
        //     \App\Listeners\TaskReopenListener::class,
        //     \App\Listeners\ProjectReopenListener::class,
        // ],
        \App\Events\StartedProjectItem::class => [
            \App\Listeners\StartMilestoneListener::class
        ],
        \App\Events\SyncMilestoneStatusEvent::class => [
            \App\Listeners\SyncMilestoneStatusListener::class,
        ],
        \App\Events\PusherEvent::class => [],
        \App\Events\SendFcmNotification::class => [
            \App\Listeners\FcmNotificationListener::class,
        ],
        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            \SocialiteProviders\Zoom\ZoomExtendSocialite::class . '@handle',
            \SocialiteProviders\Apple\AppleExtendSocialite::class . '@handle',
        ],
        \App\Events\UserDeleteEvent::class => [
            \App\Listeners\UserDeleteEventListener::class
        ]
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        \App\Listeners\ItemReopenSubscriber::class,
        \App\Listeners\ItemCompleteSubscriber::class,
    ];
}
