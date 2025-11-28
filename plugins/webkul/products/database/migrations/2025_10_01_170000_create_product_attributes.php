<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new /**
 * extends class
 *
 */
class extends Migration
{
    private $userId;
    private $attributeIds = [];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the user ID, or null if user doesn't exist yet (will be set during erp:install)
        $this->userId = DB::table('users')->where('email', 'info@tcswoodwork.com')->value('id');

        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║              CREATING GLOBAL PRODUCT ATTRIBUTES (PHASE 1)                 ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        // Create global attributes
        $this->createAttributes();

        // Create attribute options
        $this->createAttributeOptions();

        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                     ATTRIBUTE CREATION COMPLETE                           ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    /**
     * Create global attribute definitions
     */
    private function createAttributes(): void
    {
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ CREATING ATTRIBUTE DEFINITIONS                                          │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        $attributes = [
            ['name' => 'Grit', 'type' => 'select', 'sort' => 1],
            ['name' => 'Length', 'type' => 'select', 'sort' => 2],
            ['name' => 'Size', 'type' => 'select', 'sort' => 3],
            ['name' => 'Pack Size', 'type' => 'select', 'sort' => 4],
            ['name' => 'Width', 'type' => 'select', 'sort' => 5],
            ['name' => 'Color', 'type' => 'select', 'sort' => 6],
            ['name' => 'Type', 'type' => 'select', 'sort' => 7],
            ['name' => 'Standard', 'type' => 'select', 'sort' => 8],
        ];

        foreach ($attributes as $attribute) {
            // Check if attribute already exists
            $existingId = DB::table('products_attributes')
                ->where('name', $attribute['name'])
                ->value('id');

            if ($existingId) {
                echo "  ✓ Attribute '{$attribute['name']}' already exists (ID: {$existingId})\n";
                $this->attributeIds[$attribute['name']] = $existingId;
            } else {
                $data = [
                    'name' => $attribute['name'],
                    'type' => $attribute['type'],
                    'sort' => $attribute['sort'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Only set creator_id if a user exists
                if ($this->userId) {
                    $data['creator_id'] = $this->userId;
                }

                $id = DB::table('products_attributes')->insertGetId($data);

                $this->attributeIds[$attribute['name']] = $id;
                echo "  + Created attribute: {$attribute['name']} (ID: {$id})\n";
            }
        }

        echo "\n";
    }

    /**
     * Create attribute options for each attribute
     */
    private function createAttributeOptions(): void
    {
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ CREATING ATTRIBUTE OPTIONS                                              │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        // Grit options (for sanding products)
        $this->createOptions('Grit', [
            '40', '60', '80', '100', '120', '150', '180', '220', '320'
        ]);

        // Length options (for bungee cords)
        $this->createOptions('Length', [
            '24"', '36"', '48"', '60"', '72"', '80"', '96"'
        ]);

        // Size options (for sanding discs)
        $this->createOptions('Size', [
            '5"', '6"', '8"'
        ]);

        // Pack Size options
        $this->createOptions('Pack Size', [
            '2-pack', '4-pack', '6-pack', '10-pack'
        ]);

        // Width options (for sanding rolls, label tape)
        $this->createOptions('Width', [
            '2.75"', '3"', '4"', '6mm', '9mm', '12mm', '18mm', '24mm'
        ]);

        // Color options
        $this->createOptions('Color', [
            'White', 'Clear', 'Yellow', 'Blue', 'Red', 'Black', 'Cyan', 'Magenta'
        ]);

        // Type options (generic for various product types)
        $this->createOptions('Type', [
            'PSA (Adhesive)', 'Hook & Loop', 'No Backing', 'Standard', 'Carabiner', 'D-Ring'
        ]);

        // Standard options (for tool holders)
        $this->createOptions('Standard', [
            'ISO30', 'ISO40', 'CAT40', 'BT30'
        ]);

        echo "\n";
    }

    /**
     * Create options for a specific attribute
     */
    private function createOptions(string $attributeName, array $options): void
    {
        if (!isset($this->attributeIds[$attributeName])) {
            echo "  ✗ Attribute '{$attributeName}' not found, skipping options\n";
            return;
        }

        $attributeId = $this->attributeIds[$attributeName];
        $created = 0;
        $skipped = 0;

        foreach ($options as $index => $optionName) {
            // Check if option already exists
            $exists = DB::table('products_attribute_options')
                ->where('attribute_id', $attributeId)
                ->where('name', $optionName)
                ->exists();

            if ($exists) {
                $skipped++;
            } else {
                DB::table('products_attribute_options')->insert([
                    'name' => $optionName,
                    'attribute_id' => $attributeId,
                    'sort' => $index + 1,
                    'creator_id' => $this->userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $created++;
            }
        }

        echo "  {$attributeName}: {$created} created, {$skipped} existing\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "\n";
        echo "Rolling back global product attributes...\n\n";

        $attributeNames = ['Grit', 'Length', 'Size', 'Pack Size', 'Width', 'Color', 'Type', 'Standard'];

        foreach ($attributeNames as $name) {
            $attributeId = DB::table('products_attributes')
                ->where('name', $name)
                ->value('id');

            if ($attributeId) {
                // Delete attribute options first
                $optionsDeleted = DB::table('products_attribute_options')
                    ->where('attribute_id', $attributeId)
                    ->delete();

                // Delete attribute
                DB::table('products_attributes')
                    ->where('id', $attributeId)
                    ->delete();

                echo "  - Removed attribute '{$name}' and {$optionsDeleted} options\n";
            }
        }

        echo "\nRollback complete\n";
    }
};
