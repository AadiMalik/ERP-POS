<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans;
            font-size: 12px;
        }

        h2 {
            text-align: center;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 11px;
        }

        th {
            background: #efefef;
        }

        .mb-20 {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <h2>Purchase Request Quotation</h2> <br>
    <br>
    <table class="mb-20">
        <tr>
            <td><strong>Business</strong></td>
            <td>{{ $quotation->business->code ?? '' }} - {{ $quotation->business->name ?? '' }} <br>
                {{ $quotation->business->email ?? '' }} | {{ $quotation->business->phone ?? '' }}</td>
            <td><strong>Quotation No</strong></td>
            <td>{{ $quotation->purchase_request_quotation_no }}</td>
        </tr>
        <tr>
            <td><strong>Date</strong></td>
            <td>{{ localDate($quotation->sent_date) }}</td>
            <td><strong>Dilvery Date</strong></td>
            <td></td>
        </tr>
        <tr>
            <td><strong>Supplier</strong></td>
            <td>{{ $quotation->supplier->code ?? '' }} - {{ $quotation->supplier->name ?? '' }}</td>
            <td><strong>Status</strong></td>
            <td>Sent</td>
        </tr>
    </table>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Variation</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
                <th>Discount %</th>
                <th>Discount Amount</th>
                <th>Tax %</th>
                <th>Tax Amount</th>
                <th>Total</th>
                <th>Available (✓ Yes)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->purchaseRequestQuotationDetails as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ optional($item->productVariation)->name }}</td>
                    <td>{{ decimal($item->requested_quantity) }}</td>
                    <td>{{ optional($item->unit)->name }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <br>
    <table>
        <tr>
            <td width="70%">
                <strong>Supplier Remarks</strong>
                <br><br><br><br>
            </td>
            <td>
                <b>Grand Total</b>
                <br><br>
                Subtotal :
                <br><br>
                Discount :
                <br><br>
                Tax :
                <br><br>
                Total :
            </td>
        </tr>
    </table>
</body>

</html>
