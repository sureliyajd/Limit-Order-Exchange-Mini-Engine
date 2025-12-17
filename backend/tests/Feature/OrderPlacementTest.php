<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_place_buy_order_with_sufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '95000',
            'amount' => '0.1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.symbol', 'BTC')
            ->assertJsonPath('data.side', 'buy')
            ->assertJsonPath('data.status', Order::STATUS_OPEN);

        // Balance should be reduced by price * amount = 9500
        $user->refresh();
        $this->assertEquals('500.00000000', $user->balance);
    }

    public function test_buy_order_fails_with_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => '100.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '95000',
            'amount' => '0.1',
        ]);

        $response->assertStatus(400);
        
        // Balance should remain unchanged
        $user->refresh();
        $this->assertEquals('100.00000000', $user->balance);
    }

    public function test_user_can_place_sell_order_with_sufficient_asset(): void
    {
        $user = User::factory()->create(['balance' => '0.00000000']);
        $asset = Asset::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ]);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '95000',
            'amount' => '0.5',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.symbol', 'BTC')
            ->assertJsonPath('data.side', 'sell')
            ->assertJsonPath('data.status', Order::STATUS_OPEN);

        // Asset amount should decrease, locked should increase
        $asset->refresh();
        $this->assertEquals('0.50000000', $asset->amount);
        $this->assertEquals('0.50000000', $asset->locked_amount);
    }

    public function test_sell_order_fails_with_insufficient_asset(): void
    {
        $user = User::factory()->create(['balance' => '0.00000000']);
        $asset = Asset::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '0.01000000',
            'locked_amount' => '0.00000000',
        ]);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '95000',
            'amount' => '1.0',
        ]);

        $response->assertStatus(400);

        // Asset should remain unchanged
        $asset->refresh();
        $this->assertEquals('0.01000000', $asset->amount);
        $this->assertEquals('0.00000000', $asset->locked_amount);
    }

    public function test_order_placement_validates_symbol(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'INVALID',
            'side' => 'buy',
            'price' => '100',
            'amount' => '1',
        ]);

        $response->assertStatus(400);
    }

    public function test_order_placement_validates_price_is_positive(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);

        $response = $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '0',
            'amount' => '1',
        ]);

        $response->assertStatus(400);
    }
}
