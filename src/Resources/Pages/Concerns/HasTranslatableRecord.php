<?php

namespace NinjaPortal\FilamentTranslations\Resources\Pages\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasTranslatableRecord
{
    public function getRecord(): Model
    {
        if (blank($this->activeLocale)) {
            return parent::getRecord();
        }

        $previousLocale = app()->getLocale();
        app()->setLocale($this->activeLocale);

        try {
            return parent::getRecord();
        } finally {
            app()->setLocale($previousLocale);
        }
    }
}
