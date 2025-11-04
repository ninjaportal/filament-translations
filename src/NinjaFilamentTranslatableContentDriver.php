<?php

namespace NinjaPortal\FilamentTranslations;

use Filament\Support\Contracts\TranslatableContentDriver;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

use function Filament\Support\generate_search_column_expression;

class NinjaFilamentTranslatableContentDriver implements TranslatableContentDriver
{
    public function __construct(protected string $activeLocale) {}

    public function isAttributeTranslatable(string $model, string $attribute): bool
    {
        $model = app($model);
        if (! method_exists($model, 'isTranslatedAttribute')) {
            return false;
        }

        return $model->isTranslatedAttribute($attribute);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function makeRecord(string $model, array $data): Model
    {
        $record = new $model;

        if (method_exists($record, 'setLocale')) {
            $record->setLocale($this->activeLocale);
        }

        $record->fill($data);

        return $record;
    }

    public function setRecordLocale(Model $record): Model
    {
        if (! method_exists($record, 'setLocale')) {
            return $record;
        }

        return $record->setLocale($this->activeLocale);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRecord(Model $record, array $data): Model
    {
        $translatableAttributes = method_exists($record, 'getTranslatableAttributes')
            ? $record->getTranslatableAttributes()
            : [];

        $translationData = Arr::only($data, $translatableAttributes);
        $attributeData = Arr::except($data, $translatableAttributes);

        if (! empty($attributeData)) {
            $record->fill($attributeData);
            $record->save();
        }

        if (! empty($translationData)) {
            $this->persistTranslation($record, $translationData, $this->activeLocale);
        }

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecordAttributesToArray(Model $record): array
    {
        $attributes = $record->attributesToArray();

        if (! method_exists($record, 'getTranslatableAttributes')) {
            return $attributes;
        }

        if (! method_exists($record, 'getTranslation')) {
            return $attributes;
        }

        // Get the translation model for the active locale
        $translation = $record->getTranslation($this->activeLocale);

        if (! $translation) {
            return $attributes;
        }

        // Extract translatable attributes from the translation model
        foreach ($record->getTranslatableAttributes() as $attribute) {
            $attributes[$attribute] = $translation->getAttribute($attribute);
        }

        return $attributes;
    }

    public function applySearchConstraintToQuery(Builder $query, string $column, string $search, string $whereClause, ?bool $isCaseInsensitivityForced = null): Builder
    {
        /** @var Connection $databaseConnection */
        $databaseConnection = $query->getConnection();

        $column = match ($databaseConnection->getDriverName()) {
            'pgsql' => "{$column}->>'{$this->activeLocale}'",
            default => "json_extract({$column}, \"$.{$this->activeLocale}\")",
        };

        return $query->{$whereClause}(
            generate_search_column_expression($column, $isCaseInsensitivityForced, $databaseConnection),
            'like',
            (string) str($search)->wrap('%'),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function persistTranslation(Model $record, array $attributes, ?string $locale = null): void
    {
        $targetLocale = $locale ?? $this->activeLocale;

        if (! is_string($targetLocale) || $targetLocale === '') {
            return;
        }

        if (method_exists($record, 'translations')) {
            $record->translations()->updateOrCreate(
                ['locale' => $targetLocale],
                $attributes,
            );

            return;
        }

        if (method_exists($record, 'setLocale')) {
            $record->setLocale($targetLocale);
        }

        $record->fill($attributes);
        $record->save();
    }
}
