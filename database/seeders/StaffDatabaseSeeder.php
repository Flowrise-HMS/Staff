<?php

namespace Modules\Staff\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Department;
use Modules\Staff\Enums\CredentialType;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffCredential;

class StaffDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::where('is_active', true)->get();

        if ($departments->isEmpty()) {
            $departments = Department::factory()->count(5)->create();
        }

        $departments = $departments->pluck('id')->toArray();

        $medicalStaff = [
            [
                'title' => 'Dr.',
                'first_name' => 'Kwame',
                'surname' => 'Asante',
                'gender' => 'male',
                'staff_type' => StaffType::FULL_TIME,
                'departments' => ['Emergency', 'Internal Medicine'],
                'specialty' => 'Emergency Medicine',
                'specialty_code' => 'EM',
            ],
            [
                'title' => 'Dr.',
                'first_name' => 'Akosua',
                'surname' => 'Mensah',
                'gender' => 'female',
                'staff_type' => StaffType::FULL_TIME,
                'departments' => ['Cardiology'],
                'specialty' => 'Cardiology',
                'specialty_code' => 'CARD',
            ],
            [
                'title' => 'Dr.',
                'first_name' => 'Yaw',
                'surname' => 'Boakye',
                'gender' => 'male',
                'staff_type' => StaffType::CONSULTANT,
                'departments' => ['Surgery'],
                'specialty' => 'General Surgery',
                'specialty_code' => 'SURG',
            ],
            [
                'title' => 'Dr.',
                'first_name' => 'Efua',
                'surname' => 'Owusu',
                'gender' => 'female',
                'staff_type' => StaffType::RESIDENT,
                'departments' => ['Pediatrics'],
                'specialty' => 'Pediatrics',
                'specialty_code' => 'PED',
            ],
        ];

        $nursingStaff = [
            [
                'title' => 'Mrs.',
                'first_name' => 'Adwoa',
                'surname' => 'Bonsu',
                'gender' => 'female',
                'staff_type' => StaffType::FULL_TIME,
                'departments' => ['Nursing'],
            ],
            [
                'title' => 'Mr.',
                'first_name' => 'Kofi',
                'surname' => 'Agyeman',
                'gender' => 'male',
                'staff_type' => StaffType::FULL_TIME,
                'departments' => ['Nursing'],
            ],
        ];

        $allStaff = array_merge($medicalStaff, $nursingStaff);

        foreach ($allStaff as $index => $staffData) {
            $staff = Staff::factory()->create([
                'title' => $staffData['title'],
                'first_name' => $staffData['first_name'],
                'surname' => $staffData['surname'],
                'gender' => $staffData['gender'],
                'staff_type' => $staffData['staff_type'],
                'employment_status' => EmploymentStatus::ACTIVE,
                'hire_date' => now()->subMonths(rand(6, 60)),
                'phone' => fake()->phoneNumber(),
                'email' => strtolower($staffData['first_name'].'.'.$staffData['surname']).'@hospital.com',
            ]);

            foreach ($staffData['departments'] as $deptIndex => $deptName) {
                $department = Department::where('name', 'like', "%{$deptName}%")->first();
                if (! $department && ! empty($departments)) {
                    $department = Department::find($departments[array_rand($departments)]);
                }

                if ($department) {
                    $staff->assignDepartment($department, $deptIndex === 0);
                }
            }

            if (isset($staffData['specialty'])) {
                $staff->specialties()->create([
                    'specialty_name' => $staffData['specialty'],
                    'specialty_code' => $staffData['specialty_code'] ?? null,
                    'certification_date' => now()->subYears(rand(1, 5)),
                    'expiry_date' => now()->addYears(rand(2, 5)),
                    'issuing_body' => 'Ghana Medical and Dental Council',
                    'is_primary' => true,
                ]);
            }

            $this->createCredentialsForStaff($staff, $staffData);
        }

        $this->command->info('Created '.count($allStaff).' staff members with departments, specialties, and credentials.');
    }

    protected function createCredentialsForStaff(Staff $staff, array $staffData): void
    {
        $staffCredential = StaffCredential::factory()->verified()->create([
            'staff_id' => $staff->id,
            'credential_type' => CredentialType::MEDICAL_LICENSE,
            'credential_number' => 'GMC-'.fake()->numerify('######'),
            'issuing_authority' => 'Ghana Medical and Dental Council',
            'expiry_date' => now()->addYears(rand(1, 5)),
        ]);

        $blsCredential = StaffCredential::factory()->verified()->create([
            'staff_id' => $staff->id,
            'credential_type' => CredentialType::BLS_CERTIFICATION,
            'credential_number' => 'BLS-'.fake()->numerify('####'),
            'issuing_authority' => 'American Heart Association',
            'expiry_date' => now()->addMonths(rand(6, 24)),
        ]);

        if (rand(0, 1)) {
            StaffCredential::factory()->create([
                'staff_id' => $staff->id,
                'credential_type' => CredentialType::ACLS_CERTIFICATION,
                'expiry_date' => now()->addMonths(rand(6, 18)),
            ]);
        }
    }
}
