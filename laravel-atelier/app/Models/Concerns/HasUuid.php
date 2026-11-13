<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * UUID primary keys for Eloquent models.
 *
 * Laravel port of the Prisma `@id @default(cuid())` directive from
 * prisma/schema.prisma: every model that uses this trait receives a
 * freshly generated UUID in its string primary key right before the
 * first INSERT.
 *
 * The using models are responsible for declaring the key properties
 * themselves (traits cannot define properties that the Eloquent base
 * class reads):
 *
 *     protected $keyType = 'string';
 *     public $incrementing = false;
 */
trait HasUuid
{
    /**
     * Boot the trait: hook into the model's `creating` event and fill the
     * primary key with a UUID when it has not been set explicitly.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (self $model): void {
            $key = $model->getKeyName();

            if (empty($model->getAttribute($key))) {
                $model->setAttribute($key, (string) Str::uuid());
            }
        });
    }
}
