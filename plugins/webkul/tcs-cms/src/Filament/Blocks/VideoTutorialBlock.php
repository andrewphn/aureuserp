<?php

namespace Webkul\TcsCms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class VideoTutorialBlock
{
    public static function make(): Block
    {
        return Block::make('video_tutorial')
            ->label('Video Tutorial')
            ->icon('heroicon-o-video-camera')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('tutorial_title')
                            ->label('Tutorial Title')
                            ->placeholder('How to Cut Perfect Dovetails')
                            ->required(),

                        Select::make('video_source')
                            ->label('Video Source')
                            ->options([
                                'youtube' => 'YouTube',
                                'vimeo' => 'Vimeo',
                                'local' => 'Local Video File',
                                'embed_code' => 'Custom Embed Code',
                            ])
                            ->required()
                            ->default('youtube')
                            ->live(),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('video_url')
                            ->label('Video URL')
                            ->url()
                            ->required()
                            ->visible(fn(callable $get) => in_array($get('video_source'), ['youtube', 'vimeo'])),

                        FileUpload::make('video_file')
                            ->label('Video File')
                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                            ->directory('tcs-cms/videos')
                            ->required()
                            ->visible(fn(callable $get) => $get('video_source') === 'local'),

                        RichEditor::make('embed_code')
                            ->label('Embed Code')
                            ->required()
                            ->visible(fn(callable $get) => $get('video_source') === 'embed_code')
                            ->columnSpan(2),

                        TextInput::make('video_duration')
                            ->label('Video Duration')
                            ->placeholder('12:34'),
                    ]),

                Grid::make(2)
                    ->schema([
                        RichEditor::make('tutorial_description')
                            ->label('Tutorial Description')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList'])
                            ->required(),

                        FileUpload::make('video_thumbnail')
                            ->label('Custom Thumbnail')
                            ->image()
                            ->imageEditor()
                            ->directory('tcs-cms/video-thumbnails'),
                    ]),

                Grid::make(3)
                    ->schema([
                        Select::make('skill_level')
                            ->label('Skill Level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                                'expert' => 'Expert',
                                'all_levels' => 'All Levels',
                            ])
                            ->default('intermediate')
                            ->required(),

                        Select::make('tutorial_category')
                            ->label('Category')
                            ->options([
                                'joinery' => 'Joinery Techniques',
                                'finishing' => 'Finishing',
                                'tool_use' => 'Tool Usage',
                                'safety' => 'Safety',
                                'measurement' => 'Measuring & Marking',
                                'sharpening' => 'Tool Sharpening',
                                'project_build' => 'Project Build',
                                'troubleshooting' => 'Problem Solving',
                                'maintenance' => 'Shop Maintenance',
                                'general' => 'General Techniques',
                            ])
                            ->default('general')
                            ->required(),

                        TextInput::make('estimated_watch_time')
                            ->label('Estimated Watch Time')
                            ->placeholder('15 minutes'),
                    ]),

                Repeater::make('tutorial_sections')
                    ->label('Video Sections/Chapters')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('section_title')
                                    ->label('Section Title')
                                    ->required(),

                                TextInput::make('start_time')
                                    ->label('Start Time')
                                    ->placeholder('2:15'),

                                TextInput::make('end_time')
                                    ->label('End Time')
                                    ->placeholder('4:30'),
                            ]),

                        RichEditor::make('section_description')
                            ->label('Section Description')
                            ->toolbarButtons(['bold', 'italic', 'bulletList'])
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('tools_featured')
                                    ->label('Tools Featured')
                                    ->helperText('Comma-separated'),

                                TextInput::make('key_techniques')
                                    ->label('Key Techniques')
                                    ->helperText('Comma-separated'),
                            ]),
                    ])
                    ->defaultItems(0)
                    ->reorderable()
                    ->cloneable()
                    ->collapsible()
                    ->itemLabel(fn(array $state): ?string => $state['section_title'] ?? 'Untitled Section')
                    ->addActionLabel('Add Video Section')
                    ->columnSpanFull(),

                Grid::make(2)
                    ->schema([
                        RichEditor::make('materials_needed')
                            ->label('Materials Needed')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),

                        RichEditor::make('tools_needed')
                            ->label('Tools Required')
                            ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),
                    ]),

                Grid::make(2)
                    ->schema([
                        RichEditor::make('safety_notes')
                            ->label('Safety Considerations')
                            ->toolbarButtons(['bold', 'italic', 'bulletList']),

                        RichEditor::make('tips_and_tricks')
                            ->label('Pro Tips & Tricks')
                            ->toolbarButtons(['bold', 'italic', 'bulletList']),
                    ]),

                Grid::make(4)
                    ->schema([
                        Toggle::make('show_chapters')
                            ->label('Show Chapter Navigation')
                            ->default(true),

                        Toggle::make('enable_notes')
                            ->label('Enable Video Notes')
                            ->default(true),

                        Toggle::make('show_materials_list')
                            ->label('Show Materials List')
                            ->default(true),

                        Toggle::make('show_transcript')
                            ->label('Show Video Transcript')
                            ->default(false),

                        Toggle::make('autoplay')
                            ->label('Auto-play Video')
                            ->default(false),

                        Toggle::make('loop_video')
                            ->label('Loop Video')
                            ->default(false),

                        Select::make('video_quality')
                            ->label('Default Quality')
                            ->options([
                                'auto' => 'Auto',
                                '1080p' => '1080p',
                                '720p' => '720p',
                                '480p' => '480p',
                            ])
                            ->default('auto'),

                        Select::make('player_controls')
                            ->label('Player Controls')
                            ->options([
                                'default' => 'Default Controls',
                                'minimal' => 'Minimal Controls',
                                'custom' => 'Custom Controls',
                            ])
                            ->default('default'),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('instructor_name')
                            ->label('Instructor Name')
                            ->placeholder('Bryan Patton'),

                        TextInput::make('instructor_title')
                            ->label('Instructor Title')
                            ->placeholder('Master Craftsman, TCS Woodworking'),

                        RichEditor::make('transcript')
                            ->label('Video Transcript')
                            ->toolbarButtons(['bold', 'italic'])
                            ->visible(fn(callable $get) => $get('show_transcript'))
                            ->columnSpan(2),
                    ]),

                Grid::make(3)
                    ->schema([
                        TextInput::make('related_articles')
                            ->label('Related Articles')
                            ->helperText('Comma-separated'),

                        TextInput::make('follow_up_projects')
                            ->label('Follow-up Projects')
                            ->helperText('Comma-separated'),

                        TextInput::make('difficulty_rating')
                            ->label('Difficulty Rating (1-5)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->default(3),
                    ]),
            ]);
    }

    public static function mutateData(array $data): array
    {
        // Process video URL
        if (isset($data['video_url']) && isset($data['video_source'])) {
            switch ($data['video_source']) {
                case 'youtube':
                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $data['video_url'], $matches)) {
                        $data['video_id'] = $matches[1];
                        $data['embed_url'] = "https://www.youtube.com/embed/{$matches[1]}";
                        $data['thumbnail_url'] = "https://img.youtube.com/vi/{$matches[1]}/maxresdefault.jpg";
                    }
                    break;

                case 'vimeo':
                    if (preg_match('/(?:vimeo\.com\/)([0-9]+)/', $data['video_url'], $matches)) {
                        $data['video_id'] = $matches[1];
                        $data['embed_url'] = "https://player.vimeo.com/video/{$matches[1]}";
                    }
                    break;
            }
        }

        // Process local video
        if (isset($data['video_file']) && ($data['video_source'] ?? '') === 'local') {
            $data['video_file_url'] = asset('storage/'.$data['video_file']);
        }

        // Process custom thumbnail
        if (isset($data['video_thumbnail'])) {
            $data['custom_thumbnail_url'] = asset('storage/'.$data['video_thumbnail']);
        }

        // Process tutorial sections
        if (isset($data['tutorial_sections'])) {
            foreach ($data['tutorial_sections'] as &$section) {
                if (isset($section['start_time'])) {
                    $section['start_seconds'] = self::timeToSeconds($section['start_time']);
                }

                if (isset($section['end_time'])) {
                    $section['end_seconds'] = self::timeToSeconds($section['end_time']);
                }

                if (isset($section['tools_featured'])) {
                    $section['tools_array'] = array_map('trim', explode(',', $section['tools_featured']));
                }

                if (isset($section['key_techniques'])) {
                    $section['techniques_array'] = array_map('trim', explode(',', $section['key_techniques']));
                }
            }
        }

        // CSS classes
        $classes = ['video-tutorial'];
        if (isset($data['video_source'])) {
            $classes[] = 'source-'.str_replace('_', '-', $data['video_source']);
        }
        if (isset($data['skill_level'])) {
            $classes[] = 'level-'.str_replace('_', '-', $data['skill_level']);
        }
        if (isset($data['tutorial_category'])) {
            $classes[] = 'category-'.str_replace('_', '-', $data['tutorial_category']);
        }
        if ($data['show_chapters'] ?? true) {
            $classes[] = 'with-chapters';
        }
        $data['css_classes'] = implode(' ', $classes);

        // Related content
        if (isset($data['related_articles'])) {
            $data['related_articles_array'] = array_map('trim', explode(',', $data['related_articles']));
        }

        if (isset($data['follow_up_projects'])) {
            $data['follow_up_projects_array'] = array_map('trim', explode(',', $data['follow_up_projects']));
        }

        // Skill color
        $skillColors = [
            'beginner' => 'green',
            'intermediate' => 'blue',
            'advanced' => 'orange',
            'expert' => 'red',
            'all_levels' => 'gray',
        ];
        $data['skill_color'] = $skillColors[$data['skill_level'] ?? 'intermediate'] ?? 'blue';

        // Instructor info
        if (isset($data['instructor_name']) || isset($data['instructor_title'])) {
            $parts = array_filter([
                $data['instructor_name'] ?? null,
                $data['instructor_title'] ?? null,
            ]);
            $data['instructor_full'] = implode(', ', $parts);
        }

        // Player config
        $data['player_config'] = [
            'autoplay' => $data['autoplay'] ?? false,
            'loop' => $data['loop_video'] ?? false,
            'quality' => $data['video_quality'] ?? 'auto',
            'controls' => $data['player_controls'] ?? 'default',
            'chapters' => $data['show_chapters'] ?? true,
        ];

        $data['tutorial_id'] = 'tutorial-'.uniqid();

        $data['tutorial_stats'] = [
            'has_chapters' => ! empty($data['tutorial_sections']),
            'chapter_count' => count($data['tutorial_sections'] ?? []),
            'has_materials_list' => ! empty($data['materials_needed']),
            'has_tools_list' => ! empty($data['tools_needed']),
            'has_safety_notes' => ! empty($data['safety_notes']),
            'difficulty_stars' => $data['difficulty_rating'] ?? 3,
        ];

        return $data;
    }

    private static function timeToSeconds(string $time): int
    {
        $parts = explode(':', $time);

        if (count($parts) === 2) {
            return ((int) $parts[0] * 60) + (int) $parts[1];
        } elseif (count($parts) === 3) {
            return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) $parts[2];
        }

        return 0;
    }
}
