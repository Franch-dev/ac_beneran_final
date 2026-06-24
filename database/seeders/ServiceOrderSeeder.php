<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceEdit;
use App\Models\Masjid;
use App\Models\MasjidChangeRequest;
use App\Models\PhotoProof;
use App\Models\Receipt;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\GuestOrder;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServiceOrderSeeder extends Seeder
{
    public function run(): void
    {
        $masjids = Masjid::with('acUnits')->take(5)->get();

        if ($masjids->count() < 3) {
            return;
        }

        $frontdesk = User::where('role', 'frontdesk')->firstOrFail();
        $manager = User::where('role', 'manager')->firstOrFail();
        $admin = User::where('role', 'admin')->firstOrFail();
        $technicians = User::where('role', 'technician')->orderBy('id')->get();

        DB::connection('ac_service')->transaction(function () use ($masjids, $frontdesk, $manager, $admin, $technicians) {
            $pendingOrder = $this->seedPendingOrder($masjids[0], $frontdesk);
            $this->seedWaitingPaymentOrder($masjids[1], $frontdesk, $manager, $technicians[0]);
            $this->seedApprovedOrder($masjids[2], $frontdesk, $manager, $technicians[0]);
            $waitingReviewOrder = $this->seedWaitingReviewOrder($masjids[3] ?? $masjids[2], $frontdesk, $manager, $technicians[1] ?? $technicians[0]);
            $completedOrder = $this->seedCompletedOrder($masjids[4] ?? $masjids[3] ?? $masjids[2], $frontdesk, $manager, $admin, $technicians[0]);

            $this->seedAdditionalWorkflowArtifacts(
                $masjids,
                $frontdesk,
                $manager,
                $admin,
                $pendingOrder,
                $waitingReviewOrder,
                $completedOrder
            );
        });
    }

    private function seedPendingOrder(Masjid $masjid, User $frontdesk): ServiceOrder
    {
        $order = $this->createOrder($masjid, 'pending_review', now()->addDays(2), 'Cuci unit aula utama');
        $this->createDetailsFromUnits($order, $masjid);
        $this->addStep($order, 'frontdesk_created', $frontdesk, 'Order dibuat oleh front desk');

        return $order;
    }

    private function seedApprovedOrder(Masjid $masjid, User $frontdesk, User $manager, User $technician): void
    {
        $order = $this->createOrder($masjid, 'technician_assigned', now()->addDay(), 'SPK disetujui dan teknisi sudah ditugaskan');
        $this->createDetailsFromUnits($order, $masjid);

        // Timeline: created -> approved -> SPK/Invoice created -> SPK/Invoice approved -> assigned
        $this->addStep($order, 'frontdesk_created', $frontdesk, 'Order dibuat oleh front desk');
        $this->addStep($order, 'approved', $manager, 'Order disetujui manager');
        $this->createInvoice($order);
        $this->addStep($order, 'spk_invoice_created', $frontdesk, 'SPK & Invoice dibuat');
        $this->addStep($order, 'spk_invoice_approved', $manager, 'SPK & Invoice disetujui');

        $this->assignTechnician($order, $manager, $technician, 'Teknisi dijadwalkan untuk kunjungan besok');
    }

    private function seedWaitingPaymentOrder(Masjid $masjid, User $frontdesk, User $manager, User $technician): void
    {
        $order = $this->createOrder($masjid, 'waiting_payment', now()->subDay(), 'Pekerjaan sudah direview dan menunggu pembayaran');
        $this->createDetailsFromUnits($order, $masjid);

        // Timeline: created -> approved -> SPK/Invoice created -> SPK/Invoice approved -> assigned -> work done -> waiting payment
        $this->addStep($order, 'frontdesk_created', $frontdesk, 'Order dibuat oleh front desk');
        $this->addStep($order, 'approved', $manager, 'Order disetujui manager');
        $this->createInvoice($order);
        $this->addStep($order, 'spk_invoice_created', $frontdesk, 'SPK & Invoice dibuat');
        $this->addStep($order, 'spk_invoice_approved', $manager, 'SPK & Invoice disetujui');
        $this->assignTechnician($order, $manager, $technician, 'Teknisi menyelesaikan pekerjaan dan menunggu pembayaran');
        $this->markAssignmentDone(
            $order,
            'Pekerjaan selesai tanpa biaya tambahan.',
            now()->subDay()->setTime(9, 0),
            now()->subDay()->setTime(10, 45)
        );
        $this->addStep($order, 'in_progress', $technician, 'Teknisi mulai bekerja');
        $this->addStep($order, 'waiting_review', $technician, 'Teknisi menyelesaikan pekerjaan tanpa biaya tambahan');
        $this->addPhotoProof($order, $technician, 'Bukti pekerjaan selesai untuk akses pembayaran internal.');
        $this->addStep($order, 'waiting_payment', $manager, 'Menunggu pembayaran');
    }

    private function seedWaitingReviewOrder(Masjid $masjid, User $frontdesk, User $manager, User $technician): ServiceOrder
    {
        $order = $this->createOrder($masjid, 'waiting_review', now()->subDays(2), 'Teknisi melaporkan biaya tambahan, menunggu review manager');
        $this->createDetailsFromUnits($order, $masjid);

        // Timeline: created -> approved -> SPK/Invoice created -> SPK/Invoice approved -> assigned -> in_progress -> waiting_review
        $this->addStep($order, 'frontdesk_created', $frontdesk, 'Order dibuat oleh front desk');
        $this->addStep($order, 'approved', $manager, 'Order disetujui manager');

        $this->createInvoice($order);
        $this->addStep($order, 'spk_invoice_created', $frontdesk, 'SPK & Invoice dibuat');
        $this->addStep($order, 'spk_invoice_approved', $manager, 'SPK & Invoice disetujui');

        $this->assignTechnician($order, $manager, $technician, 'Prioritas tinggi untuk ruang utama');
        $this->markAssignmentDone(
            $order,
            'Ada kebutuhan penggantian komponen minor.',
            now()->subDays(2)->setTime(10, 0),
            now()->subDays(2)->setTime(12, 15)
        );
        $this->addStep($order, 'in_progress', $technician, 'Teknisi mulai bekerja');

        // Technician finishes work and reports additional fee
        $order->update([
            'field_report_notes' => 'Ada kebutuhan penggantian komponen minor.',
            'field_report_additional_fee' => 150000,
            'field_report_submitted_at' => now()->subDays(2)->setTime(12, 20),
        ]);

        $this->addStep($order, 'technician_reported', $technician, 'Laporan biaya tambahan disampaikan');
        $this->addStep($order, 'waiting_review', $technician, 'Menunggu review biaya tambahan manager');
        $this->addPhotoProof($order, $technician, 'Bukti pekerjaan untuk review biaya tambahan.');

        // Keep state as waiting_review (manager has not approved additional fee yet)
        return $order;
    }

    private function seedCompletedOrder(Masjid $masjid, User $frontdesk, User $manager, User $admin, User $technician): ServiceOrder
    {
        $order = $this->createOrder($masjid, 'completed', now()->subDays(5), 'Order historis selesai penuh');
        $this->createDetailsFromUnits($order, $masjid);
        $invoice = $this->createInvoice($order);
        $this->addStep($order, 'frontdesk_created', $frontdesk, 'Order dibuat');
        $this->addStep($order, 'approved', $manager, 'Order disetujui manager');
        $this->addStep($order, 'spk_invoice_created', $frontdesk, 'SPK & Invoice dibuat');
        $this->addStep($order, 'spk_invoice_approved', $manager, 'SPK disetujui');
        $assignment = $this->assignTechnician($order, $manager, $technician, 'Perawatan berkala bulanan');
        $assignment->update([
            'status' => 'done',
            'started_at' => now()->subDays(5)->setTime(9, 0),
            'completed_at' => now()->subDays(5)->setTime(11, 45),
            'technician_notes' => 'Semua unit normal dan siap operasi',
        ]);
        $order->update([
            'field_report_notes' => 'Semua unit normal dan siap operasi.',
            'field_report_additional_fee' => 0,
            'field_report_submitted_at' => now()->subDays(5)->setTime(11, 50),
        ]);
        $this->addStep($order, 'in_progress', $technician, 'Teknisi mulai bekerja');
        $this->addStep($order, 'waiting_review', $technician, 'Pekerjaan selesai dan menunggu finalisasi');
        $this->addPhotoProof($order, $technician, 'Bukti pekerjaan historis selesai.');
        $this->addStep($order, 'waiting_payment', $manager, 'Review selesai. Menunggu pembayaran');
        $this->markInvoicePaid($invoice, $admin, 'transfer', 'Pembayaran transfer historis diverifikasi.');
        $this->addStep($order, 'payment_verified', $admin, 'Pembayaran diverifikasi');
        $this->addStep($order, 'completed', $manager, 'Order ditutup setelah pembayaran');
        $order->update([
            'frontdesk_confirmed_complete' => true,
            'frontdesk_confirmed_by' => $frontdesk->id,
            'frontdesk_confirmed_at' => now()->subDays(5)->setTime(12, 0),
            'manager_confirmed_complete' => true,
            'manager_confirmed_by' => $manager->id,
            'manager_confirmed_at' => now()->subDays(5)->setTime(12, 5),
        ]);

        return $order;
    }

    private function createOrder(Masjid $masjid, string $status, Carbon $serviceDate, string $notes): ServiceOrder
    {
        return ServiceOrder::create([
            'masjid_id' => $masjid->id,
            'order_number' => ServiceOrder::generateOrderNumber(),
            'meeting_person' => 'dkm',
            'phone' => $masjid->phone_numbers[0] ?? '0800000000',
            'service_date' => $serviceDate->toDateString(),
            'notes' => $notes,
            'status' => $status,
        ]);
    }

    private function createDetailsFromUnits(ServiceOrder $order, Masjid $masjid): void
    {
        $units = $masjid->acUnits->take(2);

        foreach ($units as $unit) {
            ServiceDetail::create([
                'service_order_id' => $order->id,
                'pk_type' => $unit->pk_type,
                'brand' => $unit->brand,
                'quantity' => max(1, min($unit->quantity, 2)),
                'price_per_unit' => (float) getHargaServis($masjid->type, $unit->pk_type),
            ]);
        }
    }

    private function assignTechnician(ServiceOrder $order, User $manager, User $technician, string $notes): TechnicianAssignment
    {
        $this->addStep($order, 'assigned', $manager, "Ditugaskan ke {$technician->name}. {$notes}");

        return TechnicianAssignment::create([
            'service_order_id' => $order->id,
            'technician_id' => $technician->id,
            'technician_name' => $technician->name,
            'assigned_by' => $manager->id,
            'assigned_by_name' => $manager->name,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);
    }

    private function markAssignmentDone(ServiceOrder $order, string $notes, Carbon $startedAt, Carbon $completedAt): void
    {
        $order->technicianAssignment?->update([
            'status' => 'done',
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'technician_notes' => $notes,
            'completion_notes' => $notes,
        ]);
    }

    private function addPhotoProof(ServiceOrder $order, User $technician, string $description): void
    {
        PhotoProof::create([
            'service_order_id' => $order->id,
            'technician_assignment_id' => $order->technicianAssignment?->id,
            'file_path' => 'photo-proofs/demo-before-after.jpg',
            'file_name' => 'demo-before-after.jpg',
            'file_size' => 254312,
            'mime_type' => 'image/jpeg',
            'description' => $description,
            'taken_at' => now(),
            'created_by' => $technician->id,
        ]);
    }

    private function markInvoicePaid(Invoice $invoice, User $actor, string $method, string $notes): void
    {
        $invoice->update([
            'payment_method' => $method,
            'payment_verified_at' => now()->subDays(5)->setTime(12, 0),
            'payment_verified_by' => $actor->id,
            'payment_verified_by_name' => $actor->name,
            'payment_notes' => $notes,
            'payment_metadata' => [
                'seeded' => true,
                'reference' => 'TRX-AC-2026-0001',
            ],
        ]);
    }

    private function createInvoice(ServiceOrder $order): Invoice
    {
        $totalPrice = $order->serviceDetails->sum(
            fn ($detail) => $detail->price_per_unit * $detail->quantity
        );

        $invoice = Invoice::firstOrNew(['service_order_id' => $order->id]);

        if (! $invoice->exists) {
            $invoice->invoice_number = Invoice::generateInvoiceNumber();
        }

        $invoice->total_price = $totalPrice;
        $invoice->save();

        return $invoice;
    }

    private function seedAdditionalWorkflowArtifacts(
        Collection $masjids,
        User $frontdesk,
        User $manager,
        User $admin,
        ServiceOrder $pendingOrder,
        ServiceOrder $waitingReviewOrder,
        ServiceOrder $completedOrder
    ): void {
        $primaryMasjid = $masjids[0];
        $secondaryMasjid = $masjids[1] ?? $primaryMasjid;

        $pendingGuestOrder = GuestOrder::create([
            'guest_name' => 'Bapak Hendra',
            'guest_phone' => '081234567801',
            'masjid_id' => $primaryMasjid->id,
            'masjid_name' => $primaryMasjid->name,
            'address' => $primaryMasjid->address,
            'ac_type' => '2PK',
            'ac_amount' => 2,
            'brand' => 'Panasonic',
            'problem_description' => 'AC lobby tidak dingin dan keluar bunyi berisik.',
            'status' => 'pending_review',
            'additional_phone_description' => 'Hubungi juga nomor bendahara jika tidak terjangkau.',
            'ip_address' => '127.0.0.1',
        ]);

        GuestOrder::create([
            'guest_name' => 'Ibu Siti',
            'guest_phone' => '081234567802',
            'masjid_id' => $secondaryMasjid->id,
            'masjid_name' => $secondaryMasjid->name,
            'address' => $secondaryMasjid->address,
            'ac_type' => '1PK',
            'ac_amount' => 1,
            'brand' => 'Daikin',
            'problem_description' => 'AC ruang pengurus netes air.',
            'status' => 'approved',
            'additional_phone_description' => null,
            'ip_address' => '127.0.0.2',
        ]);

        GuestOrder::create([
            'guest_name' => 'Pak Rudi',
            'guest_phone' => '081234567803',
            'masjid_id' => null,
            'masjid_name' => 'Musholla Al-Ikhlas',
            'address' => 'Jl. Contoh No. 9',
            'ac_type' => '1PK',
            'ac_amount' => 1,
            'brand' => 'Sharp',
            'problem_description' => 'Butuh cuci AC sebelum acara akhir pekan.',
            'status' => 'rejected',
            'rejection_reason' => 'Data alamat belum valid untuk verifikasi.',
            'additional_phone_description' => null,
            'ip_address' => '127.0.0.3',
        ]);

        MasjidChangeRequest::create([
            'masjid_id' => $primaryMasjid->id,
            'guest_order_id' => $pendingGuestOrder->id,
            'field' => 'phone_numbers',
            'old_value' => json_encode($primaryMasjid->phone_numbers ?? [], JSON_UNESCAPED_UNICODE),
            'new_value' => json_encode(['081298765432', '081212345678'], JSON_UNESCAPED_UNICODE),
            'requested_by' => $frontdesk->id,
            'requested_by_name' => $frontdesk->name,
            'status' => 'pending',
        ]);

        $waitingReviewInvoice = $this->createInvoice($waitingReviewOrder);
        $completedInvoice = $this->createInvoice($completedOrder);

        InvoiceEdit::create([
            'invoice_id' => $waitingReviewInvoice->id,
            'service_order_id' => $waitingReviewOrder->id,
            'edited_by' => $admin->id,
            'edited_by_name' => $admin->name,
            'edited_by_role' => $admin->role,
            'edit_type' => 'update_price',
            'old_value' => ['field_report_additional_fee' => 0],
            'new_value' => ['field_report_additional_fee' => 150000],
            'notes' => 'Penyesuaian biaya tambahan dari laporan teknisi.',
            'created_at' => now()->subDays(2)->setTime(12, 30),
        ]);

        Receipt::create([
            'service_order_id' => $completedOrder->id,
            'invoice_id' => $completedInvoice->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'payment_method' => 'transfer',
            'payment_amount' => $completedInvoice->total_price,
            'payment_date' => now()->subDays(5)->toDateString(),
            'transfer_bank' => 'BCA',
            'transfer_reference' => 'TRX-AC-2026-0001',
            'qris_reference' => null,
            'verified_by' => $manager->id,
            'verified_by_name' => $manager->name,
            'digital_signature_path' => 'signatures/manager-default.png',
            'printed_name' => $manager->name,
            'notes' => 'Pembayaran diterima penuh via transfer.',
        ]);

        $pendingOrder->update([
            'notes' => $pendingOrder->notes . ' (demo: siap diproses frontdesk)',
        ]);
    }

    private function addStep(ServiceOrder $order, string $step, User $actor, string $notes): void
    {
        WorkflowStep::create([
            'service_order_id' => $order->id,
            'step' => $step,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_role' => $actor->role,
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
