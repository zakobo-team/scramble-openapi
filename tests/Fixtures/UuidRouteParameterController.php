<?php

declare(strict_types=1);

namespace Zakobo\ScrambleOpenApi\Tests\Fixtures;

class UuidRouteParameterController
{
    public function show(UuidRoutedModel $user): void {}

    public function showSnakeCase(UuidRoutedModel $oauthClient): void {}

    public function showIntegerKey(IntegerRoutedModel $product): void {}

    public function destroyToken(string $tokenId): void {}
}
