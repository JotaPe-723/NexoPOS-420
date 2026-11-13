<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'nexopos.create.expense-register' => [ 'Create Expenses', 'Allow the user to register expenses.' ],
            'nexopos.read.expense-register' => [ 'Read Expenses', 'Allow the user to view the expense register.' ],
            'nexopos.update.expense-register' => [ 'Update Expenses', 'Allow the user to edit expense records.' ],
            'nexopos.delete.expense-register' => [ 'Delete Expenses', 'Allow the user to delete expense records.' ],
        ];

        foreach ( $permissions as $namespace => [ $name, $description ] ) {
            $permission = Permission::firstOrNew( [ 'namespace' => $namespace ] );
            $permission->name = __( $name );
            $permission->description = __( $description );
            $permission->save();
        }

        $allPermissions = array_keys( $permissions );

        Role::whereIn( 'namespace', [ Role::ADMIN, Role::STOREADMIN ] )
            ->get()
            ->each( fn ( Role $role ) => $role->addPermissions( $allPermissions ) );

        Role::where( 'namespace', Role::STORECASHIER )
            ->get()
            ->each( fn ( Role $role ) => $role->addPermissions( [
                'nexopos.read.expense-register',
                'nexopos.create.expense-register',
            ] ) );
    }

    public function down(): void
    {
        $namespaces = [
            'nexopos.create.expense-register',
            'nexopos.read.expense-register',
            'nexopos.update.expense-register',
            'nexopos.delete.expense-register',
        ];

        $permissionIds = Permission::whereIn( 'namespace', $namespaces )->pluck( 'id' );

        \App\Models\RolePermission::whereIn( 'permission_id', $permissionIds )->delete();
        Permission::whereIn( 'namespace', $namespaces )->delete();
    }
};
