<?php

namespace App\Repository;

interface PaginationInterface extends \JsonSerializable
{
    public function getPerPage(): int;
    public function getCurrentPage(): int;
    public function getOffset(): int;
}
