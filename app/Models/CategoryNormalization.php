<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryNormalization extends Model
{
    protected $fillable = [
        'raw_category',
        'normalized_category',
        'regex_pattern',
        'detection_method',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Find normalization by exact match.
     */
    public static function findByExactMatch(string $raw): ?self
    {
        return static::where('raw_category', $raw)->first();
    }

    /**
     * Find normalization by regex pattern match.
     */
    public static function findByRegexMatch(string $raw): ?self
    {
        $candidates = static::whereNotNull('regex_pattern')->get();

        foreach ($candidates as $candidate) {
            if (preg_match('/' . $candidate->regex_pattern . '/i', $raw)) {
                return $candidate;
            }
        }

        return null;
    }
}
