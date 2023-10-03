<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::factory()->create(['id' => 1]);

        \App\Models\Item::factory()
            ->count(10)
            ->for($user, 'seller_user_id')
            ->create();
    }
}
