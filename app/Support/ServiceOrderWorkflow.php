<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\ServiceOrder;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\WorkflowStep;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ServiceOrderWorkflow
{
    /**
     * Canonical transition map. Controllers must call these named transitions
     * instead of updating ServiceOrder::status directly.
     */
    public const TRANSITIONS = [
        'approve_review' => ['pending_review', 'approved'],
        'create_spk_invoice' => ['approved', 'spk_invoice_created'],
        'approve_spk_invoice' => ['spk_invoice_created', 'spk_invoice_approved'],
        'assign_technician' => ['spk_invoice_approved', 'technician_assigned'],
        'start_work' => ['technician_assigned', 'in_progress'],
        'submit_report' => ['in_progress', 'waiting_review'],
        'route_fee_to_invoice_editing' => ['waiting_review', 'invoice_editing'],
        'submit_fee_review' => ['invoice_editing', 'fee_review'],
        'approve_fee_review' => ['fee_review', 'waiting_payment'],
        'reject_fee_review' => ['fee_review', 'invoice_editing'],
        'finalize_review' => ['waiting_review', 'waiting_payment'],
        'verify_payment' => ['waiting_payment', 'payment_verified'],
        'complete_after_payment' => ['payment_verified', 'completed'],
        'close_after_dual_confirmation' => ['completed', 'closed'],
    ];

    public function approveReview(ServiceOrder $order, ?string $notes = null): ServiceOrder
    {
        $this->requireStatus($order, 'pending_review', 'Order tidak dalam status menunggu persetujuan manager.');
        $this->requireManager();

        return DB::connection('ac_service')->transaction(function () use ($order, $notes) {
            $order->update(['status' => 'approved']);
            $this->log($order, 'approved', $notes ?: 'Order disetujui manager dan siap dibuatkan SPK & Invoice.');
            $this->broadcast($order, 'service_order.approved', ['status' => 'approved']);

            return $order->fresh();
        });
    }

    public function createSpkInvoice(ServiceOrder $order, ?string $notes = null): ServiceOrder
    {
        $this->requireFrontdesk();
        $this->requireStatus($order, 'approved', 'Order harus berstatus approved sebelum SPK & Invoice dibuat.');

        if ($order->invoice) {
            $this->fail('Invoice sudah dibuat.', 422);
        }

        $total = $order->serviceDetails()->get()->sum(fn ($detail) => ((float) $detail->quantity) * ((float) $detail->price_per_unit));

        return DB::connection('ac_service')->transaction(function () use ($order, $notes, $total) {
            $order->invoice()->create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'total_price' => $total,
            ]);
            $order->update(['status' => 'spk_invoice_created']);

            $this->log($order, 'spk_invoice_created', $notes ?: 'SPK & Invoice diterbitkan, menunggu persetujuan manager.');
            $this->broadcast($order, 'workflow.spk_invoice_created', ['status' => 'spk_invoice_created']);

            return $order->fresh('invoice');
        });
    }

    public function approveSpkInvoice(ServiceOrder $order, ?string $notes = null): ServiceOrder
    {
        $this->requireManager();
        $this->requireStatus($order, 'spk_invoice_created', 'Order harus dalam status SPK & Invoice dibuat.');

        if (! $order->invoice) {
            $this->fail('Invoice belum dibuat oleh frontdesk.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($order, $notes) {
            $order->update(['status' => 'spk_invoice_approved']);
            $this->log($order, 'spk_invoice_approved', $notes ?: 'SPK & Invoice disetujui manager.');
            $this->broadcast($order, 'workflow.spk_invoice_approved', ['status' => 'spk_invoice_approved']);

            return $order->fresh('invoice');
        });
    }

    public function rejectSpkInvoice(ServiceOrder $order, string $reason): ServiceOrder
    {
        $this->requireManager();
        $this->requireStatus($order, 'spk_invoice_created', 'Order harus dalam status SPK & Invoice dibuat.');

        return DB::connection('ac_service')->transaction(function () use ($order, $reason) {
            $order->invoice?->delete();
            $order->update(['status' => 'approved']);
            $this->log($order, 'approved', 'SPK & Invoice ditolak: '.$reason);
            $this->broadcast($order, 'workflow.spk_invoice_rejected', ['status' => 'approved']);

            return $order->fresh();
        });
    }

    public function assignTechnician(ServiceOrder $order, User $technician, ?string $notes = null): ServiceOrder
    {
        $this->requireManagerOrFrontdesk();

        if (! $technician->isTechnician()) {
            $this->fail('User yang dipilih bukan teknisi.', 422);
        }

        $existingAssignment = $order->technicianAssignment;
        $canInitialAssign = $order->status === 'spk_invoice_approved';
        $canReassign = $order->status === 'technician_assigned'
            && $existingAssignment
            && $existingAssignment->status === 'assigned'
            && is_null($existingAssignment->started_at)
            && is_null($existingAssignment->completed_at);

        if (! $canInitialAssign && ! $canReassign) {
            $this->fail('Order hanya bisa ditugaskan setelah SPK & Invoice disetujui.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($order, $technician, $notes) {
            $order->technicianAssignment()->delete();
            $order->technicianAssignment()->create([
                'technician_id' => $technician->id,
                'technician_name' => $technician->name,
                'assigned_by' => auth()->id(),
                'assigned_by_name' => auth()->user()->name,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            $order->update(['status' => 'technician_assigned']);
            $this->log($order, 'assigned', "Ditugaskan ke {$technician->name}".($notes ? ". {$notes}" : ''));
            $this->broadcast($order, 'workflow.assigned', [
                'status' => 'technician_assigned',
                'technician_id' => $technician->id,
            ]);

            return $order->fresh('technicianAssignment');
        });
    }

    public function startWork(ServiceOrder $order, ?string $notes = null): ServiceOrder
    {
        $assignment = $this->assignedTechnicianAssignment($order);
        $this->requireStatus($order, 'technician_assigned', 'Order harus berstatus teknisi ditugaskan sebelum pekerjaan dimulai.');

        return DB::connection('ac_service')->transaction(function () use ($order, $assignment, $notes) {
            $assignment->update([
                'status' => 'in_progress',
                'technician_notes' => $notes,
                'started_at' => $assignment->started_at ?: now(),
            ]);
            $order->update(['status' => 'in_progress']);

            $this->log($order, 'in_progress', $notes ?: 'Teknisi memulai pekerjaan.');
            $this->broadcast($order, 'workflow.progress_updated', [
                'status' => 'in_progress',
                'assignment_status' => 'in_progress',
            ]);

            return $order->fresh('technicianAssignment');
        });
    }

    public function submitTechnicianReport(ServiceOrder $order, array $payload): ServiceOrder
    {
        $assignment = $this->assignedTechnicianAssignment($order);
        $this->requireStatus($order, 'in_progress', 'Order belum dalam status service.');

        if (! $order->photoProofs()->where('technician_assignment_id', $assignment->id)->exists()) {
            $this->fail('Pekerjaan tidak bisa diselesaikan tanpa foto bukti.', 422);
        }

        $additionalFee = (float) ($payload['additional_fee'] ?? 0);
        $toolsMaterials = $payload['tools_materials'] ?? null;

        return DB::connection('ac_service')->transaction(function () use ($order, $assignment, $payload, $additionalFee, $toolsMaterials) {
            $assignment->update([
                'status' => 'done',
                'completion_notes' => $payload['notes'] ?? null,
                'technician_notes' => $payload['notes'] ?? null,
                'completed_at' => now(),
                'fee_reported' => $additionalFee > 0,
                'fee_amount' => $additionalFee > 0 ? $additionalFee : null,
                'fee_description' => $payload['fee_description'] ?? null,
                'fee_tools_materials' => $payload['fee_tools_materials'] ?? null,
                'fee_reported_at' => $additionalFee > 0 ? now() : null,
            ]);

            $order->update([
                'status' => 'waiting_review',
                'field_report_notes' => $payload['notes'] ?? null,
                'field_report_additional_fee' => $additionalFee,
                'field_report_tools_materials' => $toolsMaterials,
                'field_report_submitted_at' => now(),
                'manager_approved_additional_fee' => false,
                'additional_fee_approved_by' => null,
                'additional_fee_approved_at' => null,
            ]);

            $this->log(
                $order,
                'waiting_review',
                $additionalFee > 0
                    ? 'Teknisi menyelesaikan pekerjaan dan melaporkan biaya tambahan.'
                    : 'Teknisi menyelesaikan pekerjaan tanpa biaya tambahan.'
            );
            $this->broadcast($order, 'service_order.field_report_submitted', [
                'status' => 'waiting_review',
                'additional_fee' => $additionalFee,
            ]);

            return $order->fresh('technicianAssignment');
        });
    }

    public function finalizeReview(ServiceOrder $order): ServiceOrder
    {
        $this->requireManager();
        $this->requireStatus($order, 'waiting_review', 'Order harus menunggu review akhir sebelum masuk pembayaran.');

        if ($order->needsAdditionalFeeApproval()) {
            $this->fail('Biaya tambahan harus disetujui atau diedit sebelum finalisasi pembayaran.', 422);
        }

        $this->requireCompletedFieldWork($order);

        return $this->moveToWaitingPayment($order, 'Pekerjaan lapangan difinalisasi. Menunggu pembayaran.');
    }

    public function approveAdditionalFeeDirect(ServiceOrder $order): ServiceOrder
    {
        $this->requireManager();
        $this->requireStatus($order, 'waiting_review', 'Order harus dalam status menunggu review.');
        $this->requireCompletedFieldWork($order);

        if (! $order->needsAdditionalFeeApproval()) {
            $this->fail('Tidak ada biaya tambahan untuk disetujui.', 422);
        }

        if (! $order->invoice) {
            $this->fail('Invoice belum dibuat.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($order) {
            $this->markAdditionalFeeApproved($order);
            $invoice = $order->invoice;
            $invoice->update([
                'total_price' => ((float) $invoice->total_price) + ((float) $order->field_report_additional_fee),
                'payment_verified_at' => null,
                'payment_verified_by' => null,
                'payment_verified_by_name' => null,
                'payment_notes' => null,
                'payment_metadata' => null,
            ]);

            $order->update(['status' => 'waiting_payment']);
            $this->log($order, 'waiting_payment', 'Manager menyetujui biaya tambahan langsung. Menunggu pembayaran.');
            $this->broadcast($order, 'service_order.additional_fee_approved', ['status' => 'waiting_payment']);

            return $order->fresh('invoice');
        });
    }

    public function routeFeeToInvoiceEditing(ServiceOrder $order, ?string $notes = null): ServiceOrder
    {
        $this->requireManager();
        $this->requireStatus($order, 'waiting_review', 'Order harus menunggu review sebelum invoice diedit.');
        $this->requireCompletedFieldWork($order);

        if (! $order->needsAdditionalFeeApproval()) {
            $this->fail('Tidak ada biaya tambahan yang perlu diedit.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($order, $notes) {
            $order->update(['status' => 'invoice_editing']);
            $this->log($order, 'invoice_editing', $notes ?: 'Biaya tambahan diarahkan ke frontdesk untuk edit invoice.');
            $this->broadcast($order, 'workflow.invoice_editing', ['status' => 'invoice_editing']);

            return $order->fresh();
        });
    }

    public function submitEditedInvoiceForReview(ServiceOrder $order, ?string $notes = null): ServiceOrder
    {
        $this->requireFrontdesk();
        $this->requireStatus($order, 'invoice_editing', 'Invoice hanya bisa diajukan dari status invoice_editing.');

        return DB::connection('ac_service')->transaction(function () use ($order, $notes) {
            $order->update(['status' => 'fee_review']);
            $this->log($order, 'fee_review', $notes ?: 'Invoice hasil edit diajukan untuk review manager.');
            $this->broadcast($order, 'workflow.fee_review', ['status' => 'fee_review']);

            return $order->fresh('invoice');
        });
    }

    public function approveEditedInvoice(ServiceOrder $order, ?string $notes = null): ServiceOrder
    {
        $this->requireManager();
        $this->requireStatus($order, 'fee_review', 'Invoice edit harus menunggu review manager.');
        $this->requireCompletedFieldWork($order);

        return DB::connection('ac_service')->transaction(function () use ($order, $notes) {
            $this->markAdditionalFeeApproved($order);
            $order->update(['status' => 'waiting_payment']);
            $this->log($order, 'waiting_payment', $notes ?: 'Invoice edit disetujui manager. Menunggu pembayaran.');
            $this->broadcast($order, 'workflow.edited_invoice_approved', ['status' => 'waiting_payment']);

            return $order->fresh('invoice');
        });
    }

    public function rejectEditedInvoice(ServiceOrder $order, string $reason): ServiceOrder
    {
        $this->requireManager();
        $this->requireStatus($order, 'fee_review', 'Invoice edit harus menunggu review manager.');

        return DB::connection('ac_service')->transaction(function () use ($order, $reason) {
            $order->update(['status' => 'invoice_editing']);
            $this->log($order, 'invoice_editing', 'Invoice edit ditolak: '.$reason);
            $this->broadcast($order, 'workflow.edited_invoice_rejected', ['status' => 'invoice_editing']);

            return $order->fresh('invoice');
        });
    }

    public function verifyPayment(ServiceOrder $order, string $method, ?string $notes = null, ?array $metadata = null): ServiceOrder
    {
        $this->requireManager();
        $this->requireStatus($order, 'waiting_payment', 'Order tidak dalam status menunggu pembayaran.');

        if (! $order->invoice) {
            $this->fail('Invoice tidak ditemukan.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($order, $method, $notes, $metadata) {
            $order->invoice->update([
                'payment_method' => $method,
                'payment_verified_at' => now(),
                'payment_verified_by' => auth()->id(),
                'payment_verified_by_name' => auth()->user()->name,
                'payment_notes' => $notes,
                'payment_metadata' => $metadata,
            ]);
            $order->update(['status' => 'payment_verified']);
            $this->log($order, 'payment_verified', 'Pembayaran '.$method.' diverifikasi.');
            $this->broadcast($order, 'service_order.payment_verified', ['status' => 'payment_verified']);

            return $order->fresh('invoice');
        });
    }

    public function completeAfterPayment(ServiceOrder $order): ServiceOrder
    {
        $this->requireManager();
        $this->requireStatus($order, 'payment_verified', 'Order harus berstatus pembayaran terverifikasi.');

        if (! $order->invoice?->payment_verified_at) {
            $this->fail('Invoice belum memiliki waktu verifikasi pembayaran.', 422);
        }

        $assignment = $order->technicianAssignment;
        if (! $assignment || $assignment->status !== 'done') {
            $this->fail('Order belum memiliki pekerjaan teknisi yang selesai.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($order) {
            $order->update(['status' => 'completed']);
            $this->log($order, 'completed', 'Pembayaran diverifikasi dan order dinyatakan selesai.');
            $this->updateMasjidLastService($order);
            $this->broadcast($order, 'service_order.completed_after_payment', ['status' => 'completed']);

            return $order->fresh();
        });
    }

    public function confirmCompletion(ServiceOrder $order, string $role): ServiceOrder
    {
        $this->requireStatus($order, 'completed', 'Order belum selesai.');

        if (! in_array($role, ['frontdesk', 'manager'], true)) {
            $this->fail('Role tidak dapat mengonfirmasi order selesai.', 403);
        }

        return DB::connection('ac_service')->transaction(function () use ($order, $role) {
            if ($role === 'frontdesk') {
                if ($order->frontdesk_confirmed_complete) {
                    $this->fail('Frontdesk sudah mengonfirmasi order ini.', 422);
                }

                $order->update([
                    'frontdesk_confirmed_complete' => true,
                    'frontdesk_confirmed_by' => auth()->id(),
                    'frontdesk_confirmed_at' => now(),
                ]);
            }

            if ($role === 'manager') {
                if ($order->manager_confirmed_complete) {
                    $this->fail('Manager sudah mengonfirmasi order ini.', 422);
                }

                $order->update([
                    'manager_confirmed_complete' => true,
                    'manager_confirmed_by' => auth()->id(),
                    'manager_confirmed_at' => now(),
                ]);
            }

            $fresh = $order->fresh();
            if ($fresh->frontdesk_confirmed_complete && $fresh->manager_confirmed_complete) {
                $fresh->update([
                    'status' => 'closed',
                    'archived_at' => now(),
                ]);

                if (! $fresh->workflowSteps()->where('step', 'closed')->exists()) {
                    $this->log($fresh, 'closed', 'Frontdesk dan manager telah mengonfirmasi order selesai.');
                }
            }

            $this->broadcast($fresh, 'service_order.completion_confirmed', [
                'status' => $fresh->fresh()->status,
                'dual_confirmed' => $fresh->frontdesk_confirmed_complete && $fresh->manager_confirmed_complete,
            ]);

            return $fresh->fresh();
        });
    }

    public function archiveClosed(ServiceOrder $order, ?string $notes = null): ServiceOrder
    {
        $this->requireManager();

        if (! in_array($order->status, ['completed', 'closed'], true)) {
            $this->fail('Order harus completed atau closed sebelum diarsipkan.', 422);
        }

        if (
            $order->status === 'completed'
            && (! $order->frontdesk_confirmed_complete || ! $order->manager_confirmed_complete)
        ) {
            $this->fail('Order harus dikonfirmasi frontdesk dan manager sebelum ditutup.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($order, $notes) {
            $order->update([
                'status' => 'closed',
                'archived_at' => $order->archived_at ?: now(),
            ]);

            if (! $order->workflowSteps()->where('step', 'closed')->exists()) {
                $this->log($order, 'closed', $notes ?: 'Order ditutup dan diarsipkan.');
            }

            $this->broadcast($order, 'workflow.closed', ['status' => 'closed', 'closed' => true]);

            return $order->fresh();
        });
    }

    private function moveToWaitingPayment(ServiceOrder $order, string $notes): ServiceOrder
    {
        return DB::connection('ac_service')->transaction(function () use ($order, $notes) {
            $order->update(['status' => 'waiting_payment']);
            $this->log($order, 'waiting_payment', $notes);
            $this->broadcast($order, 'service_order.ready_for_payment', ['status' => 'waiting_payment']);

            return $order->fresh();
        });
    }

    private function markAdditionalFeeApproved(ServiceOrder $order): void
    {
        $order->update([
            'manager_approved_additional_fee' => true,
            'additional_fee_approved_by' => auth()->id(),
            'additional_fee_approved_at' => now(),
        ]);
    }

    private function requireCompletedFieldWork(ServiceOrder $order): void
    {
        $assignment = $order->technicianAssignment;

        if (! $assignment || $assignment->status !== 'done' || ! $assignment->completed_at) {
            $this->fail('Order belum memiliki pekerjaan teknisi yang selesai.', 422);
        }

        if (! $order->photoProofs()->exists()) {
            $this->fail('Pekerjaan tidak bisa difinalisasi karena tidak ada foto bukti.', 422);
        }
    }

    private function assignedTechnicianAssignment(ServiceOrder $order): TechnicianAssignment
    {
        $assignment = $order->technicianAssignment;

        if (! $assignment || (int) $assignment->technician_id !== (int) auth()->id()) {
            $this->fail('Anda tidak ditugaskan ke order ini.', 403);
        }

        return $assignment;
    }

    private function requireStatus(ServiceOrder $order, string $status, string $message): void
    {
        if ($order->status !== $status) {
            $this->fail($message, 422);
        }
    }

    private function requireFrontdesk(): void
    {
        $user = auth()->user();
        if (! $user || (! $user->isFrontdesk() && ! $user->isAdmin())) {
            $this->fail('Hanya frontdesk atau admin yang dapat melakukan aksi ini.', 403);
        }
    }

    private function requireManager(): void
    {
        $user = auth()->user();
        if (! $user || (! $user->isManager() && ! $user->isAdmin())) {
            $this->fail('Hanya manager atau admin yang dapat melakukan aksi ini.', 403);
        }
    }

    private function requireManagerOrFrontdesk(): void
    {
        $user = auth()->user();
        if (! $user || (! $user->isManager() && ! $user->isFrontdesk() && ! $user->isAdmin())) {
            $this->fail('Hanya frontdesk, manager, atau admin yang dapat melakukan aksi ini.', 403);
        }
    }

    private function updateMasjidLastService(ServiceOrder $order): void
    {
        $order->loadMissing('masjid.acUnits', 'serviceDetails');

        foreach ($order->serviceDetails as $detail) {
            $units = $order->masjid->acUnits
                ->where('pk_type', $detail->pk_type)
                ->where('brand', $detail->brand);

            foreach ($units as $unit) {
                $unit->update(['last_service_date' => $order->service_date]);
            }
        }
    }

    private function log(ServiceOrder $order, string $step, string $notes = ''): WorkflowStep
    {
        return WorkflowStep::create([
            'service_order_id' => $order->id,
            'step' => $step,
            'actor_id' => auth()->id(),
            'actor_name' => auth()->user()?->name ?? 'System',
            'actor_role' => auth()->user()?->role ?? 'system',
            'notes' => $notes,
        ]);
    }

    private function broadcast(ServiceOrder $order, string $type, array $payload): void
    {
        RealtimeSync::afterCommit($type, [
            'resource' => 'service_order',
            'resource_id' => $order->id,
            'masjid_id' => $order->masjid_id,
            'service_order_id' => $order->id,
            'payload' => $payload,
        ]);

        $this->flushMonitoringCaches();
    }

    private function flushMonitoringCaches(): void
    {
        Cache::forget('monitoring:status_counts');
        Cache::forget('monitoring:status_totals');
        Cache::forget('monitoring:status_totals:mm');
    }

    private function fail(string $message, int $status = 422): never
    {
        throw new HttpResponseException(ApiResponse::error($message, $status));
    }
}
