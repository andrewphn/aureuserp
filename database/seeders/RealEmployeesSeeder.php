<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Webkul\Employee\Models\Department;
use Webkul\Employee\Models\Employee;
use Webkul\Employee\Models\EmployeeJobPosition;
use Webkul\Employee\Models\WorkLocation;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;
use Webkul\Support\Models\State;

/**
 * Real Employees Seeder
 *
 * Creates real TCS employee records from sample data.
 * Includes Aedan Ciganek (from PDF) and other sample employees.
 *
 * Run with: php artisan db:seed --class=RealEmployeesSeeder
 */
class RealEmployeesSeeder extends Seeder
{
    protected Carbon $now;
    protected ?Company $company;
    protected ?User $admin;
    protected ?Country $usCountry;
    protected ?State $nyState;
    protected array $departments = [];
    protected array $jobPositions = [];
    protected array $workLocations = [];
    protected array $createdEmployees = [];

    /**
     * Real employee data extracted from sample PDFs
     */
    protected array $employeeData = [
        [
            // From: /sample/sample employee/TCSWoodworking Employee. - Aedan .pdf
            'name' => 'Trevor Aedan Gray Ciganek',
            'preferred_name' => 'Aedan',
            'job_title' => 'Inventory Specialist',
            'department' => 'Inventory',
            'work_email' => 'aedan@tcswoodwork.com',
            'private_email' => 'aedantag@gmail.com',
            'mobile_phone' => '(845) 375-9651',
            'private_street1' => '67 Browns Road',
            'private_city' => 'Walden',
            'private_state' => 'NY',
            'private_zip' => '12586',
            'birthday' => '2002-02-01',
            'gender' => 'male',
            'marital' => 'single',
            'emergency_contact' => 'Joanna Ciganek (Mom)',
            'emergency_phone' => '(816) 868-1204',
            'employee_type' => 'part_time',
            'notes' => "Start Date: December 10, 2025\nSchedule: Wednesday, Friday (Part-Time)\nPay: $20/hr (Bi-Weekly)\nT-Shirt: L | Gloves: XL\n\nSecondary Emergency: Phil Brander (congregation elder) - (845) 234-0104\n\nPrevious Experience:\n- Wallkill River Center for the Arts (2022-2025)\n- Position: Animation Teacher",
            'study_school' => 'Wallkill River Center for the Arts',
            'study_field' => 'Animation Teaching',
            'is_active' => true,
            'create_user' => false, // Part-time, no system user
        ],
        [
            // Sample shop foreman
            'name' => 'Mike Rodriguez',
            'preferred_name' => 'Mike',
            'job_title' => 'Shop Foreman',
            'department' => 'Production',
            'work_email' => 'mike@tcswoodwork.com',
            'mobile_phone' => '(845) 555-0101',
            'private_street1' => '234 Industrial Way',
            'private_city' => 'Newburgh',
            'private_state' => 'NY',
            'private_zip' => '12550',
            'birthday' => '1985-06-15',
            'gender' => 'male',
            'marital' => 'married',
            'spouse_complete_name' => 'Maria Rodriguez',
            'emergency_contact' => 'Maria Rodriguez (Wife)',
            'emergency_phone' => '(845) 555-0102',
            'employee_type' => 'full_time',
            'notes' => "Senior shop foreman with 15+ years experience.\nCertified in CNC operation and cabinet making.\nTeam lead for production floor.",
            'study_school' => 'BOCES Technical School',
            'study_field' => 'Woodworking Technology',
            'certificate' => 'Master Craftsman',
            'is_active' => true,
            'create_user' => true,
        ],
        [
            // Sample CNC operator
            'name' => 'Sarah Chen',
            'preferred_name' => 'Sarah',
            'job_title' => 'CNC Operator',
            'department' => 'Production',
            'work_email' => 'sarah@tcswoodwork.com',
            'mobile_phone' => '(845) 555-0201',
            'private_street1' => '456 Oak Street',
            'private_city' => 'Beacon',
            'private_state' => 'NY',
            'private_zip' => '12508',
            'birthday' => '1992-03-22',
            'gender' => 'female',
            'marital' => 'single',
            'emergency_contact' => 'David Chen (Brother)',
            'emergency_phone' => '(845) 555-0202',
            'employee_type' => 'full_time',
            'notes' => "Expert CNC programmer and operator.\nSpecializes in complex nested cutting patterns.\nTrained on Biesse and Homag machines.",
            'study_school' => 'SUNY New Paltz',
            'study_field' => 'Industrial Design',
            'is_active' => true,
            'create_user' => true,
        ],
        [
            // Sample finisher
            'name' => 'James Wilson',
            'preferred_name' => 'Jim',
            'job_title' => 'Finishing Specialist',
            'department' => 'Finishing',
            'work_email' => 'jim@tcswoodwork.com',
            'mobile_phone' => '(845) 555-0301',
            'private_street1' => '789 Maple Ave',
            'private_city' => 'Poughkeepsie',
            'private_state' => 'NY',
            'private_zip' => '12601',
            'birthday' => '1978-11-08',
            'gender' => 'male',
            'marital' => 'married',
            'spouse_complete_name' => 'Linda Wilson',
            'children' => '2',
            'emergency_contact' => 'Linda Wilson (Wife)',
            'emergency_phone' => '(845) 555-0302',
            'employee_type' => 'full_time',
            'notes' => "20+ years finishing experience.\nExpert in spray application and hand-rubbed finishes.\nTrained in waterborne and conversion varnish systems.",
            'certificate' => 'EPA RRP Certified',
            'is_active' => true,
            'create_user' => true,
        ],
        [
            // Sample project manager
            'name' => 'Emily Thompson',
            'preferred_name' => 'Emily',
            'job_title' => 'Project Manager',
            'department' => 'Administration',
            'work_email' => 'emily@tcswoodwork.com',
            'mobile_phone' => '(845) 555-0401',
            'private_street1' => '123 River Road',
            'private_city' => 'Newburgh',
            'private_state' => 'NY',
            'private_zip' => '12550',
            'birthday' => '1988-07-12',
            'gender' => 'female',
            'marital' => 'single',
            'emergency_contact' => 'Robert Thompson (Father)',
            'emergency_phone' => '(845) 555-0402',
            'employee_type' => 'full_time',
            'notes' => "Manages Nantucket and commercial projects.\nExcellent client communication skills.\nPMP Certified.",
            'study_school' => 'Marist College',
            'study_field' => 'Business Administration',
            'certificate' => 'PMP',
            'is_active' => true,
            'create_user' => true,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PRODUCTION GUARD - This seeder is for development/staging only
        if (app()->environment('production')) {
            $this->command->error('⛔ This seeder cannot run in production!');
            $this->command->error('   RealEmployeesSeeder is for development data only.');
            return;
        }

        $this->now = Carbon::now();

        $this->command->info("\n=== Real Employees Seeder ===\n");

        DB::beginTransaction();

        try {
            $this->loadPrerequisites();
            $this->ensureDepartments();
            $this->ensureJobPositions();
            $this->ensureWorkLocations();

            foreach ($this->employeeData as $data) {
                $this->createEmployee($data);
            }

            DB::commit();

            $this->printSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Import failed: " . $e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Create employee from data
     */
    protected function createEmployee(array $data): ?Employee
    {
        $name = $data['preferred_name'] ?? $data['name'];

        // Check if employee already exists
        $existingEmployee = Employee::where('name', $data['name'])
            ->orWhere('work_email', $data['work_email'] ?? null)
            ->first();

        if ($existingEmployee) {
            $this->command->warn("  ⚠ Skipping duplicate: {$name}");
            return null;
        }

        $this->command->info("Creating employee: {$name} ({$data['job_title']})");

        // Get department
        $department = $this->departments[$data['department']] ?? null;

        // Get job position
        $jobPosition = $this->jobPositions[$data['job_title']] ?? null;

        // Get state
        $state = State::where('code', $data['private_state'] ?? 'NY')->first();

        // Create user if needed
        $user = null;
        if ($data['create_user'] ?? false) {
            $user = $this->createUserForEmployee($data);
        }

        // Create employee
        $employee = Employee::create([
            // Basic info
            'name' => $data['name'],
            'job_title' => $data['job_title'],
            'employee_type' => $data['employee_type'] ?? 'full_time',

            // Contact - Work
            'work_email' => $data['work_email'] ?? null,
            'work_phone' => $data['work_phone'] ?? '(845) 816-2388', // Main TCS line
            'mobile_phone' => $data['mobile_phone'] ?? null,

            // Contact - Private
            'private_email' => $data['private_email'] ?? null,
            'private_phone' => $data['private_phone'] ?? $data['mobile_phone'] ?? null,
            'private_street1' => $data['private_street1'] ?? null,
            'private_street2' => $data['private_street2'] ?? null,
            'private_city' => $data['private_city'] ?? null,
            'private_zip' => $data['private_zip'] ?? null,
            'private_state_id' => $state?->id,
            'private_country_id' => $this->usCountry?->id,

            // Personal info
            'gender' => $data['gender'] ?? null,
            'birthday' => isset($data['birthday']) ? Carbon::parse($data['birthday']) : null,
            'marital' => $data['marital'] ?? null,
            'spouse_complete_name' => $data['spouse_complete_name'] ?? null,
            'children' => $data['children'] ?? null,
            'place_of_birth' => $data['place_of_birth'] ?? null,

            // Emergency contact
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'emergency_phone' => $data['emergency_phone'] ?? null,

            // Education/Certification
            'study_school' => $data['study_school'] ?? null,
            'study_field' => $data['study_field'] ?? null,
            'certificate' => $data['certificate'] ?? null,

            // Notes
            'notes' => $data['notes'] ?? null,
            'additional_note' => $data['additional_note'] ?? null,

            // Status
            'is_active' => $data['is_active'] ?? true,
            'is_flexible' => false,
            'is_fully_flexible' => false,

            // Location
            'country_id' => $this->usCountry?->id,
            'state_id' => $state?->id,
            'time_zone' => 'America/New_York',
            'lang' => 'en_US',

            // Relationships
            'company_id' => $this->company->id,
            'department_id' => $department?->id,
            'job_id' => $jobPosition?->id,
            'work_location_id' => $this->workLocations['Shop']?->id ?? null,
            'user_id' => $user?->id,
            'creator_id' => $this->admin->id,
            'attendance_manager_id' => $this->admin->id,
            'leave_manager_id' => $this->admin->id,
        ]);

        // Partner is auto-created by Employee model's boot method

        $this->createdEmployees[$name] = $employee;

        $this->command->info("  ✓ Created employee ID: {$employee->id}");

        return $employee;
    }

    /**
     * Create user for employee
     */
    protected function createUserForEmployee(array $data): ?User
    {
        if (empty($data['work_email'])) {
            return null;
        }

        // Check if user exists
        $existingUser = User::where('email', $data['work_email'])->first();
        if ($existingUser) {
            return $existingUser;
        }

        // Create partner first (to avoid the User model's automatic partner creation
        // which tries to copy incompatible fields like 'language')
        $partner = Partner::create([
            'name' => $data['preferred_name'] ?? $data['name'],
            'email' => $data['work_email'],
            'account_type' => 'individual',
            'sub_type' => 'employee',
            'is_active' => $data['is_active'] ?? true,
            'creator_id' => $this->admin->id,
            'company_id' => $this->company->id,
        ]);

        // Now create user with the partner_id already set
        // This bypasses the automatic partner creation in the User model
        $user = new User();
        $user->name = $data['preferred_name'] ?? $data['name'];
        $user->email = $data['work_email'];
        $user->password = Hash::make('TCS2025!');
        $user->is_active = $data['is_active'] ?? true;
        $user->language = 'en';
        $user->default_company_id = $this->company->id;
        $user->partner_id = $partner->id;
        $user->saveQuietly(); // Bypass the saved event

        // Update partner with user_id
        $partner->update(['user_id' => $user->id]);

        $this->command->info("  ✓ Created user: {$user->email}");

        return $user;
    }

    /**
     * Load prerequisites
     */
    protected function loadPrerequisites(): void
    {
        $this->command->info("Loading prerequisites...");

        $this->company = Company::first();
        if (!$this->company) {
            throw new \RuntimeException("No company found");
        }

        $this->admin = User::first();
        if (!$this->admin) {
            throw new \RuntimeException("No user found");
        }

        $this->usCountry = Country::where('code', 'US')->first();
        $this->nyState = State::where('code', 'NY')->first();

        $this->command->info("  ✓ Using company: {$this->company->name}");
        $this->command->info("  ✓ Using admin: {$this->admin->name}");
    }

    /**
     * Ensure departments exist
     */
    protected function ensureDepartments(): void
    {
        $this->command->info("Ensuring departments exist...");

        $deptData = [
            ['name' => 'Production', 'color' => '#10b981'],
            ['name' => 'Finishing', 'color' => '#f59e0b'],
            ['name' => 'Inventory', 'color' => '#3b82f6'],
            ['name' => 'Administration', 'color' => '#8b5cf6'],
            ['name' => 'Design', 'color' => '#ec4899'],
            ['name' => 'Installation', 'color' => '#6366f1'],
        ];

        foreach ($deptData as $data) {
            $dept = Department::firstOrCreate(
                ['name' => $data['name'], 'company_id' => $this->company->id],
                [
                    'complete_name' => $data['name'],
                    'color' => $data['color'],
                    'creator_id' => $this->admin->id,
                ]
            );
            $this->departments[$data['name']] = $dept;
        }

        $this->command->info("  ✓ " . count($this->departments) . " departments configured");
    }

    /**
     * Ensure job positions exist
     */
    protected function ensureJobPositions(): void
    {
        $this->command->info("Ensuring job positions exist...");

        $jobData = [
            ['name' => 'Shop Foreman', 'department' => 'Production'],
            ['name' => 'CNC Operator', 'department' => 'Production'],
            ['name' => 'Cabinet Maker', 'department' => 'Production'],
            ['name' => 'Finishing Specialist', 'department' => 'Finishing'],
            ['name' => 'Inventory Specialist', 'department' => 'Inventory'],
            ['name' => 'Project Manager', 'department' => 'Administration'],
            ['name' => 'Designer', 'department' => 'Design'],
            ['name' => 'Installer', 'department' => 'Installation'],
        ];

        foreach ($jobData as $data) {
            $dept = $this->departments[$data['department']] ?? null;

            $job = EmployeeJobPosition::firstOrCreate(
                ['name' => $data['name'], 'company_id' => $this->company->id],
                [
                    'department_id' => $dept?->id,
                    'is_active' => true,
                    'creator_id' => $this->admin->id,
                ]
            );
            $this->jobPositions[$data['name']] = $job;
        }

        $this->command->info("  ✓ " . count($this->jobPositions) . " job positions configured");
    }

    /**
     * Ensure work locations exist
     */
    protected function ensureWorkLocations(): void
    {
        $this->command->info("Ensuring work locations exist...");

        $locationData = [
            [
                'name' => 'Shop',
                'location_type' => 'office',
                'location_number' => '392 N Montgomery St, Bldg B',
            ],
            [
                'name' => 'Office',
                'location_type' => 'office',
                'location_number' => '392 N Montgomery St, Bldg B',
            ],
            [
                'name' => 'Remote',
                'location_type' => 'home',
                'location_number' => 'Remote Work',
            ],
        ];

        foreach ($locationData as $data) {
            $location = WorkLocation::firstOrCreate(
                ['name' => $data['name'], 'company_id' => $this->company->id],
                [
                    'location_type' => $data['location_type'],
                    'location_number' => $data['location_number'],
                    'is_active' => true,
                    'creator_id' => $this->admin->id,
                ]
            );
            $this->workLocations[$data['name']] = $location;
        }

        $this->command->info("  ✓ " . count($this->workLocations) . " work locations configured");
    }

    /**
     * Print summary
     */
    protected function printSummary(): void
    {
        $this->command->info("\n=== Employee Import Complete ===");
        $this->command->info("Created " . count($this->createdEmployees) . " employees:\n");

        foreach ($this->createdEmployees as $name => $employee) {
            $dept = $employee->department?->name ?? 'N/A';
            $type = $employee->employee_type;
            $this->command->info("  - {$name} ({$employee->job_title}) [{$dept}] {$type}");
        }

        $this->command->info("");
    }
}
