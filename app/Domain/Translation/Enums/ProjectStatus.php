<?php

namespace App\Domain\Translation\Enums;

enum ProjectStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case REVIEW = 'review';
    case COMPLETED = 'completed';
    case DELIVERED = 'delivered';
    case INVOICED = 'invoiced';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::IN_PROGRESS => 'In Progress',
            self::REVIEW => 'Review',
            self::COMPLETED => 'Completed',
            self::DELIVERED => 'Delivered',
            self::INVOICED => 'Invoiced',
            self::CLOSED => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => 'blue',
            self::IN_PROGRESS => 'yellow',
            self::REVIEW => 'purple',
            self::COMPLETED => 'green',
            self::DELIVERED => 'teal',
            self::INVOICED => 'indigo',
            self::CLOSED => 'gray',
        };
    }

    /** @return list<self> */
    public static function transitionableFrom(self $current): array
    {
        return match ($current) {
            self::NEW => [self::IN_PROGRESS, self::CLOSED],
            self::IN_PROGRESS => [self::REVIEW, self::COMPLETED, self::CLOSED],
            self::REVIEW => [self::IN_PROGRESS, self::COMPLETED, self::CLOSED],
            self::COMPLETED => [self::DELIVERED, self::CLOSED],
            self::DELIVERED => [self::INVOICED, self::CLOSED],
            self::INVOICED => [self::CLOSED],
            self::CLOSED => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, self::transitionableFrom($this));
    }
}
