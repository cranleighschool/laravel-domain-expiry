<?php

declare(strict_types=1);

namespace CranleighSchool\DomainExpiry\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $table = 'domain_expiry_domains';

    protected $fillable = ['domain', 'active', 'notes'];

    protected $casts = [
        'active' => 'boolean',
    ];

    /** @param Builder<Domain> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
