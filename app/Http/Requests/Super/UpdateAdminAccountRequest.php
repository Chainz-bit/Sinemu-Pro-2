<?php

namespace App\Http\Requests\Super;

use App\Models\Admin;
use App\Support\IndramayuDistricts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UpdateAdminAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('super_admin') !== null;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        /** @var Admin|null $admin */
        $admin = $this->route('admin');
        $adminId = (int) ($admin?->id ?? 0);

        return [
            'nama' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('admins', 'username')->ignore($adminId)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($adminId)],
            'nomor_telepon' => ['required', 'string', 'regex:/^(08[0-9]{8,13}|\\+628[0-9]{8,13})$/'],
            'instansi' => ['required', 'string', 'max:255'],
            'kecamatan' => ['required', 'string', 'max:100', Rule::in(IndramayuDistricts::names())],
            'alamat_lengkap' => ['required', 'string', 'max:1200'],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'pickup_lat' => ['nullable', 'required_with:pickup_lng', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['nullable', 'required_with:pickup_lat', 'numeric', 'between:-180,180'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'status_verifikasi' => ['required', Rule::in(Admin::VERIFICATION_STATUSES)],
            'alasan_penolakan' => ['nullable', 'string', 'max:1000', 'required_if:status_verifikasi,rejected'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => $this->filled('nama') ? trim((string) $this->input('nama')) : null,
            'username' => $this->filled('username') ? trim((string) $this->input('username')) : null,
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
            'nomor_telepon' => $this->filled('nomor_telepon') ? trim((string) $this->input('nomor_telepon')) : null,
            'instansi' => $this->filled('instansi') ? trim((string) $this->input('instansi')) : null,
            'kecamatan' => $this->filled('kecamatan') ? trim((string) $this->input('kecamatan')) : null,
            'alamat_lengkap' => $this->filled('alamat_lengkap') ? trim((string) $this->input('alamat_lengkap')) : null,
            'pickup_address' => $this->filled('pickup_address') ? trim((string) $this->input('pickup_address')) : null,
            'pickup_lat' => $this->filled('pickup_lat') ? trim((string) $this->input('pickup_lat')) : null,
            'pickup_lng' => $this->filled('pickup_lng') ? trim((string) $this->input('pickup_lng')) : null,
            'password' => $this->filled('password') ? (string) $this->input('password') : null,
            'password_confirmation' => $this->filled('password_confirmation') ? (string) $this->input('password_confirmation') : null,
        ]);
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'nomor_telepon.regex' => 'Nomor telepon harus menggunakan format 08xxxxxxxxxx atau +628xxxxxxxxxx.',
            'kecamatan.in' => 'Kecamatan yang dipilih tidak valid.',
            'pickup_lat.required_with' => 'Latitude dan longitude harus diisi bersama.',
            'pickup_lng.required_with' => 'Latitude dan longitude harus diisi bersama.',
            'pickup_lat.between' => 'Latitude harus berada antara -90 sampai 90.',
            'pickup_lng.between' => 'Longitude harus berada antara -180 sampai 180.',
        ];
    }
}
