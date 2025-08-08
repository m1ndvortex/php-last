<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            float: left;
            width: 50%;
        }
        .invoice-info {
            float: right;
            width: 45%;
            text-align: right;
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
            text-align: left;
        }
        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .items-table .number {
            text-align: right;
        }
        .totals {
            float: right;
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
            @if($company['logo_path'])
                <img src="{{ Storage::url($company['logo_path']) }}" alt="Company Logo" style="max-height: 60px; margin-bottom: 10px;">
            @endif
            <h1>{{ $company['name'] }}</h1>
            @if($company['address'])
                <p>{{ $company['address'] }}</p>
            @endif
            @if($company['phone'])
                <p>Phone: {{ $company['phone'] }}</p>
            @endif
            @if($company['email'])
                <p>Email: {{ $company['email'] }}</p>
            @endif
            @if($company['website'])
                <p>Website: {{ $company['website'] }}</p>
            @endif
            @if($company['tax_id'])
                <p>Tax ID: {{ $company['tax_id'] }}</p>
            @endif
            @if($company['registration_number'])
                <p>Reg. No: {{ $company['registration_number'] }}</p>
            @endif
        </div>
        <div class="invoice-info">
            <h2>INVOICE</h2>
            <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Issue Date:</strong> {{ $issue_date_formatted }}</p>
            <p><strong>Due Date:</strong> {{ $due_date_formatted }}</p>
            <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
        </div>
    </div>

    <div class="customer-info">
        <h3>Bill To:</h3>
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
                <th>Item</th>
                <th>Category</th>
                <th>Description</th>
                <th class="number">Qty</th>
                <th class="number">Unit Price</th>
                @if($items_formatted->where('gold_purity', '!=', null)->count() > 0)
                    <th class="number">Gold Purity</th>
                @endif
                @if($items_formatted->where('weight', '!=', null)->count() > 0)
                    <th class="number">Weight</th>
                @endif
                <th class="number">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items_formatted as $item)
                <tr>
                    <td>
                        @if($item['category_image'])
                            <img src="{{ $item['category_image'] }}" alt="Category" style="width: 16px; height: 16px; display: inline-block; margin-right: 4px; vertical-align: middle;">
                        @endif
                        <strong>{{ $item['name'] }}</strong>
                        @if($item['serial_number'])
                            <br><small style="color: #666;">S/N: {{ $item['serial_number'] }}</small>
                        @endif
                    </td>
                    <td>
                        @if($item['category_path'])
                            {{ $item['category_path'] }}
                        @elseif($item['category_name_localized'])
                            {{ $item['category_name_localized'] }}
                        @else
                            -
                        @endif
                    </td>
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
                <td>Subtotal:</td>
                <td class="number">{{ $subtotal_formatted }}</td>
            </tr>
            @if($invoice->discount_amount > 0)
                <tr>
                    <td>Discount:</td>
                    <td class="number">-{{ $discount_formatted }}</td>
                </tr>
            @endif
            <tr>
                <td>Tax:</td>
                <td class="number">{{ $tax_formatted }}</td>
            </tr>
            <tr class="total-row">
                <td>Total:</td>
                <td class="number">{{ $total_formatted }}</td>
            </tr>
        </table>
    </div>

    @if($invoice->notes)
        <div class="notes">
            <h4>Notes:</h4>
            <p>{{ $invoice->notes }}</p>
        </div>
    @endif
</body>
</html>