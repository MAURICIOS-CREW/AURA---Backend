<?php

namespace App\Contracts;

interface AccessNotifierInterface
{
    /**
     * Notify access validation event across WebSockets and FCM Push Notifications
     * using a standardized data format.
     *
     * @param array $payload
     * @return void
     */
    public function notify(array $payload): void;
}
