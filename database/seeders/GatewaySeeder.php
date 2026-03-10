<?php

namespace Database\Seeders;

use App\Models\Gateway;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [
            ['name' => 'Gateway 1', 'driver' => 'gateway_1', 'priority' => 0],
            ['name' => 'Gateway 2', 'driver' => 'gateway_2', 'priority' => 1],
        ];

        foreach ($gateways as $gateway) {
            Gateway::firstOrCreate(
                ['driver' => $gateway['driver']],
                ['name' => $gateway['name'], 'priority' => $gateway['priority'], 'is_active' => true]
            );
        }
    }
}
