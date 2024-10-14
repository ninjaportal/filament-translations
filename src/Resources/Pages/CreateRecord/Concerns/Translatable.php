<?php

namespace NinjaPortal\FilamentTranslations\Resources\Pages\CreateRecord\Concerns;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;
use NinjaPortal\FilamentTranslations\Resources\Concerns\HasActiveLocaleSwitcher;
use NinjaPortal\Portal\Translatable\Locales;

trait Translatable
{
    use HasActiveLocaleSwitcher;

    protected ?string $oldActiveLocale = null;

    #[Locked]
    public $otherLocaleData = [];

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
        app(Locales::class)->setLocale($this->activeLocale);
        return parent::handleRecordCreation($data);
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

        $this->data = [
            ...Arr::except($this->data, $translatableAttributes),
            ...$this->otherLocaleData[$this->activeLocale] ?? [],
        ];

        unset($this->otherLocaleData[$this->activeLocale]);
    }
}
