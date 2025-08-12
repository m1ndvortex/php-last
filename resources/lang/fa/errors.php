<?php

return [
    // General errors
    'something_went_wrong' => 'مشکلی پیش آمده است. لطفاً دوباره تلاش کنید.',
    'network_error' => 'خطای شبکه. لطفاً اتصال خود را بررسی کرده و دوباره تلاش کنید.',
    'server_error' => 'خطای سرور. لطفاً بعداً دوباره تلاش کنید.',
    'validation_failed' => 'اطلاعات ارائه شده نامعتبر است.',
    'unauthorized' => 'شما مجاز به انجام این عمل نیستید.',
    'not_found' => 'منبع درخواستی یافت نشد.',
    
    // Inventory errors
    'inventory' => [
        'insufficient_stock' => 'موجودی کافی برای اقلام درخواستی وجود ندارد.',
        'item_not_found' => 'کالای موجودی یافت نشد.',
        'failed_to_create_item' => 'ایجاد کالای موجودی ناموفق بود. لطفاً دوباره تلاش کنید.',
        'failed_to_update_item' => 'به‌روزرسانی کالای موجودی ناموفق بود. لطفاً دوباره تلاش کنید.',
        'failed_to_delete_item' => 'حذف کالای موجودی ناموفق بود. لطفاً دوباره تلاش کنید.',
        'failed_to_load_categories' => 'بارگذاری دسته‌بندی‌ها ناموفق بود. لطفاً صفحه را تازه‌سازی کنید.',
        'failed_to_load_locations' => 'بارگذاری مکان‌ها ناموفق بود. لطفاً صفحه را تازه‌سازی کنید.',
        'failed_to_load_gold_purity_options' => 'بارگذاری گزینه‌های عیار طلا ناموفق بود. لطفاً صفحه را تازه‌سازی کنید.',
        'failed_to_load_form_data' => 'بارگذاری اطلاعات فرم ناموفق بود. لطفاً صفحه را تازه‌سازی کرده و دوباره تلاش کنید.',
        'stock_reservation_failed' => 'رزرو موجودی انبار ناموفق بود.',
        'stock_restoration_failed' => 'بازگردانی موجودی انبار ناموفق بود.',
        'movement_creation_failed' => 'ایجاد رکورد حرکت موجودی ناموفق بود.',
        'invalid_quantity' => 'مقدار مشخص شده نامعتبر است.',
        'negative_stock_not_allowed' => 'موجودی نمی‌تواند کمتر از صفر باشد.',
    ],
    
    // Pricing errors
    'pricing' => [
        'calculation_failed' => 'محاسبه قیمت کالا ناموفق بود.',
        'invalid_gold_price' => 'قیمت طلای مشخص شده نامعتبر است.',
        'invalid_percentage' => 'درصد مشخص شده نامعتبر است.',
        'missing_pricing_data' => 'اطلاعات قیمت‌گذاری مورد نیاز موجود نیست.',
        'negative_price_not_allowed' => 'قیمت منفی مجاز نیست.',
        'zero_weight_not_allowed' => 'وزن کالا برای محاسبه قیمت نمی‌تواند صفر باشد.',
        'invalid_formula_parameters' => 'پارامترهای نامعتبر برای فرمول قیمت‌گذاری.',
    ],
    
    // Invoice errors
    'invoice' => [
        'creation_failed' => 'ایجاد فاکتور ناموفق بود. لطفاً دوباره تلاش کنید.',
        'update_failed' => 'به‌روزرسانی فاکتور ناموفق بود. لطفاً دوباره تلاش کنید.',
        'deletion_failed' => 'حذف فاکتور ناموفق بود. لطفاً دوباره تلاش کنید.',
        'not_found' => 'فاکتور یافت نشد.',
        'already_paid' => 'فاکتور قبلاً پرداخت شده و قابل تغییر نیست.',
        'invalid_status' => 'وضعیت فاکتور نامعتبر است.',
        'no_items' => 'فاکتور باید حداقل شامل یک کالا باشد.',
        'customer_required' => 'مشتری برای ایجاد فاکتور الزامی است.',
        'pdf_generation_failed' => 'تولید PDF ناموفق بود. لطفاً دوباره تلاش کنید.',
    ],
    
    // API errors
    'api' => [
        'endpoint_not_found' => 'نقطه پایانی API یافت نشد.',
        'method_not_allowed' => 'متد HTTP برای این نقطه پایانی مجاز نیست.',
        'rate_limit_exceeded' => 'درخواست‌های زیادی ارسال شده. لطفاً بعداً دوباره تلاش کنید.',
        'invalid_json' => 'داده JSON نامعتبر ارائه شده.',
        'missing_required_fields' => 'فیلدهای الزامی موجود نیست.',
        'invalid_data_format' => 'فرمت داده نامعتبر ارائه شده.',
    ],
    
    // Database errors
    'database' => [
        'connection_failed' => 'اتصال به پایگاه داده ناموفق بود. لطفاً بعداً دوباره تلاش کنید.',
        'query_failed' => 'کوئری پایگاه داده ناموفق بود. لطفاً دوباره تلاش کنید.',
        'constraint_violation' => 'نقض محدودیت پایگاه داده.',
        'duplicate_entry' => 'ورودی تکراری شناسایی شد.',
        'foreign_key_constraint' => 'به دلیل وجود داده‌های مرتبط، امکان حذف رکورد وجود ندارد.',
    ],
    
    // Authentication errors
    'auth' => [
        'invalid_credentials' => 'اطلاعات ورود نامعتبر است.',
        'token_expired' => 'توکن احراز هویت منقضی شده است.',
        'token_invalid' => 'توکن احراز هویت نامعتبر است.',
        'session_expired' => 'جلسه شما منقضی شده است. لطفاً دوباره وارد شوید.',
        'account_locked' => 'حساب کاربری قفل شده است. لطفاً با مدیر تماس بگیرید.',
        'insufficient_permissions' => 'شما مجوزهای کافی ندارید.',
    ],
    
    // File upload errors
    'upload' => [
        'file_too_large' => 'اندازه فایل از حد مجاز بیشتر است.',
        'invalid_file_type' => 'نوع فایل نامعتبر است. لطفاً فایل معتبر آپلود کنید.',
        'upload_failed' => 'آپلود فایل ناموفق بود. لطفاً دوباره تلاش کنید.',
        'file_not_found' => 'فایل آپلود شده یافت نشد.',
        'storage_error' => 'خطای ذخیره‌سازی فایل. لطفاً دوباره تلاش کنید.',
    ],
    
    // Report errors
    'reports' => [
        'generation_failed' => 'تولید گزارش ناموفق بود. لطفاً دوباره تلاش کنید.',
        'no_data_available' => 'هیچ داده‌ای برای معیارهای انتخاب شده موجود نیست.',
        'invalid_date_range' => 'بازه تاریخ نامعتبر مشخص شده.',
        'export_failed' => 'صادرات گزارش ناموفق بود. لطفاً دوباره تلاش کنید.',
        'invalid_format' => 'فرمت گزارش درخواستی نامعتبر است.',
    ],
];