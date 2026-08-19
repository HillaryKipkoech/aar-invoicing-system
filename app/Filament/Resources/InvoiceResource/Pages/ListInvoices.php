<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        

        return [
            Actions\CreateAction::make()
                ->label('New Invoice')
                ->icon('heroicon-o-plus-circle')
                ->color('info')
                ->modalHeading('Create New Invoice')
                ->modalSubmitActionLabel('Create')
                ->createAnother(false)
                ->slideOver(),
        ];
    }
}
