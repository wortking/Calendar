<?php

declare(strict_types=1);

namespace App\Read\Application\Query\ListUsers;

class ListUsersResponse
{
    /**
     * @param UserSummaryView[] $users
     */
    public function __construct(
        public readonly array $users,
        public readonly int $page,
        public readonly int $limit,
        public readonly int $totalItems
    ) {}

    public function serialize(): array
    {
        return [
            'items' => array_map(static fn (UserSummaryView $user) => $user->serialize(), $this->users),
            'page' => $this->page,
            'limit' => $this->limit,
            'totalItems' => $this->totalItems,
            'totalPages' => (int) ceil($this->totalItems / max(1, $this->limit)),
        ];
    }
}
