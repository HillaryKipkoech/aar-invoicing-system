<?php

namespace App\Filament\Resources\SalesEmployeeResource\Pages;

use App\Filament\Resources\SalesEmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesEmployees extends ListRecords
{
    protected static string $resource = SalesEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
