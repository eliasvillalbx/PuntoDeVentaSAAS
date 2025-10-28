<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Limpia caché de permisos/roles de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Asegura que los 4 roles existan (por si no corriste RolesSeeder antes)
        foreach ([
            'superadmin',
            'administrador_empresa',
            'gerente',
            'empleado',
        ] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Crea/actualiza el usuario superadmin
        $user = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'nombre'            => 'Admin',
                'apellido_paterno'  => 'Sistema',
                'apellido_materno'  => null,
                'telefono'          => null,
                'password'          => Hash::make('12345678'), // seguro y explícito
                'id_empresa'        => null,                   // o pon un ID si ya tienes empresa
            ]
        );

        $user->assignRole('superadmin');
    }
}
