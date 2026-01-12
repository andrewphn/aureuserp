<?php

namespace Webkul\Project\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Pullout;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Services\ComplexityScoreService;

/**
 * Artisan command to recalculate complexity scores for projects.
 *
 * Walks the hierarchy bottom-up: Components → Sections → Cabinets → Runs → Locations → Rooms → Project
 *
 * Usage:
 *   php artisan projects:recalculate-complexity --all
 *   php artisan projects:recalculate-complexity --project=123
 *   php artisan projects:recalculate-complexity --needs-recalculation
 */
class RecalculateComplexityScores extends Command
{
    protected $signature = 'projects:recalculate-complexity
        {--project= : Recalculate for a specific project ID}
        {--all : Recalculate for all projects}
        {--needs-recalculation : Only recalculate entities that need it}
        {--force : Force recalculation even if recently calculated}';

    protected $description = 'Recalculate complexity scores for project hierarchy (components → project)';

    protected ComplexityScoreService $service;

    protected int $doorsProcessed = 0;

    protected int $drawersProcessed = 0;

    protected int $shelvesProcessed = 0;

    protected int $pulloutsProcessed = 0;

    protected int $sectionsProcessed = 0;

    protected int $cabinetsProcessed = 0;

    protected int $runsProcessed = 0;

    protected int $locationsProcessed = 0;

    protected int $roomsProcessed = 0;

    protected int $projectsProcessed = 0;

