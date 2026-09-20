<?php

declare(strict_types=1);

namespace App\Read\Application\Query\GetMe;

class GetMeQuery
{
    public function __construct(
        public readonly string $userId
    ) {}
}
