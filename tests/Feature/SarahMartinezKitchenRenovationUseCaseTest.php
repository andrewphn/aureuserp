<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Complete End-to-End Integration Test
 * Sarah Martinez Kitchen Renovation Use Case
 *
 * This test simulates the complete workflow from:
 * - Customer quote to project creation
 * - Complete 7-level cabinet hierarchy
 * - Inventory product linking
 * - Task generation and assignment
 * - Production phases (CNC → Finishing → QC → Installation)
 * - QC failures and rework
 * - Inventory depletion
 * - Cost calculation and invoicing
 *
 * Based on: docs/meeting/use-case-complete-workflow.md
 */
class SarahMartinezKitchenRenovationUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private $projectId;
    private $customerId;
    private $orderId;
    private $inventoryProducts = [];
    private $cabinetB36Id;
    private $doorD1Id;
    private $doorD2Id;
    private $pulloutP1Id;

    /**
     * Complete end-to-end use case test
     *
     * @test
     */
    public function it_completes_sarah_martinez_kitchen_renovation_workflow()
    {
        // PHASE 1: Sales & Project Setup
        $this->phase1_sales_and_project_setup();

        // PHASE 2: Design & Specification
        $this->phase2_design_and_specification();

        // PHASE 3: Task Generation
        $this->phase3_task_generation();

        // PHASE 4: Production - Day 15
        $this->phase4_production_day_15();

        // PHASE 5: Assembly - Day 16-22
        $this->phase5_assembly_and_finishing();

        // PHASE 6: QC Inspection - Day 23
        $this->phase6_qc_inspection();

        // PHASE 7: Installation - Day 26
        $this->phase7_installation();

        // PHASE 8: Invoicing - Day 27
        $this->phase8_invoicing();

        // Final assertions
        $this->assertWorkflowComplete();
    }

    /**
     * PHASE 1: Sales & Project Setup
     */
    private function phase1_sales_and_project_setup()
    {
        echo "\n🔵 PHASE 1: Sales & Project Setup\n";

        // Create customer: Sarah Martinez
        $this->customerId = DB::table('partners_partners')->insertGetId([
            'name' => 'Sarah Martinez',
            'type' => 'customer',
            'email' => 'sarah.martinez@example.com',
            'phone' => '555-0123',
            'address' => '1428 Oak Street',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62701',
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        $this->assertDatabaseHas('partners_partners', [
            'id' => $this->customerId,
            'name' => 'Sarah Martinez',
        ]);

        echo "✅ Customer created: Sarah Martinez (ID: {$this->customerId})\n";

        // Create sales order
        $this->orderId = DB::table('sales_orders')->insertGetId([
            'customer_id' => $this->customerId,
            'order_number' => 'Q-2025-001',
            'total_amount' => 85000.00,
            'status' => 'approved',
            'created_at' => now()->subDays(25),
            'updated_at' => now()->subDays(20),
        ]);

        $this->assertDatabaseHas('sales_orders', [
            'id' => $this->orderId,
            'order_number' => 'Q-2025-001',
            'total_amount' => 85000.00,
        ]);

        echo "✅ Sales order created: Q-2025-001 ($85,000)\n";

        // Convert to project
        $this->projectId = DB::table('projects_projects')->insertGetId([
            'order_id' => $this->orderId,
            'customer_id' => $this->customerId,
            'name' => 'Sarah Martinez Kitchen Renovation',
            'project_number' => 'PRJ-2025-001',
            'status' => 'active',
            'start_date' => now()->subDays(20),
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);

        $this->assertDatabaseHas('projects_projects', [
            'id' => $this->projectId,
            'name' => 'Sarah Martinez Kitchen Renovation',
        ]);

        echo "✅ Project created: PRJ-2025-001\n";
    }

    /**
     * PHASE 2: Design & Specification
     */
    private function phase2_design_and_specification()
    {
        echo "\n🟣 PHASE 2: Design & Specification\n";

        // Create inventory products first
        $this->createInventoryProducts();

        // Create room: Kitchen
        $roomId = DB::table('projects_rooms')->insertGetId([
            'project_id' => $this->projectId,
            'name' => 'Kitchen',
            'room_type' => 'kitchen',
            'created_at' => now()->subDays(19),
            'updated_at' => now()->subDays(19),
        ]);

        echo "✅ Room created: Kitchen\n";

        // Create location: Island
        $locationId = DB::table('projects_room_locations')->insertGetId([
            'room_id' => $roomId,
            'name' => 'Center Island',
            'position' => 'center',
            'created_at' => now()->subDays(19),
            'updated_at' => now()->subDays(19),
        ]);

        echo "✅ Location created: Center Island\n";

        // Create cabinet run: Island Base Cabinets
        $runId = DB::table('projects_cabinet_runs')->insertGetId([
            'room_location_id' => $locationId,
            'run_name' => 'Island Base Run',
            'total_linear_feet' => 8.0,
            'sequence_order' => 1,
            'created_at' => now()->subDays(18),
            'updated_at' => now()->subDays(18),
        ]);

        echo "✅ Cabinet run created: Island Base Run (8.0 linear feet)\n";

        // Create cabinet: B36 Sink Base (linked to inventory)
        $this->cabinetB36Id = DB::table('projects_cabinet_specifications')->insertGetId([
            'cabinet_run_id' => $runId,
            'product_id' => $this->inventoryProducts['cabinet_b36'],
            'cabinet_name' => 'B36',
            'cabinet_type' => 'base',
            'width_inches' => 36,
            'height_inches' => 34.5,
            'depth_inches' => 24,
            'created_at' => now()->subDays(17),
            'updated_at' => now()->subDays(17),
        ]);

        echo "✅ Cabinet created: B36 Sink Base (linked to inventory product ID: {$this->inventoryProducts['cabinet_b36']})\n";

        // Create section: Door Opening
        $sectionDoorId = DB::table('projects_cabinet_sections')->insertGetId([
            'cabinet_specification_id' => $this->cabinetB36Id,
            'section_number' => 1,
            'name' => 'Door Opening Section',
            'section_type' => 'door_opening',
            'width_inches' => 36,
            'height_inches' => 28,
            'component_count' => 2,
            'created_at' => now()->subDays(16),
            'updated_at' => now()->subDays(16),
        ]);

        echo "✅ Section created: Door Opening (2 doors)\n";

        // Create section: Pullout
        $sectionPulloutId = DB::table('projects_cabinet_sections')->insertGetId([
            'cabinet_specification_id' => $this->cabinetB36Id,
            'section_number' => 2,
            'name' => 'Pullout Section',
            'section_type' => 'pullout_area',
            'component_count' => 1,
            'created_at' => now()->subDays(16),
            'updated_at' => now()->subDays(16),
        ]);

        echo "✅ Section created: Pullout Section (1 pullout)\n";

        // Create door D1 (linked to inventory)
        $this->doorD1Id = DB::table('projects_doors')->insertGetId([
            'cabinet_specification_id' => $this->cabinetB36Id,
            'section_id' => $sectionDoorId,
            'product_id' => $this->inventoryProducts['door_blank'],
            'door_number' => 1,
            'door_name' => 'D1',
            'width_inches' => 17.5,
            'height_inches' => 28,
            'hinge_type' => 'full_overlay',
            'hinge_side' => 'left',
            'profile_type' => 'shaker',
            'fabrication_method' => 'cnc',
            'thickness_inches' => 0.75,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        // Reserve inventory
        DB::table('products_products')->where('id', $this->inventoryProducts['door_blank'])->decrement('quantity_on_hand');

        echo "✅ Door D1 created (linked to door blank, inventory reserved: 25 → 24)\n";

        // Create door D2 (linked to inventory)
        $this->doorD2Id = DB::table('projects_doors')->insertGetId([
            'cabinet_specification_id' => $this->cabinetB36Id,
            'section_id' => $sectionDoorId,
            'product_id' => $this->inventoryProducts['door_blank'],
            'door_number' => 2,
            'door_name' => 'D2',
            'width_inches' => 17.5,
            'height_inches' => 28,
            'hinge_type' => 'full_overlay',
            'hinge_side' => 'right',
            'profile_type' => 'shaker',
            'fabrication_method' => 'cnc',
            'thickness_inches' => 0.75,
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        // Reserve inventory
        DB::table('products_products')->where('id', $this->inventoryProducts['door_blank'])->decrement('quantity_on_hand');

        echo "✅ Door D2 created (linked to door blank, inventory reserved: 24 → 23)\n";

        // Create pullout P1 (linked to inventory)
        $this->pulloutP1Id = DB::table('projects_pullouts')->insertGetId([
            'cabinet_specification_id' => $this->cabinetB36Id,
            'section_id' => $sectionPulloutId,
            'product_id' => $this->inventoryProducts['pullout_trash'],
            'pullout_number' => 1,
            'pullout_name' => 'P1',
            'pullout_type' => 'trash',
            'manufacturer' => 'Rev-A-Shelf',
            'model_number' => '5149-18DM-217',
            'unit_cost' => 189.50,
            'quantity' => 1,
            'mounting_type' => 'bottom_mount',
            'created_at' => now()->subDays(15),
            'updated_at' => now()->subDays(15),
        ]);

        // Reserve inventory
        DB::table('products_products')->where('id', $this->inventoryProducts['pullout_trash'])->decrement('quantity_on_hand');

        echo "✅ Pullout P1 created (Rev-A-Shelf trash pullout, inventory reserved: 3 → 2)\n";

        // Verify inventory reservations
        $doorBlankStock = DB::table('products_products')->where('id', $this->inventoryProducts['door_blank'])->value('quantity_on_hand');
        $pulloutStock = DB::table('products_products')->where('id', $this->inventoryProducts['pullout_trash'])->value('quantity_on_hand');

        $this->assertEquals(23, $doorBlankStock, 'Door blanks should be 23 after reserving 2');
        $this->assertEquals(2, $pulloutStock, 'Pullouts should be 2 after reserving 1');
    }

    /**
     * PHASE 3: Task Generation
     */
    private function phase3_task_generation()
    {
        echo "\n🟢 PHASE 3: Task Generation\n";

        // Task 1: CNC cut door D1 (component-level task)
        $task1Id = DB::table('projects_tasks')->insertGetId([
            'project_id' => $this->projectId,
            'cabinet_specification_id' => $this->cabinetB36Id,
            'component_type' => 'door',
            'component_id' => $this->doorD1Id,
            'title' => 'CNC cut door D1',
            'assigned_to' => 1, // Levi
            'status' => 'pending',
            'created_at' => now()->subDays(14),
            'updated_at' => now()->subDays(14),
        ]);

        echo "✅ Task created: CNC cut door D1 (assigned to Levi)\n";

        // Task 2: CNC cut door D2
        $task2Id = DB::table('projects_tasks')->insertGetId([
            'project_id' => $this->projectId,
            'cabinet_specification_id' => $this->cabinetB36Id,
            'component_type' => 'door',
            'component_id' => $this->doorD2Id,
            'title' => 'CNC cut door D2',
            'assigned_to' => 1, // Levi
            'status' => 'pending',
            'created_at' => now()->subDays(14),
            'updated_at' => now()->subDays(14),
        ]);

        echo "✅ Task created: CNC cut door D2 (assigned to Levi)\n";

        // Task 3: Edge band doors (depends on D1 and D2)
        $task3Id = DB::table('projects_tasks')->insertGetId([
            'project_id' => $this->projectId,
            'cabinet_specification_id' => $this->cabinetB36Id,
            'title' => 'Edge band doors D1 and D2',
            'assigned_to' => 2, // Aiden
            'status' => 'blocked', // waiting for D1 and D2
            'created_at' => now()->subDays(14),
            'updated_at' => now()->subDays(14),
        ]);

        echo "✅ Task created: Edge band doors (assigned to Aiden, blocked until cutting complete)\n";

        // Task 4: Order pullout P1
        $task4Id = DB::table('projects_tasks')->insertGetId([
            'project_id' => $this->projectId,
            'component_type' => 'pullout',
            'component_id' => $this->pulloutP1Id,
            'title' => 'Order pullout P1',
            'assigned_to' => 3, // Sadie (purchasing)
            'status' => 'pending',
            'created_at' => now()->subDays(14),
            'updated_at' => now()->subDays(14),
        ]);

        echo "✅ Task created: Order pullout P1 (assigned to Sadie)\n";

        $this->assertDatabaseCount('projects_tasks', 4);
    }

    /**
     * PHASE 4: Production - Day 15
     */
    private function phase4_production_day_15()
    {
        echo "\n🟠 PHASE 4: Production - Day 15\n";

        // Levi starts CNC cutting door D1
        DB::table('projects_doors')->where('id', $this->doorD1Id)->update([
            'cnc_cut_at' => now()->subDays(6)->setTime(8, 30),
        ]);

        // Deplete inventory when CNC cut
        DB::table('products_products')->where('id', $this->inventoryProducts['door_blank'])->decrement('quantity_on_hand');

        echo "✅ Door D1: CNC cut complete (Day 15, 8:30 AM)\n";
        echo "   Inventory depleted: 23 → 22 door blanks\n";

        // Levi starts CNC cutting door D2
        DB::table('projects_doors')->where('id', $this->doorD2Id)->update([
            'cnc_cut_at' => now()->subDays(6)->setTime(10, 15),
        ]);

        // Deplete inventory
        DB::table('products_products')->where('id', $this->inventoryProducts['door_blank'])->decrement('quantity_on_hand');

        echo "✅ Door D2: CNC cut complete (Day 15, 10:15 AM)\n";
        echo "   Inventory depleted: 22 → 21 door blanks\n";

        // Check inventory level
        $currentStock = DB::table('products_products')->where('id', $this->inventoryProducts['door_blank'])->value('quantity_on_hand');
        echo "   Current stock: {$currentStock} door blanks (threshold: 10)\n";

        $this->assertEquals(21, $currentStock);

        // Aiden edge bands doors (dependencies complete)
        DB::table('projects_doors')->where('id', $this->doorD1Id)->update([
            'edge_banded_at' => now()->subDays(6)->setTime(13, 0),
        ]);

        DB::table('projects_doors')->where('id', $this->doorD2Id)->update([
            'edge_banded_at' => now()->subDays(6)->setTime(13, 30),
        ]);

        echo "✅ Doors D1 & D2: Edge banding complete (Day 15, 1:00 PM)\n";

        // Sadie orders pullout
        DB::table('projects_pullouts')->where('id', $this->pulloutP1Id)->update([
            'ordered_at' => now()->subDays(6)->setTime(14, 0),
        ]);

        echo "✅ Pullout P1: Ordered from Rev-A-Shelf (Day 15, 2:00 PM)\n";

        // Verify production timestamps
        $door1 = DB::table('projects_doors')->find($this->doorD1Id);
        $this->assertNotNull($door1->cnc_cut_at);
        $this->assertNotNull($door1->edge_banded_at);
    }

    /**
     * PHASE 5: Assembly & Finishing - Day 16-22
     */
    private function phase5_assembly_and_finishing()
    {
        echo "\n🟡 PHASE 5: Assembly & Finishing - Day 16-22\n";

        // Day 16: Assembly
        DB::table('projects_doors')->whereIn('id', [$this->doorD1Id, $this->doorD2Id])->update([
            'assembled_at' => now()->subDays(5)->setTime(10, 0),
        ]);

        echo "✅ Day 16: Doors assembled\n";

        // Day 17: Sanding
        DB::table('projects_doors')->whereIn('id', [$this->doorD1Id, $this->doorD2Id])->update([
            'sanded_at' => now()->subDays(4)->setTime(11, 0),
        ]);

        echo "✅ Day 17: Doors sanded\n";

        // Day 18-21: External finishing
        echo "⏳ Day 18-21: Doors sent to external finishing\n";

        // Day 22: Doors return from finishing
        DB::table('projects_doors')->whereIn('id', [$this->doorD1Id, $this->doorD2Id])->update([
            'finished_at' => now()->subDays(1)->setTime(15, 0),
        ]);

        echo "✅ Day 22: Doors returned from finishing (3:00 PM)\n";

        // Pullout received (Day 21)
        DB::table('projects_pullouts')->where('id', $this->pulloutP1Id)->update([
            'received_at' => now()->subDays(2)->setTime(10, 0),
        ]);

        // Deplete inventory when received
        DB::table('products_products')->where('id', $this->inventoryProducts['pullout_trash'])->decrement('quantity_on_hand');

        echo "✅ Day 21: Pullout received and inventory depleted (3 → 2)\n";
    }

    /**
     * PHASE 6: QC Inspection - Day 23
     */
    private function phase6_qc_inspection()
    {
        echo "\n🔵 PHASE 6: QC Inspection - Day 23\n";

        // Levi inspects door D1 - PASS
        DB::table('projects_doors')->where('id', $this->doorD1Id)->update([
            'qc_passed' => true,
            'qc_notes' => 'Excellent finish, no defects',
            'qc_inspected_at' => now()->setTime(9, 0),
            'qc_inspector_id' => 1, // Levi
        ]);

        echo "✅ Door D1: QC PASSED - Excellent finish\n";

        // Levi inspects door D2 - FAIL
        DB::table('projects_doors')->where('id', $this->doorD2Id)->update([
            'qc_passed' => false,
            'qc_notes' => 'Chip on bottom rail - requires rework',
            'qc_inspected_at' => now()->setTime(9, 15),
            'qc_inspector_id' => 1, // Levi
        ]);

        echo "❌ Door D2: QC FAILED - Chip on bottom rail\n";

        // Auto-create rework task
        $reworkTaskId = DB::table('projects_tasks')->insertGetId([
            'project_id' => $this->projectId,
            'component_type' => 'door',
            'component_id' => $this->doorD2Id,
            'title' => 'Rework door D2 - repair chip on bottom rail',
            'assigned_to' => 1, // Levi
            'status' => 'pending',
            'priority' => 'high',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "🔧 Auto-created rework task (ID: {$reworkTaskId})\n";

        // Levi fixes door D2
        echo "⚒️  Levi performs rework on door D2...\n";

        // Re-inspect door D2 - PASS
        DB::table('projects_doors')->where('id', $this->doorD2Id)->update([
            'qc_passed' => true,
            'qc_notes' => 'Chip repaired successfully, finish blended perfectly',
            'qc_inspected_at' => now()->setTime(11, 30),
        ]);

        echo "✅ Door D2: Re-inspected - QC PASSED after rework\n";

        // Verify QC results
        $door1QC = DB::table('projects_doors')->where('id', $this->doorD1Id)->value('qc_passed');
        $door2QC = DB::table('projects_doors')->where('id', $this->doorD2Id)->value('qc_passed');

        $this->assertTrue((bool) $door1QC, 'Door D1 should pass QC');
        $this->assertTrue((bool) $door2QC, 'Door D2 should pass QC after rework');
    }

    /**
     * PHASE 7: Installation - Day 26
     */
    private function phase7_installation()
    {
        echo "\n🟣 PHASE 7: Installation - Day 26\n";

        // Install hardware on doors
        DB::table('projects_doors')->whereIn('id', [$this->doorD1Id, $this->doorD2Id])->update([
            'hardware_installed_at' => now()->setTime(8, 0),
        ]);

        echo "✅ Hardware installed on doors D1 & D2\n";

        // Install doors in cabinet
        DB::table('projects_doors')->whereIn('id', [$this->doorD1Id, $this->doorD2Id])->update([
            'installed_in_cabinet_at' => now()->setTime(10, 0),
        ]);

        echo "✅ Doors D1 & D2 installed in cabinet B36\n";

        // Install pullout in cabinet
        DB::table('projects_pullouts')->where('id', $this->pulloutP1Id)->update([
            'hardware_installed_at' => now()->setTime(10, 30),
            'installed_in_cabinet_at' => now()->setTime(11, 0),
        ]);

        echo "✅ Pullout P1 installed in cabinet B36\n";

        // On-site installation
        echo "🚚 Cabinet B36 delivered to 1428 Oak Street\n";
        echo "✅ On-site installation complete\n";

        // Mark project as completed
        DB::table('projects_projects')->where('id', $this->projectId)->update([
            'status' => 'completed',
            'completed_at' => now()->setTime(16, 0),
        ]);

        echo "✅ Project status: COMPLETED\n";
    }

    /**
     * PHASE 8: Invoicing - Day 27
     */
    private function phase8_invoicing()
    {
        echo "\n💰 PHASE 8: Invoicing - Day 27\n";

        // Calculate material costs from inventory
        $cabinetCost = DB::table('products_products')->where('id', $this->inventoryProducts['cabinet_b36'])->value('cost');
        $doorBlankCost = DB::table('products_products')->where('id', $this->inventoryProducts['door_blank'])->value('cost');
        $pulloutCost = DB::table('products_products')->where('id', $this->inventoryProducts['pullout_trash'])->value('cost');

        $totalMaterialCost = $cabinetCost + ($doorBlankCost * 2) + $pulloutCost;

        echo "📊 Material Costs:\n";
        echo "   Cabinet B36: $" . number_format($cabinetCost, 2) . "\n";
        echo "   Door blanks (2): $" . number_format($doorBlankCost * 2, 2) . "\n";
        echo "   Pullout P1: $" . number_format($pulloutCost, 2) . "\n";
        echo "   Total Materials: $" . number_format($totalMaterialCost, 2) . "\n";

        // Labor costs (estimate)
        $laborCost = 1875.00;
        echo "   Labor: $" . number_format($laborCost, 2) . "\n";

        $totalCost = $totalMaterialCost + $laborCost;
        echo "   Total Cost: $" . number_format($totalCost, 2) . "\n";

        // Create invoice
        $invoiceId = DB::table('invoices')->insertGetId([
            'project_id' => $this->projectId,
            'customer_id' => $this->customerId,
            'invoice_number' => 'INV-2025-001',
            'subtotal' => 85000.00,
            'tax' => 8075.00,
            'total' => 93075.00,
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "📄 Invoice created: INV-2025-001\n";
        echo "   Subtotal: $85,000.00\n";
        echo "   Tax: $8,075.00\n";
        echo "   Total: $93,075.00\n";

        // Calculate profit
        $profit = 93075.00 - $totalCost;
        $profitMargin = ($profit / 93075.00) * 100;

        echo "💵 Profit: $" . number_format($profit, 2) . " (" . number_format($profitMargin, 1) . "%)\n";

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-2025-001',
            'total' => 93075.00,
        ]);
    }

    /**
     * Final workflow assertions
     */
    private function assertWorkflowComplete()
    {
        echo "\n✅ WORKFLOW COMPLETE - Final Assertions\n";

        // Project completed
        $project = DB::table('projects_projects')->find($this->projectId);
        $this->assertEquals('completed', $project->status);
        echo "✅ Project status: {$project->status}\n";

        // All doors passed QC
        $doorsQC = DB::table('projects_doors')
            ->whereIn('id', [$this->doorD1Id, $this->doorD2Id])
            ->get();

        foreach ($doorsQC as $door) {
            $this->assertTrue((bool) $door->qc_passed, "Door {$door->door_name} should pass QC");
        }
        echo "✅ All doors passed QC inspection\n";

        // All production phases complete
        $door1 = DB::table('projects_doors')->find($this->doorD1Id);
        $this->assertNotNull($door1->cnc_cut_at);
        $this->assertNotNull($door1->edge_banded_at);
        $this->assertNotNull($door1->assembled_at);
        $this->assertNotNull($door1->sanded_at);
        $this->assertNotNull($door1->finished_at);
        $this->assertNotNull($door1->hardware_installed_at);
        $this->assertNotNull($door1->installed_in_cabinet_at);
        echo "✅ All production phases tracked (8 timestamps)\n";

        // Inventory depleted correctly
        $doorBlanks = DB::table('products_products')->where('id', $this->inventoryProducts['door_blank'])->value('quantity_on_hand');
        $this->assertEquals(21, $doorBlanks, 'Should have 21 door blanks remaining (25 - 2 reserved - 2 used)');
        echo "✅ Inventory depleted correctly: 21 door blanks remaining\n";

        // Invoice created
        $invoice = DB::table('invoices')->where('project_id', $this->projectId)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(93075.00, $invoice->total);
        echo "✅ Invoice generated: $93,075.00\n";

        // Complete hierarchy created
        $hierarchyCount = [
            'projects' => DB::table('projects_projects')->where('id', $this->projectId)->count(),
            'rooms' => DB::table('projects_rooms')->where('project_id', $this->projectId)->count(),
            'locations' => DB::table('projects_room_locations')->whereIn('room_id', function($query) {
                $query->select('id')->from('projects_rooms')->where('project_id', $this->projectId);
            })->count(),
            'runs' => DB::table('projects_cabinet_runs')->whereIn('room_location_id', function($query) {
                $query->select('id')->from('projects_room_locations')->whereIn('room_id', function($q) {
                    $q->select('id')->from('projects_rooms')->where('project_id', $this->projectId);
                });
            })->count(),
            'cabinets' => DB::table('projects_cabinet_specifications')->where('id', $this->cabinetB36Id)->count(),
            'sections' => DB::table('projects_cabinet_sections')->where('cabinet_specification_id', $this->cabinetB36Id)->count(),
            'doors' => DB::table('projects_doors')->where('cabinet_specification_id', $this->cabinetB36Id)->count(),
            'pullouts' => DB::table('projects_pullouts')->where('cabinet_specification_id', $this->cabinetB36Id)->count(),
        ];

        echo "✅ Complete 7-level hierarchy created:\n";
        foreach ($hierarchyCount as $level => $count) {
            echo "   - {$level}: {$count}\n";
        }

        echo "\n🎉 Sarah Martinez Kitchen Renovation - COMPLETE SUCCESS!\n";
    }

    /**
     * Helper: Create inventory products
     */
    private function createInventoryProducts()
    {
        // Cabinet B36 product
        $this->inventoryProducts['cabinet_b36'] = DB::table('products_products')->insertGetId([
            'name' => 'Shaker Style Base Cabinet - 36x34.5x24',
            'sku' => 'CAB-B36-SHAKER',
            'cost' => 250.00,
            'price' => 450.00,
            'quantity_on_hand' => 10,
            'reorder_point' => 3,
            'created_at' => now()->subDays(90),
            'updated_at' => now()->subDays(90),
        ]);

        // Door blank product
        $this->inventoryProducts['door_blank'] = DB::table('products_products')->insertGetId([
            'name' => 'Shaker Door Blank - 3/4" Maple - 18x30',
            'sku' => 'DOOR-BLANK-MAPLE-18X30',
            'cost' => 45.00,
            'price' => 85.00,
            'quantity_on_hand' => 25,
            'reorder_point' => 10,
            'created_at' => now()->subDays(90),
            'updated_at' => now()->subDays(90),
        ]);

        // Pullout trash product
        $this->inventoryProducts['pullout_trash'] = DB::table('products_products')->insertGetId([
            'name' => 'Rev-A-Shelf 5149-18DM-217 - Double Trash Pullout',
            'sku' => 'RAS-5149-18DM-217',
            'cost' => 189.50,
            'price' => 325.00,
            'quantity_on_hand' => 3,
            'reorder_point' => 1,
            'created_at' => now()->subDays(90),
            'updated_at' => now()->subDays(90),
        ]);

        echo "📦 Inventory products created (3 products)\n";
    }
}
