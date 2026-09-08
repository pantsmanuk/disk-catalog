<?php

namespace App\Models;

use App\DiskStatus;
use Illuminate\Database\Eloquent\Model;

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
}
