<?php

namespace NinjaPortal\FilamentTranslations\Tables\Actions;

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

        $this->label(__('filament-translations::actions.active_locale.label'));

        $this->setTranslatableLocaleOptions();
    }
}
