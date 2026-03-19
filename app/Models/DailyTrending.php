<?php

namespace App\Models;

use Database\Factories\DailyTrendingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['full_name', 'trending_date'])]
class DailyTrending extends Model
{
    /** @use HasFactory<DailyTrendingFactory> */
    use HasFactory;

    protected $table = 'daily_trending';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trending_date' => 'date',
        ];
    }
}
