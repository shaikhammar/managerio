<?php

namespace App\Domain\Shared\Enums;

enum BusinessRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Admin',
            self::EDITOR => 'Editor',
            self::VIEWER => 'Viewer',
        };
    }

    /**
     * Check if this role has at least the given permission level.
     * Owner > Admin > Editor > Viewer
     */
    public function hasAtLeast(self $requiredRole): bool
    {
        $hierarchy = [
            self::VIEWER->value => 0,
            self::EDITOR->value => 1,
            self::ADMIN->value => 2,
            self::OWNER->value => 3,
        ];

        return $hierarchy[$this->value] >= $hierarchy[$requiredRole->value];
    }

    public function canEdit(): bool
    {
        return $this->hasAtLeast(self::EDITOR);
    }

    public function canManage(): bool
    {
        return $this->hasAtLeast(self::ADMIN);
    }
}
