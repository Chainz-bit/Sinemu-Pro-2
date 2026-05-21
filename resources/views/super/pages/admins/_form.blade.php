@php
    $isEdit = isset($admin);
    $selectedStatus = old('status_verifikasi', $admin->status_verifikasi ?? '');
@endphp

<div class="super-manager-form-sections">
    <section class="super-form-section">
        <header class="super-form-section-head">
            <h3>Informasi Pribadi</h3>
            <p>Data dasar yang digunakan untuk identitas akun pengelola.</p>
        </header>

        <div class="super-manager-form-grid">
            <div class="super-form-field">
                <label for="nama">Nama Lengkap Pengelola <span aria-hidden="true">*</span></label>
                <input id="nama" name="nama" type="text" value="{{ old('nama', $admin->nama ?? '') }}" placeholder="Masukkan nama lengkap pengelola" class="@error('nama') is-invalid @enderror" required>
                @error('nama')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>

            <div class="super-form-field">
                <label for="username">Username <span aria-hidden="true">*</span></label>
                <input id="username" name="username" type="text" value="{{ old('username', $admin->username ?? '') }}" placeholder="Masukkan username" class="@error('username') is-invalid @enderror" required autocomplete="username">
                @error('username')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>

            <div class="super-form-field">
                <label for="email">Email <span aria-hidden="true">*</span></label>
                <input id="email" name="email" type="email" value="{{ old('email', $admin->email ?? '') }}" placeholder="Masukkan alamat email" class="@error('email') is-invalid @enderror" required autocomplete="email">
                @error('email')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>

            <div class="super-form-field">
                <label for="nomor_telepon">Nomor HP <span aria-hidden="true">*</span></label>
                <input id="nomor_telepon" name="nomor_telepon" type="tel" value="{{ old('nomor_telepon', $admin->nomor_telepon ?? '') }}" required inputmode="tel" placeholder="Contoh: 081234567890" class="@error('nomor_telepon') is-invalid @enderror">
                <small class="super-form-help">Gunakan nomor aktif, contoh: 081234567890</small>
                @error('nomor_telepon')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>
        </div>
    </section>

    <section class="super-form-section">
        <header class="super-form-section-head">
            <h3>Informasi Instansi dan Wilayah</h3>
            <p>Tentukan instansi serta area tugas pengelola barang.</p>
        </header>

        <div class="super-manager-form-grid">
            <div class="super-form-field">
                <label for="instansi">Instansi <span aria-hidden="true">*</span></label>
                <input id="instansi" name="instansi" type="text" value="{{ old('instansi', $admin->instansi ?? '') }}" placeholder="Masukkan nama instansi" class="@error('instansi') is-invalid @enderror" required>
                @error('instansi')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>

            <div class="super-form-field">
                <label for="kecamatan">Kecamatan <span aria-hidden="true">*</span></label>
                <select id="kecamatan" name="kecamatan" class="@error('kecamatan') is-invalid @enderror" required data-custom-select>
                    <option value="">Pilih kecamatan</option>
                    @foreach($kecamatanOptions as $kecamatan)
                        <option value="{{ $kecamatan }}" @selected(old('kecamatan', $admin->kecamatan ?? '') === $kecamatan)>{{ $kecamatan }}</option>
                    @endforeach
                </select>
                @error('kecamatan')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>

            <div class="super-form-field super-form-field-full">
                <label for="alamat_lengkap">Alamat atau Wilayah Tugas <span aria-hidden="true">*</span></label>
                <textarea id="alamat_lengkap" name="alamat_lengkap" rows="4" placeholder="Masukkan alamat atau wilayah tugas pengelola" class="@error('alamat_lengkap') is-invalid @enderror" required>{{ old('alamat_lengkap', $admin->alamat_lengkap ?? '') }}</textarea>
                @error('alamat_lengkap')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>
        </div>
    </section>

    <section class="super-form-section">
        <header class="super-form-section-head">
            <h3>Lokasi Pengambilan Barang</h3>
            <p>Opsional. Isi koordinat agar rute ke lokasi pengambilan lebih akurat. Jika dikosongkan, sistem memakai titik default wilayah.</p>
            <p>Untuk titik yang akurat, buka Google Maps, klik kanan lokasi pengambilan, salin koordinat, lalu masukkan latitude dan longitude. <a href="https://www.google.com/maps" target="_blank" rel="noopener noreferrer">Buka Google Maps</a></p>
        </header>

        <div class="super-manager-form-grid">
            <div class="super-form-field super-form-field-full">
                <label for="pickup_address">Alamat Titik Pengambilan</label>
                <input id="pickup_address" name="pickup_address" type="text" value="{{ old('pickup_address', $admin->pickup_address ?? '') }}" placeholder="Contoh: Kantor Kecamatan Lohbener, dekat ruang pelayanan" class="@error('pickup_address') is-invalid @enderror">
                @error('pickup_address')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>

            <div class="super-form-field">
                <label for="pickup_lat">Latitude</label>
                <input id="pickup_lat" name="pickup_lat" type="number" step="any" value="{{ old('pickup_lat', $admin->pickup_lat ?? '') }}" placeholder="-6.326400" class="@error('pickup_lat') is-invalid @enderror">
                @error('pickup_lat')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>

            <div class="super-form-field">
                <label for="pickup_lng">Longitude</label>
                <input id="pickup_lng" name="pickup_lng" type="number" step="any" value="{{ old('pickup_lng', $admin->pickup_lng ?? '') }}" placeholder="108.322700" class="@error('pickup_lng') is-invalid @enderror">
                @error('pickup_lng')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>

            <div class="super-form-field super-form-field-full">
                <div
                    class="pickup-location-picker"
                    data-pickup-location-picker
                    data-regions='@json($pickupRegionOptions ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
                    data-default-lat="-6.3264"
                    data-default-lng="108.3227"
                >
                    <div class="pickup-location-picker__map" data-pickup-map aria-label="Peta titik pengambilan barang"></div>
                    <div class="pickup-location-picker__toolbar">
                        <button type="button" class="pickup-location-picker__button" data-pickup-use-current-location>
                            Gunakan Lokasi Saya
                        </button>
                        <small class="pickup-location-picker__message super-form-help" data-pickup-location-message aria-live="polite"></small>
                    </div>
                    <small class="super-form-help">Klik peta untuk menentukan titik pengambilan.</small>
                </div>
            </div>
        </div>
    </section>

    <section class="super-form-section">
        <header class="super-form-section-head">
            <h3>Keamanan Akun</h3>
            <p>Gunakan password yang aman untuk melindungi akses akun.</p>
        </header>

        <div class="super-manager-form-grid">
            <div class="super-form-field">
                <label for="password">Password {{ $isEdit ? 'Baru' : '' }} @unless($isEdit)<span aria-hidden="true">*</span>@endunless</label>
                <div class="super-password-field">
                    <input id="password" name="password" type="password" placeholder="Masukkan password" class="@error('password') is-invalid @enderror" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
                    <button type="button" class="super-password-toggle" data-password-toggle="password" aria-label="Tampilkan password" aria-pressed="false">
                        <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                    </button>
                </div>
                <small class="super-form-help">{{ $isEdit ? 'Kosongkan jika tidak ingin mengganti password.' : 'Minimal 8 karakter. Gunakan kombinasi huruf dan angka jika memungkinkan.' }}</small>
                @error('password')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>

            <div class="super-form-field">
                <label for="password_confirmation">Konfirmasi Password @unless($isEdit)<span aria-hidden="true">*</span>@endunless</label>
                <div class="super-password-field">
                    <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi password" class="@error('password_confirmation') is-invalid @enderror" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
                    <button type="button" class="super-password-toggle" data-password-toggle="password_confirmation" aria-label="Tampilkan konfirmasi password" aria-pressed="false">
                        <iconify-icon icon="mdi:eye-outline"></iconify-icon>
                    </button>
                </div>
                @error('password_confirmation')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>
        </div>
    </section>

    <section class="super-form-section">
        <header class="super-form-section-head">
            <h3>Status Akun</h3>
            <p>Tentukan status awal akun setelah dibuat.</p>
        </header>

        <div class="super-manager-form-grid">
            <div class="super-form-field">
                <label for="status_verifikasi">Status Akun <span aria-hidden="true">*</span></label>
                <select id="status_verifikasi" name="status_verifikasi" class="@error('status_verifikasi') is-invalid @enderror" required data-custom-select>
                    <option value="" disabled @selected($selectedStatus === '')>Pilih status akun</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <small class="super-form-help">Status menentukan apakah akun langsung aktif atau perlu diverifikasi.</small>
                @error('status_verifikasi')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>

            <div class="super-form-field">
                <label for="alasan_penolakan">Alasan Penolakan/Revisi</label>
                <textarea id="alasan_penolakan" name="alasan_penolakan" rows="3" placeholder="Isi jika status Ditolak/Revisi" class="@error('alasan_penolakan') is-invalid @enderror">{{ old('alasan_penolakan', $admin->alasan_penolakan ?? '') }}</textarea>
                @error('alasan_penolakan')<small class="super-form-error">{{ $message }}</small>@enderror
            </div>
        </div>
    </section>
</div>
