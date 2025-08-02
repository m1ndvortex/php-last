<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاکتور {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
            direction: rtl;
            text-align: right;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            float: right;
            width: 50%;
        }
        .invoice-info {
            float: left;
            width: 45%;
            text-align: left;
        }
        .customer-info {
            clear: both;
            margin: 30px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .items-table .number {
            text-align: left;
        }
        .totals {
            float: left;
            width: 300px;
            margin-top: 20px;
        }
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals td {
            padding: 5px 10px;
            border-bottom: 1px solid #ddd;
        }
        .totals .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
        }
        .notes {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="header clearfix">
        <div class="company-info">
            <h1>{{ $company['name'] }}</h1>
            @if($company['address'])
                <p>{{ $company['address'] }}</p>
            @endif
            @if($company['phone'])
                <p>تلفن: {{ $company['phone'] }}</p>
            @endif
            @if($company['email'])
                <p>ایمیل: {{ $company['email'] }}</p>
            @endif
        </div>
        <div class="invoice-info">
            <h2>فاکتور</h2>
            <p><strong>شماره فاکتور:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>تاریخ صدور:</strong> {{ $issue_date_formatted }}</p>
            <p><strong>تاریخ سررسید:</strong> {{ $due_date_formatted }}</p>
            <p><strong>وضعیت:</strong> 
                @switch($invoice->status)
                    @case('draft')
                        پیش‌نویس
                        @break
                    @case('sent')
                        ارسال شده
                        @break
                    @case('paid')
                        پرداخت شده
                        @break
                    @case('overdue')
                        سررسید گذشته
                        @break
                    @case('cancelled')
                        لغو شده
                        @break
                    @default
                        {{ $invoice->status }}
                @endswitch
            </p>
        </div>
    </div>

    <div class="customer-info">
        <h3>صورتحساب برای:</h3>
        <p><strong>{{ $customer->name }}</strong></p>
        @if($customer->email)
            <p>{{ $customer->email }}</p>
        @endif
        @if($customer->phone)
            <p>{{ $customer->phone }}</p>
        @endif
        @if($customer->address)
            <p>{{ $customer->address }}</p>
        @endif
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>کالا</th>
                <th>توضیحات</th>
                <th class="number">تعداد</th>
                <th class="number">قیمت واحد</th>
                @if($items_formatted->where('gold_purity', '!=', null)->count() > 0)
                    <th class="number">عیار طلا</th>
                @endif
                @if($items_formatted->where('weight', '!=', null)->count() > 0)
                    <th class="number">وزن</th>
                @endif
                <th class="number">مجموع</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items_formatted as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['description'] ?? '-' }}</td>
                    <td class="number">{{ $item['quantity'] }}</td>
                    <td class="number">{{ $item['unit_price'] }}</td>
                    @if($items_formatted->where('gold_purity', '!=', null)->count() > 0)
                        <td class="number">{{ $item['gold_purity'] ?? '-' }}</td>
                    @endif
                    @if($items_formatted->where('weight', '!=', null)->count() > 0)
                        <td class="number">{{ $item['weight'] ?? '-' }}</td>
                    @endif
                    <td class="number">{{ $item['total_price'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>جمع کل:</td>
                <td class="number">{{ $subtotal_formatted }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
                <tr>
                    <td>تخفیف:</td>
                    <td class="number">-{{ $discount_formatted }}</td>
                </tr>
            @endif
            <tr>
                <td>مالیات:</td>
                <td class="number">{{ $tax_formatted }}</td>
            </tr>
            <tr class="total-row">
                <td>مبلغ نهایی:</td>
                <td class="number">{{ $total_formatted }}</td>
            </tr>
        </table>
    </div>

    @if($invoice->notes)
        <div class="notes">
            <h4>یادداشت:</h4>
            <p>{{ $invoice->notes }}</p>
        </div>
    @endif
</body>
</html>