<?php

namespace NinjaPortal\FilamentTranslations\Actions;

use Filament\Actions\SelectAction;
use NinjaPortal\FilamentTranslations\Actions\Concerns\HasTranslatableLocaleOptions;

class LocaleSwitcher extends SelectAction
{
    use HasTranslatableLocaleOptions;

    public static function getDefaultName(): ?string
    {
        return 'activeLocale';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('ninja-filament-translatable-plugin::actions.active_locale.label'));

        $this->setTranslatableLocaleOptions();
    }
}