    public function __construct(ComplexityScoreService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $projectId = $this->option('project');
        $all = $this->option('all');
        $needsRecalculation = $this->option('needs-recalculation');
        $force = $this->option('force');

        if (! $projectId && ! $all && ! $needsRecalculation) {
            $this->error('Please specify --project=ID, --all, or --needs-recalculation');

            return self::FAILURE;
        }

        $this->info('Starting complexity score recalculation...');
        $startTime = microtime(true);

        if ($projectId) {
            $this->recalculateForProject((int) $projectId, $force);
        } elseif ($needsRecalculation) {
            $this->recalculateNeedingRecalculation();
        } else {
            $this->recalculateAll($force);
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info('Recalculation complete!');
        $this->table(
            ['Entity', 'Processed'],
            [
                ['Doors', $this->doorsProcessed],
                ['Drawers', $this->drawersProcessed],
                ['Shelves', $this->shelvesProcessed],
                ['Pullouts', $this->pulloutsProcessed],
                ['Sections', $this->sectionsProcessed],
                ['Cabinets', $this->cabinetsProcessed],
                ['Cabinet Runs', $this->runsProcessed],
                ['Room Locations', $this->locationsProcessed],
                ['Rooms', $this->roomsProcessed],
                ['Projects', $this->projectsProcessed],
            ]
        );
        $this->info("Time elapsed: {$elapsed}s");

        return self::SUCCESS;
    }

    protected function recalculateForProject(int $projectId, bool $force = false): void
    {
        $project = Project::find($projectId);

        if (! $project) {
            $this->error("Project #{$projectId} not found");

            return;
        }

        $this->info("Recalculating complexity for project: {$project->name} (#{$projectId})");

        // Get all rooms for this project
        $rooms = $project->rooms()->with([
            'locations.cabinetRuns.cabinets.sections' => function ($query) {
                $query->with(['doors', 'drawers', 'shelves', 'pullouts']);
            },
        ])->get();

        // Process bottom-up
        $this->processRooms($rooms, $force);

        // Finally, recalculate the project itself
        $this->info('Calculating project complexity...');
        $this->service->recalculateAndSave($project);
        $this->projectsProcessed++;
    }

    protected function recalculateAll(bool $force = false): void
    {
        $this->info('Recalculating all projects...');

        $projectCount = Project::count();
        $bar = $this->output->createProgressBar($projectCount);
        $bar->start();

        Project::chunk(100, function ($projects) use ($bar, $force) {
            foreach ($projects as $project) {
                $this->recalculateForProject($project->id, $force);
                $bar->advance();
            }
        });

        $bar->finish();
    }

    protected function recalculateNeedingRecalculation(): void
    {
        $this->info('Finding entities that need recalculation...');

        // Process components first
        $this->info('Processing doors...');
        Door::needsRecalculation()->chunk(100, function ($doors) {
            foreach ($doors as $door) {
                $this->service->recalculateAndSave($door);
                $this->doorsProcessed++;
            }
        });

        $this->info('Processing drawers...');
        Drawer::needsRecalculation()->chunk(100, function ($drawers) {
            foreach ($drawers as $drawer) {
                $this->service->recalculateAndSave($drawer);
                $this->drawersProcessed++;
            }
        });

        $this->info('Processing shelves...');
        Shelf::needsRecalculation()->chunk(100, function ($shelves) {
            foreach ($shelves as $shelf) {
                $this->service->recalculateAndSave($shelf);
                $this->shelvesProcessed++;
            }
        });

        $this->info('Processing pullouts...');
        Pullout::needsRecalculation()->chunk(100, function ($pullouts) {
            foreach ($pullouts as $pullout) {
                $this->service->recalculateAndSave($pullout);
                $this->pulloutsProcessed++;
            }
        });

        // Then sections
        $this->info('Processing cabinet sections...');
        CabinetSection::needsRecalculation()->chunk(100, function ($sections) {
            foreach ($sections as $section) {
                $this->service->recalculateAndSave($section);
                $this->sectionsProcessed++;
            }
        });

        // Then cabinets
        $this->info('Processing cabinets...');
        Cabinet::needsRecalculation()->chunk(100, function ($cabinets) {
            foreach ($cabinets as $cabinet) {
                $this->service->recalculateAndSave($cabinet);
                $this->cabinetsProcessed++;
            }
        });

        // Then runs
        $this->info('Processing cabinet runs...');
        CabinetRun::needsRecalculation()->chunk(100, function ($runs) {
            foreach ($runs as $run) {
                $this->service->recalculateAndSave($run);
                $this->runsProcessed++;
            }
        });

        // Then locations
        $this->info('Processing room locations...');
        RoomLocation::needsRecalculation()->chunk(100, function ($locations) {
            foreach ($locations as $location) {
                $this->service->recalculateAndSave($location);
                $this->locationsProcessed++;
            }
        });

        // Then rooms
        $this->info('Processing rooms...');
        Room::needsRecalculation()->chunk(100, function ($rooms) {
            foreach ($rooms as $room) {
                $this->service->recalculateAndSave($room);
                $this->roomsProcessed++;
            }
        });

        // Finally projects
        $this->info('Processing projects...');
        Project::needsRecalculation()->chunk(100, function ($projects) {
            foreach ($projects as $project) {
                $this->service->recalculateAndSave($project);
                $this->projectsProcessed++;
            }
        });
    }

    protected function processRooms($rooms, bool $force = false): void
    {
        foreach ($rooms as $room) {
            // Process locations
            foreach ($room->locations as $location) {
                // Process runs
                foreach ($location->cabinetRuns as $run) {
                    // Process cabinets
                    foreach ($run->cabinets as $cabinet) {
                        // Process sections
                        foreach ($cabinet->sections as $section) {
                            // Process components
                            foreach ($section->doors as $door) {
                                if ($force || $door->needsComplexityRecalculation()) {
                                    $this->service->recalculateAndSave($door);
                                    $this->doorsProcessed++;
                                }
                            }

                            foreach ($section->drawers as $drawer) {
                                if ($force || $drawer->needsComplexityRecalculation()) {
                                    $this->service->recalculateAndSave($drawer);
                                    $this->drawersProcessed++;
                                }
                            }

                            foreach ($section->shelves as $shelf) {
                                if ($force || $shelf->needsComplexityRecalculation()) {
                                    $this->service->recalculateAndSave($shelf);
                                    $this->shelvesProcessed++;
                                }
                            }

                            foreach ($section->pullouts as $pullout) {
                                if ($force || $pullout->needsComplexityRecalculation()) {
                                    $this->service->recalculateAndSave($pullout);
                                    $this->pulloutsProcessed++;
                                }
                            }

                            // Calculate section complexity
                            if ($force || $section->needsComplexityRecalculation()) {
                                $this->service->recalculateAndSave($section);
                                $this->sectionsProcessed++;
                            }
                        }

                        // Calculate cabinet complexity
                        if ($force || $cabinet->needsComplexityRecalculation()) {
                            $this->service->recalculateAndSave($cabinet);
                            $this->cabinetsProcessed++;
                        }
                    }

                    // Calculate run complexity
                    if ($force || $run->needsComplexityRecalculation()) {
                        $this->service->recalculateAndSave($run);
                        $this->runsProcessed++;
                    }
                }

                // Calculate location complexity
                if ($force || $location->needsComplexityRecalculation()) {
                    $this->service->recalculateAndSave($location);
                    $this->locationsProcessed++;
                }
            }

            // Calculate room complexity
            if ($force || $room->needsComplexityRecalculation()) {
                $this->service->recalculateAndSave($room);
                $this->roomsProcessed++;
            }
        }
    }
}
