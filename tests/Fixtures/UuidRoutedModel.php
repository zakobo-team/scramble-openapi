<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class UuidRoutedModel extends Model
{
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
