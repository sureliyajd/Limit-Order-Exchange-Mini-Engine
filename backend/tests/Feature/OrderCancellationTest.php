<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_cancel_open_buy_order_and_get_refund(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        // Place a buy order
        $order = Order::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => Order::SIDE_BUY,
            'price' => '1000.00000000',
            'amount' => '1.00000000',
            'status' => Order::STATUS_OPEN,
        ]);

        // Simulate balance deduction (as would happen during order placement)
        $user->balance = '9000.00000000';
        $user->save();

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', Order::STATUS_CANCELLED);

        // Balance should be refunded
        $user->refresh();
        $this->assertEquals('10000.00000000', $user->balance);

        // Order status should be cancelled
        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);
    }

    public function test_user_can_cancel_open_sell_order_and_unlock_asset(): void
    {
        $user = User::factory()->create(['balance' => '0.00000000']);
        $asset = Asset::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '0.00000000',
            'locked_amount' => '1.00000000',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => Order::SIDE_SELL,
            'price' => '95000.00000000',
            'amount' => '1.00000000',
            'status' => Order::STATUS_OPEN,
        ]);

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', Order::STATUS_CANCELLED);

        // Asset should be unlocked
        $asset->refresh();
        $this->assertEquals('1.00000000', $asset->amount);
        $this->assertEquals('0.00000000', $asset->locked_amount);
    }

    public function test_cannot_cancel_already_filled_order(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $order = Order::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => Order::SIDE_BUY,
            'price' => '1000.00000000',
            'amount' => '1.00000000',
            'status' => Order::STATUS_FILLED,
        ]);

        $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(400);
    }

    public function test_cannot_cancel_another_users_order(): void
    {
        $user1 = User::factory()->create(['balance' => '10000.00000000']);
        $user2 = User::factory()->create(['balance' => '10000.00000000']);

        $order = Order::create([
            'user_id' => $user1->id,
            'symbol' => 'BTC',
            'side' => Order::SIDE_BUY,
            'price' => '1000.00000000',
            'amount' => '1.00000000',
            'status' => Order::STATUS_OPEN,
        ]);

        $response = $this->actingAs($user2)->postJson("/api/orders/{$order->id}/cancel");

        $response->assertStatus(400);
    }
}
