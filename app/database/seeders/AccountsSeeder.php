<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('accounts')->insert([
            [
                'id' => 1,
                'balance' => 1000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'balance' => 500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'balance' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
