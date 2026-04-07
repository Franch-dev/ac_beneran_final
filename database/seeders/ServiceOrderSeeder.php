<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Masjid;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
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
            $this->seedPendingOrder($masjids[0], $frontdesk);
            $this->seedApprovedOrder($masjids[1], $frontdesk, $manager, $technicians[0]);
            $this->seedWaitingInvoiceOrder($masjids[2], $frontdesk, $manager, $technicians[0]);
            $this->seedWaitingReviewOrder($masjids[3] ?? $masjids[2], $frontdesk, $manager, $admin, $technicians[1] ?? $technicians[0]);
            $this->seedCompletedOrder($masjids[4] ?? $masjids[3] ?? $masjids[2], $frontdesk, $manager, $admin, $technicians[0]);
        });
    }

    private function seedPendingOrder(Masjid $masjid, User $frontdesk): void
    {
        $order = $this->createOrder($masjid, 'pending', now()->addDays(2), 'Cuci unit aula utama');
        $this->createDetailsFromUnits($order, $masjid);
        $this->addStep($order, 'created', $frontdesk, 'Order dibuat oleh front desk');
    }

    private function seedApprovedOrder(Masjid $masjid, User $frontdesk, User $manager, User $technician): void
    {
        $order = $this->createOrder($masjid, 'approved', now()->addDay(), 'SPK terbit dan menunggu eksekusi teknisi');
        $this->createDetailsFromUnits($order, $masjid);
        $this->addStep($order, 'created', $frontdesk, 'Order dibuat');
        $this->addStep($order, 'approved', $manager, 'SPK diterbitkan');
        $this->assignTechnician($order, $manager, $technician, 'Teknisi dijadwalkan untuk kunjungan besok');
    }

    private function seedWaitingInvoiceOrder(Masjid $masjid, User $frontdesk, User $manager, User $technician): void
    {
        $order = $this->createOrder($masjid, 'waiting_invoice', now()->subDay(), 'Pekerjaan lapangan sudah selesai');
        $this->createDetailsFromUnits($order, $masjid);
        $this->addStep($order, 'created', $frontdesk, 'Order dibuat');
        $this->addStep($order, 'approved', $manager, 'SPK diterbitkan');
        $assignment = $this->assignTechnician($order, $manager, $technician, 'Tugas servis reguler');
        $assignment->update([
            'status' => 'done',
            'started_at' => now()->subDay()->setTime(9, 0),
            'completed_at' => now()->subDay()->setTime(13, 30),
            'technician_notes' => 'Pembersihan evaporator dan cek freon selesai',
        ]);
        $this->addStep($order, 'in_progress', $technician, 'Teknisi mulai bekerja');
        $this->addStep($order, 'completed', $technician, 'Pekerjaan selesai. Menunggu invoice');
    }

    private function seedWaitingReviewOrder(Masjid $masjid, User $frontdesk, User $manager, User $admin, User $technician): void
    {
        $order = $this->createOrder($masjid, 'waiting_review', now()->subDays(2), 'Invoice sudah dibuat dan menunggu review manager');
        $this->createDetailsFromUnits($order, $masjid);
        $this->addStep($order, 'created', $frontdesk, 'Order dibuat');
        $this->addStep($order, 'approved', $manager, 'SPK diterbitkan');
        $assignment = $this->assignTechnician($order, $manager, $technician, 'Prioritas tinggi untuk ruang utama');
        $assignment->update([
            'status' => 'done',
            'started_at' => now()->subDays(2)->setTime(8, 30),
            'completed_at' => now()->subDays(2)->setTime(12, 15),
            'technician_notes' => 'Ganti kapasitor dan pembersihan outdoor selesai',
        ]);
        $this->addStep($order, 'in_progress', $technician, 'Teknisi mulai bekerja');
        $this->addStep($order, 'completed', $technician, 'Pekerjaan selesai');
        $this->createInvoice($order);
        $this->addStep($order, 'invoice_generated', $admin, 'Invoice diterbitkan oleh admin');
    }

    private function seedCompletedOrder(Masjid $masjid, User $frontdesk, User $manager, User $admin, User $technician): void
    {
        $order = $this->createOrder($masjid, 'completed', now()->subDays(5), 'Order historis selesai penuh');
        $this->createDetailsFromUnits($order, $masjid);
        $this->addStep($order, 'created', $frontdesk, 'Order dibuat');
        $this->addStep($order, 'approved', $manager, 'SPK diterbitkan');
        $assignment = $this->assignTechnician($order, $manager, $technician, 'Perawatan berkala bulanan');
        $assignment->update([
            'status' => 'done',
            'started_at' => now()->subDays(5)->setTime(9, 0),
            'completed_at' => now()->subDays(5)->setTime(11, 45),
            'technician_notes' => 'Semua unit normal dan siap operasi',
        ]);
        $this->addStep($order, 'in_progress', $technician, 'Teknisi mulai bekerja');
        $this->addStep($order, 'completed', $technician, 'Pekerjaan selesai');
        $this->createInvoice($order);
        $this->addStep($order, 'invoice_generated', $admin, 'Invoice diterbitkan');
        $this->addStep($order, 'closed', $manager, 'Invoice disetujui manager');
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
        ]);
    }

    private function createInvoice(ServiceOrder $order): void
    {
        Invoice::create([
            'service_order_id' => $order->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'total_price' => $order->serviceDetails->sum(fn ($detail) => $detail->price_per_unit * $detail->quantity),
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
