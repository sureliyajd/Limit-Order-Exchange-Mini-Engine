<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::create([
            'name' => 'Test User 1',
            'email' => 'user1@test.com',
            'password' => Hash::make('password'),
            'balance' => '100000.00000000',
        ]);

        Asset::create([
            'user_id' => $user1->id,
            'symbol' => 'BTC',
            'amount' => '10.00000000',
            'locked_amount' => '0.00000000',
        ]);

        Asset::create([
            'user_id' => $user1->id,
            'symbol' => 'ETH',
            'amount' => '100.00000000',
            'locked_amount' => '0.00000000',
        ]);

        $user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'user2@test.com',
            'password' => Hash::make('password'),
            'balance' => '100000.00000000',
        ]);

        Asset::create([
            'user_id' => $user2->id,
            'symbol' => 'BTC',
            'amount' => '10.00000000',
            'locked_amount' => '0.00000000',
        ]);

        Asset::create([
            'user_id' => $user2->id,
            'symbol' => 'ETH',
            'amount' => '100.00000000',
            'locked_amount' => '0.00000000',
        ]);
    }
}

