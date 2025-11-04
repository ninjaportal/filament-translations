<?php

namespace NinjaPortal\FilamentTranslations\Resources\Pages\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use NinjaPortal\Portal\Translatable\HasTranslations;

trait HasTranslatableFormWithExistingRecordData
{
    #[Locked]
    public array $otherLocaleData = [];

    protected function fillForm(): void
    {
        $this->activeLocale = $this->getDefaultTranslatableLocale();

        /** @var Model|HasTranslations $record */
        $record = $this->getRecord();

        if (method_exists($record, 'translations')) {
            $record->loadMissing('translations');
        }

        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        foreach ($this->getTranslatableLocales() as $locale) {
            $translation = method_exists($record, 'translations')
                ? $record->translations->firstWhere('locale', $locale)
                : null;

            $translatedData = $translation
                ? Arr::only($translation->toArray(), $translatableAttributes)
                : [];

            $translatedData = array_map(function ($value) {
                if (! is_string($value)) {
                    return $value;
                }

                if (! Str::isJson($value)) {
                    return $value;
                }

                $decoded = json_decode($value, true);

                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            }, $translatedData);
            if ($locale !== $this->activeLocale) {
                $this->otherLocaleData[$locale] = $this->mutateFormDataBeforeFill($translatedData);

                continue;
            }

            /** @internal Read the DocBlock above the following method. */
            $this->fillFormWithDataAndCallHooks($record, $translatedData);
        }
    }

    protected function getDefaultTranslatableLocale(): string
    {
        $resource = static::getResource();

        $availableLocales = array_values($this->getTranslatableLocales());
        $defaultLocale = $resource::getDefaultTranslatableLocale();

        if (in_array($defaultLocale, $availableLocales, true)) {
            return $defaultLocale;
        }

        $resourceLocales = array_values($resource::getTranslatableLocales());
        $intersection = array_values(array_intersect($availableLocales, $resourceLocales));

        return $intersection[0] ?? $availableLocales[0] ?? $defaultLocale;
    }
}
