<?php

namespace App\Http\Resources\Api\Concerns;

use App\Support\WorkflowStatus;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait FormatsApiValues
{
    protected function formatDateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function publicImageUrl(?string $path): ?string
    {
        $path = str_replace('\\', '/', trim((string) $path, '/'));

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, 'storage/')) {
            $path = substr($path, 8);
        } elseif (Str::startsWith($path, 'public/')) {
            $path = substr($path, 7);
        }

        return Storage::disk('public')->url($path);
    }

    protected function reportStatusForMobile(?string $status): string
    {
        return match ((string) $status) {
            '', WorkflowStatus::REPORT_SUBMITTED => 'pending',
            default => (string) $status,
        };
    }
}
