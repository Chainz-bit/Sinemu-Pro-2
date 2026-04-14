@extends('admin.layouts.app')

@php
    $pageTitle = 'Pengaturan Sistem - Admin - SiNemu';
    $activeMenu = 'settings';
    $hideSidebar = true;
    $hideSearch = true;
    $topbarBackUrl = route('admin.dashboard');
    $topbarBackLabel = 'Kembali ke Dashboard';
    $searchPlaceholder = 'Cari laporan, barang, atau pengguna...';
@endphp

@section('page-content')
    <section class="settings-page">
        @if(session('status'))
            <div class="feedback-alert feedback-alert-toast feedback-alert-popup success" data-autoclose="3200" style="--autoclose-ms: 3200ms;" role="status" aria-live="polite">
                <span class="feedback-alert-icon" aria-hidden="true"><iconify-icon icon="mdi:check-circle"></iconify-icon></span>
                <div class="feedback-alert-body">
                    <strong>Berhasil</strong>
                    <span>{{ session('status') }}</span>
                </div>
                <button type="button" class="feedback-alert-close" data-alert-close aria-label="Tutup notifikasi">
                    <iconify-icon icon="mdi:close"></iconify-icon>
                </button>
                <span class="feedback-alert-progress" aria-hidden="true"></span>
            </div>
        @endif

        @if($errors->any())
            <div class="feedback-alert feedback-alert-toast feedback-alert-popup error" data-autoclose="3600" style="--autoclose-ms: 3600ms;" role="alert" aria-live="assertive">
                <span class="feedback-alert-icon" aria-hidden="true"><iconify-icon icon="mdi:alert-circle"></iconify-icon></span>
                <div class="feedback-alert-body">
                    <strong>Gagal</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
                <button type="button" class="feedback-alert-close" data-alert-close aria-label="Tutup notifikasi">
                    <iconify-icon icon="mdi:close"></iconify-icon>
                </button>
                <span class="feedback-alert-progress" aria-hidden="true"></span>
            </div>
        @endif

        <header class="settings-header">
            <h1>Pengaturan Sistem</h1>
            <p>Kelola konfigurasi aplikasi dan preferensi sistem Anda.</p>
        </header>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="settings-form">
            @csrf
            @method('PUT')

            <article class="settings-card">
                <header class="settings-card-head">
                    <h2>
                        <iconify-icon icon="mdi:office-building-outline"></iconify-icon>
                        Profil Instansi
                    </h2>
                </header>

                <div class="settings-grid">
                    <div class="settings-field">
                        <label for="kecamatan">Nama Kecamatan</label>
                        <input id="kecamatan" name="kecamatan" type="text" class="form-input" maxlength="100" required value="{{ old('kecamatan', $admin?->kecamatan) }}">
                        <small class="settings-field-help">Nama kecamatan yang akan ditampilkan di profil instansi.</small>
                    </div>

                    <div class="settings-field">
                        <label for="nama">Nama Camat</label>
                        <input id="nama" name="nama" type="text" class="form-input" maxlength="255" required value="{{ old('nama', $admin?->nama) }}">
                        <small class="settings-field-help">Nama penanggung jawab instansi pada halaman admin.</small>
                    </div>

                    <div class="settings-field settings-field-full">
                        <label for="email">Email Kontak</label>
                        <input id="email" name="email" type="email" class="form-input" maxlength="255" required value="{{ old('email', $admin?->email) }}">
                        <small class="settings-field-help">Email yang digunakan untuk komunikasi dan notifikasi sistem.</small>
                        <small class="settings-field-error" id="email_inline_error" role="status" aria-live="polite"></small>
                    </div>

                    <div class="settings-field settings-field-full">
                        <label for="alamat_lengkap">Alamat Instansi</label>
                        <textarea id="alamat_lengkap" name="alamat_lengkap" class="form-input settings-textarea" maxlength="1200" required>{{ old('alamat_lengkap', $admin?->alamat_lengkap) }}</textarea>
                        <small class="settings-field-help">Alamat lengkap kantor untuk memudahkan pengguna menemukan lokasi.</small>
                    </div>
                </div>
            </article>

            <article class="settings-card">
                <header class="settings-card-head">
                    <h2>
                        <iconify-icon icon="mdi:shield-check-outline"></iconify-icon>
                        Keamanan &amp; Akses
                    </h2>
                </header>

                <div class="settings-log-row">
                    <div class="settings-log-text">
                        <strong>Log Aktivitas</strong>
                        <p>Lihat riwayat perubahan yang dilakukan oleh pengguna.</p>
                    </div>
                    <a href="{{ route('admin.settings.logs') }}">Lihat Log</a>
                </div>
            </article>

            <div class="settings-actions">
                <span class="settings-dirty-indicator" id="settingsDirtyIndicator" aria-live="polite">Perubahan belum disimpan</span>
                <a href="{{ route('admin.dashboard') }}" class="settings-btn settings-btn-light">Batalkan Perubahan</a>
                <button type="submit" class="settings-btn settings-btn-primary" id="settingsSaveButton">Simpan Perubahan</button>
            </div>
        </form>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.settings-form');
            const saveButton = document.getElementById('settingsSaveButton');
            const dirtyIndicator = document.getElementById('settingsDirtyIndicator');
            const emailInput = document.getElementById('email');
            const emailInlineError = document.getElementById('email_inline_error');
            if (!form || !saveButton) return;

            const fields = Array.from(form.querySelectorAll('input, textarea, select'))
                .filter(function (element) {
                    return element.name && !element.disabled;
                });

            const initialValues = new Map();
            fields.forEach(function (field) {
                initialValues.set(field.name, field.value);
            });

            function hasChanges() {
                return fields.some(function (field) {
                    return field.value !== initialValues.get(field.name);
                });
            }

            function validateEmailInline() {
                if (!emailInput) return true;

                const value = emailInput.value.trim();
                const valid = value.length > 0 && emailInput.checkValidity();

                if (emailInlineError) {
                    emailInlineError.textContent = valid || value.length === 0
                        ? ''
                        : 'Format email tidak valid. Contoh: admin@sinemu.id';
                }

                emailInput.classList.toggle('is-invalid', !valid && value.length > 0);
                return valid;
            }

            function syncSaveState() {
                const changed = hasChanges();
                const emailValid = validateEmailInline();

                saveButton.disabled = !(changed && emailValid);
                if (dirtyIndicator) {
                    dirtyIndicator.classList.toggle('is-visible', changed);
                }
            }

            fields.forEach(function (field) {
                field.addEventListener('input', syncSaveState);
                field.addEventListener('change', syncSaveState);
            });

            form.addEventListener('submit', function (event) {
                if (!validateEmailInline()) {
                    event.preventDefault();
                    emailInput?.focus();
                }
            });

            syncSaveState();
        });
    </script>
@endsection
