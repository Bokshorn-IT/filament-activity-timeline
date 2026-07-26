<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures;

use Filament\Support\Contracts\HasLabel;

enum TestInvoiceStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::Sent => 'Versendet',
            self::Paid => 'Bezahlt',
        };
    }
}
