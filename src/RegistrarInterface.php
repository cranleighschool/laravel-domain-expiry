<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry;

use Illuminate\Support\Collection;

interface RegistrarInterface
{
    public function getDomains(): Collection;

    public function shouldTry(): bool;
}