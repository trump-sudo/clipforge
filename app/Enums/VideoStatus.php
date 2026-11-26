<?php

namespace App\Enums;

enum VideoStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function isFinal(): bool
    {
        return $this === self::Ready || $this === self::Failed;
    }
}
