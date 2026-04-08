<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, MustVerifyEmailTrait;

    protected $fillable = [
        'name', 'nama', 'username', 'email', 'password', 'profil',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getNamaAttribute($value): string
    {
        return (string) ($value ?? $this->attributes['name'] ?? '');
    }

    public function setNamaAttribute($value): void
    {
        $this->attributes['nama'] = $value;
        $this->attributes['name'] = $value;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
        if (array_key_exists('nama', $this->attributes)) {
            $this->attributes['nama'] = $value;
        }
    }

    public function laporanHilang()
    {
        return $this->hasMany(LaporanBarangHilang::class);
    }

    public function klaims()
    {
        return $this->hasMany(Klaim::class);
    }
}
