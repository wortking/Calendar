<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListActivityTypes;

class ListActivityTypesQuery
{
    /**
     * @param string[]|null $departmentIds null = sin filtro (todas)
     */
    public function __construct(
        public readonly ?array $departmentIds = null
    ) {}
}
