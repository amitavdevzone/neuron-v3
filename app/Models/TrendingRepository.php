<?php

namespace App\Models;

use Database\Factories\TrendingRepositoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'github_id',
    'name',
    'full_name',
    'owner',
    'description',
    'language',
    'stars_count',
    'forks_count',
    'open_issues_count',
    'html_url',
    'github_created_at',
    'fetched_at',
])]
class TrendingRepository extends Model
{
    /** @use HasFactory<TrendingRepositoryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'github_created_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }
}
