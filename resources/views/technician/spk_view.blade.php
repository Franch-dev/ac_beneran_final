@extends('layouts.app')

@section('title', 'Technician SPK & Invoice - AC Servis Masjid')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-file-signature"></i> SPK & Invoice {{ $serviceOrder->order_number }}</h1>
            <p class="page-subtitle">{{ $serviceOrder->masjid->name }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('spk.print', $serviceOrder) }}" target="_blank" class="btn btn-primary">
                <i class="fas fa-print"></i> Print SPK
            </a>
            @if($serviceOrder->invoice)
                <a href="{{ route('invoice.print', $serviceOrder) }}" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-file-invoice"></i> Print Invoice
                </a>
            @else
                <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed;" title="Invoice belum tersedia">
                    <i class="fas fa-file-invoice"></i> Invoice Belum Ada
                </span>
            @endif
        </div>
    </div>

    <div class="table-container">
        <table class="data-table">
            <tbody>
                <tr>
                    <th>Technician</th>
                    <td>{{ $assignment->technician_name }}</td>
                </tr>
                <tr>
                    <th>Service Date</th>
                    <td>{{ $serviceOrder->service_date->format('d M Y') }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ $assignment->status }}</td>
                </tr>
                <tr>
                    <th>Notes</th>
                    <td>{{ $serviceOrder->notes ?? '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="table-container" style="margin-top: 24px;">
        <h3 style="margin-bottom: 12px;"><i class="fas fa-list-alt"></i> Service Details</h3>
        @if($serviceOrder->serviceDetails->count() > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Service Type</th>
                        <th>Description</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($serviceOrder->serviceDetails as $index => $detail)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $detail->service_type }}</td>
                        <td>{{ $detail->description }}</td>
                        <td>Rp {{ number_format($detail->price, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: #666;">No service details available.</p>
        @endif
    </div>

    @if($serviceOrder->invoice)
    <div class="table-container" style="margin-top: 24px;">
        <h3 style="margin-bottom: 12px;"><i class="fas fa-file-invoice"></i> Invoice Info</h3>
        <table class="data-table">
            <tbody>
                <tr>
                    <th>Invoice Number</th>
                    <td>{{ $serviceOrder->invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <th>Total Amount</th>
                    <td>Rp {{ number_format($serviceOrder->invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ $serviceOrder->invoice->status }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
