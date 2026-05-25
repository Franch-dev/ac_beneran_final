<?php

namespace App\Http\Controllers;

use App\Models\GuestOrder;
use App\Models\Masjid;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\WorkflowStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class GuestOrderController extends Controller
{
    /**
     * Public: Show guest order form.
     */
    public function showForm()
    {
        return view('guest.order-form');
    }

    /**
     * Public endpoint: Submit guest order (no auth required).
     */
    public function store(Request $request)
    {
        // Rate limiting: 5 per minute per IP
        $key = 'guest-order:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'guest_phone' => "Terlalu banyak percobaan. Silakan tunggu {$seconds} detik.",
            ])->withInput();
        }

        RateLimiter::hit($key, 60);

        // Honeypot check (bot detection)
        if ($request->filled('website')) {
            // Silently reject - don't tell bot it was caught
            return back()->with('success', 'Permintaan service order Anda berhasil terkirim. Kami akan menindaklanjutinya segera.');
        }

        // Validation
        $validated = $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_phone' => 'required|string|max:20',
            'masjid_id' => 'nullable|exists:ac_service.masjids,id',
            'masjid_name' => ['required_without:masjid_id', 'nullable', 'string', 'max:255', 'regex:/^[\pL\pN\s.\-\'\/()]+$/u'],
            'address' => 'nullable|string|max:500',
            'ac_type' => 'required|in:1PK,2PK,5PK',
            'ac_amount' => 'required|integer|min:1|max:50',
            'problem_description' => 'required|string|max:1000',
            'additional_phone_description' => 'nullable|string|max:500',
        ]);

        // Phone validation: Indonesian format
        $phone = preg_replace('/[^0-9]/', '', $validated['guest_phone']);
        if (!preg_match('/^08[0-9]{8,12}$/', $phone)) {
            return back()->withErrors([
                'guest_phone' => 'Nomor telepon tidak valid. Gunakan format: 08xxxxxxxxxx',
            ])->withInput();
        }

        // Reject obviously fake numbers
        if (preg_match('/^0+$/', $phone) || strlen($phone) < 10) {
            return back()->withErrors([
                'guest_phone' => 'Nomor telepon tidak valid.',
            ])->withInput();
        }

        // IP spam check: block IPs with >10 rejected orders in 24 hours
        $rejectedCount = GuestOrder::where('ip_address', $request->ip())
            ->where('status', 'rejected')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($rejectedCount >= 10) {
            return back()->withErrors([
                'guest_phone' => 'Terlalu banyak percobaan dari alamat IP Anda. Silakan coba lagi nanti.',
            ])->withInput();
        }

        // Duplicate phone check: flag if same phone submitted >3 orders in 1 hour
        $recentCount = GuestOrder::where('guest_phone', $phone)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentCount >= 3) {
            return back()->withErrors([
                'guest_phone' => 'Nomor telepon ini telah mengirim terlalu banyak pesanan. Silakan tunggu 1 jam.',
            ])->withInput();
        }

        // Create guest order
        $guestOrder = GuestOrder::create([
            'guest_name' => $validated['guest_name'],
            'guest_phone' => $phone,
            'masjid_id' => $validated['masjid_id'] ?? null,
            'masjid_name' => $validated['masjid_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'ac_type' => $validated['ac_type'],
            'ac_amount' => $validated['ac_amount'],
            'problem_description' => $validated['problem_description'],
            'status' => 'pending_review',
            'additional_phone_description' => $validated['additional_phone_description'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        // Check duplicate: if masjid has active order, show queue message
        if ($guestOrder->masjid_id) {
            $hasActive = ServiceOrder::where('masjid_id', $guestOrder->masjid_id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->exists();

            if ($hasActive) {
                return back()->with('success', 'Permintaan service order Anda berhasil terkirim dan sedang dalam antrian. Kami akan menindaklanjutinya setelah order sebelumnya selesai.');
            }
        }

        return back()->with('success', 'Permintaan service order Anda berhasil terkirim. Kami akan menindaklanjutinya segera.');
    }

    /**
     * Frontdesk: List all pending guest orders.
     */
    public function index(Request $request)
    {
        $query = GuestOrder::with('masjid')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('guest_name', 'like', "%{$search}%")
                  ->orWhere('guest_phone', 'like', "%{$search}%")
                  ->orWhere('masjid_name', 'like', "%{$search}%");
            });
        }

        $guestOrders = $query->paginate(15);

        return view('frontdesk.guest-orders', compact('guestOrders'));
    }

    /**
     * Frontdesk: Show guest order detail with validation checklist + edit.
     */
    public function show(GuestOrder $order)
    {
        $order->load('masjid');

        // Auto cross-check phone against masjid database
        $phoneMatch = false;
        $masjidPhones = [];
        if ($order->masjid) {
            $masjidPhones = $order->masjid->phone_numbers ?? [];
            if (is_string($masjidPhones)) {
                $masjidPhones = json_decode($masjidPhones, true) ?: [];
            }
            $guestPhone = preg_replace('/[^0-9]/', '', $order->guest_phone);
            foreach ($masjidPhones as $mp) {
                $normalized = preg_replace('/[^0-9]/', '', $mp);
                if ($normalized === $guestPhone || substr($normalized, -8) === substr($guestPhone, -8)) {
                    $phoneMatch = true;
                    break;
                }
            }
        }

        // Cross-check address
        $addressMatch = false;
        if ($order->masjid && $order->address) {
            $addressMatch = stripos($order->masjid->address ?? '', $order->address) !== false
                         || stripos($order->address, $order->masjid->address ?? '') !== false;
        }

        return view('frontdesk.guest-order-detail', compact('order', 'phoneMatch', 'masjidPhones', 'addressMatch'));
    }

    /**
     * Frontdesk: Approve guest order with optional edits.
     */
    public function approve(GuestOrder $order, Request $request)
    {
        if ($order->status !== 'pending_review') {
            return back()->withErrors(['error' => 'Order tidak dalam status pending review.']);
        }

        $validated = $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_phone' => 'required|string|max:20',
            'meeting_person' => 'required|in:dkm,marbot',
            'phone' => 'required|string|max:20',
            'service_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        return DB::connection('ac_service')->transaction(function () use ($order, $validated) {
            $masjid = $order->masjid;

            // If no masjid linked, try to find by name or create
            if (!$masjid && $order->masjid_name) {
                $masjid = Masjid::where('name', $order->masjid_name)->first();
                if (!$masjid) {
                    $masjid = Masjid::create([
                        'custom_id' => Masjid::generateCustomId('masjid'),
                        'type' => 'masjid',
                        'name' => $order->masjid_name,
                        'address' => $order->address ?? '-',
                        'dkm_name' => $validated['meeting_person'] === 'dkm' ? $validated['guest_name'] : '-',
                        'marbot_name' => $validated['meeting_person'] === 'marbot' ? $validated['guest_name'] : '-',
                        'phone_numbers' => [$validated['phone']],
                        'setup_status' => 'pending_ac',
                    ]);
                }
                $order->update(['masjid_id' => $masjid->id]);
            }

            // Build notes with guest info
            $guestNotes = "Order dari guest: {$validated['guest_name']} ({$validated['guest_phone']})\n";
            $guestNotes .= "AC: {$order->ac_amount} unit {$order->ac_type}\n";
            $guestNotes .= "Masalah: {$order->problem_description}\n";
            if (!empty($validated['notes'])) {
                $guestNotes .= "Catatan: {$validated['notes']}";
            }

            // Create service order with approved status
            $serviceOrder = ServiceOrder::create([
                'masjid_id' => $masjid?->id,
                'order_number' => ServiceOrder::generateOrderNumber(),
                'meeting_person' => $validated['meeting_person'],
                'phone' => $validated['phone'],
                'service_date' => $validated['service_date'],
                'notes' => $guestNotes,
                'status' => 'pending_review',
            ]);

            // Create ServiceDetail from guest order AC data
            ServiceDetail::create([
                'service_order_id' => $serviceOrder->id,
                'pk_type' => $order->ac_type,
                'brand' => $order->brand ?? '-',
                'quantity' => $order->ac_amount,
                'price_per_unit' => 0, // will be set when SPK is created
            ]);

            // Log workflow steps
            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'guest_created',
                'actor_id' => null,
                'actor_name' => $order->guest_name,
                'actor_role' => 'guest',
                'notes' => "Order dari guest: {$order->guest_name} ({$order->guest_phone})",
            ]);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'frontdesk_created',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => "Order guest disetujui dan diverifikasi oleh frontdesk",
            ]);

            $order->update(['status' => 'approved']);

            return redirect()->route('frontdesk.guest-orders')
                ->with('success', "Order #{$serviceOrder->order_number} berhasil disetujui.");
        });
    }

    /**
     * Frontdesk: Reject guest order with reason.
     */
    public function reject(GuestOrder $order, Request $request)
    {
        if ($order->status !== 'pending_review') {
            return back()->withErrors(['error' => 'Order tidak dalam status pending review.']);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $order->reject($request->rejection_reason);

        return redirect()->route('frontdesk.guest-orders')
            ->with('success', 'Order berhasil ditolak.');
    }

    /**
     * Public API: Search masjids for autocomplete.
     */
    public function searchMasjids(Request $request): JsonResponse
    {
        $key = 'masjid-search:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json([
                'message' => 'Terlalu banyak pencarian. Silakan coba lagi nanti.',
            ], 429)->header('Retry-After', (string) RateLimiter::availableIn($key));
        }

        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        RateLimiter::hit($key, 60);

        $search = trim($validated['q']);

        $masjids = Masjid::where('name', 'like', "%{$search}%")
            ->orWhere('address', 'like', "%{$search}%")
            ->select('id', 'name', 'type', 'address')
            ->limit(10)
            ->get();

        return response()->json($masjids);
    }
}
