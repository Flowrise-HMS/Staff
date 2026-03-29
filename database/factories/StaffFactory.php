<?php

namespace Modules\Staff\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Department;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Modules\Staff\Models\Staff;

/** @use HasFactory<Staff> */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();

        return [
            'user_id' => null,
            'staff_number' => 'STF-'.now()->format('Y').'-'.str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'title' => $this->faker->randomElement(['Dr', 'Mr', 'Mrs', 'Ms', 'Prof']),
            'first_name' => $firstName,
            'middle_name' => $this->faker->optional()->firstName(),
            'last_name' => $lastName,
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-25 years'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'address' => [
                'street' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'region' => $this->faker->state(),
                'postal_code' => $this->faker->postcode(),
                'country' => 'Ghana',
            ],
            'contact' => [
                'phone' => $this->faker->phoneNumber(),
                'email' => $this->faker->unique()->safeEmail(),
            ],
            'emergency_contact' => [
                'name' => $this->faker->name(),
                'phone' => $this->faker->phoneNumber(),
                'relationship' => $this->faker->randomElement(['Spouse', 'Parent', 'Sibling', 'Friend']),
            ],
            'staff_type' => StaffType::FULL_TIME,
            'employment_status' => EmploymentStatus::ACTIVE,
            'hire_date' => $this->faker->dateTimeBetween('-10 years', 'now'),
            'termination_date' => null,
            'termination_reason' => null,
            'notes' => null,
            'metadata' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function withUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->create()->id,
        ]);
    }

    public function withoutUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => EmploymentStatus::ACTIVE,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => EmploymentStatus::INACTIVE,
        ]);
    }

    public function onLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => EmploymentStatus::ON_LEAVE,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => EmploymentStatus::SUSPENDED,
        ]);
    }

    public function terminated(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => EmploymentStatus::TERMINATED,
            'termination_date' => now()->subDays(rand(1, 365)),
            'termination_reason' => $reason ?? $this->faker->sentence(),
        ]);
    }

    public function pendingVerification(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => EmploymentStatus::PENDING_VERIFICATION,
        ]);
    }

    public function fullTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_type' => StaffType::FULL_TIME,
        ]);
    }

    public function partTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_type' => StaffType::PART_TIME,
        ]);
    }

    public function contract(): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_type' => StaffType::CONTRACT,
        ]);
    }

    public function resident(): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_type' => StaffType::RESIDENT,
        ]);
    }

    public function consultant(): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_type' => StaffType::CONSULTANT,
        ]);
    }

    public function withDepartments(int $count = 1): static
    {
        return $this->afterCreating(function (Staff $staff) use ($count) {
            $departments = Department::factory()->count(min(1, $count))->create();

            foreach ($departments as $index => $department) {
                $staff->assignDepartment($department, $index === 0);
            }
        });
    }

    public function withCredentials(int $count = 1): static
    {
        return $this->afterCreating(function (Staff $staff) use ($count) {
            StaffCredentialFactory::new()->count($count)->create(['staff_id' => $staff->id]);
        });
    }

    public function withVerifiedCredentials(int $count = 1): static
    {
        return $this->afterCreating(function (Staff $staff) use ($count) {
            StaffCredentialFactory::new()->verified()->count($count)->create(['staff_id' => $staff->id]);
        });
    }

    public function withSpecialties(int $count = 1): static
    {
        return $this->afterCreating(function (Staff $staff) use ($count) {
            StaffSpecialtyFactory::new()->count($count)->create([
                'staff_id' => $staff->id,
                'is_primary' => true,
            ]);
        });
    }

    public function withAllRelations(): static
    {
        return $this->withUser()
            ->withDepartments(2)
            ->withVerifiedCredentials(3)
            ->withSpecialties(2);
    }

    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Mr',
            'gender' => 'male',
        ]);
    }

    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $this->faker->randomElement(['Mrs', 'Ms']),
            'gender' => 'female',
        ]);
    }

    public function recentlyHired(): static
    {
        return $this->state(fn (array $attributes) => [
            'hire_date' => now()->subDays(rand(1, 30)),
        ]);
    }

    public function veteran(int $years = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'hire_date' => now()->subYears($years),
        ]);
    }
}
