<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FakeProductCategory extends Model
{
    protected $guarded = [];

    /**
     * @return HasMany<FakeProduct, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(FakeProduct::class);
    }
}
