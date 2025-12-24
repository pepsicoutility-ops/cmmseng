<?php

return [
    'chunk_size' => 500,
    'header_rows' => 1,
    'max_errors' => 50,
    'queue' => 'imports',
    'imports' => [
        'users' => [
            'columns' => [
                'A' => 'gpid',
                'B' => 'name',
                'D' => 'role',
                'E' => 'department',
            ],
            'departments' => [
                'utility',
                'mechanic',
                'electric',
            ],
            'roles' => [
                'super_admin',
                'manager',
                'asisten_manager',
                'technician',
                'tech_store',
                'operator',
            ],
            'department_required_roles' => [
                'asisten_manager',
                'technician',
            ],
            'role_aliases' => [
                'assistant_manager' => 'asisten_manager',
                'assisten_manager' => 'asisten_manager',
                'superadmin' => 'super_admin',
                'super_admin' => 'super_admin',
                'techstore' => 'tech_store',
                'tech_store' => 'tech_store',
            ],
            'department_role' => 'technician',
            'default_role' => 'operator',
            'default_password' => env('IMPORT_DEFAULT_PASSWORD', 'Cmms@2025'),
            'email_domain' => 'cmms.test',
        ],
    ],
];
