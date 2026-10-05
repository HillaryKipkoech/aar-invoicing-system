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
        return [
            Actions\CreateAction::make()
                ->label('New Sales Employee')
                ->icon('heroicon-o-plus-circle')
                ->color('info')
                ->modalHeading('New Sales Employee')
                ->modalSubmitActionLabel('Create')
                ->createAnother(false)
                ->slideOver(),
        ];
    }
}