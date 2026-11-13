<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    foreach ( [
        'create' => [ 'Create Expenses', 'Allow the user to register expenses.' ],
        'read' => [ 'Read Expenses', 'Allow the user to view the expense register.' ],
        'update' => [ 'Update Expenses', 'Allow the user to edit expense records.' ],
        'delete' => [ 'Delete Expenses', 'Allow the user to delete expense records.' ],
    ] as $action => [ $name, $description ] ) {
        $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.' . $action . '.expense-register' ] );
        $permission->name = __( $name );
        $permission->namespace = 'nexopos.' . $action . '.expense-register';
        $permission->description = __( $description );
        $permission->save();
    }
}
