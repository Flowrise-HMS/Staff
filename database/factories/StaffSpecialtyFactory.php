<?php

namespace Modules\Staff\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffSpecialty;

/** @extends Factory<StaffSpecialty> */
class StaffSpecialtyFactory extends Factory
{
    protected $model = StaffSpecialty::class;

    public function definition(): array
    {
        $specialties = [
            ['name' => 'Internal Medicine', 'code' => 'IM'],
            ['name' => 'Surgery', 'code' => 'SURG'],
            ['name' => 'Pediatrics', 'code' => 'PED'],
            ['name' => 'Obstetrics & Gynecology', 'code' => 'OBG'],
            ['name' => 'Psychiatry', 'code' => 'PSY'],
            ['name' => 'Cardiology', 'code' => 'CARD'],
            ['name' => 'Neurology', 'code' => 'NEUR'],
            ['name' => 'Orthopedics', 'code' => 'ORTH'],
            ['name' => 'Emergency Medicine', 'code' => 'EM'],
            ['name' => 'Radiology', 'code' => 'RAD'],
            ['name' => 'Pathology', 'code' => 'PATH'],
            ['name' => 'Anesthesiology', 'code' => 'ANES'],
            ['name' => 'Dermatology', 'code' => 'DERM'],
            ['name' => 'Ophthalmology', 'code' => 'OPH'],
            ['name' => 'Oncology', 'code' => 'ONC'],
            ['name' => 'Nephrology', 'code' => 'NEPH'],
            ['name' => 'Gastroenterology', 'code' => 'GI'],
            ['name' => 'Pulmonology', 'code' => 'PULM'],
            ['name' => 'Endocrinology', 'code' => 'ENDO'],
            ['name' => 'Rheumatology', 'code' => 'RHEUM'],
        ];

        $specialty = $this->faker->randomElement($specialties);

        return [
            'staff_id' => Staff::factory(),
            'specialty_name' => $specialty['name'],
            'specialty_code' => $specialty['code'],
            'description' => $this->faker->optional()->sentence(),
            'certification_date' => $this->faker->dateTimeBetween('-10 years', '-1 month'),
            'expiry_date' => $this->faker->optional(0.7)->dateTimeBetween('+1 month', '+5 years'),
            'issuing_body' => $this->faker->randomElement([
                'Ghana Medical and Dental Council',
                'West African Health Organization',
                'African Board of Surgery',
                'International Board of Specialty',
            ]),
            'certificate_number' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{6}'),
            'is_primary' => false,
            'metadata' => null,
        ];
    }

    public function forStaff(Staff $staff): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_id' => $staff->id,
        ]);
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->optional(0.8)->dateTimeBetween('+1 month', '+5 years'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
        ]);
    }

    public function expiringSoon(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => now()->addDays(rand(1, $days)),
        ]);
    }

    public function withoutExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => null,
        ]);
    }

    public function surgery(): static
    {
        return $this->state(fn (array $attributes) => [
            'specialty_name' => 'Surgery',
            'specialty_code' => 'SURG',
        ]);
    }

    public function cardiology(): static
    {
        return $this->state(fn (array $attributes) => [
            'specialty_name' => 'Cardiology',
            'specialty_code' => 'CARD',
        ]);
    }

    public function pediatrics(): static
    {
        return $this->state(fn (array $attributes) => [
            'specialty_name' => 'Pediatrics',
            'specialty_code' => 'PED',
        ]);
    }

    public function emergencyMedicine(): static
    {
        return $this->state(fn (array $attributes) => [
            'specialty_name' => 'Emergency Medicine',
            'specialty_code' => 'EM',
        ]);
    }
}
