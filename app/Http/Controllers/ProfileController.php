<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        abort_unless($request->user() !== null, 403);
        $user = $request->user();

        $defaultAvatar = asset('img/profil.jpg');
        $profilePath = trim((string) ($user->profil ?? ''));
        if ($profilePath === '') {
            $profileAvatar = $defaultAvatar;
        } elseif (str_starts_with($profilePath, 'http://') || str_starts_with($profilePath, 'https://')) {
            $profileAvatar = $profilePath;
        } else {
            $normalized = str_replace('\\', '/', ltrim($profilePath, '/'));
            if (str_starts_with($normalized, 'storage/')) {
                $normalized = substr($normalized, 8);
            } elseif (str_starts_with($normalized, 'public/')) {
                $normalized = substr($normalized, 7);
            }

            [$folder, $subPath] = array_pad(explode('/', $normalized, 2), 2, '');
            $profileAvatar = in_array($folder, ['profil-admin', 'profil-user', 'barang-hilang', 'barang-temuan', 'verifikasi-klaim'], true) && $subPath !== ''
                ? (Storage::disk('public')->exists($normalized)
                    ? ((function () use ($normalized, $folder, $subPath) {
                        $absolutePath = Storage::disk('public')->path($normalized);
                        $mimeType = mime_content_type($absolutePath) ?: 'image/jpeg';
                        $binary = @file_get_contents($absolutePath);
                        if ($binary !== false) {
                            return 'data:' . $mimeType . ';base64,' . base64_encode($binary);
                        }

                        return route('media.image', ['folder' => $folder, 'path' => $subPath]);
                    })())
                    : $defaultAvatar)
                : (Storage::disk('public')->exists($normalized)
                    ? ((function () use ($normalized) {
                        $absolutePath = Storage::disk('public')->path($normalized);
                        $mimeType = mime_content_type($absolutePath) ?: 'image/jpeg';
                        $binary = @file_get_contents($absolutePath);
                        if ($binary !== false) {
                            return 'data:' . $mimeType . ';base64,' . base64_encode($binary);
                        }

                        return asset('storage/' . $normalized);
                    })())
                    : $defaultAvatar);
        }

        $verificationLabel = !is_null($user->email_verified_at) ? 'Terverifikasi' : 'Belum Verifikasi';
        $verificationClass = !is_null($user->email_verified_at) ? 'is-active' : 'is-pending';

        return view('user.pages.profile-edit', compact(
            'user',
            'profileAvatar',
            'verificationLabel',
            'verificationClass'
        ));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        abort_unless($request->user() !== null, 403);
        $user = $request->user();

        $validated = $request->validated();
        unset($validated['profil']);
        if (!Schema::hasColumn('users', 'nomor_telepon')) {
            unset($validated['nomor_telepon']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $photo = $request->file('profil');
        if ($photo) {
            $oldProfilePath = trim((string) ($user->profil ?? ''));
            if (
                $oldProfilePath !== ''
                && !str_starts_with($oldProfilePath, 'http://')
                && !str_starts_with($oldProfilePath, 'https://')
                && !str_starts_with($oldProfilePath, '/')
            ) {
                Storage::disk('public')->delete($oldProfilePath);
            }

            $user->profil = $photo->store('profil-user/' . now()->format('Y/m'), 'public');
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'Profil user berhasil diperbarui.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
