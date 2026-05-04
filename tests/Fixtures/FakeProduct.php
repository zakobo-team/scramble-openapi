<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

#[UseResource(ProductResource::class)]
class FakeProduct extends Model
{
    protected $guarded = [];

    /**
     * @param  QueryBuilder  $query
     * @return FakeProductBuilder
     */
    public function newEloquentBuilder($query): Builder
    {
        return new FakeProductBuilder($query);
    }

    /**
     * @return BelongsTo<FakeProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FakeProductCategory::class);
    }
}
