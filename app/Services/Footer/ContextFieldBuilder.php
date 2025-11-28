<?php

namespace App\Services\Footer;

use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\HtmlString;

/**
 * Context Field Builder
 *
 * Helper /**
 * for class
 *
 */
class for building common field types used across context providers.
 * Provides consistent styling and formatting for footer fields.
 */
class ContextFieldBuilder
{
    /**
     * Create a standard text field.
     *
     * @param string $name
     * @param string $label
     * @return TextEntry
     */
    public static function text(string $name, string $label): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->placeholder('—')
            ->size(TextSize::Small);
    }

    /**
     * Create a bold/prominent text field.
     *
     * @param string $name
     * @param string $label
     * @return TextEntry
     */
    public static function prominentText(string $name, string $label): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->placeholder('—')
            ->weight(FontWeight::Bold)
            ->size(TextSize::Medium);
    }

    /**
     * Create a badge field.
     *
     * @param string $name
     * @param string $label
     * @param string $color
     * @return TextEntry
     */
    public static function badge(string $name, string $label, string $color = 'primary'): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->placeholder('—')
            ->badge()
            ->color($color)
            ->size(TextSize::Small);
    }

    /**
     * Create a currency field.
     *
     * @param string $name
     * @param string $label
     * @param string $currency
     * @return TextEntry
     */
    public static function currency(string $name, string $label, string $currency = 'USD'): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->placeholder('—')
            ->money($currency)
            ->weight(FontWeight::SemiBold)
            ->size(TextSize::Small);
    }

    /**
     * Create a date field.
     *
     * @param string $name
     * @param string $label
     * @param string $format
     * @return TextEntry
     */
    public static function date(string $name, string $label, string $format = 'M d, Y'): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->placeholder('—')
            ->date($format)
            ->size(TextSize::Small);
    }

    /**
     * Create a number field with optional suffix.
     *
     * @param string $name
     * @param string $label
     * @param string|null $suffix
     * @return TextEntry
     */
    public static function number(string $name, string $label, ?string $suffix = null): TextEntry
    {
        $field = TextEntry::make($name)
            ->label($label)
            ->placeholder('—')
            ->numeric()
            ->weight(FontWeight::SemiBold)
            ->size(TextSize::Small);

        if ($suffix) {
            $field->suffix($suffix);
        }

        return $field;
    }

    /**
     * Create a metric badge (for estimates like days, weeks, months).
     *
     * @param string $name
     * @param string $label
     * @param string $icon
     * @param string $color
     * @return TextEntry
     */
    public static function metric(string $name, string $label, string $icon = 'heroicon-o-clock', string $color = 'warning'): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->placeholder('—')
            ->badge()
            ->color($color)
            ->icon($icon)
            ->iconPosition('before')
            ->size(TextSize::Small)
            ->formatStateUsing(function ($state) use ($label) {
                if (is_null($state) || $state === '—') {
                    return '—';
                }

                // Format as decimal with 1 decimal place
                $formatted = is_numeric($state) ? number_format((float) $state, 1) : $state;

                return new HtmlString("
                    <div class='flex items-center gap-1'>
                        <span class='font-bold'>{$formatted}</span>
                        <span class='text-[10px]'>{$label}</span>
                    </div>
                ");
            });
    }

    /**
     * Create an icon with text field.
     *
     * @param string $name
     * @param string $label
     * @param string $icon
     * @return TextEntry
     */
    public static function iconText(string $name, string $label, string $icon): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->placeholder('—')
            ->icon($icon)
            ->iconPosition('before')
            ->size(TextSize::Small);
    }

    /**
     * Create a copyable field (useful for IDs, SKUs, etc.).
     *
     * @param string $name
     * @param string $label
     * @return TextEntry
     */
    public static function copyable(string $name, string $label): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->placeholder('—')
            ->copyable()
            ->weight(FontWeight::Medium)
            ->size(TextSize::Small);
    }

    /**
     * Create an HTML field (for custom rendering).
     *
     * @param string $name
     * @param string $label
     * @return TextEntry
     */
    public static function html(string $name, string $label): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->placeholder('—')
            ->html()
            ->size(TextSize::Small);
    }
}
