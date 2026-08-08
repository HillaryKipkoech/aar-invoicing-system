<?php

namespace App\Filament\Resources\SalesEmployeeResource\Pages;

use App\Filament\Resources\SalesEmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesEmployee extends EditRecord
{
    protected static string $resource = SalesEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
