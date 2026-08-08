<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Customer;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $customer = Customer::find($data['customer_id']);

        $data['customer_code'] = $customer?->customer_code;
        $data['customer_name'] = $customer?->customer_name;
        $data['doc_no'] = Invoice::nextDocNo();
        $data['status'] = 'Open';
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
