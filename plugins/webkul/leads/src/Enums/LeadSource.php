<?php

namespace Webkul\Lead\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LeadSource: string implements HasColor, HasIcon, HasLabel
{
    case WEBSITE = 'website';
    case REFERRAL = 'referral';
    case GOOGLE = 'google';
    case SOCIAL_MEDIA = 'social_media';
    case WALK_IN = 'walk_in';
    case PHONE = 'phone';
    case EMAIL = 'email';
    case HOME_SHOW = 'home_show';
    case HOUZZ = 'houzz';
    case DESIGNER = 'designer';
    case PREVIOUS_CLIENT = 'previous_client';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::WEBSITE => 'Website',
            self::REFERRAL => 'Referral',
            self::GOOGLE => 'Google Search',
            self::SOCIAL_MEDIA => 'Social Media',
            self::WALK_IN => 'Walk-In',
            self::PHONE => 'Phone Call',
            self::EMAIL => 'Email',
            self::HOME_SHOW => 'Home Show',
            self::HOUZZ => 'Houzz',
            self::DESIGNER => 'Designer/Architect',
            self::PREVIOUS_CLIENT => 'Previous Client',
            self::OTHER => 'Other',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::WEBSITE => 'primary',
            self::REFERRAL => 'success',
            self::GOOGLE => 'info',
            self::SOCIAL_MEDIA => 'warning',
            self::WALK_IN => 'gray',
            self::PHONE => 'gray',
            self::EMAIL => 'gray',
            self::HOME_SHOW => 'warning',
            self::HOUZZ => 'info',
            self::DESIGNER => 'success',
            self::PREVIOUS_CLIENT => 'success',
            self::OTHER => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::WEBSITE => 'heroicon-o-globe-alt',
            self::REFERRAL => 'heroicon-o-user-group',
            self::GOOGLE => 'heroicon-o-magnifying-glass',
            self::SOCIAL_MEDIA => 'heroicon-o-share',
            self::WALK_IN => 'heroicon-o-building-storefront',
            self::PHONE => 'heroicon-o-phone',
            self::EMAIL => 'heroicon-o-envelope',
            self::HOME_SHOW => 'heroicon-o-home',
            self::HOUZZ => 'heroicon-o-home-modern',
            self::DESIGNER => 'heroicon-o-pencil-square',
            self::PREVIOUS_CLIENT => 'heroicon-o-arrow-path',
            self::OTHER => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Get all values as array for select options
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
