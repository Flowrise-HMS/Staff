<?php

namespace Modules\Staff\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Modules\Staff\Models\Staff;

class StaffExporter extends Exporter
{
    protected static ?string $model = Staff::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('staff_number'),
            ExportColumn::make('title'),
            ExportColumn::make('first_name'),
            ExportColumn::make('middle_name'),
            ExportColumn::make('last_name'),
            ExportColumn::make('staff_type'),
            ExportColumn::make('employment_status'),
            ExportColumn::make('hire_date'),
            ExportColumn::make('termination_date'),
            ExportColumn::make('user.email'),
            ExportColumn::make('branch.name'),
            ExportColumn::make('created_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your staff export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
