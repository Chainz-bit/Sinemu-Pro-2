<?php

namespace App\Services\Super\Profile;

use App\Http\Requests\Super\UpdateProfileRequest;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SuperProfileCommandService
{
    public function update(SuperAdmin $superAdmin, UpdateProfileRequest $request): void
    {
        $validated = $request->validated();

        unset($validated['current_password'], $validated['password_confirmation']);

        $photo = $request->file('profil');
        if ($photo) {
            $oldProfilePath = trim((string) ($superAdmin->profil ?? ''));
            if (
                $oldProfilePath !== ''
                && !str_starts_with($oldProfilePath, 'http://')
                && !str_starts_with($oldProfilePath, 'https://')
                && !str_starts_with($oldProfilePath, '/')
            ) {
                Storage::disk('public')->delete($oldProfilePath);
            }

            $validated['profil'] = $photo->store('profil-super/' . now()->format('Y/m'), 'public');
        } else {
            unset($validated['profil']);
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make((string) $validated['password']);
        } else {
            unset($validated['password']);
        }

        $superAdmin->forceFill($validated)->save();
    }
}
