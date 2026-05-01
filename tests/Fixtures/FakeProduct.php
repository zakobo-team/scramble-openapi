<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

class FakeProduct extends Model
{
    /**
     * @param  QueryBuilder  $query
     * @return FakeProductBuilder
     */
    public function newEloquentBuilder($query): Builder
    {
        return new FakeProductBuilder($query);
    }
}
