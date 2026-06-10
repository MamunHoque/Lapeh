<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Predefined driver rating tags
    |--------------------------------------------------------------------------
    | Canonical list used to validate driver ratings and to render
    | localized chips in the apps. Keys are stable; labels are display-only.
    */
    'rating_tags' => [
        'excellent_service'  => ['en' => 'Excellent service',  'ar' => 'خدمة ممتازة'],
        'fast'               => ['en' => 'Fast delivery',      'ar' => 'توصيل سريع'],
        'polite'             => ['en' => 'Polite',             'ar' => 'مهذب'],
        'careful_handling'   => ['en' => 'Careful handling',   'ar' => 'تعامل بعناية'],
        'late_arrival'       => ['en' => 'Late arrival',       'ar' => 'وصول متأخر'],
        'poor_communication' => ['en' => 'Poor communication', 'ar' => 'تواصل ضعيف'],
        'damaged_delivery'   => ['en' => 'Damaged delivery',   'ar' => 'توصيل تالف'],
        'rude'               => ['en' => 'Rude behavior',      'ar' => 'سلوك غير لائق'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Complaint types
    |--------------------------------------------------------------------------
    | Must stay in sync with the `complaints.type` enum migration.
    */
    'complaint_types' => [
        'late'            => ['en' => 'Late delivery',     'ar' => 'توصيل متأخر'],
        'damaged'         => ['en' => 'Damaged order',     'ar' => 'طلب تالف'],
        'driver_behavior' => ['en' => 'Driver behavior',   'ar' => 'سلوك السائق'],
        'payment'         => ['en' => 'Payment issue',     'ar' => 'مشكلة في الدفع'],
        'other'           => ['en' => 'Other',             'ar' => 'أخرى'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone OTP verification
    |--------------------------------------------------------------------------
    | No SMS provider yet: the OTP is logged and (in non-production) returned
    | in the API response. MASTER_OTP is accepted only in non-production envs.
    */
    'otp' => [
        'length' => 6,
        'ttl_minutes' => 10,
        'master' => env('MASTER_OTP', '123456'),
        // Envs where the OTP is exposed in responses and the master OTP works.
        'dev_envs' => ['local', 'testing', 'development'],
    ],

];
