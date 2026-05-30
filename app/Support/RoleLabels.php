<?php

namespace App\Support;

class RoleLabels
{
    public static function manager(): string
    {
        return (string) config('roles.admin.display_name', 'Pengelola Barang');
    }

    public static function managerLower(): string
    {
        return (string) config('roles.admin.display_name_lower', 'pengelola barang');
    }

    public static function managerPlural(): string
    {
        return (string) config('roles.admin.display_name_plural', self::manager());
    }

    public static function managerPluralLower(): string
    {
        return (string) config('roles.admin.display_name_plural_lower', self::managerLower());
    }

    public static function managerSystemRole(): string
    {
        return (string) config('roles.admin.system_role', 'Pengelola Sistem');
    }
}
