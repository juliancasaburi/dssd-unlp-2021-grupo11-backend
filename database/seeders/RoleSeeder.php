<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role1 = Role::create(['name' => 'admin']);
        $role2 = Role::create(['name' => 'apoderado']);
        $role3 = Role::create(['name' => 'empleado-mesa-de-entradas']);
        $role4 = Role::create(['name' => 'escribano-area-legales']);

        Permission::create(['name' => 'iniciarProceso'])->syncRoles([$role1,$role2]);
    }
}
