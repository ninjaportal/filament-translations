<?php

namespace NinjaPortal\FilamentTranslations\Resources\Pages\Concerns;

use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use NinjaPortal\Translatable\HasTranslations;

trait HasTranslatableFormWithExistingRecordData
{
    #[Locked]
    public $otherLocaleData = [];

    protected function fillForm(): void
    {
        $this->activeLocale = $this->getDefaultTranslatableLocale();

        /** @var Model|HasTranslations $record */
        $record = $this->getRecord();

        foreach ($this->getTranslatableLocales() as $locale) {

            $translatedData = $record->getTranslation($locale)?->toArray() ?? [];


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

        $availableLocales = $this->getTranslatableLocales();
        $defaultLocale = $resource::getDefaultTranslatableLocale();

        if (in_array($defaultLocale, $availableLocales)) {
            return $defaultLocale;
        }

        $resourceLocales = $this->getTranslatableLocales();

        return array_intersect($availableLocales, $resourceLocales)[0] ?? $defaultLocale;
    }
}
