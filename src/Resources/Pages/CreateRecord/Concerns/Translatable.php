<?php

namespace NinjaPortal\FilamentTranslations\Resources\Pages\CreateRecord\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;
use NinjaPortal\FilamentTranslations\Resources\Concerns\HasActiveLocaleSwitcher;
use NinjaPortal\FilamentTranslations\Resources\Concerns\PersistsOtherLocaleTranslations;
use NinjaPortal\Portal\Translatable\Locales;

trait Translatable
{
    use HasActiveLocaleSwitcher;
    use PersistsOtherLocaleTranslations;

    protected ?string $oldActiveLocale = null;

    #[Locked]
    public array $otherLocaleData = [];

    public function mountTranslatable(): void
    {
        $this->activeLocale = static::getResource()::getDefaultTranslatableLocale();
    }

    public function getTranslatableLocales(): array
    {
        return static::getResource()::getTranslatableLocales();
    }

    protected function handleRecordCreation(array $data): Model
    {
        if (blank($this->activeLocale)) {
            return parent::handleRecordCreation($data);
        }

        $translatableAttributes = static::getResource()::getTranslatableAttributes();
        $activeLocalePayload = Arr::only($data, $translatableAttributes);

        if (blank($this->activeLocale)) {
            $record = parent::handleRecordCreation($data);
            $this->persistOtherLocaleTranslations($record, $this->buildLocalePayloadForActive($activeLocalePayload));

            return $record;
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
            $record = parent::handleRecordCreation($data);
        } finally {
            if ($locales) {
                $restoreLocale = $previousDomainLocale ?? $previousAppLocale;
                $locales->setLocale($restoreLocale);
            }

            app()->setLocale($previousAppLocale);
        }

    $this->persistOtherLocaleTranslations($record, $this->buildLocalePayloadForActive($activeLocalePayload));

        return $record;
    }

    public function updatingActiveLocale(): void
    {
        $this->oldActiveLocale = $this->activeLocale;
    }

    public function updatedActiveLocale(string $newActiveLocale): void
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

        $this->form->fill($this->mutateFormDataBeforeFill($formData));

        unset($this->otherLocaleData[$this->activeLocale]);
        $this->oldActiveLocale = null;
    }

}
