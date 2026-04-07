@extends('layouts.app')

@section('title', 'Technician SPK - AC Servis Masjid')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-file-signature"></i> SPK {{ $serviceOrder->order_number }}</h1>
            <p class="page-subtitle">{{ $serviceOrder->masjid->name }}</p>
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
</div>
@endsection
