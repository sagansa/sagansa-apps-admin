<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Resi - {{ $salesOrderOnline->receipt_no }}</title>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #333;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .resi-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .proof-image {
            text-align: center;
            margin: 20px 0;
        }
        .proof-image img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ccc;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .print-container {
                border: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="header">
            <h1>Cetak Resi - {{ $salesOrderOnline->receipt_no }}</h1>
            <p>Online Sales Order</p>
        </div>

        <div class="resi-info">
            <div>
                <strong>Tanggal:</strong> {{ $salesOrderOnline->created_at->format('d/m/Y H:i') }}
            </div>
            <div>
                <strong>Status:</strong> {{ $salesOrderOnline->delivery_status_label }}
            </div>
            <div>
                <strong>Store:</strong> {{ $salesOrderOnline->store->nickname ?? 'N/A' }}
            </div>
        </div>

        @if($salesOrderOnline->image_payment)
        <div class="proof-image">
            <h3>Bukti Pembayaran</h3>
            <img src="{{ PublicStorageUrl::from($salesOrderOnline->image_payment) }}" alt="Bukti Pembayaran">
        </div>
        @endif

        <div class="footer">
            <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>No. Resi: {{ $salesOrderOnline->receipt_no }}</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()">Cetak Ulang</button>
        <button onclick="window.close()">Tutup</button>
    </div>
</body>
</html>