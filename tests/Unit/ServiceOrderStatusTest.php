<?php

namespace Tests\Unit;

use App\Models\ServiceOrder;
use PHPUnit\Framework\TestCase;

class ServiceOrderStatusTest extends TestCase
{
    public function test_active_statuses_cover_all_non_terminal_workflow_states(): void
    {
        $this->assertSame([
            'pending',
            'approved',
            'in_progress',
            'waiting_invoice',
            'waiting_review',
        ], ServiceOrder::activeStatuses());
    }

    public function test_is_active_uses_the_shared_status_definition(): void
    {
        $activeOrder = new ServiceOrder(['status' => 'waiting_invoice']);
        $completedOrder = new ServiceOrder(['status' => 'completed']);

        $this->assertTrue($activeOrder->isActive());
        $this->assertFalse($completedOrder->isActive());
    }

    public function test_status_label_maps_known_and_unknown_statuses(): void
    {
        $this->assertSame('SPK Issued', ServiceOrder::statusLabel('approved'));
        $this->assertSame('Waiting Review', ServiceOrder::statusLabel('waiting_review'));
        $this->assertSame('Custom Status', ServiceOrder::statusLabel('custom_status'));
    }
}
