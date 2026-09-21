<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Enums\Title;
use Modules\Core\Models\Branch;
use Modules\Patient\Enums\Gender;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\CreateStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\EditStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\ListStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\Pages\ViewStaff;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\StaffResource;
use Modules\Staff\Models\Staff;
use Tests\Support\FilamentResourceTestSuite;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->requireModule('Staff');
    $this->migrateModules(['Core', 'Staff']);
    $this->branch = Branch::factory()->create();
    $this->setCurrentBranch($this->branch);
});

FilamentResourceTestSuite::register([
    'resource' => StaffResource::class,
    'subject' => 'Staff',
    'model' => Staff::class,
    'listPage' => ListStaff::class,
    'createPage' => CreateStaff::class,
    'editPage' => EditStaff::class,
    'viewPage' => ViewStaff::class,
    'searchColumn' => 'staff_number',
    'sortColumn' => 'staff_number',
    'hasBulkDelete' => true,
    'hasRecordDelete' => true,
    'softDeletes' => true,
    'userAttributes' => fn (TestCase $test): array => ['branch_id' => $test->branch->id],
    'makeRecord' => fn (TestCase $test, array $attributes = []): Staff => Staff::factory()->create([
        'branch_id' => $test->branch->id,
        ...$attributes,
    ]),
    'makeRecords' => fn (TestCase $test, int $count) => Staff::factory()->count($count)->create([
        'branch_id' => $test->branch->id,
        'staff_type' => StaffType::FULL_TIME,
    ]),
    'createForm' => fn (TestCase $test): array => [
        'branch_id' => $test->branch->id,
        'title' => Title::MR->value,
        'first_name' => 'Kwame',
        'last_name' => 'Mensah',
        'gender' => Gender::MALE->value,
        'staff_type' => StaffType::FULL_TIME->value,
        'employment_status' => EmploymentStatus::ACTIVE->value,
        'address' => [
            'country' => null,
            'region' => null,
        ],
        'emergency_contact' => [
            'relationship' => null,
        ],
    ],
    'updateForm' => fn (): array => [
        'first_name' => 'Updated',
        'last_name' => 'Clinician',
        'staff_type' => StaffType::PART_TIME->value,
        'address' => [
            'country' => null,
            'region' => null,
        ],
        'emergency_contact' => [
            'relationship' => null,
        ],
    ],
    'schemaState' => fn (mixed $test, Staff $record): array => [
        'first_name' => $record->first_name,
        'last_name' => $record->last_name,
        'gender' => $record->gender,
    ],
    'requiredValidation' => [
        'first name is required' => [['first_name' => null], ['first_name' => 'required']],
        'last name is required' => [['last_name' => null], ['last_name' => 'required']],
        'gender is required' => [['gender' => null], ['gender' => 'required']],
        'staff type is required' => [['staff_type' => null], ['staff_type' => 'required']],
    ],
    'databaseHasOnCreate' => fn (mixed $test, array $payload): array => [
        'first_name' => $payload['first_name'],
        'last_name' => $payload['last_name'],
        'gender' => $payload['gender'],
    ],
]);
