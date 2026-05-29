<?php

namespace App\Services\Google;

interface GoogleIdTokenVerifier
{
    /**
     * @return array<string, mixed>|null
     */
    public function verify(string $idToken): ?array;
}
