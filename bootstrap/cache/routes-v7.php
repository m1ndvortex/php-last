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
            '_route' => 'generated::jPrFTsByIkGXcd04',
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
            '_route' => 'generated::eJdnPIyRboQh4ZUz',
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
            '_route' => 'generated::WbAbVz7hZwEVo1d7',
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
            '_route' => 'generated::LHyBFePI8MS4xEPD',
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
            '_route' => 'generated::000Gm2mpre41bqjG',
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
            '_route' => 'generated::GTLx4PrWAnM2wKvQ',
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
            '_route' => 'generated::VK9oS5nbmaZM1YAh',
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
            '_route' => 'generated::qQxcXztD2OafqPki',
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
            '_route' => 'generated::jTcqC5N0JUMiZHyW',
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
            '_route' => 'generated::QvhuOD3HDXFWKSNn',
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
            '_route' => 'generated::v1dauVM75cEzoTzJ',
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
            '_route' => 'generated::xq56fgBSCKXwEXNN',
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
            '_route' => 'generated::OfSu93J7fBXl4wzH',
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
            '_route' => 'generated::beZr2xfPVH3bfgL0',
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
            '_route' => 'generated::M8XrPj9RDflyC1XY',
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
            '_route' => 'generated::0590yJpi6rIi85sM',
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
            '_route' => 'generated::q2HtkwSEDUje3AnX',
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
            '_route' => 'generated::h73NkiP1LJ5Ur0b5',
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
            '_route' => 'generated::SM4t4pqGSC6tZIdP',
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
            '_route' => 'generated::C8ZCpoul21IBHC7x',
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
            '_route' => 'generated::t8Nq16EesTanUuls',
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
            '_route' => 'generated::yKRGaqhbXiwxqdn9',
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
            '_route' => 'generated::71YdU65SJYkWgkLo',
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
            '_route' => 'generated::YiIrVWWzrSqOOhsQ',
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
            '_route' => 'generated::wft1kEJZISxblloA',
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
            '_route' => 'generated::OSYPwEygolbUrtko',
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
            '_route' => 'generated::CsOD0k50xlvA8l2t',
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
            '_route' => 'generated::TFWaKNGkURVWrze0',
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
            '_route' => 'generated::SbK6wgQL2VQin9av',
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
      '/api/customers/aging-report' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::2ENOZhbRRHfc8TYL',
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
            '_route' => 'generated::NaAYLb0XfJDwb4ZN',
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
            '_route' => 'generated::QFQQxPJILRDNQNrq',
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
            '_route' => 'generated::ZFmiT6EexVZFczzK',
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
      '/api/invoices/batch-pdf' => 
      array (
        0 => 
        array (
          0 => 
          array (
            '_route' => 'generated::wtOZv2BrBKFnXSFl',
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
            '_route' => 'generated::u3I3WnlNxMzGxJBi',
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
            '_route' => 'generated::LHPyRHuyPQ3sd3J5',
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
            '_route' => 'generated::GN9iZ8tU6tdknJNN',
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
            '_route' => 'generated::ghFAuFTtH7rt4b8i',
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
            '_route' => 'generated::TzirELablxspJMw5',
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
            '_route' => 'generated::eEz22UpbGMiihC4t',
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
            '_route' => 'generated::bOcgtWWe8TbDKgzI',
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
            '_route' => 'generated::YIsqDQOYFxvVnXBX',
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
            '_route' => 'generated::szWbpxdGnpwnWrYo',
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
            '_route' => 'generated::rg9BmcJwjkIXwbTv',
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
            '_route' => 'generated::46wMGoGwwkQPuk5N',
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
            '_route' => 'generated::0zn3T8KPZSk0T4b5',
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
            '_route' => 'generated::Hye59RoEEp9Cgacu',
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
            '_route' => 'generated::USXADxe4tJyBEVX8',
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
            '_route' => 'generated::2rONjDWMuQssGRwl',
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
            '_route' => 'generated::UZVGvBlznKP9aQXM',
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
            '_route' => 'generated::htrwg6BynIKgsKyu',
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
            '_route' => 'generated::h6ajAPAyqB9oO9BR',
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
            '_route' => 'generated::OZk3sDrtRUfjgHcj',
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
            '_route' => 'generated::xPvOQB01NpVIqc6B',
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
            '_route' => 'generated::zWv9IfqMIySuDMcs',
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
            '_route' => 'generated::JCKQ1K7Fr6PeCIrd',
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
            '_route' => 'generated::5a42mt6NhZ1jZrUh',
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
            '_route' => 'generated::IVYAN4D1vKuQ3MkQ',
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
            '_route' => 'generated::U5J5BbrcTMSXctl6',
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
            '_route' => 'generated::B3TKHRO5GjEcPEXd',
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
            '_route' => 'generated::9nbwv9lfs5HXOZ1W',
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
            '_route' => 'generated::nrNsQ16nHPBxxfSr',
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
            '_route' => 'generated::thWWcTERAFccrVZd',
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
            '_route' => 'generated::O5pN8GAljoCwhTkx',
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
            '_route' => 'generated::Ap67l3VEFM6WoJhm',
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
            '_route' => 'generated::3oqQXy7WCovpainK',
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
            '_route' => 'generated::Qz6d3m9sV57N7KG7',
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
            '_route' => 'generated::lQu240C50nfsVug9',
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
            '_route' => 'generated::wGT3yBJdQhj5p56w',
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
            '_route' => 'generated::MhyltqtbUancdBDe',
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
            '_route' => 'generated::EcZu69NNFN1qupKx',
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
            '_route' => 'generated::iJL8DUnYMObeYWng',
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
            '_route' => 'generated::zDOWz4p8ZrzrvVP8',
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
            '_route' => 'generated::xJNlw3iNCQIyx0ZH',
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
            '_route' => 'generated::vdhhTBNg3tPLMnBb',
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
            '_route' => 'generated::MrkgcM23DcCVlmUw',
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
            '_route' => 'generated::WGQQGaPAVQtzeuNI',
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
            '_route' => 'generated::wu72H6mGLHVKKoXn',
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
            '_route' => 'generated::JpAiJQY9VFBzOYxs',
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
            '_route' => 'generated::Cg8FxCQ3EgUyuN5j',
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
            '_route' => 'generated::XnKG4PFiVRxRAXkT',
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
            '_route' => 'generated::9bnid0VeI71lXti0',
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
            '_route' => 'generated::lC3mpLfeaM87DhX1',
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
            '_route' => 'generated::cddwPZDqG0AXEQwC',
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
            '_route' => 'generated::vGcUElxzNREIV8Uf',
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
            '_route' => 'generated::I32Y7Ssrvs0fQVnZ',
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
            '_route' => 'generated::Xi7MPBP40ccXqug0',
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
            '_route' => 'generated::MccP2w8SnEJjIVTN',
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
            '_route' => 'generated::BQyzG7xawPp6QRZQ',
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
            '_route' => 'generated::v0PK60bIG1QnAj1m',
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
            '_route' => 'generated::3PRkapJWlQoR0OtM',
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
            '_route' => 'generated::NI6KG2Z8nUpLPvfU',
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
            '_route' => 'generated::BIUMJeg3wF0pbqGH',
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
            '_route' => 'generated::ltDwALFTmrdSQuW8',
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
            '_route' => 'generated::OXZkUOS8n9F2i6m7',
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
            '_route' => 'generated::WflKTPp3b2LoCujB',
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
            '_route' => 'generated::fW1do9VTastryGHk',
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
            '_route' => 'generated::4x9WTsMzEUEaB7uC',
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
            '_route' => 'generated::d6kDhxXpNHMi6vNK',
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
            '_route' => 'generated::wAQsPNZdrlRu82Nt',
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
            '_route' => 'generated::rwtjtyC39tH6Wquj',
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
            '_route' => 'generated::JJ8YppVgqnv7avEc',
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
            '_route' => 'generated::jkwd36WoaCEH3Ojn',
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
            '_route' => 'generated::vSx1ZmJh5PttUxSr',
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
            '_route' => 'generated::38a4wPOCH8e2Ns4j',
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
            '_route' => 'generated::B99wgUKF6RPWICIL',
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
            '_route' => 'generated::P9xpxHw1LHEOQP4E',
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
            '_route' => 'generated::LFPncOHhh0y7AJ6M',
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
            '_route' => 'generated::Qaq65Ai7EAccrchw',
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
            '_route' => 'generated::18EQ9TNXF7ED0R8O',
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
            '_route' => 'generated::ochVnBq7uJ9A5hXs',
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
            '_route' => 'generated::cfJKioe4eLGA1TqR',
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
            '_route' => 'generated::MjmtuUEtYmGtjxeZ',
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
            '_route' => 'generated::uEMypxusDK4fNgX8',
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
            '_route' => 'generated::hL4u6FIryWBi4HRt',
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
            '_route' => 'generated::786J4diex5mTEKWR',
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
            '_route' => 'generated::lIRjeTkCwtlu2s02',
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
            '_route' => 'generated::IMZNHdpIUhPLcQJk',
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
            '_route' => 'generated::F6ve9kNHZ8ifWmwU',
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
            '_route' => 'generated::sSc2OUTsPDiiKn06',
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
            '_route' => 'generated::DaYgzuWXGSlqpzBf',
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
            '_route' => 'generated::mGsei5h3lQ3GcRru',
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
            '_route' => 'generated::UUes470ehYcCLuOQ',
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
            '_route' => 'generated::n6oZNGNhVuHkLm8w',
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
            '_route' => 'generated::WOcaEpZsvw99xlcx',
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
            '_route' => 'generated::cr1tqp5d2iEGs3Q1',
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
            '_route' => 'generated::RmrDssYag6F7CbsJ',
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
            '_route' => 'generated::z5HxuV8jUASeUhWO',
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
            '_route' => 'generated::HlLIEc4pXJ7wdpJn',
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
            '_route' => 'generated::AYLPvhVw1GSKhqBD',
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
            '_route' => 'generated::LdU4UjYTYTOPmesn',
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
            '_route' => 'generated::ARMpNzTS54uFUt9j',
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
            '_route' => 'generated::ybyZZ6HHip1DuAuU',
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
            '_route' => 'generated::HUnATHVS1BkegMRg',
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
            '_route' => 'generated::RzcBMzPw0IbfKNhl',
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
            '_route' => 'generated::Mme3fopxd57SxraP',
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
            '_route' => 'generated::L8T0GeENMklL80XL',
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
            '_route' => 'generated::cKaioFy1agQ5LnrO',
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
            '_route' => 'generated::HiVOJwq1Xcj8SyeB',
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
            '_route' => 'generated::xpCDs6UyYWaFVxZV',
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
            '_route' => 'generated::6I8zjMKH5qTtIqYK',
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
            '_route' => 'generated::moFd0gKpZgWlCCC3',
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
            '_route' => 'generated::POc6wxkhD18R5v0y',
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
            '_route' => 'generated::Ckz2oq22W9rTo6XK',
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
            '_route' => 'generated::S2fJYt1GrYUZygMp',
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
            '_route' => 'generated::szsprEGYbRoj0AVk',
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
            '_route' => 'generated::NZEeqx7LTdYET2L0',
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
            '_route' => 'generated::wlFReqiUjqxiKxOw',
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
            '_route' => 'generated::IivzRys60xfEcgbp',
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
            '_route' => 'generated::HjUcdmOREHlz9PIZ',
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
            '_route' => 'generated::la2DslD8R1ZRxwsZ',
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
            '_route' => 'generated::oG56sUuKG17ft7ca',
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
            '_route' => 'generated::wfWTFNjE6oAIanL3',
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
            '_route' => 'generated::14sIUOi7f867A4ze',
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
            '_route' => 'generated::cGIHRE19MP4ONIL1',
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
            '_route' => 'generated::U5eJULOow7AN57h6',
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
            '_route' => 'generated::WRJCNJlfVHroaBJZ',
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
            '_route' => 'generated::wBZijT6EcZxf81tm',
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
            '_route' => 'generated::nmb84wIpJnlqY74f',
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
            '_route' => 'generated::nzjdt7rvbPK24WTS',
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
            '_route' => 'generated::zebHLgjhWzIOavuj',
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
            '_route' => 'generated::IxEvqLv4cc4gx0JL',
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
            '_route' => 'generated::s5E4xEsnlAsgEERo',
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
            '_route' => 'generated::7592QGHryeoUCdU0',
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
    'generated::jPrFTsByIkGXcd04' => 
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
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"000000000000080d0000000000000000";}}',
        'namespace' => NULL,
        'prefix' => 'api',
        'where' => 
        array (
        ),
        'as' => 'generated::jPrFTsByIkGXcd04',
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
    'generated::eJdnPIyRboQh4ZUz' => 
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
        'as' => 'generated::eJdnPIyRboQh4ZUz',
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
    'generated::WbAbVz7hZwEVo1d7' => 
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
        'as' => 'generated::WbAbVz7hZwEVo1d7',
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
    'generated::LHyBFePI8MS4xEPD' => 
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
        'as' => 'generated::LHyBFePI8MS4xEPD',
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
    'generated::000Gm2mpre41bqjG' => 
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
        'as' => 'generated::000Gm2mpre41bqjG',
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
    'generated::GTLx4PrWAnM2wKvQ' => 
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
        'as' => 'generated::GTLx4PrWAnM2wKvQ',
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
    'generated::VK9oS5nbmaZM1YAh' => 
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
        'as' => 'generated::VK9oS5nbmaZM1YAh',
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
    'generated::qQxcXztD2OafqPki' => 
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
        'as' => 'generated::qQxcXztD2OafqPki',
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
    'generated::jTcqC5N0JUMiZHyW' => 
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
        'as' => 'generated::jTcqC5N0JUMiZHyW',
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
    'generated::QvhuOD3HDXFWKSNn' => 
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
        'as' => 'generated::QvhuOD3HDXFWKSNn',
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
    'generated::v1dauVM75cEzoTzJ' => 
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
        'as' => 'generated::v1dauVM75cEzoTzJ',
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
    'generated::xq56fgBSCKXwEXNN' => 
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
        'as' => 'generated::xq56fgBSCKXwEXNN',
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
    'generated::OfSu93J7fBXl4wzH' => 
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
        'as' => 'generated::OfSu93J7fBXl4wzH',
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
    'generated::beZr2xfPVH3bfgL0' => 
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
        'as' => 'generated::beZr2xfPVH3bfgL0',
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
    'generated::M8XrPj9RDflyC1XY' => 
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
        'as' => 'generated::M8XrPj9RDflyC1XY',
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
    'generated::0590yJpi6rIi85sM' => 
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
        'as' => 'generated::0590yJpi6rIi85sM',
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
    'generated::q2HtkwSEDUje3AnX' => 
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
        'as' => 'generated::q2HtkwSEDUje3AnX',
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
    'generated::h73NkiP1LJ5Ur0b5' => 
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
        'as' => 'generated::h73NkiP1LJ5Ur0b5',
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
    'generated::SM4t4pqGSC6tZIdP' => 
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
        'as' => 'generated::SM4t4pqGSC6tZIdP',
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
    'generated::C8ZCpoul21IBHC7x' => 
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
        'as' => 'generated::C8ZCpoul21IBHC7x',
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
    'generated::t8Nq16EesTanUuls' => 
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
        'as' => 'generated::t8Nq16EesTanUuls',
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
    'generated::yKRGaqhbXiwxqdn9' => 
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
        'as' => 'generated::yKRGaqhbXiwxqdn9',
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
    'generated::71YdU65SJYkWgkLo' => 
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
        'as' => 'generated::71YdU65SJYkWgkLo',
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
    'generated::YiIrVWWzrSqOOhsQ' => 
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
        'as' => 'generated::YiIrVWWzrSqOOhsQ',
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
    'generated::wft1kEJZISxblloA' => 
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
        'as' => 'generated::wft1kEJZISxblloA',
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
    'generated::OSYPwEygolbUrtko' => 
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
        'as' => 'generated::OSYPwEygolbUrtko',
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
    'generated::CsOD0k50xlvA8l2t' => 
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
        'as' => 'generated::CsOD0k50xlvA8l2t',
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
    'generated::TFWaKNGkURVWrze0' => 
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
        'as' => 'generated::TFWaKNGkURVWrze0',
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
    'generated::SbK6wgQL2VQin9av' => 
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
        'as' => 'generated::SbK6wgQL2VQin9av',
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
    'generated::2ENOZhbRRHfc8TYL' => 
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
        'as' => 'generated::2ENOZhbRRHfc8TYL',
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
    'generated::NaAYLb0XfJDwb4ZN' => 
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
        'as' => 'generated::NaAYLb0XfJDwb4ZN',
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
    'generated::QFQQxPJILRDNQNrq' => 
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
        'as' => 'generated::QFQQxPJILRDNQNrq',
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
    'generated::ZFmiT6EexVZFczzK' => 
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
        'as' => 'generated::ZFmiT6EexVZFczzK',
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
    'generated::sSc2OUTsPDiiKn06' => 
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
        'as' => 'generated::sSc2OUTsPDiiKn06',
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
    'generated::DaYgzuWXGSlqpzBf' => 
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
        'as' => 'generated::DaYgzuWXGSlqpzBf',
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
    'generated::mGsei5h3lQ3GcRru' => 
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
        'as' => 'generated::mGsei5h3lQ3GcRru',
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
    'generated::wtOZv2BrBKFnXSFl' => 
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
        'as' => 'generated::wtOZv2BrBKFnXSFl',
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
    'generated::u3I3WnlNxMzGxJBi' => 
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
        'as' => 'generated::u3I3WnlNxMzGxJBi',
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
    'generated::AYLPvhVw1GSKhqBD' => 
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
        'as' => 'generated::AYLPvhVw1GSKhqBD',
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
    'generated::LdU4UjYTYTOPmesn' => 
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
        'as' => 'generated::LdU4UjYTYTOPmesn',
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
    'generated::ARMpNzTS54uFUt9j' => 
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
        'as' => 'generated::ARMpNzTS54uFUt9j',
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
    'generated::ybyZZ6HHip1DuAuU' => 
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
        'as' => 'generated::ybyZZ6HHip1DuAuU',
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
    'generated::HUnATHVS1BkegMRg' => 
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
        'as' => 'generated::HUnATHVS1BkegMRg',
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
    'generated::RzcBMzPw0IbfKNhl' => 
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
        'as' => 'generated::RzcBMzPw0IbfKNhl',
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
    'generated::Mme3fopxd57SxraP' => 
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
        'as' => 'generated::Mme3fopxd57SxraP',
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
    'generated::LHPyRHuyPQ3sd3J5' => 
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
        'as' => 'generated::LHPyRHuyPQ3sd3J5',
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
    'generated::GN9iZ8tU6tdknJNN' => 
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
        'as' => 'generated::GN9iZ8tU6tdknJNN',
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
    'generated::L8T0GeENMklL80XL' => 
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
        'as' => 'generated::L8T0GeENMklL80XL',
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
    'generated::cKaioFy1agQ5LnrO' => 
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
        'as' => 'generated::cKaioFy1agQ5LnrO',
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
    'generated::ghFAuFTtH7rt4b8i' => 
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
        'as' => 'generated::ghFAuFTtH7rt4b8i',
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
    'generated::TzirELablxspJMw5' => 
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
        'as' => 'generated::TzirELablxspJMw5',
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
    'generated::eEz22UpbGMiihC4t' => 
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
        'as' => 'generated::eEz22UpbGMiihC4t',
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
    'generated::6I8zjMKH5qTtIqYK' => 
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
        'as' => 'generated::6I8zjMKH5qTtIqYK',
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
    'generated::moFd0gKpZgWlCCC3' => 
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
        'as' => 'generated::moFd0gKpZgWlCCC3',
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
    'generated::POc6wxkhD18R5v0y' => 
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
        'as' => 'generated::POc6wxkhD18R5v0y',
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
    'generated::bOcgtWWe8TbDKgzI' => 
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
        'as' => 'generated::bOcgtWWe8TbDKgzI',
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
    'generated::YIsqDQOYFxvVnXBX' => 
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
        'as' => 'generated::YIsqDQOYFxvVnXBX',
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
    'generated::szWbpxdGnpwnWrYo' => 
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
        'as' => 'generated::szWbpxdGnpwnWrYo',
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
    'generated::rg9BmcJwjkIXwbTv' => 
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
        'as' => 'generated::rg9BmcJwjkIXwbTv',
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
    'generated::46wMGoGwwkQPuk5N' => 
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
        'as' => 'generated::46wMGoGwwkQPuk5N',
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
    'generated::HiVOJwq1Xcj8SyeB' => 
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
        'as' => 'generated::HiVOJwq1Xcj8SyeB',
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
    'generated::xpCDs6UyYWaFVxZV' => 
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
        'as' => 'generated::xpCDs6UyYWaFVxZV',
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
    'generated::wlFReqiUjqxiKxOw' => 
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
        'as' => 'generated::wlFReqiUjqxiKxOw',
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
    'generated::IivzRys60xfEcgbp' => 
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
        'as' => 'generated::IivzRys60xfEcgbp',
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
    'generated::HjUcdmOREHlz9PIZ' => 
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
        'as' => 'generated::HjUcdmOREHlz9PIZ',
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
    'generated::la2DslD8R1ZRxwsZ' => 
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
        'as' => 'generated::la2DslD8R1ZRxwsZ',
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
    'generated::oG56sUuKG17ft7ca' => 
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
        'as' => 'generated::oG56sUuKG17ft7ca',
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
    'generated::wfWTFNjE6oAIanL3' => 
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
        'as' => 'generated::wfWTFNjE6oAIanL3',
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
    'generated::14sIUOi7f867A4ze' => 
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
        'as' => 'generated::14sIUOi7f867A4ze',
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
    'generated::cGIHRE19MP4ONIL1' => 
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
        'as' => 'generated::cGIHRE19MP4ONIL1',
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
    'generated::0zn3T8KPZSk0T4b5' => 
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
        'as' => 'generated::0zn3T8KPZSk0T4b5',
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
    'generated::Hye59RoEEp9Cgacu' => 
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
        'as' => 'generated::Hye59RoEEp9Cgacu',
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
    'generated::USXADxe4tJyBEVX8' => 
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
        'as' => 'generated::USXADxe4tJyBEVX8',
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
    'generated::2rONjDWMuQssGRwl' => 
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
        'as' => 'generated::2rONjDWMuQssGRwl',
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
    'generated::UZVGvBlznKP9aQXM' => 
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
        'as' => 'generated::UZVGvBlznKP9aQXM',
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
    'generated::htrwg6BynIKgsKyu' => 
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
        'as' => 'generated::htrwg6BynIKgsKyu',
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
    'generated::h6ajAPAyqB9oO9BR' => 
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
        'as' => 'generated::h6ajAPAyqB9oO9BR',
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
    'generated::OZk3sDrtRUfjgHcj' => 
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
        'as' => 'generated::OZk3sDrtRUfjgHcj',
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
    'generated::xPvOQB01NpVIqc6B' => 
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
        'as' => 'generated::xPvOQB01NpVIqc6B',
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
    'generated::zWv9IfqMIySuDMcs' => 
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
        'as' => 'generated::zWv9IfqMIySuDMcs',
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
    'generated::JCKQ1K7Fr6PeCIrd' => 
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
        'as' => 'generated::JCKQ1K7Fr6PeCIrd',
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
    'generated::5a42mt6NhZ1jZrUh' => 
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
        'as' => 'generated::5a42mt6NhZ1jZrUh',
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
    'generated::IVYAN4D1vKuQ3MkQ' => 
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
        'as' => 'generated::IVYAN4D1vKuQ3MkQ',
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
    'generated::UUes470ehYcCLuOQ' => 
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
        'as' => 'generated::UUes470ehYcCLuOQ',
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
    'generated::n6oZNGNhVuHkLm8w' => 
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
        'as' => 'generated::n6oZNGNhVuHkLm8w',
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
    'generated::WOcaEpZsvw99xlcx' => 
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
        'as' => 'generated::WOcaEpZsvw99xlcx',
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
    'generated::U5eJULOow7AN57h6' => 
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
        'as' => 'generated::U5eJULOow7AN57h6',
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
    'generated::WRJCNJlfVHroaBJZ' => 
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
        'as' => 'generated::WRJCNJlfVHroaBJZ',
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
    'generated::U5J5BbrcTMSXctl6' => 
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
        'as' => 'generated::U5J5BbrcTMSXctl6',
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
    'generated::zebHLgjhWzIOavuj' => 
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
        'as' => 'generated::zebHLgjhWzIOavuj',
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
    'generated::IxEvqLv4cc4gx0JL' => 
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
        'as' => 'generated::IxEvqLv4cc4gx0JL',
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
    'generated::s5E4xEsnlAsgEERo' => 
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
        'as' => 'generated::s5E4xEsnlAsgEERo',
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
    'generated::7592QGHryeoUCdU0' => 
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
        'as' => 'generated::7592QGHryeoUCdU0',
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
    'generated::B3TKHRO5GjEcPEXd' => 
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
        'as' => 'generated::B3TKHRO5GjEcPEXd',
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
    'generated::9nbwv9lfs5HXOZ1W' => 
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
        'as' => 'generated::9nbwv9lfs5HXOZ1W',
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
    'generated::nrNsQ16nHPBxxfSr' => 
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
        'as' => 'generated::nrNsQ16nHPBxxfSr',
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
    'generated::thWWcTERAFccrVZd' => 
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
        'as' => 'generated::thWWcTERAFccrVZd',
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
    'generated::O5pN8GAljoCwhTkx' => 
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
        'as' => 'generated::O5pN8GAljoCwhTkx',
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
    'generated::Ap67l3VEFM6WoJhm' => 
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
        'as' => 'generated::Ap67l3VEFM6WoJhm',
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
    'generated::3oqQXy7WCovpainK' => 
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
        'as' => 'generated::3oqQXy7WCovpainK',
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
    'generated::wBZijT6EcZxf81tm' => 
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
        'as' => 'generated::wBZijT6EcZxf81tm',
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
    'generated::nmb84wIpJnlqY74f' => 
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
        'as' => 'generated::nmb84wIpJnlqY74f',
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
    'generated::nzjdt7rvbPK24WTS' => 
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
        'as' => 'generated::nzjdt7rvbPK24WTS',
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
    'generated::Qz6d3m9sV57N7KG7' => 
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
        'as' => 'generated::Qz6d3m9sV57N7KG7',
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
    'generated::lQu240C50nfsVug9' => 
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
        'as' => 'generated::lQu240C50nfsVug9',
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
    'generated::wGT3yBJdQhj5p56w' => 
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
        'as' => 'generated::wGT3yBJdQhj5p56w',
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
    'generated::MhyltqtbUancdBDe' => 
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
        'as' => 'generated::MhyltqtbUancdBDe',
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
    'generated::EcZu69NNFN1qupKx' => 
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
        'as' => 'generated::EcZu69NNFN1qupKx',
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
    'generated::iJL8DUnYMObeYWng' => 
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
        'as' => 'generated::iJL8DUnYMObeYWng',
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
    'generated::zDOWz4p8ZrzrvVP8' => 
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
        'as' => 'generated::zDOWz4p8ZrzrvVP8',
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
    'generated::xJNlw3iNCQIyx0ZH' => 
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
        'as' => 'generated::xJNlw3iNCQIyx0ZH',
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
    'generated::vdhhTBNg3tPLMnBb' => 
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
        'as' => 'generated::vdhhTBNg3tPLMnBb',
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
    'generated::cr1tqp5d2iEGs3Q1' => 
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
        'as' => 'generated::cr1tqp5d2iEGs3Q1',
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
    'generated::MrkgcM23DcCVlmUw' => 
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
        'as' => 'generated::MrkgcM23DcCVlmUw',
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
    'generated::WGQQGaPAVQtzeuNI' => 
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
        'as' => 'generated::WGQQGaPAVQtzeuNI',
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
    'generated::wu72H6mGLHVKKoXn' => 
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
        'as' => 'generated::wu72H6mGLHVKKoXn',
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
    'generated::Ckz2oq22W9rTo6XK' => 
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
        'as' => 'generated::Ckz2oq22W9rTo6XK',
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
    'generated::S2fJYt1GrYUZygMp' => 
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
        'as' => 'generated::S2fJYt1GrYUZygMp',
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
    'generated::szsprEGYbRoj0AVk' => 
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
        'as' => 'generated::szsprEGYbRoj0AVk',
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
    'generated::NZEeqx7LTdYET2L0' => 
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
        'as' => 'generated::NZEeqx7LTdYET2L0',
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
    'generated::JpAiJQY9VFBzOYxs' => 
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
        'as' => 'generated::JpAiJQY9VFBzOYxs',
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
    'generated::Cg8FxCQ3EgUyuN5j' => 
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
        'as' => 'generated::Cg8FxCQ3EgUyuN5j',
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
    'generated::XnKG4PFiVRxRAXkT' => 
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
        'as' => 'generated::XnKG4PFiVRxRAXkT',
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
    'generated::9bnid0VeI71lXti0' => 
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
        'as' => 'generated::9bnid0VeI71lXti0',
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
    'generated::lC3mpLfeaM87DhX1' => 
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
        'as' => 'generated::lC3mpLfeaM87DhX1',
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
    'generated::cddwPZDqG0AXEQwC' => 
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
        'as' => 'generated::cddwPZDqG0AXEQwC',
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
    'generated::vGcUElxzNREIV8Uf' => 
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
        'as' => 'generated::vGcUElxzNREIV8Uf',
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
    'generated::I32Y7Ssrvs0fQVnZ' => 
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
        'as' => 'generated::I32Y7Ssrvs0fQVnZ',
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
    'generated::Xi7MPBP40ccXqug0' => 
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
        'as' => 'generated::Xi7MPBP40ccXqug0',
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
    'generated::MccP2w8SnEJjIVTN' => 
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
        'as' => 'generated::MccP2w8SnEJjIVTN',
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
    'generated::BQyzG7xawPp6QRZQ' => 
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
        'as' => 'generated::BQyzG7xawPp6QRZQ',
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
    'generated::v0PK60bIG1QnAj1m' => 
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
        'as' => 'generated::v0PK60bIG1QnAj1m',
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
    'generated::3PRkapJWlQoR0OtM' => 
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
        'as' => 'generated::3PRkapJWlQoR0OtM',
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
    'generated::NI6KG2Z8nUpLPvfU' => 
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
        'as' => 'generated::NI6KG2Z8nUpLPvfU',
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
    'generated::BIUMJeg3wF0pbqGH' => 
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
        'as' => 'generated::BIUMJeg3wF0pbqGH',
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
    'generated::ltDwALFTmrdSQuW8' => 
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
        'as' => 'generated::ltDwALFTmrdSQuW8',
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
    'generated::OXZkUOS8n9F2i6m7' => 
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
        'as' => 'generated::OXZkUOS8n9F2i6m7',
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
    'generated::WflKTPp3b2LoCujB' => 
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
        'as' => 'generated::WflKTPp3b2LoCujB',
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
    'generated::fW1do9VTastryGHk' => 
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
        'as' => 'generated::fW1do9VTastryGHk',
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
    'generated::4x9WTsMzEUEaB7uC' => 
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
        'as' => 'generated::4x9WTsMzEUEaB7uC',
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
    'generated::d6kDhxXpNHMi6vNK' => 
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
        'as' => 'generated::d6kDhxXpNHMi6vNK',
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
    'generated::wAQsPNZdrlRu82Nt' => 
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
        'as' => 'generated::wAQsPNZdrlRu82Nt',
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
    'generated::rwtjtyC39tH6Wquj' => 
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
        'as' => 'generated::rwtjtyC39tH6Wquj',
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
    'generated::JJ8YppVgqnv7avEc' => 
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
        'as' => 'generated::JJ8YppVgqnv7avEc',
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
    'generated::jkwd36WoaCEH3Ojn' => 
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
        'as' => 'generated::jkwd36WoaCEH3Ojn',
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
    'generated::RmrDssYag6F7CbsJ' => 
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
        'as' => 'generated::RmrDssYag6F7CbsJ',
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
    'generated::vSx1ZmJh5PttUxSr' => 
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
        'as' => 'generated::vSx1ZmJh5PttUxSr',
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
    'generated::38a4wPOCH8e2Ns4j' => 
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
        'as' => 'generated::38a4wPOCH8e2Ns4j',
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
    'generated::B99wgUKF6RPWICIL' => 
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
        'as' => 'generated::B99wgUKF6RPWICIL',
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
    'generated::z5HxuV8jUASeUhWO' => 
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
        'as' => 'generated::z5HxuV8jUASeUhWO',
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
    'generated::HlLIEc4pXJ7wdpJn' => 
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
        'as' => 'generated::HlLIEc4pXJ7wdpJn',
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
    'generated::P9xpxHw1LHEOQP4E' => 
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
        'as' => 'generated::P9xpxHw1LHEOQP4E',
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
    'generated::LFPncOHhh0y7AJ6M' => 
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
        'as' => 'generated::LFPncOHhh0y7AJ6M',
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
    'generated::Qaq65Ai7EAccrchw' => 
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
        'as' => 'generated::Qaq65Ai7EAccrchw',
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
    'generated::18EQ9TNXF7ED0R8O' => 
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
        'as' => 'generated::18EQ9TNXF7ED0R8O',
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
    'generated::ochVnBq7uJ9A5hXs' => 
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
        'as' => 'generated::ochVnBq7uJ9A5hXs',
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
    'generated::cfJKioe4eLGA1TqR' => 
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
        'as' => 'generated::cfJKioe4eLGA1TqR',
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
    'generated::MjmtuUEtYmGtjxeZ' => 
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
        'as' => 'generated::MjmtuUEtYmGtjxeZ',
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
    'generated::uEMypxusDK4fNgX8' => 
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
        'as' => 'generated::uEMypxusDK4fNgX8',
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
    'generated::hL4u6FIryWBi4HRt' => 
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
        'as' => 'generated::hL4u6FIryWBi4HRt',
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
    'generated::786J4diex5mTEKWR' => 
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
        'as' => 'generated::786J4diex5mTEKWR',
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
    'generated::lIRjeTkCwtlu2s02' => 
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
        'as' => 'generated::lIRjeTkCwtlu2s02',
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
    'generated::IMZNHdpIUhPLcQJk' => 
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
        'as' => 'generated::IMZNHdpIUhPLcQJk',
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
    'generated::F6ve9kNHZ8ifWmwU' => 
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
}";s:5:"scope";s:37:"Illuminate\\Routing\\RouteFileRegistrar";s:4:"this";N;s:4:"self";s:32:"00000000000008100000000000000000";}}',
        'namespace' => NULL,
        'prefix' => '',
        'where' => 
        array (
        ),
        'as' => 'generated::F6ve9kNHZ8ifWmwU',
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
