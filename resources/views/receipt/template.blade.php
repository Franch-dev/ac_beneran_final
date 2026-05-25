@extends('layouts.app')

@section('title', 'Tanda Terima - ' . $receipt->receipt_number)

@section('content')
<div class="page-container" style="max-width: 800px; margin: 0 auto; padding: 2rem 1rem;">
    <div class="mb-4 flex justify-between items-center no-print">
        <a href="{{ route('manager.receipts') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Kembali ke Daftar</a>
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-print"></i> Cetak
        </button>
    </div>

    <div id="receipt-print" style="background: white; padding: 40px; border: 1px solid #e5e7eb; border-radius: 8px;">
        <!-- Letterhead -->
        <div style="text-align: center; border-bottom: 3px double #333; padding-bottom: 20px; margin-bottom: 30px;">
            <h1 style="margin: 0; font-size: 28px; font-weight: bold; color: #1a1a1a;">AC BENERAN</h1>
            <p style="margin: 4px 0; font-size: 12px; color: #666;">Jl. Contoh No. 123, Kota Bandung, Jawa Barat 40123</p>
            <p style="margin: 4px 0; font-size: 12px; color: #666;">Telp: (022) 1234-5678 | WA: 0812-3456-7890</p>
            <p style="margin: 4px 0; font-size: 12px; color: #666;">Email: admin@acbeneran.com</p>
        </div>

        <!-- Title -->
        <h2 style="text-align: center; margin: 0 0 30px 0; font-size: 20px; font-weight: bold; letter-spacing: 2px; color: #333;">TANDA TERIMA PEMBAYARAN</h2>

        <!-- Receipt Info -->
        <table style="width: 100%; margin-bottom: 20px; font-size: 14px;">
            <tr>
                <td style="width: 50%; padding: 4px 0;">No. Tanda Terima</td>
                <td style="padding: 4px 0;">: <strong>{{ $receipt->receipt_number }}</strong></td>
            </tr>
            <tr>
                <td style="padding: 4px 0;">No. Order</td>
                <td style="padding: 4px 0;">: {{ $receipt->serviceOrder->order_number }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0;">No. Invoice</td>
                <td style="padding: 4px 0;">: {{ $receipt->invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0;">Tanggal Terima</td>
                <td style="padding: 4px 0;">: {{ \Carbon\Carbon::parse($receipt->payment_date)->translatedFormat('d F Y') }}</td>
            </tr>
        </table>

        <!-- Customer Info -->
        <div style="background: #f9fafb; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <table style="width: 100%; font-size: 14px;">
                <tr>
                    <td style="width: 35%; padding: 4px 0;">Nama Masjid/Musholla</td>
                    <td style="padding: 4px 0;">: <strong>{{ $receipt->serviceOrder->masjid->name }}</strong></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;">Alamat</td>
                    <td style="padding: 4px 0;">: {{ $receipt->serviceOrder->masjid->address ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;">Kontak</td>
                    <td style="padding: 4px 0;">: {{ $receipt->serviceOrder->phone ?? $receipt->serviceOrder->meeting_person }}</td>
                </tr>
            </table>
        </div>

        <!-- Service Details -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px;">
            <thead>
                <tr style="background: #f3f4f6;">
                    <th style="border: 1px solid #d1d5db; padding: 8px 12px; text-align: left;">No</th>
                    <th style="border: 1px solid #d1d5db; padding: 8px 12px; text-align: left;">Deskripsi</th>
                    <th style="border: 1px solid #d1d5db; padding: 8px 12px; text-align: center;">Qty</th>
                    <th style="border: 1px solid #d1d5db; padding: 8px 12px; text-align: right;">Harga</th>
                    <th style="border: 1px solid #d1d5db; padding: 8px 12px; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->serviceOrder->serviceDetails as $i => $detail)
                <tr>
                    <td style="border: 1px solid #d1d5db; padding: 8px 12px;">{{ $i + 1 }}</td>
                    <td style="border: 1px solid #d1d5db; padding: 8px 12px;">{{ $detail->description }}</td>
                    <td style="border: 1px solid #d1d5db; padding: 8px 12px; text-align: center;">{{ $detail->quantity }}</td>
                    <td style="border: 1px solid #d1d5db; padding: 8px 12px; text-align: right;">Rp {{ number_format($detail->price, 0, ',', '.') }}</td>
                    <td style="border: 1px solid #d1d5db; padding: 8px 12px; text-align: right;">Rp {{ number_format($detail->quantity * $detail->price, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Payment Summary -->
        <div style="text-align: right; margin-bottom: 30px;">
            <table style="display: inline-table; font-size: 14px;">
                <tr>
                    <td style="padding: 4px 12px; text-align: right;">Total Invoice</td>
                    <td style="padding: 4px 12px; text-align: right; font-weight: bold;">Rp {{ number_format($receipt->invoice->total_price, 0, ',', '.') }}</td>
                </tr>
                <tr style="border-top: 2px solid #333;">
                    <td style="padding: 8px 12px; text-align: right; font-weight: bold;">Jumlah Dibayar</td>
                    <td style="padding: 8px 12px; text-align: right; font-weight: bold; font-size: 16px; color: #16a34a;">Rp {{ number_format($receipt->payment_amount, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <!-- Payment Info -->
        <div style="background: #f0fdf4; padding: 15px; border-radius: 6px; border: 1px solid #bbf7d0; margin-bottom: 30px;">
            <table style="width: 100%; font-size: 14px;">
                <tr>
                    <td style="width: 35%; padding: 4px 0;">Metode Pembayaran</td>
                    <td style="padding: 4px 0;">: <strong>{{ $receipt->payment_method_label }}</strong></td>
                </tr>
                @if($receipt->transfer_bank)
                <tr>
                    <td style="padding: 4px 0;">Bank</td>
                    <td style="padding: 4px 0;">: {{ $receipt->transfer_bank }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;">No. Referensi</td>
                    <td style="padding: 4px 0;">: {{ $receipt->transfer_reference ?? '-' }}</td>
                </tr>
                @endif
                @if($receipt->qris_reference)
                <tr>
                    <td style="padding: 4px 0;">Ref. QRIS</td>
                    <td style="padding: 4px 0;">: {{ $receipt->qris_reference }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 4px 0;">Tanggal Pembayaran</td>
                    <td style="padding: 4px 0;">: {{ \Carbon\Carbon::parse($receipt->payment_date)->translatedFormat('d F Y') }}</td>
                </tr>
            </table>
        </div>

        <!-- Signature Section -->
        <table style="width: 100%; font-size: 14px; margin-top: 40px;">
            <tr>
                <td style="width: 50%; text-align: center; vertical-align: bottom;">
                    <p style="margin: 0 0 60px 0;">Yang Menerima,</p>
                    <div style="border-top: 1px solid #333; display: inline-block; width: 180px; padding-top: 4px;">
                        <p style="margin: 0; font-size: 12px;">( {{ $receipt->serviceOrder->meeting_person }} )</p>
                    </div>
                </td>
                <td style="width: 50%; text-align: center; vertical-align: bottom;">
                    <p style="margin: 0 0 60px 0;">Yang Menyerahkan,</p>
                    <div style="border-top: 1px solid #333; display: inline-block; width: 180px; padding-top: 4px;">
                        <p style="margin: 0; font-weight: bold;">{{ $receipt->printed_name }}</p>
                        <p style="margin: 0; font-size: 12px;">( {{ ucfirst($receipt->verified_by_name) }} )</p>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0; font-size: 12px; color: #666;">Terima kasih atas pembayaran Anda.</p>
            <p style="margin: 4px 0; font-size: 11px; color: #999;">Dokumen ini dicetak secara elektronik dan sah tanpa tanda tangan basah.</p>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #receipt-print, #receipt-print * { visibility: visible; }
        #receipt-print {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none !important;
            padding: 20mm !important;
        }
        .no-print { display: none !important; }
        @page { margin: 10mm; }
    }
</style>
@endsection
