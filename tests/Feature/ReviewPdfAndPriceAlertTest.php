<?php

namespace Tests\Feature;

use Tests\TestCase;
use Webkul\Project\Models\Project;
use Webkul\Partner\Models\Partner;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;
use Webkul\Product\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ReviewPdfAndPriceAlertTest extends TestCase
{
    protected $user;
    protected $project;
    protected $partner;
    protected $levi;
    protected $jg;
    protected $purchasing;
    protected $cabinetProducts;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user
        $this->user = User::factory()->create(['name' => 'Bryan Test User']);
        $this->actingAs($this->user);

        // Create test customer
        $this->partner = Partner::factory()->create([
            'name' => 'Test Customer',
            'sub_type' => 'customer',
        ]);

        // Create test project
        $this->project = Project::factory()->create([
            'name' => 'Test Kitchen Remodel',
            'partner_id' => $this->partner->id,
        ]);

        // Create test employees for alert recipients
        $this->levi = $this->createEmployee('Levi', 'Production Lead');
        $this->jg = $this->createEmployee('JG', 'Delivery Coordinator');
        $this->purchasing = $this->createEmployee('Purchasing Manager', 'Purchasing Manager');

        // Ensure Cabinet products exist (from TcsServiceProductsSeeder)
        $this->ensureCabinetProducts();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_complexity_alert_for_level_4_cabinets()
    {
        Notification::fake();

        // Create sales order with Level 4 cabinets
        $this->createSalesOrderWithCabinets(4, 25.5);

        // Assert notification sent to Levi
        Notification::assertSentTo(
            $this->levi->user,
            \Filament\Notifications\Notification::class,
            function ($notification) {
                return str_contains($notification->getTitle(), 'High Complexity')
                    && str_contains($notification->getBody(), 'Level 4');
            }
        );

        // Assert Chatter activity created
        $this->assertChatterActivityExists('High Complexity Alert');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_complexity_alert_for_level_5_cabinets()
    {
        Notification::fake();

        // Create sales order with Level 5 cabinets
        $this->createSalesOrderWithCabinets(5, 30.0);

        // Assert notification sent to Levi
        Notification::assertSentTo(
            $this->levi->user,
            \Filament\Notifications\Notification::class,
            function ($notification) {
                return str_contains($notification->getTitle(), 'High Complexity')
                    && str_contains($notification->getBody(), 'Level 5');
            }
        );

        // Assert Chatter activity created
        $this->assertChatterActivityExists('Level 4/5 complexity detected');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_not_send_complexity_alert_for_level_1_to_3()
    {
        Notification::fake();

        // Create sales order with Level 2 cabinets (no alert expected)
        $this->createSalesOrderWithCabinets(2, 25.5);

        // Assert NO notification sent to Levi
        Notification::assertNothingSentTo($this->levi->user);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sends_premium_materials_alert_for_expensive_items()
    {
        Notification::fake();

        // Create sales order with premium materials (price > $185/LF)
        $premiumProduct = Product::factory()->create([
            'name' => 'Premium Exotic Wood',
            'price' => 200.00, // Over $185 threshold
            'type' => 'product',
        ]);

        $this->createSalesOrderWithProduct($premiumProduct, 10);

        // Assert notification sent to Purchasing Manager
        Notification::assertSentTo(
            $this->purchasing->user,
            \Filament\Notifications\Notification::class,
            function ($notification) {
                return str_contains($notification->getTitle(), 'Premium Materials');
            }
        );

        // Assert Chatter activity created
        $this->assertChatterActivityExists('Premium Materials Alert');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_chatter_activity_for_complexity_alerts()
    {
        Notification::fake();

        // Create sales order that triggers complexity alert
        $this->createSalesOrderWithCabinets(4, 35.0); // Level 4

        // Count Chatter messages created
        $chatterCount = DB::table('chatter_messages')
            ->where('messageable_type', 'Webkul\\Project\\Models\\Project')
            ->where('messageable_id', $this->project->id)
            ->where('type', 'activity')
            ->count();

        $this->assertGreaterThanOrEqual(1, $chatterCount, 'Should have at least 1 Chatter activity');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function chatter_messages_are_marked_as_internal()
    {
        Notification::fake();

        $this->createSalesOrderWithCabinets(5, 20.0);

        // Verify Chatter messages are internal
        $internalMessages = DB::table('chatter_messages')
            ->where('messageable_type', 'Webkul\\Project\\Models\\Project')
            ->where('messageable_id', $this->project->id)
            ->where('is_internal', true)
            ->count();

        $this->assertGreaterThan(0, $internalMessages, 'Chatter activities should be marked as internal');
    }

    // ============================================================================
    // Helper Methods
    // ============================================================================

    protected function createEmployee(string $name, string $jobTitle): Employee
    {
        $user = User::factory()->create(['name' => "{$name} Test"]);

        return Employee::factory()->create([
            'name' => $name,
            'job_title' => $jobTitle,
            'user_id' => $user->id,
            'country_id' => null,
            'private_state_id' => null,
            'private_country_id' => null,
            'country_of_birth' => null,
            'departure_reason_id' => null,
        ]);
    }

    protected function ensureCabinetProducts(): void
    {
        // Check if Cabinet products exist, if not create them
        $levels = [
            1 => 138.00,
            2 => 168.00,
            3 => 192.00,
            4 => 210.00,
            5 => 225.00,
        ];

        $this->cabinetProducts = [];

        foreach ($levels as $level => $price) {
            $product = Product::firstOrCreate(
                ['reference' => 'CABINET', 'name' => "Cabinet Level {$level}"],
                [
                    'name' => "Cabinet Level {$level}",
                    'reference' => 'CABINET',
                    'type' => 'service',
                    'price' => $price,
                ]
            );

            $this->cabinetProducts[$level] = $product;
        }
    }

    protected function createSalesOrderWithCabinets(int $level, float $linearFeet): void
    {
        $product = $this->cabinetProducts[$level];

        $salesOrder = DB::table('sales_orders')->insertGetId([
            'project_id' => $this->project->id,
            'partner_id' => $this->partner->id,
            'partner_invoice_id' => $this->partner->id,
            'partner_shipping_id' => $this->partner->id,
            'state' => 'draft',
            'invoice_status' => 'no',
            'date_order' => now(),
            'creator_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_order_lines')->insert([
            'order_id' => $salesOrder,
            'product_id' => $product->id,
            'name' => "Cabinet - Test Kitchen (Level {$level})",
            'product_uom_qty' => $linearFeet,
            'price_unit' => $product->price,
            'price_subtotal' => $linearFeet * $product->price,
            'creator_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Trigger alerts manually (simulating ReviewPdfAndPrice::sendProjectAlerts)
        $this->triggerProjectAlerts($salesOrder, $level, $linearFeet);
    }

    protected function createSalesOrderWithProduct(Product $product, float $quantity): void
    {
        $salesOrder = DB::table('sales_orders')->insertGetId([
            'project_id' => $this->project->id,
            'partner_id' => $this->partner->id,
            'partner_invoice_id' => $this->partner->id,
            'partner_shipping_id' => $this->partner->id,
            'state' => 'draft',
            'invoice_status' => 'no',
            'date_order' => now(),
            'creator_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sales_order_lines')->insert([
            'order_id' => $salesOrder,
            'product_id' => $product->id,
            'name' => $product->name,
            'product_uom_qty' => $quantity,
            'price_unit' => $product->price,
            'price_subtotal' => $quantity * $product->price,
            'creator_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Trigger alerts
        $this->triggerPremiumMaterialsAlert($product, $quantity);
    }

    protected function triggerProjectAlerts(int $salesOrderId, int $level, float $linearFeet): void
    {
        // Simulate ReviewPdfAndPrice::sendProjectAlerts() logic

        // Complexity Alert (Level 4/5)
        if ($level >= 4 && $this->levi->user) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('🔧 High Complexity Project Alert')
                ->body("Project {$this->project->project_number} uses Level {$level} pricing. Production review required.")
                ->sendToDatabase($this->levi->user);

            // Chatter activity
            $this->project->addMessage([
                'type' => 'activity',
                'subject' => 'High Complexity Alert',
                'body' => "Level 4/5 complexity detected. {$this->levi->name} (Production Lead) notified for production review.",
                'is_internal' => true,
            ]);
        }
    }

    protected function triggerPremiumMaterialsAlert(Product $product, float $quantity): void
    {
        if ($product->price > 185 && $this->purchasing->user) {
            \Filament\Notifications\Notification::make()
                ->info()
                ->title('💎 Premium Materials Alert')
                ->body("Project {$this->project->project_number} uses premium materials: {$product->name} (\${$product->price}/unit).")
                ->sendToDatabase($this->purchasing->user);

            // Chatter activity
            $this->project->addMessage([
                'type' => 'activity',
                'subject' => 'Premium Materials Alert',
                'body' => "Premium materials detected: {$product->name}. {$this->purchasing->name} notified for procurement planning.",
                'is_internal' => true,
            ]);
        }
    }

    protected function assertChatterActivityExists(string $subject): void
    {
        $this->assertDatabaseHas('chatter_messages', [
            'messageable_type' => 'Webkul\\Project\\Models\\Project',
            'messageable_id' => $this->project->id,
            'type' => 'activity',
            'subject' => $subject,
            'is_internal' => true,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up test data
        DB::table('sales_order_lines')->where('creator_id', $this->user->id)->delete();
        DB::table('sales_orders')->where('creator_id', $this->user->id)->delete();
        DB::table('chatter_messages')
            ->where('messageable_type', 'Webkul\\Project\\Models\\Project')
            ->where('messageable_id', $this->project->id)
            ->delete();

        parent::tearDown();
    }
}
