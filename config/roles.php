<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Labels
    |--------------------------------------------------------------------------
    |
    | Nama teknis seperti guard, route, model, dan folder tetap memakai "admin".
    | Label di bawah dipakai untuk teks yang tampil ke pengguna.
    |
    */

    'admin' => [
        'technical_name' => 'admin',
        'guard' => 'admin',
        'middleware' => 'admin',
        'region_middleware' => 'admin.region.barang',
        'route_name_prefix' => 'admin',
        'url_prefix' => 'pengelola-barang',
        'legacy_url_prefix' => 'admin',
        'display_name' => 'Pengelola Barang',
        'display_name_lower' => 'pengelola barang',
        'display_name_plural' => 'Pengelola Barang',
        'display_name_plural_lower' => 'pengelola barang',
        'system_role' => 'Pengelola Sistem',
        'default_email' => 'pengelola@email.com',
    ],

    'super_admin' => [
        'technical_name' => 'super_admin',
        'display_name' => 'Super Admin',
        'display_name_lower' => 'super admin',
    ],
];
