<?php

namespace NinjaPortal\FilamentTranslations\Resources\RelationManagers\Concerns;

use NinjaPortal\FilamentTranslations\NinjaFilamentTranslatablePlugin;
use NinjaPortal\FilamentTranslations\Resources\Concerns\HasActiveLocaleSwitcher;

trait Translatable
{
    use HasActiveLocaleSwitcher;

    public function mountTranslatable(): void
    {
        if (
            blank($this->activeLocale) ||
            (! in_array($this->activeLocale, $this->getTranslatableLocales(), true))
        ) {
            $this->setActiveLocale();
        }
    }

    public function getTranslatableLocales(): array
    {
        $plugin = filament('ninja-filament-translatable');

        if ($plugin instanceof NinjaFilamentTranslatablePlugin) {
            return $plugin->getDefaultLocales();
        }

        $fallbackLocales = config('ninjaportal.translatable.locales', ['en', 'ar']);

        return array_values($fallbackLocales);
    }

    public function getDefaultTranslatableLocale(): string
    {
        $locales = array_values($this->getTranslatableLocales());

        return $locales[0] ?? config('app.locale');
    }

    public function getActiveTableLocale(): ?string
    {
        return $this->activeLocale;
    }

    protected function setActiveLocale(): void
    {
        $this->activeLocale = $this->getDefaultTranslatableLocale();
    }
}
