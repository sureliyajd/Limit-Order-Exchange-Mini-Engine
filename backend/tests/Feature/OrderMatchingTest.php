<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderMatchingTest extends TestCase
{
    use RefreshDatabase;

    private const COMMISSION_RATE = '0.015';

    public function test_orders_match_when_price_and_amount_are_equal(): void
    {
        // Setup buyer with USD
        $buyer = User::factory()->create(['balance' => '10000.00000000']);

        // Setup seller with BTC
        $seller = User::factory()->create(['balance' => '0.00000000']);
        Asset::create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ]);

        // Seller places sell order first
        $this->actingAs($seller)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '1000',
            'amount' => '0.5',
        ]);

        // Buyer places matching buy order
        $response = $this->actingAs($buyer)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '1000',
            'amount' => '0.5',
        ]);

        $response->assertCreated();

        // Both orders should be FILLED
        $this->assertDatabaseHas('orders', [
            'user_id' => $seller->id,
            'status' => Order::STATUS_FILLED,
        ]);
        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'status' => Order::STATUS_FILLED,
        ]);

        // Trade should be created
        $this->assertDatabaseCount('trades', 1);

        // Verify balances
        $buyer->refresh();
        $seller->refresh();

        // USD volume = 1000 * 0.5 = 500
        // Commission = 500 * 0.015 = 7.5
        // Buyer pays: 500 (order) + 7.5 (commission) = 507.5, so balance = 10000 - 507.5 = 9492.5
        // Seller receives: 500
        $this->assertEquals('9492.50000000', $buyer->balance);
        $this->assertEquals('500.00000000', $seller->balance);

        // Verify assets
        $buyerAsset = Asset::where('user_id', $buyer->id)->where('symbol', 'BTC')->first();
        $sellerAsset = Asset::where('user_id', $seller->id)->where('symbol', 'BTC')->first();

        $this->assertEquals('0.50000000', $buyerAsset->amount);
        $this->assertEquals('0.50000000', $sellerAsset->amount);
        $this->assertEquals('0.00000000', $sellerAsset->locked_amount);
    }

    public function test_buy_order_matches_lower_priced_sell_order(): void
    {
        $buyer = User::factory()->create(['balance' => '10000.00000000']);
        $seller = User::factory()->create(['balance' => '0.00000000']);
        Asset::create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ]);

        // Seller places sell order at 900
        $this->actingAs($seller)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '900',
            'amount' => '0.5',
        ]);

        // Buyer places buy order at 1000 (willing to pay more)
        $this->actingAs($buyer)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '1000',
            'amount' => '0.5',
        ]);

        // Orders should match at seller's price (900)
        $trade = Trade::first();
        $this->assertNotNull($trade);
        $this->assertEquals('900.00000000', $trade->price);
    }

    public function test_no_match_when_amounts_differ(): void
    {
        $buyer = User::factory()->create(['balance' => '10000.00000000']);
        $seller = User::factory()->create(['balance' => '0.00000000']);
        Asset::create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ]);

        // Seller places sell order for 0.5 BTC
        $this->actingAs($seller)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '1000',
            'amount' => '0.5',
        ]);

        // Buyer places buy order for 0.3 BTC (different amount)
        $this->actingAs($buyer)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '1000',
            'amount' => '0.3',
        ]);

        // No trade should be created
        $this->assertDatabaseCount('trades', 0);

        // Both orders should remain OPEN
        $this->assertDatabaseHas('orders', [
            'user_id' => $seller->id,
            'status' => Order::STATUS_OPEN,
        ]);
        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'status' => Order::STATUS_OPEN,
        ]);
    }

    public function test_no_match_when_prices_do_not_cross(): void
    {
        $buyer = User::factory()->create(['balance' => '10000.00000000']);
        $seller = User::factory()->create(['balance' => '0.00000000']);
        Asset::create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ]);

        // Seller wants 1000
        $this->actingAs($seller)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '1000',
            'amount' => '0.5',
        ]);

        // Buyer only willing to pay 900
        $this->actingAs($buyer)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '900',
            'amount' => '0.5',
        ]);

        // No trade should be created
        $this->assertDatabaseCount('trades', 0);
    }

    public function test_user_cannot_match_own_orders(): void
    {
        $user = User::factory()->create(['balance' => '10000.00000000']);
        Asset::create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ]);

        // User places sell order
        $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '1000',
            'amount' => '0.5',
        ]);

        // Same user places matching buy order
        $this->actingAs($user)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '1000',
            'amount' => '0.5',
        ]);

        // No trade should be created (self-match prevention)
        $this->assertDatabaseCount('trades', 0);
    }

    public function test_commission_is_correctly_calculated(): void
    {
        $buyer = User::factory()->create(['balance' => '10000.00000000']);
        $seller = User::factory()->create(['balance' => '0.00000000']);
        Asset::create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => '1.00000000',
            'locked_amount' => '0.00000000',
        ]);

        $this->actingAs($seller)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => '2000',
            'amount' => '0.5',
        ]);

        $this->actingAs($buyer)->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '2000',
            'amount' => '0.5',
        ]);

        $trade = Trade::first();
        $this->assertNotNull($trade);

        // USD volume = 2000 * 0.5 = 1000
        // Commission = 1000 * 0.015 = 15
        $this->assertEquals('1000.00000000', $trade->usd_volume);
        $this->assertEquals('15.00000000', $trade->commission);
    }
}
