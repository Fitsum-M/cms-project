<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'Administrator';
    case Editor = 'Editor';
    case Author = 'Author';
    case Contributor = 'Contributor';

    public function description(): string
    {
        return match ($this) {
            self::Administrator => 'Full system access including all modules, user management, role assignment, and system configuration.',
            self::Editor => 'Full content and taxonomy management; can publish, edit others\' content, and manage media. Restricted from user role assignment, system settings, and SEO Defaults.',
            self::Author => 'Can create and edit own posts and pages, assign taxonomies, upload media, and configure SEO on own content. Cannot publish; submits content for review.',
            self::Contributor => 'Most restricted role. Can create own draft posts only. Cannot upload media directly; may select from existing library. Cannot manage pages.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Administrator => 'danger',
            self::Editor => 'warning',
            self::Author => 'info',
            self::Contributor => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Administrator => 'heroicon-o-shield-check',
            self::Editor => 'heroicon-o-pencil-square',
            self::Author => 'heroicon-o-document-text',
            self::Contributor => 'heroicon-o-pencil',
        };
    }
}
