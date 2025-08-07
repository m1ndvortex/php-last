<?php

/*
|--------------------------------------------------------------------------
| Load The Cached Routes
|--------------------------------------------------------------------------
|
| Here we will decode and unserialize the RouteCollection instance that
| holds all of the route information for an application. This allows
| us to instantaneously load the entire route map into the router.
|
*/

app('router')->setCompiledRoutes(
    array (
  'compiled' => 
  array (
    0 => false,
    1 => 
    array (
      '/sanctum/csrf-cookie' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'sanctum.csrf-cookie',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/_ignition/health-check' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'ignition.healthCheck',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/_ignition/execute-solution' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'ignition.executeSolution',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/_ignition/update-config' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'ignition.updateConfig',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/health' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::TO130cyr4pX4FF91',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/auth/login' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::4cLaiANUkPx36ADJ',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/auth/logout' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::FKpGOmDFwqDbeBI2',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/auth/user' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::jIpM6q3s5pjK1hHd',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/auth/profile' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::YwEMR0X1VF7LS1VW',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/auth/password' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::fu12HaCmHUug5F4w',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/localization/current' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::QYNqzaSlPGgibPXA',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/localization/supported' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Hc0doq6srlGSLWUZ',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/localization/translations' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::cIEarWsUaqsrd7kR',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/localization/switch-language' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::BVQTTV53ksYvAir6',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/localization/calendar' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::KHhZmWnxyA5jgXXp',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/localization/convert-date' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::ugwUA13K9uIgn3Oq',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/localization/format-number' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::i20luA8AisKSGHRE',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/localization/number-to-words' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::1OFIguAQrnfNNvUG',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/kpis' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::I90Os6ljhUK73Gfd',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/sales-chart' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::2laIJN3UJizSunp6',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/category-performance' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::TamVH6iMSY0fsM6N',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/gold-purity-performance' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::JXD3ZBAYL06DEWPz',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/category-stock-alerts' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::UdG5QkaAwqw56Z1R',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/alerts' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::ID3dMujSmZYZ7YNr',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/alerts/mark-read' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::G2LZ3xgb042WugGy',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/layout' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::hLh6wt8NOUFmXXYp',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::at1j1idpJ68a1LS9',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/presets' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::NeoEzGhTDtTg0Tu1',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/presets/apply' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::uXKRUudMZuJEhDT7',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/widgets/available' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::FifM3DBhgJp0I8by',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/widgets/add' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::WLmxGnq9lOqmRO3T',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/widgets/remove' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::iHmgiiI5gk2tJqfy',
          ),
          1 => NULL,
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/widgets/config' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::i4mUXLTNHrYh1Qdu',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/reset' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::6R20yWUPCh5FsH8g',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/dashboard/clear-cache' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::9RXUyaaumDDGcFZA',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory-reports/category-hierarchy' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::C2C7QiwcJu8xD0qs',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory-reports/category-sales-performance' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::TD8fl58ntM2VcBY2',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory-reports/category-stock-levels' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::msfL6ph4sH5QJg9h',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory-reports/gold-purity-analysis' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::t28LJJ2ZJRhGzAL0',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory-reports/inventory-analytics' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::rt0r2BR3ocZRyJaD',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/customers/aging-report' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::V9Yxc68C3YMQY47w',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/customers/crm-pipeline' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::GCm94qHNTGTKoCa0',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/customers/upcoming-birthdays' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::cynw7TJGyVVfoxTy',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/customers/upcoming-anniversaries' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::zoouMuVrBtS9x9Mz',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/customers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'customers.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/invoices/category-stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::dTO1tvapPORO3Oxi',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/invoices/gold-purity-stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::YEhDxNofi8s5Yc8W',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/invoices/batch-pdf' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::6itlx7Gi4KROTJlZ',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/invoices/batch-download' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::NLldaS3LTyKaEbUO',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/invoices' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'invoices.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'invoices.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/invoice-templates/default-structure' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::ekwjxoRx746onmsq',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/invoice-templates/validate-structure' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::p6rUDQ83Cg3tC5qt',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/invoice-templates' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'invoice-templates.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'invoice-templates.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/recurring-invoices/process-due' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::CxpijPHkAJHiXBNw',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/recurring-invoices/upcoming' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::qV0ZdAuumfKnMNNc',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/recurring-invoices/stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::KyrGBlnkhnri9T3R',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/recurring-invoices' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'recurring-invoices.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'recurring-invoices.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory/low-stock' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Emi9Zye3hYmrjUgU',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory/expiring' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::v7UXTfk0c7S0LifA',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory/expired' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::b9V7BNylxYpDKgjF',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory/summary/location' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::NtCwv9q4QLRj7VDF',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory/summary/category' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::fkxSfbnjidiukQQx',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory/gold-purity-options' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::uEmpPaIbXAXmdHkX',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/inventory' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'inventory.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'inventory.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/stock-audits' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'stock-audits.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'stock-audits.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/bom/production-cost' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::yAgdKYo1EGLkQZV5',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/bom/can-produce' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::pwLJH2AEBlgfACwD',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/bom/produce' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::mr2Jr1C2KgLLp6wF',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/bom/production-requirements' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::uNINDY62vcS8QypN',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/bom/tree' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::En9WP5eQXs6wDY3U',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/bom/usage-report' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::dTOZ2z7o3mFvboUc',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/bom' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bom.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'bom.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/categories/hierarchy' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::7sjki3k1EpzC3dwk',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/categories/for-select' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::t4RyDs9ua6aP0eFI',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/categories/main-categories' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::PIOFjo0ZMCDEyW8x',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/categories/subcategories' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::7MVqKfLZ3Li3roQA',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/categories/search' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::IoFz0BqyJrd2MjzZ',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/categories/gold-purity-options' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::kdwVIdtyHArvWeDM',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/categories/reorder' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::nzyqcK89XxMMFEgl',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/categories' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'categories.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'categories.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/locations' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'locations.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'locations.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/accounts' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'accounts.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'accounts.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/chart-of-accounts' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::hafio160kHkSIoRi',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/transactions' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'transactions.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'transactions.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/reports/trial-balance' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::K2JfIcVhxVm4Fljn',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/reports/balance-sheet' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::w2uYQSzslKFQlyq1',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/reports/income-statement' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::8zewWOhCj4N7bScl',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/reports/cash-flow-statement' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::eTbBsfS22ZqL3jxs',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/reports/aged-receivables' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::RLBF0np1j4baPs0R',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/reports/aged-payables' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::OvbqHDTOMbZ0Q57s',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/reports/custom' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::z9gOGbqegIn6MYz5',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/cost-centers' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cost-centers.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'cost-centers.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/assets' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'assets.index',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'assets.store',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/asset-register' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Zs8eTldRXgfymJid',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/accounting/process-depreciation' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::m7XuaAXiidA22RPR',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/config/business-info' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::CqNo5bIaaztifElG',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::VIMdqwvFTvM7bCfq',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/config/logo' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::GappUKgTTp13wSJI',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/config/tax' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Qk1kNjatlCbW4NjY',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::2GM5wdJ5Hg2pfIYD',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/config/profit' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::8vEjl4hTMZeebygB',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::CthH9KF2q2o05TOJ',
          ),
          1 => NULL,
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/config/clear-cache' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::BKJoGKw6yUtUgejT',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/roles' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::czCxXZ1KhDSYzKk3',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::pJNIan6DKgaTbgDF',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/permissions' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::WEOKuavIoaBTkgwc',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/permissions/user' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lZ6NMNDthZIdHUaj',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/permissions/check' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Ln3ZkZYAecOix3zw',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/message-templates' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::O69Hx7b72fxQ6pJD',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lWbgetEkMPsPJ5kv',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/message-templates/by-type-category' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::ghFhlvgL7efaTHjR',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/message-templates/render' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Agx3xeXCX1xbi6nx',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/message-templates/default-variables' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::zBZgaAddoqWKbMok',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/2fa/enable' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::AYklvpWEUjlMKOR7',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/2fa/confirm' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::URRS8mlP8kmqow6R',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/2fa/disable' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::12MNxXRpTRJhpDQV',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/2fa/regenerate-backup-codes' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::9rbWXNqINViKMx3y',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/sessions/active' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::d0Uf1vGWk2MUdJaB',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/sessions/terminate' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::6VpPZpyAdExrZZ2q',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/sessions/terminate-others' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::t286rN3BqndWZpCj',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/sessions/stats' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::WgImt3Opld0Q3Okw',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/audit/logs' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::6Sx7rWoyE8pFJCOK',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/audit/statistics' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::gy5yosza2YTQUZPx',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/audit/export' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lrhglepyp7U0IVRx',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/anomalies' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::5pCCeoAUHY1xNlT0',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/security/anomalies/statistics' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::KnsIv0DdwaCKtHkJ',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/compliance/data-types' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::FuP4HAxNPhD8ujFP',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/compliance/statistics' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::QacAsrnH7tRI7XNO',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/compliance/export' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::mowjs1e5GcEny82u',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::tDl1zJ5889vYDrpS',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/compliance/export/process' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Xyu7zriyi6l148VP',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/compliance/deletion' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::64CmIR5MA4D6wrmf',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::MWXtFJcvXZWakWZQ',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/compliance/deletion/process' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::m8iduBjf6jRITTAw',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/queue' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::ibHv12On0g9FUa0a',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/queue/history' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::jdonDa5lc34gxWtr',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/queue/backup' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::URLD3CSNZiU8syj1',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/queue/recurring-invoices' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::u0QfGpI55eCERwwx',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/queue/reminders' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::4DwaKwTQ0h9Sd3bk',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/queue/stock-alerts' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::tQNXx8YHPCE6QsXk',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/queue/communication' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::wjxfyVpIi3PDI2Ut',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/queue/bulk-communications' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::XEw2ITOEuw9jovrP',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/queue/sync-offline' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::VZV2MH8Y9hOOm5pR',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/queue/failed-jobs' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::qylyzrcYSL0cBlTd',
          ),
          1 => NULL,
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/api/queue/retry-job' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::6cUp7fJjVf7rLqN3',
          ),
          1 => NULL,
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      '/' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::GEVBwylq4ULEOCQf',
          ),
          1 => NULL,
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
    ),
    2 => 
    array (
      0 => '{^(?|/api/(?|c(?|ustomers/([^/]++)(?|/(?|c(?|rm\\-stage(*:56)|ommunicate(*:73))|vcard(*:86))|(*:94))|ategories/([^/]++)(?|/(?|image(?|(*:135))|path(*:148))|(*:157))|o(?|nfig/category/([^/]++)(*:192)|mpliance/(?|export/([^/]++)/download(*:236)|deletion/([^/]++)/(?|approve(*:272)|reject(*:286)))))|inv(?|oice(?|s/([^/]++)(?|/(?|duplicate(*:337)|pdf(?|(*:351)|/download(*:368))|mark\\-(?|sent(*:390)|paid(*:402))|attachments(?|(*:425)|/([^/]++)(*:442)))|(*:452))|\\-templates/([^/]++)(?|/(?|duplicate(*:497)|set\\-default(*:517))|(*:526)))|entory/([^/]++)(?|/(?|transfer(*:566)|movements(*:583))|(*:592)))|r(?|ecurring\\-invoices/([^/]++)(?|/(?|generate(*:648)|pause(*:661)|resume(*:675))|(*:684))|oles/(?|([^/]++)(?|(*:712))|assign(*:727)|remove(*:741)))|stock\\-audits/([^/]++)(?|/(?|start(*:785)|c(?|omplete(*:804)|ancel(*:817))|items/([^/]++)(*:840)|bulk\\-update(*:860)|variance\\-report(*:884)|uncounted\\-items(*:908)|export(*:922))|(*:931))|bom/([^/]++)(?|(*:955))|locations/([^/]++)(?|(*:985))|accounting/(?|a(?|ccounts/([^/]++)(?|(*:1031)|/(?|balance(*:1051)|ledger(*:1066)))|ssets/([^/]++)(?|(*:1094)|/d(?|ispose(*:1114)|epreciation(?|(*:1137)|\\-schedule(*:1156)))))|transactions/([^/]++)(?|(*:1193)|/(?|lock(*:1210)|unlock(*:1225)|approve(*:1241)|duplicate(*:1259)))|cost\\-centers/([^/]++)(?|(*:1295)))))/?$}sDu',
    ),
    3 => 
    array (
      56 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::wkWzGgQoCaZLdsyy',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      73 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::iaDIqttX79cwDMSe',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      86 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::GgI0IsH1UGEKX9Vp',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      94 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'customers.show',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'customers.update',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'customers.destroy',
          ),
          1 => 
          array (
            0 => 'customer',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      135 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::d9ZhSbuh7ndDzZlI',
          ),
          1 => 
          array (
            0 => 'category',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::HEAW3pbeWaPqRSgi',
          ),
          1 => 
          array (
            0 => 'category',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      148 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::NwtmGrjdmjNvkULT',
          ),
          1 => 
          array (
            0 => 'category',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      157 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'categories.show',
          ),
          1 => 
          array (
            0 => 'category',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'categories.update',
          ),
          1 => 
          array (
            0 => 'category',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'categories.destroy',
          ),
          1 => 
          array (
            0 => 'category',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      192 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::G7df8rvBgq9dfMHD',
          ),
          1 => 
          array (
            0 => 'category',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      236 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::9l3YRX1zdFcbmfVa',
          ),
          1 => 
          array (
            0 => 'exportRequest',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      272 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::gc5xKdde5JaBhb9X',
          ),
          1 => 
          array (
            0 => 'deletionRequest',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      286 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::na94l0C7L8UWkF5J',
          ),
          1 => 
          array (
            0 => 'deletionRequest',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      337 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::k7BcT65rzoBfX8LN',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      351 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::1UOIgPhesA2DqP8i',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      368 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::LVpuLg47WNRlm7Nj',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      390 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::sej8r7JysTFpKnQb',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      402 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::DQFCpHRDOoA4JwVa',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      425 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::VvGtWSf3TxQHldjY',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      442 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::wbtr9MtB7lMhh31N',
          ),
          1 => 
          array (
            0 => 'invoice',
            1 => 'attachment',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      452 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'invoices.show',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'invoices.update',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'invoices.destroy',
          ),
          1 => 
          array (
            0 => 'invoice',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      497 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::tnSujjp9vT2jerfX',
          ),
          1 => 
          array (
            0 => 'invoiceTemplate',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      517 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Iqlk5exB52020e5h',
          ),
          1 => 
          array (
            0 => 'invoiceTemplate',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      526 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'invoice-templates.show',
          ),
          1 => 
          array (
            0 => 'invoice_template',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'invoice-templates.update',
          ),
          1 => 
          array (
            0 => 'invoice_template',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'invoice-templates.destroy',
          ),
          1 => 
          array (
            0 => 'invoice_template',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      566 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::OzxtRoDyBrrjUIRo',
          ),
          1 => 
          array (
            0 => 'inventory',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      583 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Znz1M8j4X3LvX94U',
          ),
          1 => 
          array (
            0 => 'inventory',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      592 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'inventory.show',
          ),
          1 => 
          array (
            0 => 'inventory',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'inventory.update',
          ),
          1 => 
          array (
            0 => 'inventory',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'inventory.destroy',
          ),
          1 => 
          array (
            0 => 'inventory',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      648 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::Kb1Ow4SXAxZAZDW8',
          ),
          1 => 
          array (
            0 => 'recurringInvoice',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      661 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::hPDEhjxJp8L7D5Mc',
          ),
          1 => 
          array (
            0 => 'recurringInvoice',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      675 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::nQU1R72grGZT3pe1',
          ),
          1 => 
          array (
            0 => 'recurringInvoice',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      684 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'recurring-invoices.show',
          ),
          1 => 
          array (
            0 => 'recurring_invoice',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'recurring-invoices.update',
          ),
          1 => 
          array (
            0 => 'recurring_invoice',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'recurring-invoices.destroy',
          ),
          1 => 
          array (
            0 => 'recurring_invoice',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      712 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::29votZU4To9KqflV',
          ),
          1 => 
          array (
            0 => 'role',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'generated::EqSXDyFRRkzVxo9D',
          ),
          1 => 
          array (
            0 => 'role',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      727 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::41vH4vxgc4fj68q7',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      741 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::pBMhqS0Zr7GYiZvN',
          ),
          1 => 
          array (
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      785 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::pFZfA1lE8DjlOXzp',
          ),
          1 => 
          array (
            0 => 'stockAudit',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      804 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::s7Jhq7rsPQnBwJuZ',
          ),
          1 => 
          array (
            0 => 'stockAudit',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      817 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::M7pKu3sYH34LFBHJ',
          ),
          1 => 
          array (
            0 => 'stockAudit',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      840 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::STzT0XPBAcBDeM56',
          ),
          1 => 
          array (
            0 => 'stockAudit',
            1 => 'auditItem',
          ),
          2 => 
          array (
            'PUT' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      860 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::gxPxNr8SqF90flwg',
          ),
          1 => 
          array (
            0 => 'stockAudit',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      884 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::XpCBUdl6hb0RsdMV',
          ),
          1 => 
          array (
            0 => 'stockAudit',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      908 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::53vZKV7XkbIciJpC',
          ),
          1 => 
          array (
            0 => 'stockAudit',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      922 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::W61BErLzmGh3BnOt',
          ),
          1 => 
          array (
            0 => 'stockAudit',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      931 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'stock-audits.show',
          ),
          1 => 
          array (
            0 => 'stock_audit',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'stock-audits.update',
          ),
          1 => 
          array (
            0 => 'stock_audit',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'stock-audits.destroy',
          ),
          1 => 
          array (
            0 => 'stock_audit',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      955 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'bom.show',
          ),
          1 => 
          array (
            0 => 'bom',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'bom.update',
          ),
          1 => 
          array (
            0 => 'bom',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'bom.destroy',
          ),
          1 => 
          array (
            0 => 'bom',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      985 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'locations.show',
          ),
          1 => 
          array (
            0 => 'location',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'locations.update',
          ),
          1 => 
          array (
            0 => 'location',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'locations.destroy',
          ),
          1 => 
          array (
            0 => 'location',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1031 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'accounts.show',
          ),
          1 => 
          array (
            0 => 'account',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'accounts.update',
          ),
          1 => 
          array (
            0 => 'account',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'accounts.destroy',
          ),
          1 => 
          array (
            0 => 'account',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1051 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::SiqeiG5mOiQKxN1S',
          ),
          1 => 
          array (
            0 => 'account',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1066 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::NxDnrLWB3HdrHMVO',
          ),
          1 => 
          array (
            0 => 'account',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1094 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'assets.show',
          ),
          1 => 
          array (
            0 => 'asset',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'assets.update',
          ),
          1 => 
          array (
            0 => 'asset',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'assets.destroy',
          ),
          1 => 
          array (
            0 => 'asset',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1114 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::edHerBgEss9pPzu0',
          ),
          1 => 
          array (
            0 => 'asset',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1137 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::lFCTboRBpfaP5MPy',
          ),
          1 => 
          array (
            0 => 'asset',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1156 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::CszHo4yXhq7LOQP9',
          ),
          1 => 
          array (
            0 => 'asset',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1193 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'transactions.show',
          ),
          1 => 
          array (
            0 => 'transaction',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'transactions.update',
          ),
          1 => 
          array (
            0 => 'transaction',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'transactions.destroy',
          ),
          1 => 
          array (
            0 => 'transaction',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
      ),
      1210 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::teRhiwRlCWM7LXeC',
          ),
          1 => 
          array (
            0 => 'transaction',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1225 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::XQdG97VHNuRpdgoz',
          ),
          1 => 
          array (
            0 => 'transaction',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1241 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::QfzZMnE2XgZUhRSL',
          ),
          1 => 
          array (
            0 => 'transaction',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1259 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::H8PP5ruQw5kASuQr',
          ),
          1 => 
          array (
            0 => 'transaction',
          ),
          2 => 
          array (
            'POST' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => false,
          6 => NULL,
        ),
      ),
      1295 => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'cost-centers.show',
          ),
          1 => 
          array (
            0 => 'cost_center',
          ),
          2 => 
          array (
            'GET' => 0,
            'HEAD' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        1 => 
        array (
          0 => 
          array (
            '_route' => 'cost-centers.update',
          ),
          1 => 
          array (
            0 => 'cost_center',
          ),
          2 => 
          array (
            'PUT' => 0,
            'PATCH' => 1,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        2 => 
        array (
          0 => 
          array (
            '_route' => 'cost-centers.destroy',
          ),
          1 => 
          array (
            0 => 'cost_center',
          ),
          2 => 
          array (
            'DELETE' => 0,
          ),
          3 => NULL,
          4 => false,
          5 => true,
          6 => NULL,
        ),
        3 => 
        array (
          0 => NULL,
          1 => NULL,
          2 => NULL,
          3 => NULL,
          4 => false,
          5 => false,
          6 => 0,
        ),
      ),
    ),
    4 => NULL,
  ),
  'attributes' => 
  array (
    'sanctum.csrf-cookie' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'sanctum/csrf-cookie',
      'action' => 
      array (
        'uses' => 'Laravel\\Sanctum\\Http\\Controllers\\CsrfCookieController@show',
        'controller' => 'Laravel\\Sanctum\\Http\\Controllers\\CsrfCookieController@show',
        'namespace' => NULL,
        'prefix' => 'sanctum',
        'where' => 
        array (
        ),
        'middleware' => 
        array (
          0 => 'web',
        ),
        'as' => 'sanctum.csrf-cookie',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'ignition.healthCheck' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '_ignition/health-check',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'Spatie\\LaravelIgnition\\Http\\Middleware\\RunnableSolutionsEnabled',
        ),
        'uses' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\HealthCheckController@__invoke',
        'controller' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\HealthCheckController',
        'as' => 'ignition.healthCheck',
        'namespace' => NULL,
        'prefix' => '_ignition',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'ignition.executeSolution' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => '_ignition/execute-solution',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'Spatie\\LaravelIgnition\\Http\\Middleware\\RunnableSolutionsEnabled',
        ),
        'uses' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\ExecuteSolutionController@__invoke',
        'controller' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\ExecuteSolutionController',
        'as' => 'ignition.executeSolution',
        'namespace' => NULL,
        'prefix' => '_ignition',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'ignition.updateConfig' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => '_ignition/update-config',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'Spatie\\LaravelIgnition\\Http\\Middleware\\RunnableSolutionsEnabled',
        ),
        'uses' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\UpdateConfigController@__invoke',
        'controller' => 'Spatie\\LaravelIgnition\\Http\\Controllers\\UpdateConfigController',
        'as' => 'ignition.updateConfig',
        'namespace' => NULL,
        'prefix' => '_ignition',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::TO130cyr4pX4FF91' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/health',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:171:"function () {
    return \\response()->json([
        \'status\' => \'ok\',
        \'timestamp\' => \\now(),
        \'version\' => \\config(\'app.version\', \'1.0.0\'),
    ]);
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000008210000000000000000";}}',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::TO130cyr4pX4FF91',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::4cLaiANUkPx36ADJ' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/auth/login',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthController@login',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthController@login',
        'namespace' => NULL,
        'prefix' => 'api/auth',
        'where' => 
        array (
        ),
        'as' => 'generated::4cLaiANUkPx36ADJ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::FKpGOmDFwqDbeBI2' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/auth/logout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthController@logout',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthController@logout',
        'namespace' => NULL,
        'prefix' => 'api/auth',
        'where' => 
        array (
        ),
        'as' => 'generated::FKpGOmDFwqDbeBI2',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::jIpM6q3s5pjK1hHd' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/auth/user',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthController@user',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthController@user',
        'namespace' => NULL,
        'prefix' => 'api/auth',
        'where' => 
        array (
        ),
        'as' => 'generated::jIpM6q3s5pjK1hHd',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::YwEMR0X1VF7LS1VW' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/auth/profile',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthController@updateProfile',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthController@updateProfile',
        'namespace' => NULL,
        'prefix' => 'api/auth',
        'where' => 
        array (
        ),
        'as' => 'generated::YwEMR0X1VF7LS1VW',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::fu12HaCmHUug5F4w' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/auth/password',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\Auth\\AuthController@changePassword',
        'controller' => 'App\\Http\\Controllers\\Auth\\AuthController@changePassword',
        'namespace' => NULL,
        'prefix' => 'api/auth',
        'where' => 
        array (
        ),
        'as' => 'generated::fu12HaCmHUug5F4w',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::QYNqzaSlPGgibPXA' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/localization/current',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\LocalizationController@getCurrentLocale',
        'controller' => 'App\\Http\\Controllers\\LocalizationController@getCurrentLocale',
        'namespace' => NULL,
        'prefix' => 'api/localization',
        'where' => 
        array (
        ),
        'as' => 'generated::QYNqzaSlPGgibPXA',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Hc0doq6srlGSLWUZ' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/localization/supported',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\LocalizationController@getSupportedLocales',
        'controller' => 'App\\Http\\Controllers\\LocalizationController@getSupportedLocales',
        'namespace' => NULL,
        'prefix' => 'api/localization',
        'where' => 
        array (
        ),
        'as' => 'generated::Hc0doq6srlGSLWUZ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::cIEarWsUaqsrd7kR' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/localization/translations',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\LocalizationController@getTranslations',
        'controller' => 'App\\Http\\Controllers\\LocalizationController@getTranslations',
        'namespace' => NULL,
        'prefix' => 'api/localization',
        'where' => 
        array (
        ),
        'as' => 'generated::cIEarWsUaqsrd7kR',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::BVQTTV53ksYvAir6' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/localization/switch-language',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\LocalizationController@switchLanguage',
        'controller' => 'App\\Http\\Controllers\\LocalizationController@switchLanguage',
        'namespace' => NULL,
        'prefix' => 'api/localization',
        'where' => 
        array (
        ),
        'as' => 'generated::BVQTTV53ksYvAir6',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::KHhZmWnxyA5jgXXp' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/localization/calendar',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\LocalizationController@getCalendarInfo',
        'controller' => 'App\\Http\\Controllers\\LocalizationController@getCalendarInfo',
        'namespace' => NULL,
        'prefix' => 'api/localization',
        'where' => 
        array (
        ),
        'as' => 'generated::KHhZmWnxyA5jgXXp',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::ugwUA13K9uIgn3Oq' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/localization/convert-date',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\LocalizationController@convertDate',
        'controller' => 'App\\Http\\Controllers\\LocalizationController@convertDate',
        'namespace' => NULL,
        'prefix' => 'api/localization',
        'where' => 
        array (
        ),
        'as' => 'generated::ugwUA13K9uIgn3Oq',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::i20luA8AisKSGHRE' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/localization/format-number',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\LocalizationController@formatNumber',
        'controller' => 'App\\Http\\Controllers\\LocalizationController@formatNumber',
        'namespace' => NULL,
        'prefix' => 'api/localization',
        'where' => 
        array (
        ),
        'as' => 'generated::i20luA8AisKSGHRE',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::1OFIguAQrnfNNvUG' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/localization/number-to-words',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
        ),
        'uses' => 'App\\Http\\Controllers\\LocalizationController@numberToWords',
        'controller' => 'App\\Http\\Controllers\\LocalizationController@numberToWords',
        'namespace' => NULL,
        'prefix' => 'api/localization',
        'where' => 
        array (
        ),
        'as' => 'generated::1OFIguAQrnfNNvUG',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::I90Os6ljhUK73Gfd' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/dashboard/kpis',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getKPIs',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getKPIs',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::I90Os6ljhUK73Gfd',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::2laIJN3UJizSunp6' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/dashboard/sales-chart',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getSalesChart',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getSalesChart',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::2laIJN3UJizSunp6',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::TamVH6iMSY0fsM6N' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/dashboard/category-performance',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getCategoryPerformance',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getCategoryPerformance',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::TamVH6iMSY0fsM6N',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::JXD3ZBAYL06DEWPz' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/dashboard/gold-purity-performance',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getGoldPurityPerformance',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getGoldPurityPerformance',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::JXD3ZBAYL06DEWPz',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::UdG5QkaAwqw56Z1R' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/dashboard/category-stock-alerts',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getCategoryStockAlerts',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getCategoryStockAlerts',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::UdG5QkaAwqw56Z1R',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::ID3dMujSmZYZ7YNr' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/dashboard/alerts',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getAlerts',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getAlerts',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::ID3dMujSmZYZ7YNr',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::G2LZ3xgb042WugGy' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/dashboard/alerts/mark-read',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@markAlertAsRead',
        'controller' => 'App\\Http\\Controllers\\DashboardController@markAlertAsRead',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::G2LZ3xgb042WugGy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::hLh6wt8NOUFmXXYp' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/dashboard/layout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getDashboardLayout',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getDashboardLayout',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::hLh6wt8NOUFmXXYp',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::at1j1idpJ68a1LS9' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/dashboard/layout',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@saveDashboardLayout',
        'controller' => 'App\\Http\\Controllers\\DashboardController@saveDashboardLayout',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::at1j1idpJ68a1LS9',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::NeoEzGhTDtTg0Tu1' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/dashboard/presets',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getDashboardPresets',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getDashboardPresets',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::NeoEzGhTDtTg0Tu1',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::uXKRUudMZuJEhDT7' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/dashboard/presets/apply',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@applyDashboardPreset',
        'controller' => 'App\\Http\\Controllers\\DashboardController@applyDashboardPreset',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::uXKRUudMZuJEhDT7',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::FifM3DBhgJp0I8by' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/dashboard/widgets/available',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@getAvailableWidgets',
        'controller' => 'App\\Http\\Controllers\\DashboardController@getAvailableWidgets',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::FifM3DBhgJp0I8by',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::WLmxGnq9lOqmRO3T' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/dashboard/widgets/add',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@addWidget',
        'controller' => 'App\\Http\\Controllers\\DashboardController@addWidget',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::WLmxGnq9lOqmRO3T',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::iHmgiiI5gk2tJqfy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/dashboard/widgets/remove',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@removeWidget',
        'controller' => 'App\\Http\\Controllers\\DashboardController@removeWidget',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::iHmgiiI5gk2tJqfy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::i4mUXLTNHrYh1Qdu' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/dashboard/widgets/config',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@updateWidgetConfig',
        'controller' => 'App\\Http\\Controllers\\DashboardController@updateWidgetConfig',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::i4mUXLTNHrYh1Qdu',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::6R20yWUPCh5FsH8g' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/dashboard/reset',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@resetDashboard',
        'controller' => 'App\\Http\\Controllers\\DashboardController@resetDashboard',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::6R20yWUPCh5FsH8g',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::9RXUyaaumDDGcFZA' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/dashboard/clear-cache',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DashboardController@clearCache',
        'controller' => 'App\\Http\\Controllers\\DashboardController@clearCache',
        'namespace' => NULL,
        'prefix' => 'api/dashboard',
        'where' => 
        array (
        ),
        'as' => 'generated::9RXUyaaumDDGcFZA',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::C2C7QiwcJu8xD0qs' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory-reports/category-hierarchy',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryReportController@categoryHierarchyReport',
        'controller' => 'App\\Http\\Controllers\\InventoryReportController@categoryHierarchyReport',
        'namespace' => NULL,
        'prefix' => 'api/inventory-reports',
        'where' => 
        array (
        ),
        'as' => 'generated::C2C7QiwcJu8xD0qs',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::TD8fl58ntM2VcBY2' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory-reports/category-sales-performance',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryReportController@categorySalesPerformance',
        'controller' => 'App\\Http\\Controllers\\InventoryReportController@categorySalesPerformance',
        'namespace' => NULL,
        'prefix' => 'api/inventory-reports',
        'where' => 
        array (
        ),
        'as' => 'generated::TD8fl58ntM2VcBY2',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::msfL6ph4sH5QJg9h' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory-reports/category-stock-levels',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryReportController@categoryStockLevels',
        'controller' => 'App\\Http\\Controllers\\InventoryReportController@categoryStockLevels',
        'namespace' => NULL,
        'prefix' => 'api/inventory-reports',
        'where' => 
        array (
        ),
        'as' => 'generated::msfL6ph4sH5QJg9h',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::t28LJJ2ZJRhGzAL0' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory-reports/gold-purity-analysis',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryReportController@goldPurityAnalysis',
        'controller' => 'App\\Http\\Controllers\\InventoryReportController@goldPurityAnalysis',
        'namespace' => NULL,
        'prefix' => 'api/inventory-reports',
        'where' => 
        array (
        ),
        'as' => 'generated::t28LJJ2ZJRhGzAL0',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::rt0r2BR3ocZRyJaD' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory-reports/inventory-analytics',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryReportController@inventoryAnalytics',
        'controller' => 'App\\Http\\Controllers\\InventoryReportController@inventoryAnalytics',
        'namespace' => NULL,
        'prefix' => 'api/inventory-reports',
        'where' => 
        array (
        ),
        'as' => 'generated::rt0r2BR3ocZRyJaD',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::V9Yxc68C3YMQY47w' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/customers/aging-report',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@agingReport',
        'controller' => 'App\\Http\\Controllers\\CustomerController@agingReport',
        'namespace' => NULL,
        'prefix' => 'api/customers',
        'where' => 
        array (
        ),
        'as' => 'generated::V9Yxc68C3YMQY47w',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::GCm94qHNTGTKoCa0' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/customers/crm-pipeline',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@crmPipeline',
        'controller' => 'App\\Http\\Controllers\\CustomerController@crmPipeline',
        'namespace' => NULL,
        'prefix' => 'api/customers',
        'where' => 
        array (
        ),
        'as' => 'generated::GCm94qHNTGTKoCa0',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::cynw7TJGyVVfoxTy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/customers/upcoming-birthdays',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@upcomingBirthdays',
        'controller' => 'App\\Http\\Controllers\\CustomerController@upcomingBirthdays',
        'namespace' => NULL,
        'prefix' => 'api/customers',
        'where' => 
        array (
        ),
        'as' => 'generated::cynw7TJGyVVfoxTy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::zoouMuVrBtS9x9Mz' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/customers/upcoming-anniversaries',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@upcomingAnniversaries',
        'controller' => 'App\\Http\\Controllers\\CustomerController@upcomingAnniversaries',
        'namespace' => NULL,
        'prefix' => 'api/customers',
        'where' => 
        array (
        ),
        'as' => 'generated::zoouMuVrBtS9x9Mz',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::wkWzGgQoCaZLdsyy' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/customers/{customer}/crm-stage',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@updateCrmStage',
        'controller' => 'App\\Http\\Controllers\\CustomerController@updateCrmStage',
        'namespace' => NULL,
        'prefix' => 'api/customers',
        'where' => 
        array (
        ),
        'as' => 'generated::wkWzGgQoCaZLdsyy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::iaDIqttX79cwDMSe' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/customers/{customer}/communicate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@sendCommunication',
        'controller' => 'App\\Http\\Controllers\\CustomerController@sendCommunication',
        'namespace' => NULL,
        'prefix' => 'api/customers',
        'where' => 
        array (
        ),
        'as' => 'generated::iaDIqttX79cwDMSe',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::GgI0IsH1UGEKX9Vp' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/customers/{customer}/vcard',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CustomerController@exportVCard',
        'controller' => 'App\\Http\\Controllers\\CustomerController@exportVCard',
        'namespace' => NULL,
        'prefix' => 'api/customers',
        'where' => 
        array (
        ),
        'as' => 'generated::GgI0IsH1UGEKX9Vp',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/customers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'customers.index',
        'uses' => 'App\\Http\\Controllers\\CustomerController@index',
        'controller' => 'App\\Http\\Controllers\\CustomerController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/customers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'customers.store',
        'uses' => 'App\\Http\\Controllers\\CustomerController@store',
        'controller' => 'App\\Http\\Controllers\\CustomerController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/customers/{customer}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'customers.show',
        'uses' => 'App\\Http\\Controllers\\CustomerController@show',
        'controller' => 'App\\Http\\Controllers\\CustomerController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/customers/{customer}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'customers.update',
        'uses' => 'App\\Http\\Controllers\\CustomerController@update',
        'controller' => 'App\\Http\\Controllers\\CustomerController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'customers.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/customers/{customer}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'customers.destroy',
        'uses' => 'App\\Http\\Controllers\\CustomerController@destroy',
        'controller' => 'App\\Http\\Controllers\\CustomerController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::dTO1tvapPORO3Oxi' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/invoices/category-stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@getCategoryStats',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@getCategoryStats',
        'namespace' => NULL,
        'prefix' => 'api/invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::dTO1tvapPORO3Oxi',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::YEhDxNofi8s5Yc8W' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/invoices/gold-purity-stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@getGoldPurityStats',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@getGoldPurityStats',
        'namespace' => NULL,
        'prefix' => 'api/invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::YEhDxNofi8s5Yc8W',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::6itlx7Gi4KROTJlZ' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoices/batch-pdf',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@generateBatchPDFs',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@generateBatchPDFs',
        'namespace' => NULL,
        'prefix' => 'api/invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::6itlx7Gi4KROTJlZ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::NLldaS3LTyKaEbUO' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoices/batch-download',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@downloadBatchPDFs',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@downloadBatchPDFs',
        'namespace' => NULL,
        'prefix' => 'api/invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::NLldaS3LTyKaEbUO',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::k7BcT65rzoBfX8LN' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoices/{invoice}/duplicate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@duplicate',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@duplicate',
        'namespace' => NULL,
        'prefix' => 'api/invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::k7BcT65rzoBfX8LN',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::1UOIgPhesA2DqP8i' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoices/{invoice}/pdf',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@generatePDF',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@generatePDF',
        'namespace' => NULL,
        'prefix' => 'api/invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::1UOIgPhesA2DqP8i',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::LVpuLg47WNRlm7Nj' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/invoices/{invoice}/pdf/download',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@downloadPDF',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@downloadPDF',
        'namespace' => NULL,
        'prefix' => 'api/invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::LVpuLg47WNRlm7Nj',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::sej8r7JysTFpKnQb' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoices/{invoice}/mark-sent',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@markAsSent',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@markAsSent',
        'namespace' => NULL,
        'prefix' => 'api/invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::sej8r7JysTFpKnQb',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::DQFCpHRDOoA4JwVa' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoices/{invoice}/mark-paid',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@markAsPaid',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@markAsPaid',
        'namespace' => NULL,
        'prefix' => 'api/invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::DQFCpHRDOoA4JwVa',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::VvGtWSf3TxQHldjY' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoices/{invoice}/attachments',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@addAttachment',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@addAttachment',
        'namespace' => NULL,
        'prefix' => 'api/invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::VvGtWSf3TxQHldjY',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::wbtr9MtB7lMhh31N' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/invoices/{invoice}/attachments/{attachment}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceController@removeAttachment',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@removeAttachment',
        'namespace' => NULL,
        'prefix' => 'api/invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::wbtr9MtB7lMhh31N',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoices.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/invoices',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'invoices.index',
        'uses' => 'App\\Http\\Controllers\\InvoiceController@index',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoices.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoices',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'invoices.store',
        'uses' => 'App\\Http\\Controllers\\InvoiceController@store',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoices.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/invoices/{invoice}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'invoices.show',
        'uses' => 'App\\Http\\Controllers\\InvoiceController@show',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoices.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/invoices/{invoice}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'invoices.update',
        'uses' => 'App\\Http\\Controllers\\InvoiceController@update',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoices.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/invoices/{invoice}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'invoices.destroy',
        'uses' => 'App\\Http\\Controllers\\InvoiceController@destroy',
        'controller' => 'App\\Http\\Controllers\\InvoiceController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::ekwjxoRx746onmsq' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/invoice-templates/default-structure',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceTemplateController@getDefaultStructure',
        'controller' => 'App\\Http\\Controllers\\InvoiceTemplateController@getDefaultStructure',
        'namespace' => NULL,
        'prefix' => 'api/invoice-templates',
        'where' => 
        array (
        ),
        'as' => 'generated::ekwjxoRx746onmsq',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::p6rUDQ83Cg3tC5qt' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoice-templates/validate-structure',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceTemplateController@validateStructure',
        'controller' => 'App\\Http\\Controllers\\InvoiceTemplateController@validateStructure',
        'namespace' => NULL,
        'prefix' => 'api/invoice-templates',
        'where' => 
        array (
        ),
        'as' => 'generated::p6rUDQ83Cg3tC5qt',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::tnSujjp9vT2jerfX' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoice-templates/{invoiceTemplate}/duplicate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceTemplateController@duplicate',
        'controller' => 'App\\Http\\Controllers\\InvoiceTemplateController@duplicate',
        'namespace' => NULL,
        'prefix' => 'api/invoice-templates',
        'where' => 
        array (
        ),
        'as' => 'generated::tnSujjp9vT2jerfX',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Iqlk5exB52020e5h' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoice-templates/{invoiceTemplate}/set-default',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InvoiceTemplateController@setAsDefault',
        'controller' => 'App\\Http\\Controllers\\InvoiceTemplateController@setAsDefault',
        'namespace' => NULL,
        'prefix' => 'api/invoice-templates',
        'where' => 
        array (
        ),
        'as' => 'generated::Iqlk5exB52020e5h',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoice-templates.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/invoice-templates',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'invoice-templates.index',
        'uses' => 'App\\Http\\Controllers\\InvoiceTemplateController@index',
        'controller' => 'App\\Http\\Controllers\\InvoiceTemplateController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoice-templates.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/invoice-templates',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'invoice-templates.store',
        'uses' => 'App\\Http\\Controllers\\InvoiceTemplateController@store',
        'controller' => 'App\\Http\\Controllers\\InvoiceTemplateController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoice-templates.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/invoice-templates/{invoice_template}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'invoice-templates.show',
        'uses' => 'App\\Http\\Controllers\\InvoiceTemplateController@show',
        'controller' => 'App\\Http\\Controllers\\InvoiceTemplateController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoice-templates.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/invoice-templates/{invoice_template}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'invoice-templates.update',
        'uses' => 'App\\Http\\Controllers\\InvoiceTemplateController@update',
        'controller' => 'App\\Http\\Controllers\\InvoiceTemplateController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'invoice-templates.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/invoice-templates/{invoice_template}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'invoice-templates.destroy',
        'uses' => 'App\\Http\\Controllers\\InvoiceTemplateController@destroy',
        'controller' => 'App\\Http\\Controllers\\InvoiceTemplateController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::CxpijPHkAJHiXBNw' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/recurring-invoices/process-due',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RecurringInvoiceController@processDue',
        'controller' => 'App\\Http\\Controllers\\RecurringInvoiceController@processDue',
        'namespace' => NULL,
        'prefix' => 'api/recurring-invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::CxpijPHkAJHiXBNw',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::qV0ZdAuumfKnMNNc' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/recurring-invoices/upcoming',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RecurringInvoiceController@upcoming',
        'controller' => 'App\\Http\\Controllers\\RecurringInvoiceController@upcoming',
        'namespace' => NULL,
        'prefix' => 'api/recurring-invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::qV0ZdAuumfKnMNNc',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::KyrGBlnkhnri9T3R' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/recurring-invoices/stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RecurringInvoiceController@stats',
        'controller' => 'App\\Http\\Controllers\\RecurringInvoiceController@stats',
        'namespace' => NULL,
        'prefix' => 'api/recurring-invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::KyrGBlnkhnri9T3R',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Kb1Ow4SXAxZAZDW8' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/recurring-invoices/{recurringInvoice}/generate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RecurringInvoiceController@generateInvoice',
        'controller' => 'App\\Http\\Controllers\\RecurringInvoiceController@generateInvoice',
        'namespace' => NULL,
        'prefix' => 'api/recurring-invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::Kb1Ow4SXAxZAZDW8',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::hPDEhjxJp8L7D5Mc' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/recurring-invoices/{recurringInvoice}/pause',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RecurringInvoiceController@pause',
        'controller' => 'App\\Http\\Controllers\\RecurringInvoiceController@pause',
        'namespace' => NULL,
        'prefix' => 'api/recurring-invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::hPDEhjxJp8L7D5Mc',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::nQU1R72grGZT3pe1' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/recurring-invoices/{recurringInvoice}/resume',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RecurringInvoiceController@resume',
        'controller' => 'App\\Http\\Controllers\\RecurringInvoiceController@resume',
        'namespace' => NULL,
        'prefix' => 'api/recurring-invoices',
        'where' => 
        array (
        ),
        'as' => 'generated::nQU1R72grGZT3pe1',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'recurring-invoices.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/recurring-invoices',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'recurring-invoices.index',
        'uses' => 'App\\Http\\Controllers\\RecurringInvoiceController@index',
        'controller' => 'App\\Http\\Controllers\\RecurringInvoiceController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'recurring-invoices.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/recurring-invoices',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'recurring-invoices.store',
        'uses' => 'App\\Http\\Controllers\\RecurringInvoiceController@store',
        'controller' => 'App\\Http\\Controllers\\RecurringInvoiceController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'recurring-invoices.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/recurring-invoices/{recurring_invoice}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'recurring-invoices.show',
        'uses' => 'App\\Http\\Controllers\\RecurringInvoiceController@show',
        'controller' => 'App\\Http\\Controllers\\RecurringInvoiceController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'recurring-invoices.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/recurring-invoices/{recurring_invoice}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'recurring-invoices.update',
        'uses' => 'App\\Http\\Controllers\\RecurringInvoiceController@update',
        'controller' => 'App\\Http\\Controllers\\RecurringInvoiceController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'recurring-invoices.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/recurring-invoices/{recurring_invoice}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'recurring-invoices.destroy',
        'uses' => 'App\\Http\\Controllers\\RecurringInvoiceController@destroy',
        'controller' => 'App\\Http\\Controllers\\RecurringInvoiceController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Emi9Zye3hYmrjUgU' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory/low-stock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryController@lowStock',
        'controller' => 'App\\Http\\Controllers\\InventoryController@lowStock',
        'namespace' => NULL,
        'prefix' => 'api/inventory',
        'where' => 
        array (
        ),
        'as' => 'generated::Emi9Zye3hYmrjUgU',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::v7UXTfk0c7S0LifA' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory/expiring',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryController@expiring',
        'controller' => 'App\\Http\\Controllers\\InventoryController@expiring',
        'namespace' => NULL,
        'prefix' => 'api/inventory',
        'where' => 
        array (
        ),
        'as' => 'generated::v7UXTfk0c7S0LifA',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::b9V7BNylxYpDKgjF' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory/expired',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryController@expired',
        'controller' => 'App\\Http\\Controllers\\InventoryController@expired',
        'namespace' => NULL,
        'prefix' => 'api/inventory',
        'where' => 
        array (
        ),
        'as' => 'generated::b9V7BNylxYpDKgjF',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::NtCwv9q4QLRj7VDF' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory/summary/location',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryController@summaryByLocation',
        'controller' => 'App\\Http\\Controllers\\InventoryController@summaryByLocation',
        'namespace' => NULL,
        'prefix' => 'api/inventory',
        'where' => 
        array (
        ),
        'as' => 'generated::NtCwv9q4QLRj7VDF',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::fkxSfbnjidiukQQx' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory/summary/category',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryController@summaryByCategory',
        'controller' => 'App\\Http\\Controllers\\InventoryController@summaryByCategory',
        'namespace' => NULL,
        'prefix' => 'api/inventory',
        'where' => 
        array (
        ),
        'as' => 'generated::fkxSfbnjidiukQQx',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::uEmpPaIbXAXmdHkX' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory/gold-purity-options',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryController@goldPurityOptions',
        'controller' => 'App\\Http\\Controllers\\InventoryController@goldPurityOptions',
        'namespace' => NULL,
        'prefix' => 'api/inventory',
        'where' => 
        array (
        ),
        'as' => 'generated::uEmpPaIbXAXmdHkX',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::OzxtRoDyBrrjUIRo' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/inventory/{inventory}/transfer',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryController@transfer',
        'controller' => 'App\\Http\\Controllers\\InventoryController@transfer',
        'namespace' => NULL,
        'prefix' => 'api/inventory',
        'where' => 
        array (
        ),
        'as' => 'generated::OzxtRoDyBrrjUIRo',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Znz1M8j4X3LvX94U' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory/{inventory}/movements',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\InventoryController@movements',
        'controller' => 'App\\Http\\Controllers\\InventoryController@movements',
        'namespace' => NULL,
        'prefix' => 'api/inventory',
        'where' => 
        array (
        ),
        'as' => 'generated::Znz1M8j4X3LvX94U',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'inventory.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'inventory.index',
        'uses' => 'App\\Http\\Controllers\\InventoryController@index',
        'controller' => 'App\\Http\\Controllers\\InventoryController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'inventory.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/inventory',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'inventory.store',
        'uses' => 'App\\Http\\Controllers\\InventoryController@store',
        'controller' => 'App\\Http\\Controllers\\InventoryController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'inventory.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/inventory/{inventory}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'inventory.show',
        'uses' => 'App\\Http\\Controllers\\InventoryController@show',
        'controller' => 'App\\Http\\Controllers\\InventoryController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'inventory.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/inventory/{inventory}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'inventory.update',
        'uses' => 'App\\Http\\Controllers\\InventoryController@update',
        'controller' => 'App\\Http\\Controllers\\InventoryController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'inventory.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/inventory/{inventory}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'inventory.destroy',
        'uses' => 'App\\Http\\Controllers\\InventoryController@destroy',
        'controller' => 'App\\Http\\Controllers\\InventoryController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::pFZfA1lE8DjlOXzp' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/stock-audits/{stockAudit}/start',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\StockAuditController@start',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@start',
        'namespace' => NULL,
        'prefix' => 'api/stock-audits',
        'where' => 
        array (
        ),
        'as' => 'generated::pFZfA1lE8DjlOXzp',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::s7Jhq7rsPQnBwJuZ' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/stock-audits/{stockAudit}/complete',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\StockAuditController@complete',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@complete',
        'namespace' => NULL,
        'prefix' => 'api/stock-audits',
        'where' => 
        array (
        ),
        'as' => 'generated::s7Jhq7rsPQnBwJuZ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::M7pKu3sYH34LFBHJ' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/stock-audits/{stockAudit}/cancel',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\StockAuditController@cancel',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@cancel',
        'namespace' => NULL,
        'prefix' => 'api/stock-audits',
        'where' => 
        array (
        ),
        'as' => 'generated::M7pKu3sYH34LFBHJ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::STzT0XPBAcBDeM56' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/stock-audits/{stockAudit}/items/{auditItem}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\StockAuditController@updateItem',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@updateItem',
        'namespace' => NULL,
        'prefix' => 'api/stock-audits',
        'where' => 
        array (
        ),
        'as' => 'generated::STzT0XPBAcBDeM56',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::gxPxNr8SqF90flwg' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/stock-audits/{stockAudit}/bulk-update',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\StockAuditController@bulkUpdate',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@bulkUpdate',
        'namespace' => NULL,
        'prefix' => 'api/stock-audits',
        'where' => 
        array (
        ),
        'as' => 'generated::gxPxNr8SqF90flwg',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::XpCBUdl6hb0RsdMV' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/stock-audits/{stockAudit}/variance-report',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\StockAuditController@varianceReport',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@varianceReport',
        'namespace' => NULL,
        'prefix' => 'api/stock-audits',
        'where' => 
        array (
        ),
        'as' => 'generated::XpCBUdl6hb0RsdMV',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::53vZKV7XkbIciJpC' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/stock-audits/{stockAudit}/uncounted-items',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\StockAuditController@uncountedItems',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@uncountedItems',
        'namespace' => NULL,
        'prefix' => 'api/stock-audits',
        'where' => 
        array (
        ),
        'as' => 'generated::53vZKV7XkbIciJpC',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::W61BErLzmGh3BnOt' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/stock-audits/{stockAudit}/export',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\StockAuditController@export',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@export',
        'namespace' => NULL,
        'prefix' => 'api/stock-audits',
        'where' => 
        array (
        ),
        'as' => 'generated::W61BErLzmGh3BnOt',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'stock-audits.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/stock-audits',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'stock-audits.index',
        'uses' => 'App\\Http\\Controllers\\StockAuditController@index',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'stock-audits.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/stock-audits',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'stock-audits.store',
        'uses' => 'App\\Http\\Controllers\\StockAuditController@store',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'stock-audits.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/stock-audits/{stock_audit}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'stock-audits.show',
        'uses' => 'App\\Http\\Controllers\\StockAuditController@show',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'stock-audits.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/stock-audits/{stock_audit}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'stock-audits.update',
        'uses' => 'App\\Http\\Controllers\\StockAuditController@update',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'stock-audits.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/stock-audits/{stock_audit}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'stock-audits.destroy',
        'uses' => 'App\\Http\\Controllers\\StockAuditController@destroy',
        'controller' => 'App\\Http\\Controllers\\StockAuditController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::yAgdKYo1EGLkQZV5' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/bom/production-cost',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BOMController@productionCost',
        'controller' => 'App\\Http\\Controllers\\BOMController@productionCost',
        'namespace' => NULL,
        'prefix' => 'api/bom',
        'where' => 
        array (
        ),
        'as' => 'generated::yAgdKYo1EGLkQZV5',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::pwLJH2AEBlgfACwD' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/bom/can-produce',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BOMController@canProduce',
        'controller' => 'App\\Http\\Controllers\\BOMController@canProduce',
        'namespace' => NULL,
        'prefix' => 'api/bom',
        'where' => 
        array (
        ),
        'as' => 'generated::pwLJH2AEBlgfACwD',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::mr2Jr1C2KgLLp6wF' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/bom/produce',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BOMController@produce',
        'controller' => 'App\\Http\\Controllers\\BOMController@produce',
        'namespace' => NULL,
        'prefix' => 'api/bom',
        'where' => 
        array (
        ),
        'as' => 'generated::mr2Jr1C2KgLLp6wF',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::uNINDY62vcS8QypN' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/bom/production-requirements',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BOMController@productionRequirements',
        'controller' => 'App\\Http\\Controllers\\BOMController@productionRequirements',
        'namespace' => NULL,
        'prefix' => 'api/bom',
        'where' => 
        array (
        ),
        'as' => 'generated::uNINDY62vcS8QypN',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::En9WP5eQXs6wDY3U' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/bom/tree',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BOMController@bomTree',
        'controller' => 'App\\Http\\Controllers\\BOMController@bomTree',
        'namespace' => NULL,
        'prefix' => 'api/bom',
        'where' => 
        array (
        ),
        'as' => 'generated::En9WP5eQXs6wDY3U',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::dTOZ2z7o3mFvboUc' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/bom/usage-report',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BOMController@usageReport',
        'controller' => 'App\\Http\\Controllers\\BOMController@usageReport',
        'namespace' => NULL,
        'prefix' => 'api/bom',
        'where' => 
        array (
        ),
        'as' => 'generated::dTOZ2z7o3mFvboUc',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bom.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/bom',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'bom.index',
        'uses' => 'App\\Http\\Controllers\\BOMController@index',
        'controller' => 'App\\Http\\Controllers\\BOMController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bom.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/bom',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'bom.store',
        'uses' => 'App\\Http\\Controllers\\BOMController@store',
        'controller' => 'App\\Http\\Controllers\\BOMController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bom.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/bom/{bom}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'bom.show',
        'uses' => 'App\\Http\\Controllers\\BOMController@show',
        'controller' => 'App\\Http\\Controllers\\BOMController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bom.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/bom/{bom}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'bom.update',
        'uses' => 'App\\Http\\Controllers\\BOMController@update',
        'controller' => 'App\\Http\\Controllers\\BOMController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'bom.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/bom/{bom}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'bom.destroy',
        'uses' => 'App\\Http\\Controllers\\BOMController@destroy',
        'controller' => 'App\\Http\\Controllers\\BOMController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::7sjki3k1EpzC3dwk' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/categories/hierarchy',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CategoryController@getHierarchy',
        'controller' => 'App\\Http\\Controllers\\CategoryController@getHierarchy',
        'namespace' => NULL,
        'prefix' => 'api/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::7sjki3k1EpzC3dwk',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::t4RyDs9ua6aP0eFI' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/categories/for-select',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CategoryController@getForSelect',
        'controller' => 'App\\Http\\Controllers\\CategoryController@getForSelect',
        'namespace' => NULL,
        'prefix' => 'api/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::t4RyDs9ua6aP0eFI',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::PIOFjo0ZMCDEyW8x' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/categories/main-categories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CategoryController@getMainCategories',
        'controller' => 'App\\Http\\Controllers\\CategoryController@getMainCategories',
        'namespace' => NULL,
        'prefix' => 'api/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::PIOFjo0ZMCDEyW8x',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::7MVqKfLZ3Li3roQA' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/categories/subcategories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CategoryController@getSubcategories',
        'controller' => 'App\\Http\\Controllers\\CategoryController@getSubcategories',
        'namespace' => NULL,
        'prefix' => 'api/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::7MVqKfLZ3Li3roQA',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::IoFz0BqyJrd2MjzZ' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/categories/search',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CategoryController@search',
        'controller' => 'App\\Http\\Controllers\\CategoryController@search',
        'namespace' => NULL,
        'prefix' => 'api/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::IoFz0BqyJrd2MjzZ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::kdwVIdtyHArvWeDM' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/categories/gold-purity-options',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CategoryController@getGoldPurityOptions',
        'controller' => 'App\\Http\\Controllers\\CategoryController@getGoldPurityOptions',
        'namespace' => NULL,
        'prefix' => 'api/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::kdwVIdtyHArvWeDM',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::nzyqcK89XxMMFEgl' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/categories/reorder',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CategoryController@reorder',
        'controller' => 'App\\Http\\Controllers\\CategoryController@reorder',
        'namespace' => NULL,
        'prefix' => 'api/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::nzyqcK89XxMMFEgl',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::d9ZhSbuh7ndDzZlI' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/categories/{category}/image',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CategoryController@uploadImage',
        'controller' => 'App\\Http\\Controllers\\CategoryController@uploadImage',
        'namespace' => NULL,
        'prefix' => 'api/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::d9ZhSbuh7ndDzZlI',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::HEAW3pbeWaPqRSgi' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/categories/{category}/image',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CategoryController@removeImage',
        'controller' => 'App\\Http\\Controllers\\CategoryController@removeImage',
        'namespace' => NULL,
        'prefix' => 'api/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::HEAW3pbeWaPqRSgi',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::NwtmGrjdmjNvkULT' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/categories/{category}/path',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\CategoryController@getCategoryPath',
        'controller' => 'App\\Http\\Controllers\\CategoryController@getCategoryPath',
        'namespace' => NULL,
        'prefix' => 'api/categories',
        'where' => 
        array (
        ),
        'as' => 'generated::NwtmGrjdmjNvkULT',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'categories.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/categories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'categories.index',
        'uses' => 'App\\Http\\Controllers\\CategoryController@index',
        'controller' => 'App\\Http\\Controllers\\CategoryController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'categories.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/categories',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'categories.store',
        'uses' => 'App\\Http\\Controllers\\CategoryController@store',
        'controller' => 'App\\Http\\Controllers\\CategoryController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'categories.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/categories/{category}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'categories.show',
        'uses' => 'App\\Http\\Controllers\\CategoryController@show',
        'controller' => 'App\\Http\\Controllers\\CategoryController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'categories.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/categories/{category}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'categories.update',
        'uses' => 'App\\Http\\Controllers\\CategoryController@update',
        'controller' => 'App\\Http\\Controllers\\CategoryController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'categories.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/categories/{category}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'categories.destroy',
        'uses' => 'App\\Http\\Controllers\\CategoryController@destroy',
        'controller' => 'App\\Http\\Controllers\\CategoryController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'locations.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/locations',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'locations.index',
        'uses' => 'App\\Http\\Controllers\\LocationController@index',
        'controller' => 'App\\Http\\Controllers\\LocationController@index',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'locations.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/locations',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'locations.store',
        'uses' => 'App\\Http\\Controllers\\LocationController@store',
        'controller' => 'App\\Http\\Controllers\\LocationController@store',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'locations.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/locations/{location}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'locations.show',
        'uses' => 'App\\Http\\Controllers\\LocationController@show',
        'controller' => 'App\\Http\\Controllers\\LocationController@show',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'locations.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/locations/{location}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'locations.update',
        'uses' => 'App\\Http\\Controllers\\LocationController@update',
        'controller' => 'App\\Http\\Controllers\\LocationController@update',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'locations.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/locations/{location}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'locations.destroy',
        'uses' => 'App\\Http\\Controllers\\LocationController@destroy',
        'controller' => 'App\\Http\\Controllers\\LocationController@destroy',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'accounts.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/accounts',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'accounts.index',
        'uses' => 'App\\Http\\Controllers\\AccountController@index',
        'controller' => 'App\\Http\\Controllers\\AccountController@index',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'accounts.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/accounting/accounts',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'accounts.store',
        'uses' => 'App\\Http\\Controllers\\AccountController@store',
        'controller' => 'App\\Http\\Controllers\\AccountController@store',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'accounts.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/accounts/{account}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'accounts.show',
        'uses' => 'App\\Http\\Controllers\\AccountController@show',
        'controller' => 'App\\Http\\Controllers\\AccountController@show',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'accounts.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/accounting/accounts/{account}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'accounts.update',
        'uses' => 'App\\Http\\Controllers\\AccountController@update',
        'controller' => 'App\\Http\\Controllers\\AccountController@update',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'accounts.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/accounting/accounts/{account}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'accounts.destroy',
        'uses' => 'App\\Http\\Controllers\\AccountController@destroy',
        'controller' => 'App\\Http\\Controllers\\AccountController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::SiqeiG5mOiQKxN1S' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/accounts/{account}/balance',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\AccountController@balance',
        'controller' => 'App\\Http\\Controllers\\AccountController@balance',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::SiqeiG5mOiQKxN1S',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::NxDnrLWB3HdrHMVO' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/accounts/{account}/ledger',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\AccountController@ledger',
        'controller' => 'App\\Http\\Controllers\\AccountController@ledger',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::NxDnrLWB3HdrHMVO',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::hafio160kHkSIoRi' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/chart-of-accounts',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\AccountController@chartOfAccounts',
        'controller' => 'App\\Http\\Controllers\\AccountController@chartOfAccounts',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::hafio160kHkSIoRi',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'transactions.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/transactions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'transactions.index',
        'uses' => 'App\\Http\\Controllers\\TransactionController@index',
        'controller' => 'App\\Http\\Controllers\\TransactionController@index',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'transactions.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/accounting/transactions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'transactions.store',
        'uses' => 'App\\Http\\Controllers\\TransactionController@store',
        'controller' => 'App\\Http\\Controllers\\TransactionController@store',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'transactions.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/transactions/{transaction}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'transactions.show',
        'uses' => 'App\\Http\\Controllers\\TransactionController@show',
        'controller' => 'App\\Http\\Controllers\\TransactionController@show',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'transactions.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/accounting/transactions/{transaction}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'transactions.update',
        'uses' => 'App\\Http\\Controllers\\TransactionController@update',
        'controller' => 'App\\Http\\Controllers\\TransactionController@update',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'transactions.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/accounting/transactions/{transaction}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'transactions.destroy',
        'uses' => 'App\\Http\\Controllers\\TransactionController@destroy',
        'controller' => 'App\\Http\\Controllers\\TransactionController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::teRhiwRlCWM7LXeC' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/accounting/transactions/{transaction}/lock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\TransactionController@lock',
        'controller' => 'App\\Http\\Controllers\\TransactionController@lock',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::teRhiwRlCWM7LXeC',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::XQdG97VHNuRpdgoz' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/accounting/transactions/{transaction}/unlock',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\TransactionController@unlock',
        'controller' => 'App\\Http\\Controllers\\TransactionController@unlock',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::XQdG97VHNuRpdgoz',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::QfzZMnE2XgZUhRSL' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/accounting/transactions/{transaction}/approve',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\TransactionController@approve',
        'controller' => 'App\\Http\\Controllers\\TransactionController@approve',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::QfzZMnE2XgZUhRSL',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::H8PP5ruQw5kASuQr' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/accounting/transactions/{transaction}/duplicate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\TransactionController@duplicate',
        'controller' => 'App\\Http\\Controllers\\TransactionController@duplicate',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::H8PP5ruQw5kASuQr',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::K2JfIcVhxVm4Fljn' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/reports/trial-balance',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\FinancialReportController@trialBalance',
        'controller' => 'App\\Http\\Controllers\\FinancialReportController@trialBalance',
        'namespace' => NULL,
        'prefix' => 'api/accounting/reports',
        'where' => 
        array (
        ),
        'as' => 'generated::K2JfIcVhxVm4Fljn',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::w2uYQSzslKFQlyq1' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/reports/balance-sheet',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\FinancialReportController@balanceSheet',
        'controller' => 'App\\Http\\Controllers\\FinancialReportController@balanceSheet',
        'namespace' => NULL,
        'prefix' => 'api/accounting/reports',
        'where' => 
        array (
        ),
        'as' => 'generated::w2uYQSzslKFQlyq1',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::8zewWOhCj4N7bScl' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/reports/income-statement',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\FinancialReportController@incomeStatement',
        'controller' => 'App\\Http\\Controllers\\FinancialReportController@incomeStatement',
        'namespace' => NULL,
        'prefix' => 'api/accounting/reports',
        'where' => 
        array (
        ),
        'as' => 'generated::8zewWOhCj4N7bScl',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::eTbBsfS22ZqL3jxs' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/reports/cash-flow-statement',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\FinancialReportController@cashFlowStatement',
        'controller' => 'App\\Http\\Controllers\\FinancialReportController@cashFlowStatement',
        'namespace' => NULL,
        'prefix' => 'api/accounting/reports',
        'where' => 
        array (
        ),
        'as' => 'generated::eTbBsfS22ZqL3jxs',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::RLBF0np1j4baPs0R' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/reports/aged-receivables',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\FinancialReportController@agedReceivables',
        'controller' => 'App\\Http\\Controllers\\FinancialReportController@agedReceivables',
        'namespace' => NULL,
        'prefix' => 'api/accounting/reports',
        'where' => 
        array (
        ),
        'as' => 'generated::RLBF0np1j4baPs0R',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::OvbqHDTOMbZ0Q57s' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/reports/aged-payables',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\FinancialReportController@agedPayables',
        'controller' => 'App\\Http\\Controllers\\FinancialReportController@agedPayables',
        'namespace' => NULL,
        'prefix' => 'api/accounting/reports',
        'where' => 
        array (
        ),
        'as' => 'generated::OvbqHDTOMbZ0Q57s',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::z9gOGbqegIn6MYz5' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/accounting/reports/custom',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\FinancialReportController@customReport',
        'controller' => 'App\\Http\\Controllers\\FinancialReportController@customReport',
        'namespace' => NULL,
        'prefix' => 'api/accounting/reports',
        'where' => 
        array (
        ),
        'as' => 'generated::z9gOGbqegIn6MYz5',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cost-centers.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/cost-centers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'cost-centers.index',
        'uses' => 'App\\Http\\Controllers\\CostCenterController@index',
        'controller' => 'App\\Http\\Controllers\\CostCenterController@index',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cost-centers.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/accounting/cost-centers',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'cost-centers.store',
        'uses' => 'App\\Http\\Controllers\\CostCenterController@store',
        'controller' => 'App\\Http\\Controllers\\CostCenterController@store',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cost-centers.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/cost-centers/{cost_center}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'cost-centers.show',
        'uses' => 'App\\Http\\Controllers\\CostCenterController@show',
        'controller' => 'App\\Http\\Controllers\\CostCenterController@show',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cost-centers.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/accounting/cost-centers/{cost_center}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'cost-centers.update',
        'uses' => 'App\\Http\\Controllers\\CostCenterController@update',
        'controller' => 'App\\Http\\Controllers\\CostCenterController@update',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'cost-centers.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/accounting/cost-centers/{cost_center}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'cost-centers.destroy',
        'uses' => 'App\\Http\\Controllers\\CostCenterController@destroy',
        'controller' => 'App\\Http\\Controllers\\CostCenterController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'assets.index' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/assets',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'assets.index',
        'uses' => 'App\\Http\\Controllers\\AssetController@index',
        'controller' => 'App\\Http\\Controllers\\AssetController@index',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'assets.store' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/accounting/assets',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'assets.store',
        'uses' => 'App\\Http\\Controllers\\AssetController@store',
        'controller' => 'App\\Http\\Controllers\\AssetController@store',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'assets.show' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/assets/{asset}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'assets.show',
        'uses' => 'App\\Http\\Controllers\\AssetController@show',
        'controller' => 'App\\Http\\Controllers\\AssetController@show',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'assets.update' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
        1 => 'PATCH',
      ),
      'uri' => 'api/accounting/assets/{asset}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'assets.update',
        'uses' => 'App\\Http\\Controllers\\AssetController@update',
        'controller' => 'App\\Http\\Controllers\\AssetController@update',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'assets.destroy' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/accounting/assets/{asset}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'as' => 'assets.destroy',
        'uses' => 'App\\Http\\Controllers\\AssetController@destroy',
        'controller' => 'App\\Http\\Controllers\\AssetController@destroy',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::edHerBgEss9pPzu0' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/accounting/assets/{asset}/dispose',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\AssetController@dispose',
        'controller' => 'App\\Http\\Controllers\\AssetController@dispose',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::edHerBgEss9pPzu0',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lFCTboRBpfaP5MPy' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/assets/{asset}/depreciation',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\AssetController@depreciation',
        'controller' => 'App\\Http\\Controllers\\AssetController@depreciation',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::lFCTboRBpfaP5MPy',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::CszHo4yXhq7LOQP9' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/assets/{asset}/depreciation-schedule',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\AssetController@depreciationSchedule',
        'controller' => 'App\\Http\\Controllers\\AssetController@depreciationSchedule',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::CszHo4yXhq7LOQP9',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Zs8eTldRXgfymJid' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/accounting/asset-register',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\AssetController@register',
        'controller' => 'App\\Http\\Controllers\\AssetController@register',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::Zs8eTldRXgfymJid',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::m7XuaAXiidA22RPR' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/accounting/process-depreciation',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\AssetController@processDepreciation',
        'controller' => 'App\\Http\\Controllers\\AssetController@processDepreciation',
        'namespace' => NULL,
        'prefix' => 'api/accounting',
        'where' => 
        array (
        ),
        'as' => 'generated::m7XuaAXiidA22RPR',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::CqNo5bIaaztifElG' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/config/business-info',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BusinessConfigurationController@getBusinessInfo',
        'controller' => 'App\\Http\\Controllers\\BusinessConfigurationController@getBusinessInfo',
        'namespace' => NULL,
        'prefix' => 'api/config',
        'where' => 
        array (
        ),
        'as' => 'generated::CqNo5bIaaztifElG',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::VIMdqwvFTvM7bCfq' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/config/business-info',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BusinessConfigurationController@updateBusinessInfo',
        'controller' => 'App\\Http\\Controllers\\BusinessConfigurationController@updateBusinessInfo',
        'namespace' => NULL,
        'prefix' => 'api/config',
        'where' => 
        array (
        ),
        'as' => 'generated::VIMdqwvFTvM7bCfq',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::GappUKgTTp13wSJI' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/config/logo',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BusinessConfigurationController@uploadLogo',
        'controller' => 'App\\Http\\Controllers\\BusinessConfigurationController@uploadLogo',
        'namespace' => NULL,
        'prefix' => 'api/config',
        'where' => 
        array (
        ),
        'as' => 'generated::GappUKgTTp13wSJI',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Qk1kNjatlCbW4NjY' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/config/tax',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BusinessConfigurationController@getTaxConfig',
        'controller' => 'App\\Http\\Controllers\\BusinessConfigurationController@getTaxConfig',
        'namespace' => NULL,
        'prefix' => 'api/config',
        'where' => 
        array (
        ),
        'as' => 'generated::Qk1kNjatlCbW4NjY',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::2GM5wdJ5Hg2pfIYD' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/config/tax',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BusinessConfigurationController@updateTaxConfig',
        'controller' => 'App\\Http\\Controllers\\BusinessConfigurationController@updateTaxConfig',
        'namespace' => NULL,
        'prefix' => 'api/config',
        'where' => 
        array (
        ),
        'as' => 'generated::2GM5wdJ5Hg2pfIYD',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::8vEjl4hTMZeebygB' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/config/profit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BusinessConfigurationController@getProfitConfig',
        'controller' => 'App\\Http\\Controllers\\BusinessConfigurationController@getProfitConfig',
        'namespace' => NULL,
        'prefix' => 'api/config',
        'where' => 
        array (
        ),
        'as' => 'generated::8vEjl4hTMZeebygB',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::CthH9KF2q2o05TOJ' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/config/profit',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BusinessConfigurationController@updateProfitConfig',
        'controller' => 'App\\Http\\Controllers\\BusinessConfigurationController@updateProfitConfig',
        'namespace' => NULL,
        'prefix' => 'api/config',
        'where' => 
        array (
        ),
        'as' => 'generated::CthH9KF2q2o05TOJ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::G7df8rvBgq9dfMHD' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/config/category/{category}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BusinessConfigurationController@getByCategory',
        'controller' => 'App\\Http\\Controllers\\BusinessConfigurationController@getByCategory',
        'namespace' => NULL,
        'prefix' => 'api/config',
        'where' => 
        array (
        ),
        'as' => 'generated::G7df8rvBgq9dfMHD',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::BKJoGKw6yUtUgejT' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/config/clear-cache',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\BusinessConfigurationController@clearCache',
        'controller' => 'App\\Http\\Controllers\\BusinessConfigurationController@clearCache',
        'namespace' => NULL,
        'prefix' => 'api/config',
        'where' => 
        array (
        ),
        'as' => 'generated::BKJoGKw6yUtUgejT',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::czCxXZ1KhDSYzKk3' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/roles',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RolePermissionController@getRoles',
        'controller' => 'App\\Http\\Controllers\\RolePermissionController@getRoles',
        'namespace' => NULL,
        'prefix' => 'api/roles',
        'where' => 
        array (
        ),
        'as' => 'generated::czCxXZ1KhDSYzKk3',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::pJNIan6DKgaTbgDF' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/roles',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RolePermissionController@createRole',
        'controller' => 'App\\Http\\Controllers\\RolePermissionController@createRole',
        'namespace' => NULL,
        'prefix' => 'api/roles',
        'where' => 
        array (
        ),
        'as' => 'generated::pJNIan6DKgaTbgDF',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::29votZU4To9KqflV' => 
    array (
      'methods' => 
      array (
        0 => 'PUT',
      ),
      'uri' => 'api/roles/{role}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RolePermissionController@updateRole',
        'controller' => 'App\\Http\\Controllers\\RolePermissionController@updateRole',
        'namespace' => NULL,
        'prefix' => 'api/roles',
        'where' => 
        array (
        ),
        'as' => 'generated::29votZU4To9KqflV',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::EqSXDyFRRkzVxo9D' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/roles/{role}',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RolePermissionController@deleteRole',
        'controller' => 'App\\Http\\Controllers\\RolePermissionController@deleteRole',
        'namespace' => NULL,
        'prefix' => 'api/roles',
        'where' => 
        array (
        ),
        'as' => 'generated::EqSXDyFRRkzVxo9D',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::41vH4vxgc4fj68q7' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/roles/assign',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RolePermissionController@assignRole',
        'controller' => 'App\\Http\\Controllers\\RolePermissionController@assignRole',
        'namespace' => NULL,
        'prefix' => 'api/roles',
        'where' => 
        array (
        ),
        'as' => 'generated::41vH4vxgc4fj68q7',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::pBMhqS0Zr7GYiZvN' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/roles/remove',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RolePermissionController@removeRole',
        'controller' => 'App\\Http\\Controllers\\RolePermissionController@removeRole',
        'namespace' => NULL,
        'prefix' => 'api/roles',
        'where' => 
        array (
        ),
        'as' => 'generated::pBMhqS0Zr7GYiZvN',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::WEOKuavIoaBTkgwc' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/permissions',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RolePermissionController@getPermissions',
        'controller' => 'App\\Http\\Controllers\\RolePermissionController@getPermissions',
        'namespace' => NULL,
        'prefix' => 'api/permissions',
        'where' => 
        array (
        ),
        'as' => 'generated::WEOKuavIoaBTkgwc',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lZ6NMNDthZIdHUaj' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/permissions/user',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RolePermissionController@getUserPermissions',
        'controller' => 'App\\Http\\Controllers\\RolePermissionController@getUserPermissions',
        'namespace' => NULL,
        'prefix' => 'api/permissions',
        'where' => 
        array (
        ),
        'as' => 'generated::lZ6NMNDthZIdHUaj',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Ln3ZkZYAecOix3zw' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/permissions/check',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\RolePermissionController@checkPermission',
        'controller' => 'App\\Http\\Controllers\\RolePermissionController@checkPermission',
        'namespace' => NULL,
        'prefix' => 'api/permissions',
        'where' => 
        array (
        ),
        'as' => 'generated::Ln3ZkZYAecOix3zw',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::O69Hx7b72fxQ6pJD' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/message-templates',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\MessageTemplateController@index',
        'controller' => 'App\\Http\\Controllers\\MessageTemplateController@index',
        'namespace' => NULL,
        'prefix' => 'api/message-templates',
        'where' => 
        array (
        ),
        'as' => 'generated::O69Hx7b72fxQ6pJD',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lWbgetEkMPsPJ5kv' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/message-templates',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\MessageTemplateController@store',
        'controller' => 'App\\Http\\Controllers\\MessageTemplateController@store',
        'namespace' => NULL,
        'prefix' => 'api/message-templates',
        'where' => 
        array (
        ),
        'as' => 'generated::lWbgetEkMPsPJ5kv',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::ghFhlvgL7efaTHjR' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/message-templates/by-type-category',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\MessageTemplateController@getByTypeAndCategory',
        'controller' => 'App\\Http\\Controllers\\MessageTemplateController@getByTypeAndCategory',
        'namespace' => NULL,
        'prefix' => 'api/message-templates',
        'where' => 
        array (
        ),
        'as' => 'generated::ghFhlvgL7efaTHjR',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Agx3xeXCX1xbi6nx' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/message-templates/render',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\MessageTemplateController@render',
        'controller' => 'App\\Http\\Controllers\\MessageTemplateController@render',
        'namespace' => NULL,
        'prefix' => 'api/message-templates',
        'where' => 
        array (
        ),
        'as' => 'generated::Agx3xeXCX1xbi6nx',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::zBZgaAddoqWKbMok' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/message-templates/default-variables',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\MessageTemplateController@getDefaultVariables',
        'controller' => 'App\\Http\\Controllers\\MessageTemplateController@getDefaultVariables',
        'namespace' => NULL,
        'prefix' => 'api/message-templates',
        'where' => 
        array (
        ),
        'as' => 'generated::zBZgaAddoqWKbMok',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::AYklvpWEUjlMKOR7' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/security/2fa/enable',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@enable2FA',
        'controller' => 'App\\Http\\Controllers\\SecurityController@enable2FA',
        'namespace' => NULL,
        'prefix' => 'api/security/2fa',
        'where' => 
        array (
        ),
        'as' => 'generated::AYklvpWEUjlMKOR7',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::URRS8mlP8kmqow6R' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/security/2fa/confirm',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@confirm2FA',
        'controller' => 'App\\Http\\Controllers\\SecurityController@confirm2FA',
        'namespace' => NULL,
        'prefix' => 'api/security/2fa',
        'where' => 
        array (
        ),
        'as' => 'generated::URRS8mlP8kmqow6R',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::12MNxXRpTRJhpDQV' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/security/2fa/disable',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@disable2FA',
        'controller' => 'App\\Http\\Controllers\\SecurityController@disable2FA',
        'namespace' => NULL,
        'prefix' => 'api/security/2fa',
        'where' => 
        array (
        ),
        'as' => 'generated::12MNxXRpTRJhpDQV',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::9rbWXNqINViKMx3y' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/security/2fa/regenerate-backup-codes',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@regenerateBackupCodes',
        'controller' => 'App\\Http\\Controllers\\SecurityController@regenerateBackupCodes',
        'namespace' => NULL,
        'prefix' => 'api/security/2fa',
        'where' => 
        array (
        ),
        'as' => 'generated::9rbWXNqINViKMx3y',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::d0Uf1vGWk2MUdJaB' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/security/sessions/active',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@getActiveSessions',
        'controller' => 'App\\Http\\Controllers\\SecurityController@getActiveSessions',
        'namespace' => NULL,
        'prefix' => 'api/security/sessions',
        'where' => 
        array (
        ),
        'as' => 'generated::d0Uf1vGWk2MUdJaB',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::6VpPZpyAdExrZZ2q' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/security/sessions/terminate',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@terminateSession',
        'controller' => 'App\\Http\\Controllers\\SecurityController@terminateSession',
        'namespace' => NULL,
        'prefix' => 'api/security/sessions',
        'where' => 
        array (
        ),
        'as' => 'generated::6VpPZpyAdExrZZ2q',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::t286rN3BqndWZpCj' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/security/sessions/terminate-others',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@terminateOtherSessions',
        'controller' => 'App\\Http\\Controllers\\SecurityController@terminateOtherSessions',
        'namespace' => NULL,
        'prefix' => 'api/security/sessions',
        'where' => 
        array (
        ),
        'as' => 'generated::t286rN3BqndWZpCj',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::WgImt3Opld0Q3Okw' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/security/sessions/stats',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@getSessionStats',
        'controller' => 'App\\Http\\Controllers\\SecurityController@getSessionStats',
        'namespace' => NULL,
        'prefix' => 'api/security/sessions',
        'where' => 
        array (
        ),
        'as' => 'generated::WgImt3Opld0Q3Okw',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::6Sx7rWoyE8pFJCOK' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/security/audit/logs',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@getAuditLogs',
        'controller' => 'App\\Http\\Controllers\\SecurityController@getAuditLogs',
        'namespace' => NULL,
        'prefix' => 'api/security/audit',
        'where' => 
        array (
        ),
        'as' => 'generated::6Sx7rWoyE8pFJCOK',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::gy5yosza2YTQUZPx' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/security/audit/statistics',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@getAuditStatistics',
        'controller' => 'App\\Http\\Controllers\\SecurityController@getAuditStatistics',
        'namespace' => NULL,
        'prefix' => 'api/security/audit',
        'where' => 
        array (
        ),
        'as' => 'generated::gy5yosza2YTQUZPx',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::lrhglepyp7U0IVRx' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/security/audit/export',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@exportAuditLogs',
        'controller' => 'App\\Http\\Controllers\\SecurityController@exportAuditLogs',
        'namespace' => NULL,
        'prefix' => 'api/security/audit',
        'where' => 
        array (
        ),
        'as' => 'generated::lrhglepyp7U0IVRx',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::5pCCeoAUHY1xNlT0' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/security/anomalies',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@getLoginAnomalies',
        'controller' => 'App\\Http\\Controllers\\SecurityController@getLoginAnomalies',
        'namespace' => NULL,
        'prefix' => 'api/security/anomalies',
        'where' => 
        array (
        ),
        'as' => 'generated::5pCCeoAUHY1xNlT0',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::KnsIv0DdwaCKtHkJ' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/security/anomalies/statistics',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\SecurityController@getAnomalyStatistics',
        'controller' => 'App\\Http\\Controllers\\SecurityController@getAnomalyStatistics',
        'namespace' => NULL,
        'prefix' => 'api/security/anomalies',
        'where' => 
        array (
        ),
        'as' => 'generated::KnsIv0DdwaCKtHkJ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::FuP4HAxNPhD8ujFP' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/compliance/data-types',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DataComplianceController@getDataTypes',
        'controller' => 'App\\Http\\Controllers\\DataComplianceController@getDataTypes',
        'namespace' => NULL,
        'prefix' => 'api/compliance',
        'where' => 
        array (
        ),
        'as' => 'generated::FuP4HAxNPhD8ujFP',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::QacAsrnH7tRI7XNO' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/compliance/statistics',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DataComplianceController@getStatistics',
        'controller' => 'App\\Http\\Controllers\\DataComplianceController@getStatistics',
        'namespace' => NULL,
        'prefix' => 'api/compliance',
        'where' => 
        array (
        ),
        'as' => 'generated::QacAsrnH7tRI7XNO',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::mowjs1e5GcEny82u' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/compliance/export',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DataComplianceController@createExportRequest',
        'controller' => 'App\\Http\\Controllers\\DataComplianceController@createExportRequest',
        'namespace' => NULL,
        'prefix' => 'api/compliance/export',
        'where' => 
        array (
        ),
        'as' => 'generated::mowjs1e5GcEny82u',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::tDl1zJ5889vYDrpS' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/compliance/export',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DataComplianceController@getExportRequests',
        'controller' => 'App\\Http\\Controllers\\DataComplianceController@getExportRequests',
        'namespace' => NULL,
        'prefix' => 'api/compliance/export',
        'where' => 
        array (
        ),
        'as' => 'generated::tDl1zJ5889vYDrpS',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::9l3YRX1zdFcbmfVa' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/compliance/export/{exportRequest}/download',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DataComplianceController@downloadExport',
        'controller' => 'App\\Http\\Controllers\\DataComplianceController@downloadExport',
        'namespace' => NULL,
        'prefix' => 'api/compliance/export',
        'where' => 
        array (
        ),
        'as' => 'generated::9l3YRX1zdFcbmfVa',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::Xyu7zriyi6l148VP' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/compliance/export/process',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DataComplianceController@processExportRequests',
        'controller' => 'App\\Http\\Controllers\\DataComplianceController@processExportRequests',
        'namespace' => NULL,
        'prefix' => 'api/compliance/export',
        'where' => 
        array (
        ),
        'as' => 'generated::Xyu7zriyi6l148VP',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::64CmIR5MA4D6wrmf' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/compliance/deletion',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DataComplianceController@createDeletionRequest',
        'controller' => 'App\\Http\\Controllers\\DataComplianceController@createDeletionRequest',
        'namespace' => NULL,
        'prefix' => 'api/compliance/deletion',
        'where' => 
        array (
        ),
        'as' => 'generated::64CmIR5MA4D6wrmf',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::MWXtFJcvXZWakWZQ' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/compliance/deletion',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DataComplianceController@getDeletionRequests',
        'controller' => 'App\\Http\\Controllers\\DataComplianceController@getDeletionRequests',
        'namespace' => NULL,
        'prefix' => 'api/compliance/deletion',
        'where' => 
        array (
        ),
        'as' => 'generated::MWXtFJcvXZWakWZQ',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::gc5xKdde5JaBhb9X' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/compliance/deletion/{deletionRequest}/approve',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DataComplianceController@approveDeletionRequest',
        'controller' => 'App\\Http\\Controllers\\DataComplianceController@approveDeletionRequest',
        'namespace' => NULL,
        'prefix' => 'api/compliance/deletion',
        'where' => 
        array (
        ),
        'as' => 'generated::gc5xKdde5JaBhb9X',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::na94l0C7L8UWkF5J' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/compliance/deletion/{deletionRequest}/reject',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DataComplianceController@rejectDeletionRequest',
        'controller' => 'App\\Http\\Controllers\\DataComplianceController@rejectDeletionRequest',
        'namespace' => NULL,
        'prefix' => 'api/compliance/deletion',
        'where' => 
        array (
        ),
        'as' => 'generated::na94l0C7L8UWkF5J',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::m8iduBjf6jRITTAw' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/compliance/deletion/process',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\DataComplianceController@processDeletionRequests',
        'controller' => 'App\\Http\\Controllers\\DataComplianceController@processDeletionRequests',
        'namespace' => NULL,
        'prefix' => 'api/compliance/deletion',
        'where' => 
        array (
        ),
        'as' => 'generated::m8iduBjf6jRITTAw',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::ibHv12On0g9FUa0a' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/queue',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\QueueController@index',
        'controller' => 'App\\Http\\Controllers\\QueueController@index',
        'namespace' => NULL,
        'prefix' => 'api/queue',
        'where' => 
        array (
        ),
        'as' => 'generated::ibHv12On0g9FUa0a',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::jdonDa5lc34gxWtr' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => 'api/queue/history',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\QueueController@getJobHistory',
        'controller' => 'App\\Http\\Controllers\\QueueController@getJobHistory',
        'namespace' => NULL,
        'prefix' => 'api/queue',
        'where' => 
        array (
        ),
        'as' => 'generated::jdonDa5lc34gxWtr',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::URLD3CSNZiU8syj1' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/queue/backup',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\QueueController@scheduleBackup',
        'controller' => 'App\\Http\\Controllers\\QueueController@scheduleBackup',
        'namespace' => NULL,
        'prefix' => 'api/queue',
        'where' => 
        array (
        ),
        'as' => 'generated::URLD3CSNZiU8syj1',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::u0QfGpI55eCERwwx' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/queue/recurring-invoices',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\QueueController@processRecurringInvoices',
        'controller' => 'App\\Http\\Controllers\\QueueController@processRecurringInvoices',
        'namespace' => NULL,
        'prefix' => 'api/queue',
        'where' => 
        array (
        ),
        'as' => 'generated::u0QfGpI55eCERwwx',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::4DwaKwTQ0h9Sd3bk' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/queue/reminders',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\QueueController@sendReminders',
        'controller' => 'App\\Http\\Controllers\\QueueController@sendReminders',
        'namespace' => NULL,
        'prefix' => 'api/queue',
        'where' => 
        array (
        ),
        'as' => 'generated::4DwaKwTQ0h9Sd3bk',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::tQNXx8YHPCE6QsXk' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/queue/stock-alerts',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\QueueController@sendStockAlerts',
        'controller' => 'App\\Http\\Controllers\\QueueController@sendStockAlerts',
        'namespace' => NULL,
        'prefix' => 'api/queue',
        'where' => 
        array (
        ),
        'as' => 'generated::tQNXx8YHPCE6QsXk',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::wjxfyVpIi3PDI2Ut' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/queue/communication',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\QueueController@sendCommunication',
        'controller' => 'App\\Http\\Controllers\\QueueController@sendCommunication',
        'namespace' => NULL,
        'prefix' => 'api/queue',
        'where' => 
        array (
        ),
        'as' => 'generated::wjxfyVpIi3PDI2Ut',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::XEw2ITOEuw9jovrP' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/queue/bulk-communications',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\QueueController@sendBulkCommunications',
        'controller' => 'App\\Http\\Controllers\\QueueController@sendBulkCommunications',
        'namespace' => NULL,
        'prefix' => 'api/queue',
        'where' => 
        array (
        ),
        'as' => 'generated::XEw2ITOEuw9jovrP',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::VZV2MH8Y9hOOm5pR' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/queue/sync-offline',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\QueueController@syncOfflineData',
        'controller' => 'App\\Http\\Controllers\\QueueController@syncOfflineData',
        'namespace' => NULL,
        'prefix' => 'api/queue',
        'where' => 
        array (
        ),
        'as' => 'generated::VZV2MH8Y9hOOm5pR',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::qylyzrcYSL0cBlTd' => 
    array (
      'methods' => 
      array (
        0 => 'DELETE',
      ),
      'uri' => 'api/queue/failed-jobs',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\QueueController@clearFailedJobs',
        'controller' => 'App\\Http\\Controllers\\QueueController@clearFailedJobs',
        'namespace' => NULL,
        'prefix' => 'api/queue',
        'where' => 
        array (
        ),
        'as' => 'generated::qylyzrcYSL0cBlTd',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::6cUp7fJjVf7rLqN3' => 
    array (
      'methods' => 
      array (
        0 => 'POST',
      ),
      'uri' => 'api/queue/retry-job',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'api',
          1 => 'auth:sanctum',
          2 => 'auth.api',
        ),
        'uses' => 'App\\Http\\Controllers\\QueueController@retryFailedJob',
        'controller' => 'App\\Http\\Controllers\\QueueController@retryFailedJob',
        'namespace' => NULL,
        'prefix' => 'api/queue',
        'where' => 
        array (
        ),
        'as' => 'generated::6cUp7fJjVf7rLqN3',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
    'generated::GEVBwylq4ULEOCQf' => 
    array (
      'methods' => 
      array (
        0 => 'GET',
        1 => 'HEAD',
      ),
      'uri' => '/',
      'action' => 
      array (
        'middleware' => 
        array (
          0 => 'web',
        ),
        'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:62:"function () {
    return [\'Laravel\' => \\app()->version()];
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000008240000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::GEVBwylq4ULEOCQf',
      ),
      'fallback' => false,
      'defaults' => 
      array (
      ),
      'wheres' => 
      array (
      ),
      'bindingFields' => 
      array (
      ),
      'lockSeconds' => NULL,
      'waitSeconds' => NULL,
      'withTrashed' => false,
    ),
  ),
)
);
