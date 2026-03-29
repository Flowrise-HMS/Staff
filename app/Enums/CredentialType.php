<?php

namespace Modules\Staff\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum CredentialType: string implements HasColor, HasLabel
{
    case MEDICAL_LICENSE = 'medical_license';
    case NURSING_LICENSE = 'nursing_license';
    case PHARMACY_LICENSE = 'pharmacy_license';
    case SPECIALTY_CERTIFICATION = 'specialty_certification';
    case BLS_CERTIFICATION = 'bls_certification';
    case ACLS_CERTIFICATION = 'acls_certification';
    case PALS_CERTIFICATION = 'pals_certification';
    case ADVANCED_RADIOLOGY_CERTIFICATION = 'advanced_radiology_certification';
    case LABORATORY_ACCREDITATION = 'laboratory_accreditation';
    case PHLEBOTOMY_CERTIFICATION = 'phlebotomy_certification';
    case DENTAL_LICENSE = 'dental_license';
    case PHARMACY_TECHNICIAN_LICENSE = 'pharmacy_technician_license';
    case NPI_NUMBER = 'npi_number';
    case DEA_REGISTRATION = 'dea_registration';
    case BOARD_CERTIFICATION = 'board_certification';
    case RESIDENCY_CERTIFICATE = 'residency_certificate';
    case BLS_CPR_CERTIFICATION = 'bls_cpr_certification';
    case TRAUMA_LIFE_SUPPORT = 'trauma_life_support';
    case PEDIATRIC_LIFE_SUPPORT = 'pediatric_life_support';
    case NEONATAL_LIFE_SUPPORT = 'neonatal_life_support';
    case OCCUPATIONAL_LICENSE = 'occupational_license';
    case PROFESSIONAL_INDEMNITY_INSURANCE = 'professional_indemnity_insurance';
    case HEALTH_SAFETY_CERTIFICATION = 'health_safety_certification';
    case DATA_PROTECTION_CERTIFICATION = 'data_protection_certification';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::MEDICAL_LICENSE => 'Medical License',
            self::NURSING_LICENSE => 'Nursing License',
            self::PHARMACY_LICENSE => 'Pharmacy License',
            self::SPECIALTY_CERTIFICATION => 'Specialty Certification',
            self::BLS_CERTIFICATION => 'Basic Life Support (BLS)',
            self::ACLS_CERTIFICATION => 'Advanced Cardiac Life Support (ACLS)',
            self::PALS_CERTIFICATION => 'Pediatric Advanced Life Support (PALS)',
            self::ADVANCED_RADIOLOGY_CERTIFICATION => 'Advanced Radiology Certification',
            self::LABORATORY_ACCREDITATION => 'Laboratory Accreditation',
            self::PHLEBOTOMY_CERTIFICATION => 'Phlebotomy Certification',
            self::DENTAL_LICENSE => 'Dental License',
            self::PHARMACY_TECHNICIAN_LICENSE => 'Pharmacy Technician License',
            self::NPI_NUMBER => 'National Provider Identifier (NPI)',
            self::DEA_REGISTRATION => 'DEA Registration',
            self::BOARD_CERTIFICATION => 'Board Certification',
            self::RESIDENCY_CERTIFICATE => 'Residency Certificate',
            self::BLS_CPR_CERTIFICATION => 'BLS/CPR Certification',
            self::TRAUMA_LIFE_SUPPORT => 'Trauma Life Support',
            self::PEDIATRIC_LIFE_SUPPORT => 'Pediatric Life Support',
            self::NEONATAL_LIFE_SUPPORT => 'Neonatal Life Support',
            self::OCCUPATIONAL_LICENSE => 'Occupational License',
            self::PROFESSIONAL_INDEMNITY_INSURANCE => 'Professional Indemnity Insurance',
            self::HEALTH_SAFETY_CERTIFICATION => 'Health & Safety Certification',
            self::DATA_PROTECTION_CERTIFICATION => 'Data Protection Certification',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::MEDICAL_LICENSE, self::DENTAL_LICENSE => 'primary',
            self::NURSING_LICENSE, self::PHARMACY_LICENSE,
            self::PHARMACY_TECHNICIAN_LICENSE => 'info',

            self::BLS_CERTIFICATION, self::ACLS_CERTIFICATION,
            self::PALS_CERTIFICATION, self::BLS_CPR_CERTIFICATION,
            self::TRAUMA_LIFE_SUPPORT, self::PEDIATRIC_LIFE_SUPPORT,
            self::NEONATAL_LIFE_SUPPORT => 'success',

            self::SPECIALTY_CERTIFICATION, self::BOARD_CERTIFICATION,
            self::RESIDENCY_CERTIFICATE => 'purple',

            self::ADVANCED_RADIOLOGY_CERTIFICATION,
            self::LABORATORY_ACCREDITATION,
            self::PHLEBOTOMY_CERTIFICATION => 'warning',

            self::NPI_NUMBER, self::DEA_REGISTRATION => 'teal',

            self::OCCUPATIONAL_LICENSE => 'orange',
            self::PROFESSIONAL_INDEMNITY_INSURANCE => 'secondary',

            self::HEALTH_SAFETY_CERTIFICATION,
            self::DATA_PROTECTION_CERTIFICATION => 'gray',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function requiresExpiry(): bool
    {
        return ! in_array($this, [self::NPI_NUMBER, self::DEA_REGISTRATION]);
    }

    public function isLifeSupport(): bool
    {
        return in_array($this, [
            self::BLS_CERTIFICATION, self::ACLS_CERTIFICATION, self::PALS_CERTIFICATION,
            self::BLS_CPR_CERTIFICATION, self::TRAUMA_LIFE_SUPPORT,
            self::PEDIATRIC_LIFE_SUPPORT, self::NEONATAL_LIFE_SUPPORT,
        ]);
    }

    public function isLicense(): bool
    {
        return in_array($this, [
            self::MEDICAL_LICENSE, self::NURSING_LICENSE, self::PHARMACY_LICENSE,
            self::DENTAL_LICENSE, self::PHARMACY_TECHNICIAN_LICENSE,
            self::OCCUPATIONAL_LICENSE,
        ]);
    }
}
