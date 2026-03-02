<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\ServiceDetail;
use App\Models\Masjid;
use App\Models\AcUnit;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ServiceOrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'masjid_id'            => 'required|exists:masjids,id',
            'meeting_person'       => 'required|in:dkm,marbot',
            'phone'                => 'required|string',
            'service_date'         => 'required|date|after_or_equal:today',
            'notes'                => 'nullable|string',
            'details'              => 'required|array|min:1',
            'details.*.pk_type'    => 'required|in:1PK,2PK,5PK',
            'details.*.brand'      => 'required|string',
            'details.*.quantity'   => 'required|integer|min:1',
        ]);

        $masjid = Masjid::with('acUnits')->findOrFail($request->masjid_id);

        // Cek apakah sudah ada order aktif (pending/approved) untuk masjid ini
        $orderLama = ServiceOrder::where('masjid_id', $request->masjid_id)
            ->whereIn('status', ['pending', 'approved'])
            ->latest()
            ->first();

        // Jika ada order lama dan user TIDAK memilih untuk replace, tolak
        if ($orderLama && !$request->boolean('force_replace')) {
            return response()->json([
                'success'       => false,
                'has_existing'  => true,
                'existing_order' => [
                    'id'           => $orderLama->id,
                    'order_number' => $orderLama->order_number,
                    'status'       => $orderLama->status,
                    'service_date' => $orderLama->service_date->format('d M Y'),
                ],
                'message' => "Masjid ini sudah memiliki service order aktif ({$orderLama->order_number}, status: {$orderLama->status}, tanggal: {$orderLama->service_date->format('d M Y')}). Apakah ingin mengganti order lama dengan yang baru?",
            ], 409);
        }

        // Jika force_replace = true, hapus order lama beserta detailnya
        if ($orderLama && $request->boolean('force_replace')) {
            $orderLama->serviceDetails()->delete();
            $orderLama->invoice()?->delete();
            $orderLama->delete();
        }

        // Validasi jumlah unit tidak melebihi yang tersedia
        foreach ($request->details as $detail) {
            $available = $masjid->acUnits
                ->where('pk_type', $detail['pk_type'])
                ->where('brand', $detail['brand'])
                ->sum('quantity');

            if ($detail['quantity'] > $available) {
                return response()->json([
                    'success' => false,
                    'message' => "Jumlah unit {$detail['pk_type']} {$detail['brand']} melebihi unit tersedia ({$available})",
                ], 422);
            }
        }

        $order = ServiceOrder::create([
            'masjid_id'      => $request->masjid_id,
            'order_number'   => ServiceOrder::generateOrderNumber(),
            'meeting_person' => $request->meeting_person,
            'phone'          => $request->phone,
            'service_date'   => $request->service_date,
            'notes'          => $request->notes,
            'status'         => 'pending',
        ]);

        foreach ($request->details as $detail) {
            $hargaServer = getHargaServis($masjid->type, $detail['pk_type']);
            $hargaKirim  = isset($detail['price_per_unit']) ? (int) $detail['price_per_unit'] : 0;
            $hargaFinal  = $hargaServer > 0 ? $hargaServer : $hargaKirim;

            ServiceDetail::create([
                'service_order_id' => $order->id,
                'pk_type'          => $detail['pk_type'],
                'brand'            => $detail['brand'],
                'quantity'         => $detail['quantity'],
                'price_per_unit'   => $hargaFinal,
            ]);
        }

        return response()->json(['success' => true, 'order' => $order]);
    }

    public function approve(ServiceOrder $serviceOrder)
    {
        if ($serviceOrder->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dalam status pending.',
            ], 422);
        }

        $serviceOrder->update(['status' => 'approved']);

        $total = $serviceOrder->serviceDetails->sum(fn($d) => $d->quantity * $d->price_per_unit);

        Invoice::create([
            'service_order_id' => $serviceOrder->id,
            'invoice_number'   => Invoice::generateInvoiceNumber(),
            'total_price'      => $total,
        ]);

        foreach ($serviceOrder->serviceDetails as $detail) {
            $units = $serviceOrder->masjid->acUnits
                ->where('pk_type', $detail->pk_type)
                ->where('brand', $detail->brand);
            foreach ($units as $unit) {
                $unit->update(['last_service_date' => $serviceOrder->service_date]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function cancelApprove(ServiceOrder $serviceOrder)
    {
        if ($serviceOrder->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dalam status approved.',
            ], 422);
        }

        $serviceOrder->update(['status' => 'pending']);
        $serviceOrder->invoice?->delete();

        return response()->json(['success' => true]);
    }

    public function destroy(ServiceOrder $serviceOrder)
    {
        $serviceOrder->serviceDetails()->delete();
        $serviceOrder->invoice?->delete();
        $serviceOrder->delete();
        return response()->json(['success' => true]);
    }

    public function history(Masjid $masjid)
    {
        $orders = $masjid->serviceOrders()
            ->with('serviceDetails')
            ->latest()
            ->get();
        return response()->json($orders);
    }

    public function cleanExpired()
    {
        $expired = ServiceOrder::where('status', 'pending')
            ->where('service_date', '<', now()->toDateString())
            ->get();

        foreach ($expired as $order) {
            $order->serviceDetails()->delete();
            $order->delete();
        }

        return response()->json(['cleaned' => $expired->count()]);
    }
}
