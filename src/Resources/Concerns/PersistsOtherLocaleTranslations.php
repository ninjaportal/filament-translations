<?php

namespace NinjaPortal\FilamentTranslations\Resources\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait PersistsOtherLocaleTranslations
{
    /**
     * @param  array<string, array<string, mixed>>  $additionalLocaleData
     */
    protected function persistOtherLocaleTranslations(Model $record, array $additionalLocaleData = []): void
    {
        if (! method_exists($record, 'translations')) {
            return;
        }

        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        $localePayload = $this->normalizeLocalePayload($additionalLocaleData)
            ->merge($this->normalizeLocalePayload($this->otherLocaleData ?? []));

        $localePayload
            ->each(function (array $attributes, string $locale) use ($record, $translatableAttributes): void {
                $payload = Arr::only($attributes, $translatableAttributes);

                if ($this->localePayloadIsEmpty($payload)) {
                    return;
                }

                $record->translations()->updateOrCreate(
                    ['locale' => $locale],
                    $payload,
                );
            });

        if (method_exists($record, 'setLocale') && filled($this->activeLocale)) {
            $record->setLocale($this->activeLocale);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, array<string, mixed>>
     */
    protected function buildLocalePayloadForActive(array $payload): array
    {
        if (blank($this->activeLocale)) {
            return [];
        }

        return [$this->activeLocale => $payload];
    }

    /**
     * @param  array<string, mixed>  $source
     */
    protected function normalizeLocalePayload(array $source): Collection
    {
        return collect($source)
            ->filter(fn ($value, $locale): bool => is_string($locale))
            ->map(function ($attributes): array {
                if ($attributes instanceof Collection) {
                    $attributes = $attributes->all();
                }

                if (! is_array($attributes)) {
                    return [];
                }

                return collect($attributes)
                    ->map(fn ($value) => $this->normalizeAttributeValue($value))
                    ->all();
            })
            ->filter(fn (array $attributes): bool => ! empty($attributes));
    }

    protected function normalizeAttributeValue(mixed $value): mixed
    {
        if ($value instanceof TemporaryUploadedFile) {
            return $this->storeTemporaryUploadedFile($value);
        }

        if (is_string($value) && Str::isJson($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->normalizeAttributeValue($decoded);
            }
        }

        if ($value instanceof Collection) {
            return $value->map(fn ($nested) => $this->normalizeAttributeValue($nested))->all();
        }

        if ($value instanceof Arrayable) {
            return $this->normalizeAttributeValue($value->toArray());
        }

        if (is_array($value)) {
            $normalized = array_map(fn ($nested) => $this->normalizeAttributeValue($nested), $value);

            $normalizedValues = array_values(array_filter($normalized, fn ($item) => $item !== null && $item !== ''));

            if (
                count($normalizedValues) === 1 &&
                is_string($normalizedValues[0])
            ) {
                return $normalizedValues[0];
            }

            return $normalized;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return $value;
    }

    protected function storeTemporaryUploadedFile(TemporaryUploadedFile $file): ?string
    {
        if (! $file->exists()) {
            return null;
        }

        $disk = config('filament.default_filesystem_disk', config('filesystems.default', 'public'));
        $directory = trim(config('filament-translations.upload_directory', 'filament-uploads'), '/');

        $filename = (string) Str::ulid();

        $extension = $file->getClientOriginalExtension();
        if (! empty($extension)) {
            $filename .= '.' . $extension;
        }

        $storedPath = $file->storePubliclyAs($directory, $filename, $disk);

        return $storedPath ?: null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function localePayloadIsEmpty(array $payload): bool
    {
        foreach ($payload as $value) {
            if ($this->valueHasContent($value)) {
                return false;
            }
        }

        return true;
    }

    protected function valueHasContent(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $nested) {
                if ($this->valueHasContent($nested)) {
                    return true;
                }
            }

            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return $value !== null;
    }
}
