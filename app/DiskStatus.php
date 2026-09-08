<?php

namespace App;

enum DiskStatus: string
{
    case Active = 'active';
    case HotSpare = 'hot-spare';
    case WarmSpare = 'warm-spare';
    case Faulted = 'faulted';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::HotSpare => 'Hot spare',
            self::WarmSpare => 'Warm spare',
            self::Faulted => 'Faulted',
        };
    }
}
