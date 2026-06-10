<?php

// Human labels for activity-log action keys (nested so __('activity.order.created') resolves).
return [
    'auth' => [
        'login' => 'Signed in',
        'logout' => 'Signed out',
        'register' => 'Registered',
    ],
    'order' => [
        'created' => 'Created order',
        'cancelled' => 'Cancelled order',
        'rated' => 'Rated driver',
        'link_resent' => 'Resent customer link',
        'location_confirmed' => 'Confirmed location',
        'paid' => 'Paid for order',
        'status_updated' => 'Updated order status',
        'delivered' => 'Delivered order',
    ],
    'offer' => [
        'accepted' => 'Accepted offer',
        'rejected' => 'Rejected offer',
    ],
    'driver' => [
        'online' => 'Went online',
        'offline' => 'Went offline',
        'created' => 'Created driver',
        'updated' => 'Updated driver',
        'deleted' => 'Deleted driver',
    ],
    'restaurant' => [
        'created' => 'Created restaurant',
        'updated' => 'Updated restaurant',
        'deleted' => 'Deleted restaurant',
    ],
    'zone' => [
        'created' => 'Created zone',
        'updated' => 'Updated zone',
        'deleted' => 'Deleted zone',
    ],
    'complaint' => [
        'created' => 'Filed complaint',
        'updated' => 'Updated complaint',
    ],
    'pricing' => [
        'updated' => 'Updated pricing',
    ],
    'user' => [
        'updated' => 'Updated user',
    ],
];
