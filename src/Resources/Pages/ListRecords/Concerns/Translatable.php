<?php

namespace NinjaPortal\FilamentTranslations\Resources\Pages\ListRecords\Concerns;

use NinjaPortal\FilamentTranslations\Resources\Concerns\HasActiveLocaleSwitcher;
use NinjaPortal\Portal\Translatable\Locales;

trait Translatable
{
    use HasActiveLocaleSwitcher;

    public function mountTranslatable(): void
    {
        $this->activeLocale = static::getResource()::getDefaultTranslatableLocale();
        $this->applyActiveLocaleContext();
    }

    public function getTranslatableLocales(): array
    {
        return static::getResource()::getTranslatableLocales();
    }

    public function getActiveTableLocale(): ?string
    {
        return $this->activeLocale;
    }

    public function updatedActiveLocale(?string $locale): void
    {
        $this->applyActiveLocaleContext();
        $this->resetTable();
    }

    public function hydrateTranslatable(): void
    {
        $this->applyActiveLocaleContext();
    }

    protected function applyActiveLocaleContext(): void
    {
        if (blank($this->activeLocale)) {
            return;
        }

        if (app()->bound(Locales::class)) {
            $locales = app(Locales::class);

            if (method_exists($locales, 'setLocale')) {
                $locales->setLocale($this->activeLocale);
            }
        }

        app()->setLocale($this->activeLocale);
    }
}
