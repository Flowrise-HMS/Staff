<?php

namespace Modules\Staff\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum StaffRole: string implements HasColor, HasDescription, HasLabel
{
    case PROVIDER = 'provider';
    case NURSE = 'nurse';
    case ADMINISTRATOR = 'administrator';
    case BILLING = 'billing';
    case RECEPTIONIST = 'receptionist';
    case PHARMACIST = 'pharmacist';
    case LAB_TECHNICIAN = 'lab_technician';
    case RADIOGRAPHER = 'radiographer';
    case PHYSIOTHERAPIST = 'physiotherapist';
    case SOCIAL_WORKER = 'social_worker';
    case COUNSELOR = 'counselor';
    case DENTIST = 'dentist';
    case OPTOMETRIST = 'optometrist';
    case NUTRITIONIST = 'nutritionist';
    case ANESTHETIST = 'anesthetist';
    case SURGEON = 'surgeon';
    case CARDIOLOGIST = 'cardiologist';
    case NEUROLOGIST = 'neurologist';
    case PEDIATRICIAN = 'pediatrician';
    case PSYCHIATRIST = 'psychiatrist';
    case GENERAL_PRACTITIONER = 'general_practitioner';
    case EMERGENCY_PHYSICIAN = 'emergency_physician';
    case RADIOLOGIST = 'radiologist';
    case PATHOLOGIST = 'pathologist';
    case PHLEBOTOMIST = 'phlebotomist';
    case OPERATING_THEATRE_TECHNICIAN = 'operating_theatre_technician';
    case WARD_CLERK = 'ward_clerk';
    case CASE_MANAGER = 'case_manager';
    case HEALTH_INFORMATION_MANAGER = 'health_information_manager';
    case BIOMEDICAL_ENGINEER = 'biomedical_engineer';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::PROVIDER => 'Provider',
            self::NURSE => 'Nurse',
            self::ADMINISTRATOR => 'Administrator',
            self::BILLING => 'Billing Staff',
            self::RECEPTIONIST => 'Receptionist',
            self::PHARMACIST => 'Pharmacist',
            self::LAB_TECHNICIAN => 'Lab Technician',
            self::RADIOGRAPHER => 'Radiographer',
            self::PHYSIOTHERAPIST => 'Physiotherapist',
            self::SOCIAL_WORKER => 'Social Worker',
            self::COUNSELOR => 'Counselor',
            self::DENTIST => 'Dentist',
            self::OPTOMETRIST => 'Optometrist',
            self::NUTRITIONIST => 'Nutritionist',
            self::ANESTHETIST => 'Anesthetist',
            self::SURGEON => 'Surgeon',
            self::CARDIOLOGIST => 'Cardiologist',
            self::NEUROLOGIST => 'Neurologist',
            self::PEDIATRICIAN => 'Pediatrician',
            self::PSYCHIATRIST => 'Psychiatrist',
            self::GENERAL_PRACTITIONER => 'General Practitioner',
            self::EMERGENCY_PHYSICIAN => 'Emergency Physician',
            self::RADIOLOGIST => 'Radiologist',
            self::PATHOLOGIST => 'Pathologist',
            self::PHLEBOTOMIST => 'Phlebotomist',
            self::OPERATING_THEATRE_TECHNICIAN => 'Operating Theatre Technician',
            self::WARD_CLERK => 'Ward Clerk',
            self::CASE_MANAGER => 'Case Manager',
            self::HEALTH_INFORMATION_MANAGER => 'Health Information Manager',
            self::BIOMEDICAL_ENGINEER => 'Biomedical Engineer',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::PROVIDER => 'General healthcare provider designation',
            self::NURSE => 'Registered or enrolled nurse',
            self::ADMINISTRATOR => 'Administrative/management staff',
            self::BILLING => 'Finance and billing department staff',
            self::RECEPTIONIST => 'Front desk and patient registration',
            self::PHARMACIST => 'Licensed pharmacist',
            self::LAB_TECHNICIAN => 'Laboratory technical staff',
            self::RADIOGRAPHER => 'Medical imaging technician',
            self::PHYSIOTHERAPIST => 'Physical rehabilitation specialist',
            self::SOCIAL_WORKER => 'Medical social worker',
            self::COUNSELOR => 'Mental health counselor',
            self::DENTIST => 'Licensed dentist',
            self::OPTOMETRIST => 'Eye care specialist',
            self::NUTRITIONIST => 'Clinical nutritionist/dietician',
            self::ANESTHETIST => 'Anesthesia specialist',
            self::SURGEON => 'Surgical specialist',
            self::CARDIOLOGIST => 'Heart and cardiovascular specialist',
            self::NEUROLOGIST => 'Neurological specialist',
            self::PEDIATRICIAN => 'Child health specialist',
            self::PSYCHIATRIST => 'Psychiatric specialist',
            self::GENERAL_PRACTITIONER => 'General practice physician',
            self::EMERGENCY_PHYSICIAN => 'Emergency department physician',
            self::RADIOLOGIST => 'Diagnostic imaging specialist',
            self::PATHOLOGIST => 'Laboratory medicine specialist',
            self::PHLEBOTOMIST => 'Blood collection specialist',
            self::OPERATING_THEATRE_TECHNICIAN => 'Surgical theater support',
            self::WARD_CLERK => 'Ward administration support',
            self::CASE_MANAGER => 'Patient case coordinator',
            self::HEALTH_INFORMATION_MANAGER => 'Medical records manager',
            self::BIOMEDICAL_ENGINEER => 'Medical equipment engineer',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PROVIDER, self::SURGEON, self::CARDIOLOGIST, self::NEUROLOGIST,
            self::PEDIATRICIAN, self::PSYCHIATRIST, self::GENERAL_PRACTITIONER,
            self::EMERGENCY_PHYSICIAN, self::RADIOLOGIST, self::PATHOLOGIST,
            self::DENTIST, self::ANESTHETIST => 'primary',

