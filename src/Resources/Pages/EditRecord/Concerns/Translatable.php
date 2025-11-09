<?php

namespace NinjaPortal\FilamentTranslations\Resources\Pages\EditRecord\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use NinjaPortal\FilamentTranslations\Resources\Concerns\HasActiveLocaleSwitcher;
use NinjaPortal\FilamentTranslations\Resources\Concerns\PersistsOtherLocaleTranslations;
use NinjaPortal\FilamentTranslations\Resources\Pages\Concerns\HasTranslatableFormWithExistingRecordData;
use NinjaPortal\FilamentTranslations\Resources\Pages\Concerns\HasTranslatableRecord;
use NinjaPortal\Portal\Translatable\Locales;

trait Translatable
{
    use HasActiveLocaleSwitcher;
    use HasTranslatableFormWithExistingRecordData;
    use HasTranslatableRecord;
    use PersistsOtherLocaleTranslations;

    protected ?string $oldActiveLocale = null;

    public function getTranslatableLocales(): array
    {
        return static::getResource()::getTranslatableLocales();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $translatableAttributes = static::getResource()::getTranslatableAttributes();
        $activeLocalePayload = Arr::only($data, $translatableAttributes);

        if (blank($this->activeLocale)) {
            $updatedRecord = parent::handleRecordUpdate($record, $data);
            $this->persistOtherLocaleTranslations($updatedRecord, $this->buildLocalePayloadForActive($activeLocalePayload));

            return $updatedRecord;
        }

        $previousAppLocale = app()->getLocale();

        $locales = app()->bound(Locales::class) ? app(Locales::class) : null;
        $previousDomainLocale = null;

        if ($locales) {
            $previousDomainLocale = method_exists($locales, 'getLocale') ? $locales->getLocale() : null;
            $locales->setLocale($this->activeLocale);
        }

        app()->setLocale($this->activeLocale);

        try {
            $updatedRecord = parent::handleRecordUpdate($record, $data);
        } finally {
            if ($locales) {
                $restoreLocale = $previousDomainLocale ?? $previousAppLocale;
                $locales->setLocale($restoreLocale);
            }

            app()->setLocale($previousAppLocale);
        }

        $this->persistOtherLocaleTranslations($updatedRecord, $this->buildLocalePayloadForActive($activeLocalePayload));

        return $updatedRecord;
    }

    public function updatingActiveLocale(): void
    {
        $this->oldActiveLocale = $this->activeLocale;
    }

    public function updatedActiveLocale(?string $newActiveLocale = null): void
    {
        if (blank($this->oldActiveLocale)) {
            return;
        }

        $this->resetValidation();

        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        $this->otherLocaleData[$this->oldActiveLocale] = Arr::only($this->data, $translatableAttributes);

        $formData = [
            ...Arr::except($this->data, $translatableAttributes),
            ...($this->otherLocaleData[$this->activeLocale] ?? []),
        ];

        $this->fillFormWithDataAndCallHooks($this->getRecord(), $formData);

        unset($this->otherLocaleData[$this->activeLocale]);
        $this->oldActiveLocale = null;
    }

    public function setActiveLocale(string $locale): void
    {
        $this->updatingActiveLocale();
        $this->activeLocale = $locale;
        $this->updatedActiveLocale();
    }
}
