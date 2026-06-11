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

    /*
    |--------------------------------------------------------------------------
    | Help & support
    |--------------------------------------------------------------------------
    | Contact channels and localized FAQ surfaced on the apps' Help screen.
    */
    'support' => [
        'phone' => env('SUPPORT_PHONE', '+97180052734'),
        'email' => env('SUPPORT_EMAIL', 'support@lapeh.ae'),
        'whatsapp' => env('SUPPORT_WHATSAPP', '+97180052734'),
        'faq' => [
            [
                'q_en' => 'How do I create a delivery request?',
                'q_ar' => 'كيف أنشئ طلب توصيل؟',
                'a_en' => 'From the Home tab tap “New delivery request”, enter the customer details and package items, then send. The customer receives a link to confirm their location and pay.',
                'a_ar' => 'من تبويب الرئيسية اضغط «طلب توصيل جديد»، أدخل بيانات العميل والطرد ثم أرسل. يصل العميل رابط لتأكيد موقعه والدفع.',
            ],
            [
                'q_en' => 'How is the delivery fee calculated?',
                'q_ar' => 'كيف يتم احتساب رسوم التوصيل؟',
                'a_en' => 'The fee is based on the distance between pickup and the customer’s confirmed location, using the zone base fee plus a per-kilometer rate.',
                'a_ar' => 'تُحتسب الرسوم حسب المسافة بين الاستلام وموقع العميل المؤكد، باستخدام رسوم المنطقة الأساسية بالإضافة إلى رسم لكل كيلومتر.',
            ],
            [
                'q_en' => 'Can I change my registered phone number?',
                'q_ar' => 'هل يمكنني تغيير رقم هاتفي المسجل؟',
                'a_en' => 'Phone numbers are tied to account verification and cannot be changed in the app yet. Contact support if you need to update it.',
                'a_ar' => 'يرتبط رقم الهاتف بتوثيق الحساب ولا يمكن تغييره من التطبيق حالياً. تواصل مع الدعم لتحديثه.',
            ],
            [
                'q_en' => 'How do I track an active delivery?',
                'q_ar' => 'كيف أتتبع طلب توصيل نشط؟',
                'a_en' => 'Open the Deliveries tab and tap any active order to see the driver’s live location and status updates.',
                'a_ar' => 'افتح تبويب التوصيلات واضغط أي طلب نشط لرؤية موقع السائق المباشر وتحديثات الحالة.',
            ],
        ],
    ],

];
