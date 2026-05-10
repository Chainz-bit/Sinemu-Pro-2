<?php

use App\Support\ManagerPortal;

if (! function_exists('manager_route')) {
    /**
     * Generate a URL for the Pengelola Barang portal without hardcoding
     * the current technical route prefix.
     *
     * @param  mixed  $parameters
     */
    function manager_route(string $name, $parameters = [], bool $absolute = true): string
    {
        return route(ManagerPortal::routeName($name), $parameters, $absolute);
    }
}
