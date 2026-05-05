<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

class FakeProductCategory extends Model
{
    protected $guarded = [];

    /**
     * @param  QueryBuilder  $query
     * @return FakeProductCategoryBuilder
     */
    public function newEloquentBuilder($query): Builder
    {
        return new FakeProductCategoryBuilder($query);
    }

    /**
     * @return HasMany<FakeProduct, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(FakeProduct::class);
    }
}
