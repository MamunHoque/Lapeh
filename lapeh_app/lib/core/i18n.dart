import 'app_state.dart';

String tr(String key) {
  final lang = localeNotifier.value.languageCode;
  return (_strings[lang]?[key]) ?? _strings['en']![key] ?? key;
}

const Map<String, Map<String, String>> _strings = {
  'en': {
    // Auth
    'welcome_back': 'Welcome back',
    'dispatch_tagline': 'Dispatch a driver in seconds.',
    'driver_signin': 'Driver sign in',
    'driver_tagline': 'Go online and start earning.',
    'phone_email': 'Phone or email',
    'mobile_number': 'Mobile number',
    'password': 'Password',
    'sign_in': 'Sign in',
    'restaurant': 'Restaurant',
    'driver': 'Driver',
    'role_hint': 'Your interface adapts to your account role.',

    // Nav
    'home': 'Home',
    'deliveries': 'Deliveries',
    'reports': 'Reports',
    'profile': 'Profile',
    'trips': 'Trips',
    'earnings': 'Earnings',

    // Dashboard
    'good_evening': 'Good evening 👋',
    'new_delivery': 'New delivery request',
    'active_deliveries': 'Active deliveries',
    'see_all': 'See all',
    'no_active': 'No active deliveries',

    // Create order
    'create_send': 'Create & send link',
    'customer_name': 'Customer name',
    'mobile_number_label': 'Mobile number',
    'order_value': 'Order value',
    'prep_time': 'Prep time (min)',
    'notes_optional': 'Notes (optional)',
    'sms_hint': "We'll text the customer a link to confirm location & pay.",

    // Waiting / tracking
    'link_sent': 'Link sent via SMS',
    'customer_notified': 'Customer notified',
    'customer_progress': 'Customer progress',
    'link_delivered': 'Link delivered',
    'location_confirmed': 'Location confirmed',
    'payment_completed': 'Payment completed',
    'ready_dispatch': 'Ready to dispatch',
    'waiting_customer': 'Waiting for the customer to confirm location & pay…',
    'resend_link': 'Resend link',
    'copy_link': 'Copy link',
    'link_copied': 'Link copied',
    'live_tracking': 'Live tracking',
    'delivery_fee': 'Delivery fee',

    // Order status labels
    'status_created': 'Created',
    'status_searching': 'Searching driver',
    'status_assigned': 'Driver assigned',
    'status_picked_up': 'Picked up',
    'status_on_the_way': 'On the way',
    'status_delivered': 'Delivered',
    'status_cancelled': 'Cancelled',

    // Driver home
    'youre_online': "You're online",
    'youre_offline': "You're offline",
    'go_online_hint': 'Go online to receive requests',
    'searching_hint': 'Searching for requests nearby…',
    'location_permission': 'Location permission required to go online',
    'today': 'Today',
    'online': 'Online',

    // Delivery flow
    'to_restaurant': 'To restaurant',
    'to_customer': 'To customer',
    'pickup': 'Pickup',
    'arrived_restaurant': 'Arrived at restaurant',
    'picked_up_start': 'Picked up — start delivery',
    'ive_arrived': "I've arrived",
    'confirm_delivery': 'Confirm delivery',
    'enter_code': 'Enter delivery code',
    'ask_code': 'Ask the customer for the 4-digit code on their tracking page.',
    'add_photo': 'Add delivery photo (optional)',
    'confirm_btn': 'Confirm delivery',
    'wrong_code': 'Wrong code or server error. Try again.',

    // Delivered
    'delivered_title': 'Delivered!',
    'back_online': 'Back online',
    'go_offline': 'Go offline',
    'delivery_earnings': 'Delivery earnings',
    'distance': 'Distance',
    'order_value_label': 'Order value',

    // Profile
    'language': 'Language',
    'notifications': 'Notifications',
    'help_support': 'Help & Support',
    'logout': 'Log out',
    'switch_role': 'Switch role',

    // Errors / misc
    'error_phone_pass': 'Enter phone and password',
    'error_fields': 'Customer name, phone and order value are required',
    'error_value': 'Enter a valid order value',
    'no_trips': 'No trips yet today',
    'no_orders': 'No orders yet',
    'error_prefix': 'Error',
    'failed_prefix': 'Failed',

    // Lists / tabs
    'active': 'Active',
    'history': 'History',
    'no_driver_yet': 'No driver yet',
    'recent_trips': 'Recent trips',
    'trips_today': 'trips today',
    'completed_label': 'completed',
    'link_resent': 'Link resent',
    'restaurant_pickup': 'Restaurant',

    // Tracking status sentences
    'track_searching': 'Searching for driver…',
    'track_assigned': 'Driver assigned — heading to pickup',
    'track_picked_up': 'Order picked up',
    'track_on_the_way': 'On the way to customer',
    'track_delivered': 'Delivered ✓',
    'track_cancelled': 'Order cancelled',
    'order_created': 'Order created',

    // Delivery flow extras
    'no_coordinates': 'No coordinates',
    'order_prefix': 'Order',
    'head_to': 'Head to',
    'collect_order': 'Collect order',
    'you_marker': 'You',

    // Dashboard / driver home / offer sheet
    'revenue': 'Revenue',
    'aed_fees': 'AED fees',
    'on_the_road': 'on the road',
    'waiting_for_customer': 'Waiting for customer',
    'new_request': 'New request',
    'sec_suffix': 's',
    'aed_earnings': 'AED earnings',
    'pickup_short': 'Pickup',
    'km_away': 'km away',
    'dropoff': 'Drop-off',
    'reject': 'Reject',
    'accept': 'Accept',
  },
  'ar': {
    // Auth
    'welcome_back': 'مرحبًا بعودتك',
    'dispatch_tagline': 'أرسل سائقًا في ثوانٍ.',
    'driver_signin': 'تسجيل دخول السائق',
    'driver_tagline': 'اتصل بالإنترنت وابدأ الكسب.',
    'phone_email': 'الهاتف أو البريد الإلكتروني',
    'mobile_number': 'رقم الجوال',
    'password': 'كلمة المرور',
    'sign_in': 'تسجيل الدخول',
    'restaurant': 'مطعم',
    'driver': 'سائق',
    'role_hint': 'تتكيّف الواجهة حسب نوع حسابك.',

    // Nav
    'home': 'الرئيسية',
    'deliveries': 'التوصيلات',
    'reports': 'التقارير',
    'profile': 'الملف',
    'trips': 'الرحلات',
    'earnings': 'الأرباح',

    // Dashboard
    'good_evening': 'مساء الخير 👋',
    'new_delivery': 'طلب توصيل جديد',
    'active_deliveries': 'التوصيلات النشطة',
    'see_all': 'عرض الكل',
    'no_active': 'لا توجد توصيلات نشطة',

    // Create order
    'create_send': 'إنشاء وإرسال الرابط',
    'customer_name': 'اسم العميل',
    'mobile_number_label': 'رقم الجوال',
    'order_value': 'قيمة الطلب',
    'prep_time': 'وقت التحضير (دقيقة)',
    'notes_optional': 'ملاحظات (اختياري)',
    'sms_hint': 'سنرسل للعميل رابطًا عبر رسالة نصية لتأكيد الموقع والدفع.',

    // Waiting / tracking
    'link_sent': 'تم إرسال الرابط عبر الرسائل',
    'customer_notified': 'تم إخطار العميل',
    'customer_progress': 'تقدّم العميل',
    'link_delivered': 'تم تسليم الرابط',
    'location_confirmed': 'تم تأكيد الموقع',
    'payment_completed': 'تم اكتمال الدفع',
    'ready_dispatch': 'جاهز للإرسال',
    'waiting_customer': 'في انتظار العميل لتأكيد الموقع والدفع…',
    'resend_link': 'إعادة إرسال الرابط',
    'copy_link': 'نسخ الرابط',
    'link_copied': 'تم نسخ الرابط',
    'live_tracking': 'تتبع مباشر',
    'delivery_fee': 'رسوم التوصيل',

    // Order status labels
    'status_created': 'تم الإنشاء',
    'status_searching': 'البحث عن سائق',
    'status_assigned': 'تم تعيين سائق',
    'status_picked_up': 'تم الاستلام',
    'status_on_the_way': 'في الطريق',
    'status_delivered': 'تم التوصيل',
    'status_cancelled': 'ملغي',

    // Driver home
    'youre_online': 'أنت متصل',
    'youre_offline': 'أنت غير متصل',
    'go_online_hint': 'اتصل لاستقبال الطلبات',
    'searching_hint': 'جارٍ البحث عن طلبات قريبة…',
    'location_permission': 'مطلوب إذن الموقع للاتصال',
    'today': 'اليوم',
    'online': 'متصل',

    // Delivery flow
    'to_restaurant': 'إلى المطعم',
    'to_customer': 'إلى العميل',
    'pickup': 'استلام الطلب',
    'arrived_restaurant': 'وصلت إلى المطعم',
    'picked_up_start': 'تم الاستلام — ابدأ التوصيل',
    'ive_arrived': 'لقد وصلت',
    'confirm_delivery': 'تأكيد التسليم',
    'enter_code': 'أدخل رمز التسليم',
    'ask_code': 'اطلب من العميل الرمز المكوّن من 4 أرقام في صفحة التتبع.',
    'add_photo': 'إضافة صورة توصيل (اختياري)',
    'confirm_btn': 'تأكيد التسليم',
    'wrong_code': 'رمز خاطئ أو خطأ في الخادم. حاول مجددًا.',

    // Delivered
    'delivered_title': 'تم التوصيل!',
    'back_online': 'العودة للاتصال',
    'go_offline': 'قطع الاتصال',
    'delivery_earnings': 'أرباح التوصيل',
    'distance': 'المسافة',
    'order_value_label': 'قيمة الطلب',

    // Profile
    'language': 'اللغة',
    'notifications': 'الإشعارات',
    'help_support': 'المساعدة والدعم',
    'logout': 'تسجيل الخروج',
    'switch_role': 'تبديل الدور',

    // Errors / misc
    'error_phone_pass': 'أدخل رقم الهاتف وكلمة المرور',
    'error_fields': 'اسم العميل والهاتف وقيمة الطلب إلزامية',
    'error_value': 'أدخل قيمة طلب صحيحة',
    'no_trips': 'لا توجد رحلات اليوم',
    'no_orders': 'لا توجد طلبات بعد',
    'error_prefix': 'خطأ',
    'failed_prefix': 'فشل',

    // Lists / tabs
    'active': 'نشط',
    'history': 'السجل',
    'no_driver_yet': 'لا يوجد سائق بعد',
    'recent_trips': 'رحلات حديثة',
    'trips_today': 'رحلة اليوم',
    'completed_label': 'مكتملة',
    'link_resent': 'تمت إعادة إرسال الرابط',
    'restaurant_pickup': 'المطعم',

    // Tracking status sentences
    'track_searching': 'جارٍ البحث عن سائق…',
    'track_assigned': 'تم تعيين سائق — متجه إلى الاستلام',
    'track_picked_up': 'تم استلام الطلب',
    'track_on_the_way': 'في الطريق إلى العميل',
    'track_delivered': 'تم التوصيل ✓',
    'track_cancelled': 'تم إلغاء الطلب',
    'order_created': 'تم إنشاء الطلب',

    // Delivery flow extras
    'no_coordinates': 'لا توجد إحداثيات',
    'order_prefix': 'طلب',
    'head_to': 'توجّه إلى',
    'collect_order': 'استلم الطلب',
    'you_marker': 'أنت',

    // Dashboard / driver home / offer sheet
    'revenue': 'الإيرادات',
    'aed_fees': 'رسوم بالدرهم',
    'on_the_road': 'على الطريق',
    'waiting_for_customer': 'في انتظار العميل',
    'new_request': 'طلب جديد',
    'sec_suffix': 'ث',
    'aed_earnings': 'أرباح بالدرهم',
    'pickup_short': 'استلام',
    'km_away': 'كم',
    'dropoff': 'التسليم',
    'reject': 'رفض',
    'accept': 'قبول',
  },
};
