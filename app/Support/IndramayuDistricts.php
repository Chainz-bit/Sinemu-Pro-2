<?php

namespace App\Support;

final class IndramayuDistricts
{
    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return [
            'Anjatan',
            'Arahan',
            'Balongan',
            'Bangodua',
            'Bongas',
            'Cantigi',
            'Cikedung',
            'Gabuswetan',
            'Gantar',
            'Haurgeulis',
            'Indramayu',
            'Jatibarang',
            'Juntinyuat',
            'Kandanghaur',
            'Karangampel',
            'Kedokan Bunder',
            'Kertasemaya',
            'Krangkeng',
            'Kroya',
            'Lelea',
            'Lohbener',
            'Losarang',
            'Pasekan',
            'Patrol',
            'Sindang',
            'Sliyeg',
            'Sukagumiwang',
            'Sukra',
            'Terisi',
            'Tukdana',
            'Widasari',
        ];
    }

    /**
     * @return array<int, array{nama_wilayah: string, lat: float|null, lng: float|null}>
     */
    public static function wilayahItems(): array
    {
        return array_map(
            static fn (string $district): array => [
                'nama_wilayah' => self::wilayahName($district),
                'lat' => null,
                'lng' => null,
            ],
            self::names()
        );
    }

    public static function wilayahName(string $district): string
    {
        $district = self::normalizeName($district);

        return str_starts_with(strtolower($district), 'kecamatan ')
            ? $district
            : 'Kecamatan ' . $district;
    }

    public static function normalizeName(string $district): string
    {
        $district = trim(preg_replace('/\s+/', ' ', $district) ?? '');
        $normalized = strtolower(str_replace(['-', '_'], ' ', $district));

        return match ($normalized) {
            'indramayu kota', 'kota indramayu' => 'Indramayu',
            'lobener' => 'Lohbener',
            'kedokanbunder', 'kedokan bunder' => 'Kedokan Bunder',
            default => ucwords($normalized),
        };
    }
}
