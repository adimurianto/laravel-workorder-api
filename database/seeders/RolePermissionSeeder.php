<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $managerRoleId = DB::table('roles')->insertGetId(['name' => 'Production Manager']);
        $operatorRoleId = DB::table('roles')->insertGetId(['name' => 'Operator']);

        $resources = ['work_orders'];
        $actions = ['create', 'read', 'update', 'delete']; 

        $permissionIds = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissionName = "$resource.$action";
                $permissionIds[$permissionName] = DB::table('permissions')->insertGetId(['name' => $permissionName]);
            }
        }

        DB::table('role_permissions')->insert([
            ['role_id' => $managerRoleId, 'permission_id' => $permissionIds['work_orders.create']],
            ['role_id' => $managerRoleId, 'permission_id' => $permissionIds['work_orders.read']],
            ['role_id' => $managerRoleId, 'permission_id' => $permissionIds['work_orders.update']],
            ['role_id' => $managerRoleId, 'permission_id' => $permissionIds['work_orders.delete']],
        ]);

        DB::table('role_permissions')->insert([
            ['role_id' => $operatorRoleId, 'permission_id' => $permissionIds['work_orders.read']],
            ['role_id' => $operatorRoleId, 'permission_id' => $permissionIds['work_orders.update']],
        ]);

        DB::table('users')->insert([
            ['name' => 'Adi Murianto', 'email' => 'pm@mail.com', 'password' => Hash::make('12345'), 'role_id' => $managerRoleId],
            ['name' => 'Abdul', 'email' => 'op1@mail.com', 'password' => Hash::make('12345'), 'role_id' => $operatorRoleId],
            ['name' => 'Ahmad', 'email' => 'op2@mail.com', 'password' => Hash::make('12345'), 'role_id' => $operatorRoleId],
        ]);
    }
}