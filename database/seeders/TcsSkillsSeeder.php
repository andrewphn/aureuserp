<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;

/**
 * Tcs Skills Seeder database seeder
 *
 */
class TcsSkillsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        // Delete existing sample skill types and skills
        DB::table('employees_employee_skills')->whereIn('skill_id', function ($query) {
            $query->select('id')->from('employees_skills')->whereIn('skill_type_id', [1, 2, 3, 4, 5]);
        })->delete();

        DB::table('employees_skills')->whereIn('skill_type_id', [1, 2, 3, 4, 5])->delete();
        DB::table('employees_skill_types')->whereIn('id', [1, 2, 3, 4, 5])->delete();

        // Create TCS Woodwork skill types
        $skillTypes = [
            [
                'id' => 1,
                'name' => 'Woodworking Machinery',
                'color' => 'warning',
                'is_active' => true,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Hand Tools & Joinery',
                'color' => 'primary',
                'is_active' => true,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Finishing & Surface Prep',
                'color' => 'success',
                'is_active' => true,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Installation & Assembly',
                'color' => 'info',
                'is_active' => true,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'Materials & Hardware',
                'color' => 'gray',
                'is_active' => true,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'name' => 'Safety & Compliance',
                'color' => 'danger',
                'is_active' => true,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'name' => 'Design & Planning',
                'color' => 'purple',
                'is_active' => true,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'name' => 'Languages',
                'color' => 'indigo',
                'is_active' => true,
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($skillTypes as $skillType) {
            DB::table('employees_skill_types')->insert($skillType);
        }

        // Create TCS Woodwork skills
        $skills = [
            // Woodworking Machinery (Type 1) - Apprentice starts here
            ['name' => 'Table Saw Operation', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Band Saw Operation', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Miter Saw Operation', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Panel Saw Operation', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Jointer Operation', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Planer/Thickness Planer', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Router & Router Table', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'CNC Router Operation', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Edge Bander Operation', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Drill Press Operation', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Belt Sander Operation', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Wide Belt Sander', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Drum Sander Operation', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Lathe Operation', 'skill_type_id' => 1, 'sort' => 10],
            ['name' => 'Mortiser Operation', 'skill_type_id' => 1, 'sort' => 10],

            // Hand Tools & Joinery (Type 2) - Journey level excels
            ['name' => 'Hand Planing', 'skill_type_id' => 2, 'sort' => 10],
            ['name' => 'Chisel Work', 'skill_type_id' => 2, 'sort' => 10],
            ['name' => 'Mortise & Tenon Joinery', 'skill_type_id' => 2, 'sort' => 10],
            ['name' => 'Dovetail Joinery', 'skill_type_id' => 2, 'sort' => 10],
            ['name' => 'Dowel Joinery', 'skill_type_id' => 2, 'sort' => 10],
            ['name' => 'Biscuit Joinery', 'skill_type_id' => 2, 'sort' => 10],
            ['name' => 'Pocket Hole Joinery', 'skill_type_id' => 2, 'sort' => 10],
            ['name' => 'Hand Saw Techniques', 'skill_type_id' => 2, 'sort' => 10],
            ['name' => 'Precision Measuring & Layout', 'skill_type_id' => 2, 'sort' => 10],
            ['name' => 'Clamping & Glue-up Techniques', 'skill_type_id' => 2, 'sort' => 10],
            ['name' => 'Jig Building & Setup', 'skill_type_id' => 2, 'sort' => 10],

            // Finishing & Surface Prep (Type 3) - Apprentice assists
            ['name' => 'Sanding & Surface Prep', 'skill_type_id' => 3, 'sort' => 10],
            ['name' => 'Grain Direction Assessment', 'skill_type_id' => 3, 'sort' => 10],
            ['name' => 'Staining Techniques', 'skill_type_id' => 3, 'sort' => 10],
            ['name' => 'Clear Coat Application', 'skill_type_id' => 3, 'sort' => 10],
            ['name' => 'Lacquer Finishing', 'skill_type_id' => 3, 'sort' => 10],
            ['name' => 'Polyurethane Application', 'skill_type_id' => 3, 'sort' => 10],
            ['name' => 'Oil & Wax Finishing', 'skill_type_id' => 3, 'sort' => 10],
            ['name' => 'Spray Booth Operation', 'skill_type_id' => 3, 'sort' => 10],
            ['name' => 'HVLP Spray Gun Operation', 'skill_type_id' => 3, 'sort' => 10],
            ['name' => 'Color Matching', 'skill_type_id' => 3, 'sort' => 10],
            ['name' => 'Distressing & Antiquing', 'skill_type_id' => 3, 'sort' => 10],
            ['name' => 'Finish Schedule Interpretation', 'skill_type_id' => 3, 'sort' => 10],

            // Installation & Assembly (Type 4)
            ['name' => 'Cabinet Installation', 'skill_type_id' => 4, 'sort' => 10],
            ['name' => 'Countertop Installation', 'skill_type_id' => 4, 'sort' => 10],
            ['name' => 'Trim & Molding Installation', 'skill_type_id' => 4, 'sort' => 10],
            ['name' => 'Door Hanging & Alignment', 'skill_type_id' => 4, 'sort' => 10],
            ['name' => 'Drawer Assembly & Installation', 'skill_type_id' => 4, 'sort' => 10],
            ['name' => 'Hardware Installation (Hinges, Slides)', 'skill_type_id' => 4, 'sort' => 10],
            ['name' => 'Leveling & Shimming', 'skill_type_id' => 4, 'sort' => 10],
            ['name' => 'Scribing to Walls/Floors', 'skill_type_id' => 4, 'sort' => 10],
            ['name' => 'Custom Furniture Assembly', 'skill_type_id' => 4, 'sort' => 10],
            ['name' => 'Site Measurement & Templating', 'skill_type_id' => 4, 'sort' => 10],

            // Materials & Hardware (Type 5) - Lead craftsman deep knowledge
            ['name' => 'Wood Species Identification', 'skill_type_id' => 5, 'sort' => 10],
            ['name' => 'Hardwood vs Softwood Selection', 'skill_type_id' => 5, 'sort' => 10],
            ['name' => 'Plywood & Sheet Goods Knowledge', 'skill_type_id' => 5, 'sort' => 10],
            ['name' => 'Veneer Selection & Application', 'skill_type_id' => 5, 'sort' => 10],
            ['name' => 'Solid Surface Materials', 'skill_type_id' => 5, 'sort' => 10],
            ['name' => 'Adhesives & Glues Selection', 'skill_type_id' => 5, 'sort' => 10],
            ['name' => 'Fasteners & Hardware Knowledge', 'skill_type_id' => 5, 'sort' => 10],
            ['name' => 'Cabinet Hardware Selection', 'skill_type_id' => 5, 'sort' => 10],
            ['name' => 'Material Estimation & Optimization', 'skill_type_id' => 5, 'sort' => 10],
            ['name' => 'Wood Movement & Moisture Control', 'skill_type_id' => 5, 'sort' => 10],

            // Safety & Compliance (Type 6) - ALL levels require
            ['name' => 'Shop Safety Procedures', 'skill_type_id' => 6, 'sort' => 10],
            ['name' => 'OSHA Compliance', 'skill_type_id' => 6, 'sort' => 10],
            ['name' => 'PPE Usage & Maintenance', 'skill_type_id' => 6, 'sort' => 10],
            ['name' => 'Dust Collection Systems', 'skill_type_id' => 6, 'sort' => 10],
            ['name' => 'Fire Safety & Extinguisher Use', 'skill_type_id' => 6, 'sort' => 10],
            ['name' => 'First Aid & Emergency Response', 'skill_type_id' => 6, 'sort' => 10],
            ['name' => 'Machine Guarding & Lockout/Tagout', 'skill_type_id' => 6, 'sort' => 10],
            ['name' => 'Chemical Safety (Finishes/Adhesives)', 'skill_type_id' => 6, 'sort' => 10],
            ['name' => 'Forklift/Material Handler Certification', 'skill_type_id' => 6, 'sort' => 10],
            ['name' => 'Hearing Conservation', 'skill_type_id' => 6, 'sort' => 10],
            ['name' => 'Respiratory Protection', 'skill_type_id' => 6, 'sort' => 10],

            // Design & Planning (Type 7) - Lead craftsman & PM focus
            ['name' => 'Blueprint Reading & Interpretation', 'skill_type_id' => 7, 'sort' => 10],
            ['name' => 'CAD Software (SketchUp/AutoCAD)', 'skill_type_id' => 7, 'sort' => 10],
            ['name' => 'Custom Cabinet Design', 'skill_type_id' => 7, 'sort' => 10],
            ['name' => 'Build Strategy Planning', 'skill_type_id' => 7, 'sort' => 10],
            ['name' => 'Project Estimation', 'skill_type_id' => 7, 'sort' => 10],
            ['name' => 'Cut List Generation', 'skill_type_id' => 7, 'sort' => 10],
            ['name' => 'Linear Feet Calculation', 'skill_type_id' => 7, 'sort' => 10],
            ['name' => 'Material Optimization & Yield', 'skill_type_id' => 7, 'sort' => 10],
            ['name' => 'Buildability Assessment', 'skill_type_id' => 7, 'sort' => 10],
            ['name' => 'Customer Consultation', 'skill_type_id' => 7, 'sort' => 10],
            ['name' => 'Technical Problem Solving', 'skill_type_id' => 7, 'sort' => 10],
            ['name' => 'Quality Control & Inspection', 'skill_type_id' => 7, 'sort' => 10],

            // Languages (Type 8)
            ['name' => 'English (Fluent)', 'skill_type_id' => 8, 'sort' => 10],
            ['name' => 'English (Conversational)', 'skill_type_id' => 8, 'sort' => 10],
            ['name' => 'Spanish (Fluent)', 'skill_type_id' => 8, 'sort' => 10],
            ['name' => 'Spanish (Conversational)', 'skill_type_id' => 8, 'sort' => 10],
            ['name' => 'French (Fluent)', 'skill_type_id' => 8, 'sort' => 10],
            ['name' => 'French (Conversational)', 'skill_type_id' => 8, 'sort' => 10],
            ['name' => 'Portuguese', 'skill_type_id' => 8, 'sort' => 10],
        ];

        $skillId = 1;
        foreach ($skills as $skill) {
            DB::table('employees_skills')->insert([
                'id' => $skillId++,
                'sort' => $skill['sort'],
                'name' => $skill['name'],
                'skill_type_id' => $skill['skill_type_id'],
                'creator_id' => $user?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('TCS Woodwork skills and skill types created successfully!');
        $this->command->info('Created 8 skill categories with ' . count($skills) . ' woodworking-specific skills.');
    }
}
