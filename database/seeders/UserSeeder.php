<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminPassword = env('ADMIN_PASSWORD', 'password');
        $customerPassword = env('CUSTOMER_PASSWORD', 'password');

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make($adminPassword),
            ]
        );
        $admin->assignRole('admin');

        $customer = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Customer',
                'password' => Hash::make($customerPassword),
            ]
        );
        $customer->assignRole('customer');

        $customers = User::factory()->count(5)->create();
        $customers->each(fn (User $user) => $user->assignRole('customer'));
    }
}
