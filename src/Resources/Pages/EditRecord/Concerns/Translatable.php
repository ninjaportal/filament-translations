<?php

namespace NinjaPortal\FilamentTranslations\Resources\Pages\EditRecord\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use NinjaPortal\FilamentTranslations\Resources\Concerns\HasActiveLocaleSwitcher;
use NinjaPortal\FilamentTranslations\Resources\Pages\Concerns\HasTranslatableFormWithExistingRecordData;
use NinjaPortal\FilamentTranslations\Resources\Pages\Concerns\HasTranslatableRecord;
use NinjaPortal\Portal\Translatable\Locales;

trait Translatable
{
    use HasActiveLocaleSwitcher;
    use HasTranslatableFormWithExistingRecordData;
    use HasTranslatableRecord;

    protected ?string $oldActiveLocale = null;

    public function getTranslatableLocales(): array
    {
        return static::getResource()::getTranslatableLocales();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        app(Locales::class)->setLocale($this->activeLocale);
        return parent::handleRecordUpdate($record, $data);
    }

    public function updatingActiveLocale(): void
    {
        $this->oldActiveLocale = $this->activeLocale;
    }

    public function updatedActiveLocale(): void
    {
        if (blank($this->oldActiveLocale)) {
            return;
        }

        $this->resetValidation();

        $translatableAttributes = static::getResource()::getTranslatableAttributes();

        $this->otherLocaleData[$this->oldActiveLocale] = Arr::only($this->data, $translatableAttributes);
        $nextLocaleData = $this->otherLocaleData[$this->activeLocale] ?? $this->resolveLocaleData($this->activeLocale, $translatableAttributes);

        $this->data = [
            ...Arr::except($this->data, $translatableAttributes),
            ...$nextLocaleData,
        ];
    }

    public function setActiveLocale(string $locale): void
    {
        $this->updatingActiveLocale();
        $this->activeLocale = $locale;
        $this->updatedActiveLocale();
    }

    protected function resolveLocaleData(string $locale, array $translatableAttributes): array
    {
        if (isset($this->otherLocaleData[$locale])) {
            return $this->otherLocaleData[$locale];
        }

        $record = $this->getRecord();
        $translation = $record->getTranslation($locale, false);

        if ($translation === null) {
            return $this->otherLocaleData[$locale] = [];
        }

        return $this->otherLocaleData[$locale] = Arr::only(
            $translation->toArray(),
            $translatableAttributes
        );
    }
}
