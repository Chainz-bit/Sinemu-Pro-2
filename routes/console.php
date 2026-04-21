<?php

use App\Models\SuperAdmin;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('super-admin:create
    {--name= : Nama super admin}
    {--email= : Email super admin}
    {--username= : Username super admin}
    {--password= : Password super admin}
    {--random-password : Generate password acak}
    {--force : Update akun jika email/username sudah ada}', function () {
    $name = trim((string) ($this->option('name') ?? ''));
    $email = trim(strtolower((string) ($this->option('email') ?? '')));
    $username = trim(strtolower((string) ($this->option('username') ?? '')));
    $passwordOption = (string) ($this->option('password') ?? '');
    $randomPassword = (bool) $this->option('random-password');
    $force = (bool) $this->option('force');

    if ($name === '') {
        $name = trim((string) $this->ask('Nama super admin'));
    }
    if ($email === '') {
        $email = trim(strtolower((string) $this->ask('Email super admin')));
    }
    if ($username === '') {
        $username = trim(strtolower((string) $this->ask('Username super admin')));
    }

    $passwordPlain = $passwordOption;
    if ($randomPassword) {
        $passwordPlain = Str::password(16, true, true, false, false);
    }
    if ($passwordPlain === '') {
        $passwordPlain = (string) $this->secret('Password super admin (minimal 8 karakter)');
    }

    $validator = Validator::make(
        [
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password' => $passwordPlain,
        ],
        [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]
    );

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }
        return self::FAILURE;
    }

    $existing = SuperAdmin::query()
        ->where('email', $email)
        ->orWhere('username', $username)
        ->first();

    if ($existing && !$force) {
        $this->error('Super admin dengan email/username tersebut sudah ada. Gunakan --force untuk update.');
        return self::FAILURE;
    }

    $payload = [
        'nama' => $name,
        'email' => $email,
        'username' => $username,
        'password' => Hash::make($passwordPlain),
    ];

    if ($existing) {
        $existing->forceFill($payload)->save();
        $superAdmin = $existing;
        $action = 'updated';
    } else {
        $superAdmin = SuperAdmin::query()->create($payload);
        $action = 'created';
    }

    $this->info('Super admin berhasil di-' . $action . '.');
    $this->line('ID       : ' . $superAdmin->id);
    $this->line('Nama     : ' . $superAdmin->nama);
    $this->line('Email    : ' . $superAdmin->email);
    $this->line('Username : ' . $superAdmin->username);

    if ($randomPassword) {
        $this->warn('Password acak: ' . $passwordPlain);
    }

    return self::SUCCESS;
})->purpose('Create or update super admin account securely (internal use only)');
