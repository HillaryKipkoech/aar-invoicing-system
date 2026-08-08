<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesEmployee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => bcrypt('password')]
        );

        Customer::firstOrCreate(['customer_code' => 'CC00001'], [
            'customer_name' => 'Walk In Customer - HQ',
            'contact_person' => null,
            'currency' => 'KES',
            'kra_pin' => null,
        ]);

        Item::firstOrCreate(['item_no' => 'FG00011'], [
            'item_description' => 'Umi All Purpose Home Baking Flour 2Kg',
            'uom_code' => 'Bales',
            'unit_price' => 1850.000,
            'warehouse' => 'FG WHS',
            'qty_in_whse' => 648,
        ]);

        SalesEmployee::firstOrCreate(['name' => 'Farouk Abdulrehman Mohamed'], [
            'email' => 'farouk@example.com',
        ]);
    }
}
