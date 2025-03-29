<!DOCTYPE html>
<html>

<head>
    <title>Invoice {{ $invoice->invoice_id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .logo {
            max-height: 80px;
        }

        .business-info {
            flex: 1;
        }

        .invoice-info {
            text-align: right;
        }

        h1 {
            color: #333;
            margin-bottom: 5px;
        }

        .section {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
        }

        .total-row {
            font-weight: bold;
        }

        .notes {
            margin-top: 30px;
            padding: 10px;
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="business-info">
                @if ($invoice->business_logo_url)
                    <img src="{{ $invoice->business_logo_url }}" class="logo">
                @endif
                <h1>{{ $invoice->business_name }}</h1>
                <p>{{ $invoice->business_address }}</p>
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p><strong>Invoice #:</strong> {{ $invoice->invoice_id }}</p>
                <p><strong>Date:</strong> {{ $invoice->created_at->format('Y-m-d') }}</p>
                @if ($invoice->due_date)
                    <p><strong>Due Date:</strong> {{ $invoice->due_date->format('Y-m-d') }}</p>
                @endif
            </div>
        </div>

        <div class="section">
            <h3>Bill To:</h3>
            <p>{{ $invoice->customer_name }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->invoice_items as $item)
                    <tr>
                        <td>{{ $item['item_name'] }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>{{ number_format($item['price_per_item'], 2) }}</td>
                        <td>{{ number_format($item['item_total'] ?? $item['quantity'] * $item['price_per_item'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">Grand Total:</td>
                    <td>{{ number_format($invoice->grand_total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if ($invoice->additional_notes)
            <div class="notes">
                <h4>Notes:</h4>
                <p>{{ $invoice->additional_notes }}</p>
            </div>
        @endif
    </div>
</body>

</html>
