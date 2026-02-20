<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK - {{ $serviceOrder->order_number }}</title>
    <link rel="stylesheet" href="{{ asset('css/print.css') }}">
    <style>
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body class="print-page">
    <!-- Print Button -->
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
