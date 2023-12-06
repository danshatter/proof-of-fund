<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the user role
        Role::create([
            'id' => Role::USER,
            'name' => 'user'
        ]);

        // Create the individual agent role
        Role::create([
            'id' => Role::INDIVIDUAL_AGENT,
            'name' => 'individual agent'
        ]);

        // Create the agency role
        Role::create([
            'id' => Role::AGENCY,
            'name' => 'agency'
        ]);

        // Create the administrator role
        Role::create([
            'id' => Role::ADMINISTRATOR,
            'name' => 'administrator'
        ]);
    }
}
