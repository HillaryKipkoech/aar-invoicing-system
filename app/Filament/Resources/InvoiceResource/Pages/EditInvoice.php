<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // "Copy From" — pulls line items in from another document type.
            // Wire this up to a Sales Order / Quotation resource once one exists.
            Actions\Action::make('copyFrom')
                ->label('Copy From')
                ->color('gray')
                ->action(function () {
                    // TODO: open a modal listing source documents (e.g. Sales Orders)
                    // and copy their lines into $this->data['lines'].
                }),

            // "Copy To" — pushes this invoice's data into a new downstream
            // document (e.g. a Delivery or Credit Note).
            Actions\Action::make('copyTo')
                ->label('Copy To')
                ->color('gray')
                ->action(function () {
                    // TODO: implement once a target document type exists.
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $customer = Customer::find($data['customer_id']);
        $data['customer_code'] = $customer?->customer_code;
        $data['customer_name'] = $customer?->customer_name;

        // Same guard as CreateInvoice — edits are validated equally.
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
