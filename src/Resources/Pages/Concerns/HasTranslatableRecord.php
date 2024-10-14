<?php

namespace NinjaPortal\FilamentTranslations\Resources\Pages\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasTranslatableRecord
{
    public function getRecord(): Model
    {
        $oldAppLocale = app()->getLocale();
        app()->setLocale($this->activeLocale);
        $record = parent::getRecord();
        app()->setLocale($oldAppLocale);
        return $record;
    }
}
