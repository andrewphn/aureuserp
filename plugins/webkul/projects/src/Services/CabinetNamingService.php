<?php

namespace Webkul\Project\Services;

/**
 * Cabinet Naming Service
 * 
 * Handles auto-naming logic for rooms, locations, runs, and cabinets
 */
class CabinetNamingService
{
    /**
     * Room type labels
     */
    protected static array $roomTypes = [
        'kitchen' => 'Kitchen',
        'bathroom' => 'Bathroom',
        'laundry' => 'Laundry',
        'pantry' => 'Pantry',
        'closet' => 'Closet',
        'mudroom' => 'Mudroom',
        'office' => 'Office',
        'bedroom' => 'Bedroom',
        'living_room' => 'Living Room',
        'dining_room' => 'Dining Room',
        'garage' => 'Garage',
        'basement' => 'Basement',
        'other' => 'Other',
    ];

    /**
     * Location type labels
     */
    protected static array $locationTypes = [
        'wall' => 'Wall',
        'island' => 'Island',
        'peninsula' => 'Peninsula',
        'corner' => 'Corner',
        'alcove' => 'Alcove',
        'sink_wall' => 'Sink Wall',
        'range_wall' => 'Range Wall',
        'refrigerator_wall' => 'Refrigerator Wall',
    ];

    /**
     * Run type labels
     */
    protected static array $runTypes = [
        'base' => 'Base Cabinets',
        'wall' => 'Wall Cabinets',
        'tall' => 'Tall Cabinets',
        'island' => 'Island',
    ];

    /**
     * Generate auto-name for a new room
     * 
     * @param string $roomType
     * @param array $existingRooms
     * @return string
     */
    public static function generateRoomName(string $roomType, array $existingRooms = []): string
    {
        $typeLabel = static::$roomTypes[$roomType] ?? 'Room';

        // Count existing rooms of this type
        $count = 0;
        foreach ($existingRooms as $room) {
            if (($room['room_type'] ?? '') === $roomType) {
                $count++;
            }
        }

        // If this is the first room of this type, just use the type name
        // Otherwise, add a number
        if ($count === 0) {
            return $typeLabel;
        }

        return $typeLabel . ' ' . ($count + 1);
    }

    /**
     * Generate auto-name for a new location within a room
     * 
     * @param string $locationType
     * @param array $room
     * @return string
     */
    public static function generateLocationName(string $locationType, array $room = []): string
    {
        $typeLabel = static::$locationTypes[$locationType] ?? 'Location';
        $existingLocations = $room['children'] ?? [];

        // Count existing locations of this type
        $count = 0;
        foreach ($existingLocations as $loc) {
            if (($loc['location_type'] ?? '') === $locationType) {
                $count++;
            }
        }

        // Use letters for wall positions (Wall A, Wall B, Wall C)
        // Or numbers for other types (Island 1, Island 2)
        if ($locationType === 'wall') {
            $letter = chr(65 + $count); // A, B, C, D...
            return $typeLabel . ' ' . $letter;
        }

        if ($count === 0) {
            return $typeLabel;
        }

        return $typeLabel . ' ' . ($count + 1);
    }

    /**
     * Generate auto-name for a new cabinet run within a location
     * 
     * @param string $runType
     * @param array $location
     * @return string
     */
    public static function generateRunName(string $runType, array $location = []): string
    {
        $typeLabel = static::$runTypes[$runType] ?? 'Cabinet Run';
        $existingRuns = $location['children'] ?? [];

        // Count existing runs of this type
        $count = 0;
        foreach ($existingRuns as $run) {
            if (($run['run_type'] ?? '') === $runType) {
                $count++;
            }
        }

        // Always include number for clarity: "Base Cabinets 1", "Wall Cabinets 1"
        return $typeLabel . ' ' . ($count + 1);
    }

    /**
     * Get naming prefix based on run type
     * B = Base, W = Wall, T = Tall, I = Island
     * 
     * @param string $runType
     * @return string
     */
    public static function getRunPrefix(string $runType): string
    {
        return match (strtolower($runType)) {
            'base' => 'B',
            'wall' => 'W',
            'tall' => 'T',
            'island' => 'I',
            default => 'C', // Generic cabinet
        };
    }

    /**
     * Get next auto-generated cabinet number for a run
     * Format: B1, B2, B3... for base; W1, W2... for wall; etc.
     * 
     * @param array $runNode The run node data
     * @param string $runType
     * @return string
     */
    public static function getNextCabinetNumber(array $runNode, string $runType): string
    {
        $prefix = static::getRunPrefix($runType);
        $existingCabinets = $runNode['children'] ?? [];

        // Count existing cabinets with same prefix
        $count = 0;
        foreach ($existingCabinets as $cabinet) {
            $name = $cabinet['name'] ?? '';
            if (preg_match('/^' . $prefix . '(\d+)/', strtoupper($name), $matches)) {
                $num = (int) $matches[1];
                if ($num > $count) {
                    $count = $num;
                }
            }
        }

        return $prefix . ($count + 1);
    }
}
