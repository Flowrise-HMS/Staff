<?php

namespace Modules\Staff\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Department;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffDepartment;

/** @extends Factory<StaffDepartment> */
class StaffDepartmentFactory extends Factory
{
    protected $model = StaffDepartment::class;

    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'department_id' => Department::factory(),
            'is_primary' => false,
            'start_date' => now()->subMonths(rand(1, 36)),
            'end_date' => null,
            'designation' => $this->faker->randomElement([
                'Senior', 'Junior', 'Head', 'Lead', 'Consultant',
                'Attending', 'Resident', 'Registrar', null,
            ]),
            'metadata' => null,
        ];
    }

    public function forStaff(Staff $staff): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_id' => $staff->id,
        ]);
    }

    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department->id,
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
            'end_date' => null,
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => now()->subDays(rand(1, 180)),
        ]);
    }

    public function startingNow(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now(),
        ]);
    }

    public function endingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => now()->addDays(rand(1, 30)),
        ]);
    }

    public function withDesignation(string $designation): static
    {
        return $this->state(fn (array $attributes) => [
            'designation' => $designation,
        ]);
    }
}
