<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = \App\Models\User::factory()->create(['id' => 1, 'email' => 'test-user@point-market.jp']);

        \App\Models\Item::factory(['seller_user_id' => $user->id])
            ->count(1000)
            ->create();
    }
}
