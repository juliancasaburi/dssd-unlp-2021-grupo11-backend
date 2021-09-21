<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name'      =>  'apoderado.test',
            'email'     =>  'apoderado.test@acme.com',
            'password'  =>  bcrypt('grupo11'),
            'email_verified_at' => Carbon::now()
        ])->assignRole('apoderado');

        User::create([
            'name'      =>  'grupo11.admin',
            'email'     =>  'grupo11.admin@acme.com',
            'password'  =>  bcrypt('grupo11'),
            'email_verified_at' => Carbon::now()
        ])->assignRole('admin');
    }
}
