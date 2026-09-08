<?php

namespace App\Models;

use App\DiskStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Disk extends Model
{
    protected $fillable = [
        'location_type', 'location', 'gptid', 'device', 'serial', 'model',
        'capacity', 'interface', 'pool', 'notes',
        'status',
    ];

    protected $attributes = [
        'status' => DiskStatus::Active->value,
    ];

    protected function casts(): array
    {
        return ['status' => DiskStatus::class];
    }

    public static function normalizeIdentifier(?string $value): ?string
    {
        $value = trim($value ?? '');

        if (! mb_check_encoding($value, 'ASCII')) {
            throw new InvalidArgumentException('Disk identifiers must contain only ASCII characters.');
        }

        $value = strtolower($value);

        return $value === '' ? null : $value;
    }

    protected function gptid(): Attribute
    {
        return Attribute::make(set: fn (?string $value): ?string => self::normalizeIdentifier($value));
    }

    protected function device(): Attribute
    {
        return Attribute::make(set: fn (?string $value): ?string => self::normalizeIdentifier($value));
    }
}
