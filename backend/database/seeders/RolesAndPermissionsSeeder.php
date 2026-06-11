<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permisos = [
            'ver fincas',
            'crear fincas',
            'editar fincas',
            'eliminar fincas',
            'ver animales',
            'crear animales',
            'editar animales',
            'eliminar animales',
            'registrar pesaje',
            'generar reportes',
            // Permisos del módulo veterinario
            'ver historial veterinario',
            'registrar atencion veterinaria',
            'generar reporte veterinario',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Crear roles y asignar permisos
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $ganaderoRole = Role::firstOrCreate(['name' => 'ganadero']);
        $ganaderoRole->givePermissionTo([
            'ver fincas',
            'crear fincas',
            'editar fincas',
            'eliminar fincas',
            'ver animales',
            'crear animales',
            'editar animales',
            'registrar pesaje',
            'generar reportes',
            'ver historial veterinario',
            'generar reporte veterinario',
        ]);

        $trabajadorRole = Role::firstOrCreate(['name' => 'trabajador']);
        $trabajadorRole->givePermissionTo([
            'ver fincas',
            'ver animales',
            'registrar pesaje',
        ]);

        // Rol veterinario (usuario invitado con acceso médico)
        $veterinarioRole = Role::firstOrCreate(['name' => 'veterinario']);
        $veterinarioRole->givePermissionTo([
            'ver fincas',
            'ver animales',
            'ver historial veterinario',
            'registrar atencion veterinaria',
            'generar reporte veterinario',
        ]);

        // Rol invitado general
        Role::firstOrCreate(['name' => 'invitado']);

        // Crear usuario administrador por defecto
        $admin = User::firstOrCreate([
            'email' => 'admin@bovweight.com'
        ], [
            'name' => 'Administrador BovWeight',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole($adminRole);

        // Crear usuario ganadero de prueba
        $ganadero = User::firstOrCreate([
            'email' => 'ganadero@prueba.com'
        ], [
            'name' => 'Ganadero de Prueba',
            'password' => bcrypt('password123'),
        ]);
        $ganadero->assignRole($ganaderoRole);
    }
}
