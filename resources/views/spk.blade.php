<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK - {{ $serviceOrder->order_number }}</title>
    <!-- Dedicated print stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        /* Page setup */
        @page {
            size: A4;
            margin: 12mm;
        }
        /* Screen helpers */
        body { font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif; background: #f5f7fa; }
        .print-page { padding: 16px; }
        .print-document {
            background: #fff;
            width: 190mm;
            margin: 0 auto;
            padding: 18mm 16mm;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .print-controls {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        .btn-print, .btn-back {
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-back { background: #e5e7eb; color: #111827; }

        /* Layout + typography */
        .doc-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .company-info h1 { margin: 0; font-size: 18px; letter-spacing: .3px; }
        .company-info p { margin: 2px 0; font-size: 12px; color: #4b5563; }
        .logo-icon { width: 34px; height: 34px; border-radius: 8px; background:#e0f2fe; display:flex; align-items:center; justify-content:center; font-size:16px; }
        .doc-type { text-align: right; }
        .doc-type-label { font-weight: 700; font-size: 15px; }
        .doc-type-sub { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .4px; }
        .doc-divider { border-top: 2px solid #e5e7eb; margin: 12px 0 16px; }

        .meta-table, .info-table { width: 100%; border-collapse: collapse; }
        .meta-table td, .info-table td { padding: 4px 6px; font-size: 12px; vertical-align: top; }

        .section-block { margin-top: 14px; page-break-inside: avoid; }
        .section-block-title { font-weight: 700; font-size: 13px; letter-spacing: .3px; margin-bottom: 6px; text-transform: uppercase; }
        .work-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .work-table th, .work-table td { border: 1px solid #e5e7eb; padding: 8px; }
        .work-table th { background: #f3f4f6; text-align: left; }
        .work-table tbody tr { page-break-inside: avoid; }
        .notes-box { border: 1px dashed #cbd5e1; padding: 10px; font-size: 12px; color: #111827; min-height: 40px; }
        .signature-section { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 18px; }
        .sig-box { border: 1px solid #e5e7eb; padding: 10px; min-height: 110px; page-break-inside: avoid; }
        .sig-title { font-weight: 700; font-size: 12px; }
        .sig-role { font-size: 11px; color: #6b7280; margin-bottom: 12px; }
        .sig-line { border-bottom: 1px solid #cbd5e1; margin: 20px 0 6px; }
        .sig-name, .sig-date { font-size: 11px; color: #111827; }
        .doc-footer { margin-top: 12px; font-size: 11px; color: #6b7280; page-break-inside: avoid; }

        /* Print rules */
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .print-document { box-shadow: none; width: auto; padding: 0; margin: 0; }
            .work-table tbody tr { page-break-inside: avoid; }
            .signature-section, .section-block, .doc-footer { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="print-page">
    <!-- Print Button (hidden on print) -->
    <div class="no-print print-controls">
        <button onclick="window.print()" class="btn-print">
            <i>🖨️</i> Cetak SPK
        </button>
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
                    <p>Email: cs@acservismasjid.id | www.acservismasjid.id</p>
                </div>
            </div>
            <div class="doc-type">
                <div class="doc-type-label">SURAT PERINTAH KERJA</div>
                <div class="doc-type-sub">Work Order</div>
            </div>
        </div>

        <div class="doc-divider"></div>

        <!-- SPK Info -->
        <div class="spk-meta">
            <table class="meta-table">
                <tr>
                    <td width="30%"><strong>Nomor SPK</strong></td>
                    <td width="5%">:</td>
                    <td><strong>{{ $serviceOrder->order_number }}</strong></td>
                    <td width="30%"><strong>Tanggal SPK</strong></td>
                    <td width="5%">:</td>
                    <td>{{ $serviceOrder->created_at->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Tanggal Servis</strong></td>
                    <td>:</td>
                    <td>{{ $serviceOrder->service_date->format('d F Y') }}</td>
                    <td><strong>Status</strong></td>
                    <td>:</td>
                    <td><strong style="color: green">APPROVED</strong></td>
                </tr>
            </table>
        </div>

        <!-- Masjid Info -->
        <div class="section-block">
            <div class="section-block-title">INFORMASI LOKASI</div>
            <table class="info-table">
                <tr>
                    <td width="25%">ID Lokasi</td>
                    <td width="5%">:</td>
                    <td>{{ $serviceOrder->masjid->custom_id }}</td>
                    <td width="25%">Tipe</td>
                    <td width="5%">:</td>
                    <td>{{ ucfirst($serviceOrder->masjid->type) }}</td>
                </tr>
                <tr>
                    <td>Nama</td>
                    <td>:</td>
                    <td colspan="3">{{ $serviceOrder->masjid->name }}</td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>:</td>
                    <td colspan="3">{{ $serviceOrder->masjid->address }}</td>
                </tr>
                <tr>
                    <td>Ketua DKM</td>
                    <td>:</td>
                    <td>{{ $serviceOrder->masjid->dkm_name }}</td>
                    <td>Marbot</td>
                    <td>:</td>
                    <td>{{ $serviceOrder->masjid->marbot_name }}</td>
                </tr>
                <tr>
                    <td>Yang Ditemui</td>
                    <td>:</td>
                    <td>{{ ucfirst($serviceOrder->meeting_person) }}</td>
                    <td>Nomor HP</td>
                    <td>:</td>
                    <td>{{ $serviceOrder->phone }}</td>
                </tr>
            </table>
        </div>

        <!-- AC Details -->
        <div class="section-block">
            <div class="section-block-title">RINCIAN PEKERJAAN</div>
            <table class="work-table">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>PK</th>
                        <th>Merk AC</th>
                        <th width="15%">Jumlah Unit</th>
                        <th>Jenis Pekerjaan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($serviceOrder->serviceDetails as $i => $detail)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $detail->pk_type }}</td>
                        <td>{{ $detail->brand }}</td>
                        <td>{{ $detail->quantity }} unit</td>
                        <td>Servis, Cuci, Cek Freon</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total Unit</strong></td>
                        <td><strong>{{ $serviceOrder->serviceDetails->sum('quantity') }} unit</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Notes -->
        @if($serviceOrder->notes)
        <div class="section-block">
            <div class="section-block-title">INSTRUKSI KHUSUS</div>
            <div class="notes-box">{{ $serviceOrder->notes }}</div>
        </div>
        @endif

        <!-- Signatures -->
        <div class="signature-section">
            <div class="sig-box">
                <div class="sig-title">Dibuat oleh,</div>
                <div class="sig-role">Frontdesk</div>
                <div class="sig-line"></div>
                <div class="sig-name">(...........................)</div>
                <div class="sig-date">Tanggal: _______________</div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Disetujui oleh,</div>
                <div class="sig-role">Manager</div>
                <div class="sig-line"></div>
                <div class="sig-name">(...........................)</div>
                <div class="sig-date">Tanggal: _______________</div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Diterima oleh,</div>
                <div class="sig-role">Pihak Masjid</div>
                <div class="sig-line"></div>
                <div class="sig-name">(...........................)</div>
                <div class="sig-date">Tanggal: _______________</div>
            </div>
        </div>

        <div class="doc-footer">
            <p>Dokumen ini dicetak secara otomatis oleh Sistem Servis AC Masjid. Sah tanpa tanda tangan basah jika sudah diverifikasi secara digital.</p>
        </div>
    </div>
</body>
</html>
