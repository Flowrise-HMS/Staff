<?php

namespace Modules\Staff\Tests\Unit;

use Modules\Core\Support\ModuleAvailability;
use Modules\Staff\Filament\Clusters\StaffCluster\Resources\Staff\StaffResource;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class StaffResourceRelationManagersSoftBoundaryTest extends TestCase
{
    public function test_it_includes_attendance_relation_managers_when_attendance_is_enabled(): void
    {
        $this->requireModule('Attendance');

        $relations = StaffResource::getRelations();

        $this->assertContains(
            'Modules\\Attendance\\Filament\\RelationManagers\\Staff\\StaffAttendanceRecordsRelationManager',
            $relations,
        );
        $this->assertContains(
            'Modules\\Attendance\\Filament\\RelationManagers\\Staff\\StaffDailyAttendanceRelationManager',
            $relations,
        );
    }

    public function test_it_skips_attendance_relation_managers_when_attendance_is_disabled(): void
    {
        $this->requireModule('Attendance');

        $module = Module::find('Attendance');
        $this->assertNotNull($module);

        try {
            $module->disable();
            $this->assertFalse(ModuleAvailability::attendanceEnabled());

            $relations = StaffResource::getRelations();

            $this->assertNotContains(
                'Modules\\Attendance\\Filament\\RelationManagers\\Staff\\StaffAttendanceRecordsRelationManager',
                $relations,
            );
            $this->assertNotContains(
                'Modules\\Attendance\\Filament\\RelationManagers\\Staff\\StaffDailyAttendanceRelationManager',
                $relations,
            );
        } finally {
            $module->enable();
        }
    }

    public function test_it_always_keeps_core_relation_managers(): void
    {
        $relations = StaffResource::getRelations();

        $this->assertContains(
            'Modules\\Staff\\Filament\\Clusters\\StaffCluster\\Resources\\Staff\\RelationManagers\\CredentialsRelationManager',
            $relations,
        );
        $this->assertContains(
            'Modules\\Staff\\Filament\\Clusters\\StaffCluster\\Resources\\Staff\\RelationManagers\\DepartmentsRelationManager',
            $relations,
        );
        $this->assertContains(
            'Modules\\Staff\\Filament\\Clusters\\StaffCluster\\Resources\\Staff\\RelationManagers\\SpecialtiesRelationManager',
            $relations,
        );
    }

    public function test_it_includes_appointment_relation_manager_when_appointment_is_enabled(): void
    {
        $this->requireModule('Appointment');

        $relations = StaffResource::getRelations();

        $this->assertContains(
            'Modules\\Appointment\\Filament\\RelationManagers\\Staff\\StaffAppointmentsRelationManager',
            $relations,
        );
    }

    public function test_it_skips_appointment_relation_manager_when_appointment_is_disabled(): void
    {
        $this->requireModule('Appointment');

        $module = Module::find('Appointment');
        $this->assertNotNull($module);

        try {
            $module->disable();

            $relations = StaffResource::getRelations();

            $this->assertNotContains(
                'Modules\\Appointment\\Filament\\RelationManagers\\Staff\\StaffAppointmentsRelationManager',
                $relations,
            );
        } finally {
            $module->enable();
        }
    }
}
