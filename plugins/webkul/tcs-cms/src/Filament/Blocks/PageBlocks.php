<?php

namespace Webkul\TcsCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;

/**
 * Registry class providing all TCS CMS page builder blocks.
 *
 * Usage in a Filament resource:
 *
 * Builder::make('blocks')
 *     ->blocks(PageBlocks::all())
 *     ->collapsible()
 *     ->cloneable()
 *     ->reorderable()
 */
class PageBlocks
{
    /**
     * Get all available page blocks.
     *
     * @return array<Block>
     */
    public static function all(): array
    {
        return [
            FAQBlock::make(),
            ProjectBlock::make(),
            CraftsmanQuoteBlock::make(),
            MaterialShowcaseBlock::make(),
            WoodSpeciesBlock::make(),
            ProcessTimelineBlock::make(),
            BeforeAfterBlock::make(),
            WorkshopTipBlock::make(),
            ProjectGalleryBlock::make(),
            VideoTutorialBlock::make(),
            TechnicalSpecBlock::make(),
        ];
    }

    /**
     * Get content-focused blocks (FAQs, Quotes, Tips).
     *
     * @return array<Block>
     */
    public static function content(): array
    {
        return [
            FAQBlock::make(),
            CraftsmanQuoteBlock::make(),
            WorkshopTipBlock::make(),
        ];
    }

    /**
     * Get portfolio/project-focused blocks.
     *
     * @return array<Block>
     */
    public static function portfolio(): array
    {
        return [
            ProjectBlock::make(),
            ProjectGalleryBlock::make(),
            BeforeAfterBlock::make(),
        ];
    }

    /**
     * Get material/wood-focused blocks.
     *
     * @return array<Block>
     */
    public static function materials(): array
    {
        return [
            MaterialShowcaseBlock::make(),
            WoodSpeciesBlock::make(),
        ];
    }

    /**
     * Get process/tutorial blocks.
     *
     * @return array<Block>
     */
    public static function tutorials(): array
    {
        return [
            ProcessTimelineBlock::make(),
            VideoTutorialBlock::make(),
            TechnicalSpecBlock::make(),
        ];
    }

    /**
     * Mutate block data for frontend rendering.
     * Call the appropriate block's mutateData method.
     *
     * @param  string  $blockType  The block type (e.g., 'faq', 'project')
     * @param  array  $data  The block data
     * @return array The mutated data
     */
    public static function mutateData(string $blockType, array $data): array
    {
        $blockClasses = [
            'faq' => FAQBlock::class,
            'project' => ProjectBlock::class,
            'craftsman_quote' => CraftsmanQuoteBlock::class,
            'material_showcase' => MaterialShowcaseBlock::class,
            'wood_species' => WoodSpeciesBlock::class,
            'process_timeline' => ProcessTimelineBlock::class,
            'before_after' => BeforeAfterBlock::class,
            'workshop_tip' => WorkshopTipBlock::class,
            'project_gallery' => ProjectGalleryBlock::class,
            'video_tutorial' => VideoTutorialBlock::class,
            'technical_spec' => TechnicalSpecBlock::class,
        ];

        if (isset($blockClasses[$blockType])) {
            return $blockClasses[$blockType]::mutateData($data);
        }

        return $data;
    }
}
