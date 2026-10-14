<?php

namespace App\Support;

use App\Models\ProductOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductOptionGroups
{
    public const COLOR_GROUP_NAME = 'Color';

    public const DEVICE_GROUP_NAME = 'Compatible Device';

    /**
     * @param  Collection<int, ProductOption>  $options
     * @return Collection<int, ProductOption>
     */
    public static function colorOptions(Collection $options): Collection
    {
        return $options
            ->filter(fn (ProductOption $option) => $option->isColorOption())
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * @param  Collection<int, ProductOption>  $options
     * @return Collection<int, ProductOption>
     */
    public static function compatibilityOptions(Collection $options): Collection
    {
        return $options
            ->filter(fn (ProductOption $option) => $option->isCompatibilityOption())
            ->sortBy('sort_order')
            ->values();
    }

    /**
     * @param  Collection<int, ProductOption>  $options
     * @return Collection<int, ProductOption>
     */
    public static function otherOptions(Collection $options): Collection
    {
        return $options->reject(
            fn (ProductOption $option) => $option->isCompatibilityOption() || $option->isColorOption()
        );
    }

    /**
     * @param  Collection<int, ProductOption>  $options
     * @return Collection<string, array{key: string, label: string, type: string, options: Collection<int, ProductOption>}>
     */
    public static function storefrontGroups(Collection $options): Collection
    {
        $groups = collect();

        $compatibilityOptions = self::compatibilityOptions($options);
        if ($compatibilityOptions->isNotEmpty()) {
            $sharedCompatibilityName = $compatibilityOptions->pluck('name')->unique()->count() === 1
                ? $compatibilityOptions->first()->name
                : null;
            $compatibilityLabel = $sharedCompatibilityName
                && $compatibilityOptions->contains(
                    fn (ProductOption $option) => strcasecmp($option->name, $option->deviceModel?->name ?? $option->value) !== 0
                )
                ? $sharedCompatibilityName
                : self::DEVICE_GROUP_NAME;

            $groups->put('device', [
                'key' => 'device',
                'label' => $compatibilityLabel,
                'type' => 'device',
                'options' => $compatibilityOptions,
            ]);
        }

        $colorOptions = self::colorOptions($options);
        if ($colorOptions->isNotEmpty()) {
            $groups->put('color', [
                'key' => 'color',
                'label' => 'Available Colors',
                'type' => 'color',
                'options' => $colorOptions,
            ]);
        }

        foreach (self::otherOptions($options)->groupBy('name') as $groupName => $groupOptions) {
            $groupKey = Str::slug((string) $groupName) ?: 'variant';

            $groups->put($groupKey, [
                'key' => $groupKey,
                'label' => (string) $groupName,
                'type' => 'variant',
                'options' => $groupOptions->sortBy('sort_order')->values(),
            ]);
        }

        return $groups;
    }

    /**
     * @param  Collection<int, ProductOption>  $options
     * @return Collection<int, Collection<int, ProductOption>>
     */
    public static function validationGroups(Collection $options): Collection
    {
        $groups = collect();

        $compatibilityOptions = self::compatibilityOptions($options);
        if ($compatibilityOptions->isNotEmpty()) {
            $groups->push($compatibilityOptions);
        }

        $colorOptions = self::colorOptions($options);
        if ($colorOptions->isNotEmpty()) {
            $groups->push($colorOptions);
        }

        foreach (self::otherOptions($options)->groupBy('name') as $groupOptions) {
            $groups->push($groupOptions->values());
        }

        return $groups;
    }

    /**
     * @param  Collection<int, ProductOption>  $options
     */
    public static function formatSummary(Collection $options): ?string
    {
        if ($options->isEmpty()) {
            return null;
        }

        return $options
            ->map(function (ProductOption $option): string {
                if ($option->isCompatibilityOption()) {
                    return self::DEVICE_GROUP_NAME . ': ' . ($option->deviceModel?->name ?? $option->value);
                }

                if ($option->isColorOption()) {
                    return 'Color: ' . $option->value;
                }

                return "{$option->name}: {$option->value}";
            })
            ->implode(', ');
    }
}
