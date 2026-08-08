<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Customer;
use App\Models\Invoice;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    // Replaces Filament's default "Create" button with the SAP-B1-style
    // Add & New / Add Draft & New / Cancel buttons.
    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('addAndNew')
                ->label('Add & New')
                ->color('primary')
                ->action('createAndNew'),

            Actions\Action::make('addDraftAndNew')
                ->label('Add Draft & New')
                ->color('gray')
                ->action(function () {
                    $this->data['status'] = 'Draft';
                    $this->create();
                }),

            Actions\Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('index')),
        ];
    }

    public function createAndNew(): void
    {
        $this->create();
        $this->redirect(static::getResource()::getUrl('create'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $customer = Customer::find($data['customer_id']);

        $data['customer_code'] = $customer?->customer_code;
        $data['customer_name'] = $customer?->customer_name;
        $data['doc_no'] = Invoice::nextDocNo();
        $data['status'] = $data['status'] ?? 'Open';

        // Server-side guard mirroring the UI validations.
        foreach ($data['lines'] ?? [] as $line) {
            if (($line['discount'] ?? 0) > 50) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'lines' => 'Discount cannot exceed 50%.',
                ]);
            }
        }

        if (empty(trim($data['remarks'] ?? ''))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'remarks' => 'Remarks is mandatory.',
            ]);
        }

        return $data;
    }
}
