<?php

namespace NinjaPortal\FilamentTranslations\Actions\Concerns;

use NinjaPortal\FilamentTranslations\NinjaFilamentTranslatablePlugin;

trait HasTranslatableLocaleOptions
{
    public function setTranslatableLocaleOptions(): static
    {
        $this->options(function (): array {
            $livewire = $this->getLivewire();

            if (! method_exists($livewire, 'getTranslatableLocales')) {
                return [];
            }

            $locales = [];

            /** @var NinjaFilamentTranslatablePlugin $plugin */
            $plugin = filament('ninja-filament-translatable');

            foreach ($livewire->getTranslatableLocales() as $locale) {
                $locales[$locale] = $plugin->getLocaleLabel($locale) ?? $locale;
            }

            return $locales;
        });

        return $this;
    }
}
