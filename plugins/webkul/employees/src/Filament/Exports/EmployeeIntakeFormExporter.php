<?php

namespace Webkul\Employee\Filament\Exports;

use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;
use Carbon\Carbon;

class EmployeeIntakeFormExporter
{
    /**
     * Generate pre-filled Employee Intake Form HTML
     */
    public static function generateIntakeFormHtml(int $employeeId): string
    {
        $employee = Employee::with(['user', 'department', 'job', 'privateState', 'privateCountry'])
            ->findOrFail($employeeId);

        $user = $employee->user;

        // Load template
        $templatePath = base_path('templates/hr-documents/tcs-employee-intake-form-template.html');

        if (!file_exists($templatePath)) {
            throw new \Exception('Employee intake form template not found at: ' . $templatePath);
        }

        $html = file_get_contents($templatePath);

        // Replace all placeholders with employee data
        $html = self::replacePersonalInfo($html, $employee);
        $html = self::replaceContactInfo($html, $employee);
        $html = self::replaceEmergencyContacts($html, $employee);
        $html = self::replaceEmploymentInfo($html, $employee);
        $html = self::replaceIdentification($html, $employee);

        return $html;
    }

    /**
     * Replace personal information placeholders
     */
    private static function replacePersonalInfo(string $html, Employee $employee): string
    {
        $nameParts = explode(' ', $employee->name ?? '', 3);
        $firstName = $nameParts[0] ?? '';
        $middleName = count($nameParts) > 2 ? $nameParts[1] : '';
        $lastName = count($nameParts) > 2 ? $nameParts[2] : ($nameParts[1] ?? '');

        $replacements = [
            '{{FIRST_NAME}}' => $firstName,
            '{{MIDDLE_NAME}}' => $middleName,
            '{{LAST_NAME}}' => $lastName,
            '{{PREFERRED_NAME}}' => '', // Usually blank on intake
            '{{DATE_OF_BIRTH}}' => $employee->birthday ? Carbon::parse($employee->birthday)->format('m/d/Y') : '',
            '{{SSN}}' => $employee->ssnid ? self::formatSSN($employee->ssnid) : '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    /**
     * Replace contact information placeholders
     */
    private static function replaceContactInfo(string $html, Employee $employee): string
    {
        $replacements = [
            '{{STREET_ADDRESS}}' => $employee->private_street1 ?? '',
            '{{STREET_ADDRESS_2}}' => $employee->private_street2 ?? '',
            '{{CITY}}' => $employee->private_city ?? '',
            '{{STATE}}' => $employee->privateState->name ?? '',
            '{{ZIP_CODE}}' => $employee->private_zip ?? '',
            '{{PRIMARY_PHONE}}' => self::formatPhone($employee->mobile_phone ?? $employee->private_phone ?? ''),
            '{{SECONDARY_PHONE}}' => self::formatPhone($employee->work_phone ?? ''),
            '{{EMAIL}}' => $employee->private_email ?? $employee->work_email ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    /**
     * Replace emergency contact placeholders
     */
    private static function replaceEmergencyContacts(string $html, Employee $employee): string
    {
        $replacements = [
            '{{EMERGENCY_CONTACT_NAME}}' => $employee->emergency_contact ?? '',
            '{{EMERGENCY_CONTACT_RELATIONSHIP}}' => '', // Not stored in current schema
            '{{EMERGENCY_CONTACT_PHONE}}' => self::formatPhone($employee->emergency_phone ?? ''),
            '{{EMERGENCY_CONTACT_2_NAME}}' => '',
            '{{EMERGENCY_CONTACT_2_PHONE}}' => '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    /**
     * Replace employment information placeholders
     */
    private static function replaceEmploymentInfo(string $html, Employee $employee): string
    {
        $employmentType = strtolower($employee->employment_type ?? '');

        $replacements = [
            '{{POSITION}}' => $employee->job_title ?? ($employee->job->name ?? ''),
            '{{START_DATE}}' => '', // Would come from contract/hire date
            '{{PAY_RATE}}' => $employee->hourly_rate ? '$' . number_format($employee->hourly_rate, 2) : ($employee->pay_rate ? '$' . number_format($employee->pay_rate, 2) : ''),
            '{{FULL_TIME_CHECKED}}' => $employmentType === 'employee' || $employmentType === 'full-time' ? 'checked' : '',
            '{{PART_TIME_CHECKED}}' => $employmentType === 'part-time' ? 'checked' : '',
            '{{SEASONAL_CHECKED}}' => $employmentType === 'seasonal' ? 'checked' : '',
            '{{CONTRACT_CHECKED}}' => $employmentType === 'contractor' ? 'checked' : '',
            '{{WEEKLY_CHECKED}}' => '', // Pay frequency not in current schema
            '{{BIWEEKLY_CHECKED}}' => 'checked', // Default
            '{{MONTHLY_CHECKED}}' => '',
            '{{DEPARTMENT}}' => $employee->department->name ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    /**
     * Replace identification placeholders
     */
    private static function replaceIdentification(string $html, Employee $employee): string
    {
        $replacements = [
            '{{DRIVERS_LICENSE}}' => $employee->identification_id ?? '',
            '{{LICENSE_STATE}}' => '', // Not stored separately
            '{{LICENSE_EXPIRATION}}' => '',
            '{{TRANSPORTATION_YES}}' => '', // Not tracked
            '{{TRANSPORTATION_NO}}' => '',
            '{{WORK_AUTH_YES}}' => $employee->work_permit ? 'checked' : '',
            '{{WORK_AUTH_NO}}' => '',
            '{{PREVIOUS_EMPLOYER}}' => '',
            '{{PREVIOUS_EMPLOYER_PHONE}}' => '',
            '{{PREVIOUS_POSITION}}' => '',
            '{{PREVIOUS_DATES}}' => '',
            '{{PREVIOUS_REASON}}' => '',
            '{{EMPLOYEE_ID}}' => 'EMP-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
    }

    /**
     * Format phone number
     */
    private static function formatPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s',
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6, 4)
            );
        }

        return $phone;
    }

    /**
     * Format SSN (masked for security)
     */
    private static function formatSSN(?string $ssn): string
    {
        if (empty($ssn)) {
            return '';
        }

        $digits = preg_replace('/[^0-9]/', '', $ssn);

        if (strlen($digits) === 9) {
            // Show last 4 only for security
            return 'XXX-XX-' . substr($digits, 5, 4);
        }

        return $ssn;
    }

    /**
     * Generate blank form (no pre-fill)
     */
    public static function generateBlankFormHtml(): string
    {
        $templatePath = base_path('templates/hr-documents/tcs-employee-intake-form.html');

        if (!file_exists($templatePath)) {
            throw new \Exception('Employee intake form template not found');
        }

        return file_get_contents($templatePath);
    }
}
