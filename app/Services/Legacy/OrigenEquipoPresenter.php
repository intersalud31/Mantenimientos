<?php

namespace App\Services\Legacy;

class OrigenEquipoPresenter
{
    private const INTERNAL_OPTIONS = [
        'normal' => 'Inventario base',
        'nuevo' => 'Adquisición posterior',
    ];

    private const INTERNAL_ALIASES = [
        'viejo' => 'normal',
    ];

    public function fieldLabel(): string
    {
        return 'Origen';
    }

    public function label(?string $value): string
    {
        $normalized = $this->normalize($value);

        return $normalized !== null
            ? self::INTERNAL_OPTIONS[$normalized]
            : 'Sin origen';
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function options(): array
    {
        $options = [];

        foreach (self::INTERNAL_OPTIONS as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $options;
    }

    public function badgeVariant(?string $value): string
    {
        return $this->normalize($value) ?? 'normal';
    }

    public function optionValue(?string $value, string $fallback = 'normal'): string
    {
        return $this->normalize($value) ?? $fallback;
    }

    private function normalize(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value), 'UTF-8');

        if ($value === '') {
            return null;
        }

        $value = self::INTERNAL_ALIASES[$value] ?? $value;

        return array_key_exists($value, self::INTERNAL_OPTIONS)
            ? $value
            : null;
    }
}
