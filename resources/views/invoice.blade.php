<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $serviceOrder->invoice->invoice_number }}</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body class="print-page">
    <div class="no-print print-controls">
        <button onclick="window.print()" class="btn-print">🖨️ Cetak Invoice</button>
        <button onclick="window.close()" class="btn-back">← Kembali</button>
    </div>

    <div class="print-document a4">
        <!-- Header -->
        <div class="doc-header">
            <div class="company-logo">
                <div class="logo-icon">❄️</div>
                <div class="company-info">
                    <h1>AC SERVIS MASJID</h1>
                    <p>Jl. Contoh No. 123, Jakarta | Telp: (021) 1234-5678</p>
                    <p>Email: cs@acservismasjid.id | NPWP: 12.345.678.9-000.000</p>
                </div>
            </div>
            <div class="doc-type">
                <div class="doc-type-label">INVOICE</div>
                <div class="doc-type-sub">Faktur Pembayaran</div>
            </div>
        </div>

        <div class="doc-divider"></div>

        <!-- Invoice Meta -->
        <div class="spk-meta">
            <table class="meta-table">
                <tr>
                    <td width="30%"><strong>Nomor Invoice</strong></td>
                    <td width="5%">:</td>
                    <td><strong>{{ $serviceOrder->invoice->invoice_number }}</strong></td>
                    <td width="30%"><strong>Tanggal Invoice</strong></td>
                    <td width="5%">:</td>
                    <td>{{ $serviceOrder->invoice->created_at->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Nomor SPK</strong></td>
                    <td>:</td>
                    <td>{{ $serviceOrder->order_number }}</td>
                    <td><strong>Tanggal Jatuh Tempo</strong></td>
                    <td>:</td>
                    <td><strong>{{ $serviceOrder->invoice->created_at->addDays(14)->format('d F Y') }}</strong></td>
                </tr>
            </table>
        </div>

        <!-- Billing To -->
        <div class="billing-section">
            <div class="billing-from">
                <div class="billing-title">Dari:</div>
                <strong>AC Servis Masjid</strong><br>
                Jl. Contoh No. 123, Jakarta 12345<br>
                Telp: (021) 1234-5678
            </div>
            <div class="billing-to">
                <div class="billing-title">Kepada:</div>
                <strong>{{ $serviceOrder->masjid->name }}</strong><br>
                {{ $serviceOrder->masjid->address }}<br>
                Telp: {{ $serviceOrder->phone }}<br>
                a.n. {{ $serviceOrder->meeting_person === 'dkm' ? $serviceOrder->masjid->dkm_name : $serviceOrder->masjid->marbot_name }}
            </div>
        </div>

        <!-- Items -->
        <div class="section-block">
            <div class="section-block-title">RINCIAN TAGIHAN</div>
            <table class="work-table invoice-table">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Deskripsi Pekerjaan</th>
                        <th width="12%">PK</th>
                        <th width="10%">Qty</th>
                        <th width="18%">Harga Satuan</th>
                        <th width="18%">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($serviceOrder->serviceDetails as $i => $detail)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>Servis AC {{ $detail->brand }} - Cuci, Cek Freon, Pembersihan</td>
                        <td>{{ $detail->pk_type }}</td>
                        <td>{{ $detail->quantity }} unit</td>
                        <td>Rp {{ number_format($detail->price_per_unit, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="subtotal-row">
                        <td colspan="5">Subtotal</td>
                        <td>Rp {{ number_format($serviceOrder->serviceDetails->sum('subtotal'), 0, ',', '.') }}</td>
                    </tr>
                    <tr class="subtotal-row">
                        <td colspan="5">PPN (11%)</td>
                        <td>Rp {{ number_format($serviceOrder->serviceDetails->sum('subtotal') * 0.11, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="5"><strong>TOTAL</strong></td>
                        <td><strong>Rp {{ number_format($serviceOrder->invoice->total_price, 0, ',', '.') }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <div class="payment-method">
                <div class="section-block-title">INFORMASI PEMBAYARAN</div>
                <table class="meta-table">
                    <tr>
                        <td width="40%">Bank</td>
                        <td width="5%">:</td>
                        <td>BCA / Mandiri / BRI</td>
                    </tr>
                    <tr>
                        <td>No. Rekening</td>
                        <td>:</td>
                        <td>1234567890</td>
                    </tr>
                    <tr>
                        <td>Atas Nama</td>
                        <td>:</td>
                        <td>PT. AC Servis Masjid</td>
                    </tr>
                </table>
            </div>
            <div class="total-highlight">
                <div class="total-label">Total Tagihan</div>
                <div class="total-amount">Rp {{ number_format($serviceOrder->invoice->total_price, 0, ',', '.') }}</div>
                <div class="total-terbilang">
                    @php
                        // Simple terbilang (placeholder)
                        $total = $serviceOrder->invoice->total_price;
                    @endphp
                    Terbilang: <em>{{ ucfirst(terbilang($total)) }} Rupiah</em>
                </div>
            </div>
        </div>

        <!-- Signature -->
        <div class="signature-section">
            <div class="sig-box">
                <div class="sig-title">Hormat kami,</div>
                <div class="sig-role">AC Servis Masjid</div>
                <div class="sig-line" style="margin-top: 3rem"></div>
                <div class="sig-name">(...........................)</div>
                <div class="sig-date">Manager</div>
            </div>
            <div class="sig-box" style="text-align: center; align-self: flex-end">
                <div class="payment-stamp">
                    <div>LUNAS</div>
                </div>
                <div class="sig-date" style="margin-top: 0.5rem">Tanggal: _______________</div>
            </div>
        </div>

        <div class="doc-footer">
            <p>Terima kasih atas kepercayaan Anda. Invoice ini sah secara hukum. Pembayaran lewat jatuh tempo dikenakan denda 2% per bulan.</p>
        </div>
    </div>
</body>
</html>
