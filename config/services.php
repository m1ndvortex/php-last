<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp Business API integration
    |
    */
    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS service providers
    |
    */
    'sms' => [
        'provider' => env('SMS_PROVIDER', 'kavenegar'), // kavenegar, twilio, aws_sns
        'api_url' => env('SMS_API_URL'),
        'api_key' => env('SMS_API_KEY'),
        'sender_id' => env('SMS_SENDER_ID'),
        
        // Kavenegar specific settings (Iranian SMS provider)
        'kavenegar' => [
            'api_url' => env('KAVENEGAR_API_URL', 'https://api.kavenegar.com/v1'),
            'api_key' => env('KAVENEGAR_API_KEY'),
            'sender' => env('KAVENEGAR_SENDER'),
        ],
        
        // Twilio specific settings
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
        
        // AWS SNS specific settings
        'aws_sns' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Service Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for email service providers
    |
    */
    'email' => [
        'provider' => env('EMAIL_PROVIDER', 'smtp'), // smtp, mailgun, ses, postmark
        'from_address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'from_name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for push notification services
    |
    */
    'push_notifications' => [
        'fcm' => [
            'server_key' => env('FCM_SERVER_KEY'),
            'sender_id' => env('FCM_SENDER_ID'),
        ],
        'apns' => [
            'key_id' => env('APNS_KEY_ID'),
            'team_id' => env('APNS_TEAM_ID'),
            'app_bundle_id' => env('APNS_APP_BUNDLE_ID'),
            'private_key_path' => env('APNS_PRIVATE_KEY_PATH'),
            'production' => env('APNS_PRODUCTION', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for file storage services
    |
    */
    'storage' => [
        's3' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics and Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for analytics and monitoring services
    |
    */
    'analytics' => [
        'google_analytics' => [
            'tracking_id' => env('GOOGLE_ANALYTICS_TRACKING_ID'),
        ],
        'mixpanel' => [
            'token' => env('MIXPANEL_TOKEN'),
        ],
    ],

    'monitoring' => [
        'sentry' => [
            'dsn' => env('SENTRY_LARAVEL_DSN'),
            'environment' => env('APP_ENV', 'production'),
        ],
    ],

];