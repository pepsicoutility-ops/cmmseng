<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Barcode QR Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 40px;
        }
        .qr-container {
            border: 2px solid #000;
            padding: 20px;
            display: inline-block;
            margin: 20px auto;
        }
        .qr-code {
            margin: 20px 0;
        }
        .info {
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 16px;
            margin-bottom: 20px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="title">PEPSICO ENGINEERING CMMS</div>
    <div class="subtitle">Work Order Barcode Scanner</div>
    
    <div class="qr-container">
        <div class="qr-code">
            @if(isset($isSvg) && $isSvg)
                <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR Code" style="width: 300px; height: 300px;">
            @else
                <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code">
            @endif
        </div>
        <div class="info">
            <p><strong>Token:</strong> {{ substr($token, 0, 8) }}...</p>
            <p><strong>URL:</strong> {{ $url }}</p>
            <p>Scan this QR code to create a Work Order</p>
        </div>
    </div>
    
    <div style="margin-top: 40px; font-size: 10px; color: #999;">
        Generated on {{ now()->format('Y-m-d H:i:s') }}
    </div>
</body>
</html>
