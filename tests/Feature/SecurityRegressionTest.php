<?php

namespace Tests\Feature;

use App\Models\GuestOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $frontdesk;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->frontdesk = User::factory()->create(['role' => 'frontdesk']);
        $this->manager = User::factory()->create(['role' => 'manager']);
    }

    /** @test */
    public function admin_only_routes_reject_non_admin_users(): void
    {
        $this->actingAs($this->frontdesk)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($this->frontdesk)
            ->get(route('admin.logs.index'))
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->get(route('admin.logs.index'))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_access_user_management_and_logs(): void
    {
        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.logs.index'))
            ->assertOk();
    }

    /** @test */
    public function guest_order_honeypot_is_ignored_and_rate_limited_by_ip(): void
    {
        $basePayload = [
            'reporter_name' => 'Ahmad',
            'masjid_name' => 'Masjid Al-Hikmah',
            'masjid_address' => 'Jl. Melati 12',
            'meeting_person' => 'dkm',
            'service_date' => now()->addDay()->toDateString(),
            'notes' => 'Perlu pengecekan unit indoor.',
            'details' => [
                [
                    'pk_type' => '1PK',
                    'brand' => 'Panasonic',
                    'quantity' => 1,
                ],
            ],
        ];

        $honeypotPayload = $basePayload + [
            'phone' => '081234567890',
            'website' => 'https://spam.example',
        ];

        $this->post(route('modules.ac-service.guest-order.store'), $honeypotPayload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, GuestOrder::count(), 'Honeypot submissions should not create records.');

        for ($i = 0; $i < 5; $i++) {
            $payload = $basePayload + [
                'phone' => '08123456789' . $i,
            ];

            $this->post(route('modules.ac-service.guest-order.store'), $payload)
                ->assertRedirect()
                ->assertSessionHas('success');
        }

        $this->post(route('modules.ac-service.guest-order.store'), $basePayload + [
            'phone' => '081234567899',
        ])->assertSessionHasErrors('phone');
    }
}