            self::NURSE => 'info',

            self::PHARMACIST, self::LAB_TECHNICIAN, self::PHLEBOTOMIST,
            self::RADIOGRAPHER => 'warning',

            self::PHYSIOTHERAPIST, self::NUTRITIONIST,
            self::OPTOMETRIST => 'teal',

            self::COUNSELOR, self::SOCIAL_WORKER => 'purple',

            self::ADMINISTRATOR, self::BILLING, self::RECEPTIONIST,
            self::WARD_CLERK, self::HEALTH_INFORMATION_MANAGER => 'gray',

            self::CASE_MANAGER => 'secondary',

            self::OPERATING_THEATRE_TECHNICIAN, self::BIOMEDICAL_ENGINEER => 'orange',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function default(): self
    {
        return self::PROVIDER;
    }

    public function isMedicalDoctor(): bool
    {
        return in_array($this, [
            self::SURGEON, self::CARDIOLOGIST, self::NEUROLOGIST,
            self::PEDIATRICIAN, self::PSYCHIATRIST, self::GENERAL_PRACTITIONER,
            self::EMERGENCY_PHYSICIAN, self::RADIOLOGIST, self::PATHOLOGIST,
            self::DENTIST, self::ANESTHETIST, self::PROVIDER,
        ]);
    }

    public function isNursing(): bool
    {
        return $this === self::NURSE;
    }

    public function isAlliedHealth(): bool
    {
        return in_array($this, [
            self::PHARMACIST, self::LAB_TECHNICIAN, self::RADIOGRAPHER,
            self::PHYSIOTHERAPIST, self::NUTRITIONIST, self::OPTOMETRIST,
            self::PHLEBOTOMIST, self::SOCIAL_WORKER, self::COUNSELOR,
            self::OPERATING_THEATRE_TECHNICIAN, self::BIOMEDICAL_ENGINEER,
        ]);
    }

    public function isAdministrative(): bool
    {
        return in_array($this, [
            self::ADMINISTRATOR, self::BILLING, self::RECEPTIONIST,
            self::WARD_CLERK, self::CASE_MANAGER, self::HEALTH_INFORMATION_MANAGER,
        ]);
    }

    public function requiresLicense(): bool
    {
        return in_array($this, [
            self::PROVIDER, self::NURSE, self::PHARMACIST, self::DENTIST,
            self::OPTOMETRIST, self::ANESTHETIST, self::SURGEON, self::CARDIOLOGIST,
            self::NEUROLOGIST, self::PEDIATRICIAN, self::PSYCHIATRIST,
            self::GENERAL_PRACTITIONER, self::EMERGENCY_PHYSICIAN, self::RADIOLOGIST,
            self::PATHOLOGIST, self::PHYSIOTHERAPIST, self::RADIOGRAPHER,
            self::LAB_TECHNICIAN, self::SOCIAL_WORKER,
        ]);
    }
}
