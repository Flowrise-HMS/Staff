<?php

namespace Modules\Staff\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Enums\CredentialType;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffCredential;

/** @extends Factory<StaffCredential> */
class StaffCredentialFactory extends Factory
{
    protected $model = StaffCredential::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(CredentialType::cases());
        $issueDate = $this->faker->dateTimeBetween('-5 years', '-1 month');
        $expiryDate = $type->requiresExpiry()
            ? $this->faker->dateTimeBetween('+1 month', '+5 years')
            : null;

        return [
            'staff_id' => Staff::factory(),
            'credential_type' => $type,
            'credential_number' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{6,8}'),
            'issuing_authority' => $this->faker->randomElement([
                'Ghana Medical and Dental Council',
                'Nursing and Midwifery Council Ghana',
                'Pharmacy Council Ghana',
                'Allied Health Professions Council',
                'Ghana Health Service',
                'Ministry of Health Ghana',
            ]),
            'issuing_country' => 'Ghana',
            'issuing_state' => $this->faker->randomElement([
                'Greater Accra', 'Ashanti', 'Central', 'Eastern',
                'Northern', 'Upper East', 'Upper West', 'Volta',
            ]),
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'status' => CredentialStatus::PENDING,
            'verified_by' => null,
            'verified_at' => null,
            'verification_notes' => null,
            'rejection_reason' => null,
            'document_path' => null,
            'metadata' => null,
        ];
    }

    public function forStaff(Staff $staff): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_id' => $staff->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CredentialStatus::PENDING,
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CredentialStatus::VERIFIED,
            'verified_by' => User::factory()->create()->id,
            'verified_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CredentialStatus::EXPIRED,
            'expiry_date' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CredentialStatus::REJECTED,
            'verified_by' => User::factory()->create()->id,
            'verified_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CredentialStatus::UNDER_REVIEW,
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CredentialStatus::REVOKED,
            'verified_by' => User::factory()->create()->id,
            'verified_at' => now()->subMonths(rand(1, 12)),
            'verification_notes' => 'Credential revoked due to '.$this->faker->sentence(),
        ]);
    }

    public function expiringSoon(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CredentialStatus::VERIFIED,
            'verified_by' => User::factory()->create()->id,
            'verified_at' => now()->subMonths(rand(1, 12)),
            'expiry_date' => now()->addDays(rand(1, $days)),
        ]);
    }

    public function ofType(CredentialType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'credential_type' => $type,
        ]);
    }

    public function medicalLicense(): static
    {
        return $this->ofType(CredentialType::MEDICAL_LICENSE);
    }

    public function nursingLicense(): static
    {
        return $this->ofType(CredentialType::NURSING_LICENSE);
    }

    public function blsCertification(): static
    {
        return $this->ofType(CredentialType::BLS_CERTIFICATION);
    }

    public function pharmacyLicense(): static
    {
        return $this->ofType(CredentialType::PHARMACY_LICENSE);
    }

    public function withoutExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => null,
        ]);
    }

    public function withDocument(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_path' => 'credentials/'.$this->faker->uuid().'.pdf',
        ]);
    }
}
