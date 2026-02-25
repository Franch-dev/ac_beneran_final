<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $serviceOrder->order_number }}</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        @media print { body { margin: 0; } .no-print { display: none; } }
        body { font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif; background:#f8fafc; }
        .print-page { padding: 24px; }
        .print-document { background:#fff; max-width: 900px; margin:0 auto; padding:28px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .doc-header { display:flex; justify-content:space-between; gap:16px; align-items:center; }
        .company-info h1 { margin:0; font-size:20px; letter-spacing:0.5px; }
        .company-info p { margin:2px 0; font-size:12px; color:#555; }
        .doc-type { text-align:right; }
        .doc-type-label { font-weight:700; font-size:16px; }
        .doc-divider { border-top:2px solid #e5e7eb; margin:14px 0; }
        .meta-table td { padding:4px 6px; font-size:13px; }
        .section-title { font-weight:700; margin:18px 0 8px; letter-spacing:0.2px; }
        .info-table td { padding:4px 6px; font-size:13px; vertical-align:top; }
        .line-table { width:100%; border-collapse:collapse; margin-top:12px; }
        .line-table th, .line-table td { border:1px solid #e5e7eb; padding:8px; font-size:13px; }
        .line-table th { background:#f3f4f6; text-align:left; }
        .totals { text-align:right; margin-top:10px; font-size:14px; }
        .totals strong { font-size:15px; }
        .print-controls { display:flex; gap:8px; margin-bottom:12px; }
        .btn-print, .btn-back { padding:8px 12px; border:none; border-radius:6px; cursor:pointer; font-weight:600; }
        .btn-print { background:#2563eb; color:#fff; }
        .btn-back { background:#e5e7eb; color:#111827; }
    </style>
</head>
<body class="print-page">
    <div class="no-print print-controls">
        <button onclick="window.print()" class="btn-print"><i>🖨️</i> Cetak Invoice</button>
        <button onclick="window.close()" class="btn-back">← Tutup</button>
    </div>

    <div class="print-document">
        <div class="doc-header">
            <div class="company-info">
                <h1>AC SERVIS MASJID</h1>
                <p>Jl. Contoh No. 123, Jakarta | Telp: (021) 1234-5678</p>
                <p>Email: cs@acservismasjid.id</p>
            </div>
            <div class="doc-type">
                <div class="doc-type-label">INVOICE</div>
                <div class="text-sm">#{{ $serviceOrder->invoice->invoice_number }}</div>
                <div class="text-sm">Order: {{ $serviceOrder->order_number }}</div>
            </div>
        </div>

        <div class="doc-divider"></div>

        <table class="meta-table">
            <tr>
                <td><strong>Tanggal Invoice</strong></td><td>:</td><td>{{ $serviceOrder->invoice->created_at->format('d F Y') }}</td>
                <td><strong>Tanggal Servis</strong></td><td>:</td><td>{{ $serviceOrder->service_date->format('d F Y') }}</td>
            </tr>
            <tr>
                <td><strong>Status</strong></td><td>:</td><td>{{ strtoupper($serviceOrder->status) }}</td>
                <td><strong>Kontak</strong></td><td>:</td><td>{{ $serviceOrder->phone }}</td>
            </tr>
        </table>

        <div class="section-title">Masjid</div>
        <table class="info-table">
            <tr><td width="28%">Nama</td><td width="5%">:</td><td>{{ $serviceOrder->masjid->name }}</td></tr>
            <tr><td>ID Lokasi</td><td>:</td><td>{{ $serviceOrder->masjid->custom_id }}</td></tr>
            <tr><td>Alamat</td><td>:</td><td>{{ $serviceOrder->masjid->address }}</td></tr>
            <tr><td>DKM</td><td>:</td><td>{{ $serviceOrder->masjid->dkm_name }}</td></tr>
            <tr><td>Marbot</td><td>:</td><td>{{ $serviceOrder->masjid->marbot_name }}</td></tr>
            <tr><td>Telepon</td><td>:</td><td>{{ implode(', ', $serviceOrder->masjid->phone_numbers ?? []) }}</td></tr>
        </table>

        <div class="section-title">Rincian Biaya</div>
        <table class="line-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>PK</th>
                    <th>Merk</th>
                    <th>Qty</th>
                    <th>Harga/Unit</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($serviceOrder->serviceDetails as $i => $detail)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $detail->pk_type }}</td>
                    <td>{{ $detail->brand }}</td>
                    <td>{{ $detail->quantity }}</td>
                    <td>Rp {{ number_format($detail->price_per_unit, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($detail->quantity * $detail->price_per_unit, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div><strong>Total: Rp {{ number_format($serviceOrder->invoice->total_price, 0, ',', '.') }}</strong></div>
        </div>
    </div>
</body>
</html>
