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

        $departmentIds = $departments->pluck('id')->toArray();

        $staffData = [
            [
                'title' => 'Dr',
                'first_name' => 'Kwame',
                'last_name' => 'Asante',
                'gender' => 'male',
                'staff_type' => StaffType::FULL_TIME,
                'departments' => ['Emergency', 'Internal Medicine'],
                'specialty' => 'Emergency Medicine',
                'specialty_code' => 'EM',
            ],
            [
                'title' => 'Dr',
                'first_name' => 'Akosua',
                'last_name' => 'Mensah',
                'gender' => 'female',
                'staff_type' => StaffType::FULL_TIME,
                'departments' => ['Cardiology'],
                'specialty' => 'Cardiology',
                'specialty_code' => 'CARD',
            ],
            [
                'title' => 'Dr',
                'first_name' => 'Yaw',
                'last_name' => 'Boakye',
                'gender' => 'male',
                'staff_type' => StaffType::CONSULTANT,
                'departments' => ['Surgery'],
                'specialty' => 'General Surgery',
                'specialty_code' => 'SURG',
            ],
            [
                'title' => 'Dr',
                'first_name' => 'Efua',
                'last_name' => 'Owusu',
                'gender' => 'female',
                'staff_type' => StaffType::RESIDENT,
                'departments' => ['Pediatrics'],
                'specialty' => 'Pediatrics',
                'specialty_code' => 'PED',
            ],
            [
                'title' => 'Mrs',
                'first_name' => 'Adwoa',
                'last_name' => 'Bonsu',
                'gender' => 'female',
                'staff_type' => StaffType::FULL_TIME,
                'departments' => ['Nursing'],
            ],
            [
                'title' => 'Mr',
                'first_name' => 'Kofi',
                'last_name' => 'Agyeman',
                'gender' => 'male',
                'staff_type' => StaffType::FULL_TIME,
                'departments' => ['Nursing'],
            ],
        ];

        foreach ($staffData as $data) {
            $staff = Staff::factory()->create([
                'title' => $data['title'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'staff_type' => $data['staff_type'],
                'employment_status' => EmploymentStatus::ACTIVE,
                'hire_date' => now()->subMonths(rand(6, 60)),
                'contact' => [
                    'email' => strtolower($data['first_name'].'.'.$data['last_name']).'@hospital.com',
                    'phone' => fake()->phoneNumber(),
                ],
            ]);

            foreach ($data['departments'] as $deptIndex => $deptName) {
                $department = Department::where('name', 'like', "%{$deptName}%")->first();
                if (! $department && ! empty($departmentIds)) {
                    $department = Department::find($departmentIds[array_rand($departmentIds)]);
                }

                if ($department) {
                    $staff->assignDepartment($department, $deptIndex === 0);
                }
            }

            if (isset($data['specialty'])) {
                $staff->specialties()->create([
                    'specialty_name' => $data['specialty'],
                    'specialty_code' => $data['specialty_code'] ?? null,
                    'certification_date' => now()->subYears(rand(1, 5)),
                    'expiry_date' => now()->addYears(rand(2, 5)),
                    'issuing_body' => 'Ghana Medical and Dental Council',
                    'is_primary' => true,
                ]);
            }

            $this->createCredentialsForStaff($staff);
        }

        $this->command->info('Created '.count($staffData).' staff members with departments, specialties, and credentials.');
    }

    protected function createCredentialsForStaff(Staff $staff): void
    {
        StaffCredential::factory()->verified()->create([
            'staff_id' => $staff->id,
            'credential_type' => CredentialType::MEDICAL_LICENSE,
            'credential_number' => 'GMC-'.fake()->numerify('######'),
            'issuing_authority' => 'Ghana Medical and Dental Council',
            'expiry_date' => now()->addYears(rand(1, 5)),
        ]);

        StaffCredential::factory()->verified()->create([
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
