<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Booking
            'create-booking',
            'view-booking',
            'update-booking',
            'delete-booking',

            // Compound
            'create-compound',
            'view-compound',
            'update-compound',
            'delete-compound',

            // Property
            'create-property',
            'view-property',
            'update-property',
            'delete-property',

            // Property Category
            'create-property-category',
            'view-property-category',
            'update-property-category',
            'delete-property-category',

            // Tenant
            'create-tenant',
            'view-tenant',
            'update-tenant',
            'delete-tenant',

            // Agent
            'create-agent',
            'view-agent',
            'update-agent',
            'delete-agent',

            // Maintenance Request
            'create-maintenance-request',
            'view-maintenance-request',
            'update-maintenance-request',
            'delete-maintenance-request',

            // Renewal
            'create-renewal',
            'view-renewal',
            'update-renewal',
            'delete-renewal',

            // Payment
            'create-payment',
            'view-payment',
            'update-payment',
            'delete-payment',

            // Invoice
            'create-invoice',
            'view-invoice',
            'update-invoice',
            'delete-invoice',

            // Next of Kin
            'create-next-of-kin',
            'view-next-of-kin',
            'update-next-of-kin',
            'delete-next-of-kin',

            // Users
            'create-user',
            'view-user',
            'update-user',
            'delete-user',

            // Reports
            'view-report',
            'export-report',

            // Dashboard
            'view-dashboard',

            // Settings
            'manage-settings',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [

            'admin' => Permission::pluck('name')->toArray(),

            'landlord' => [
                'view-dashboard',

                'create-compound',
                'view-compound',
                'update-compound',
                'delete-compound',

                'create-property',
                'view-property',
                'update-property',
                'delete-property',

                'create-property-category',
                'view-property-category',

                'create-booking',
                'view-booking',
                'update-booking',
                'delete-booking',

                'create-maintenance-request',
                'view-maintenance-request',
                'update-maintenance-request',
                'delete-maintenance-request',

                'create-renewal',
                'view-renewal',
                'update-renewal',
                'delete-renewal',

                'create-payment',
                'view-payment',

                'create-invoice',
                'view-invoice',

                'create-tenant',
                'view-tenant',
                'update-tenant',

                'create-agent',
                'view-agent',
                'update-agent',

                'view-report',
            ],

            'tenant' => [
                'view-dashboard',

                'view-property',

                'create-booking',
                'view-booking',

                'create-maintenance-request',
                'view-maintenance-request',
                'update-maintenance-request',

                'view-renewal',

                'create-payment',
                'view-payment',

                'view-invoice',

                'view-next-of-kin',
                'update-next-of-kin',
            ],

            'agent' => [
                'view-dashboard',

                'view-property',

                'create-booking',
                'view-booking',
                'update-booking',

                'create-maintenance-request',
                'view-maintenance-request',
                'update-maintenance-request',

                'view-renewal',

                'view-payment',

                'view-invoice',

                'create-tenant',
                'view-tenant',
                'update-tenant',

                'view-report',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }

        // Clear cache again
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}