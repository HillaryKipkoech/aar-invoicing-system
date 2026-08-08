<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\SalesEmployee;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ManageInvoice extends Page
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.resources.invoice-resource.pages.manage-invoice';

    public ?Invoice $record = null;

    public string $activeTab = 'Contents';

    // ---- Header fields ----
    public ?int $customer_id = null;
    public string $display_name = '';
    public string $contact_person = '';
    public string $bp_currency = '';
    public string $kra_pin = '';
    public string $doc_no = '';
    public string $status = 'Open';
    public ?string $posting_date = null;
    public ?string $value_date = null;
    public ?string $document_date = null;

    // ---- Lines ----
    public array $lines = [];

    // ---- Footer / totals ----
    public ?int $sales_employee_id = null;
    public string $owner = '';
    public bool $payment_order_run = false;
    public string $remarks = '';
    public string $qr_code = '';

    public float $total_before_discount = 0;
    public float $discount_percent = 0;
    public float $total_down_payment = 0;
    public float $freight = 0;
    public bool $rounding = false;
    public float $tax = 0;
    public float $total_after_discount = 0;
    public float $applied_amount = 0;
    public float $balance_due = 0;

    public bool $needs_approval = false;
    public float $approval_amount = 0;

    public function mount(?Invoice $record = null): void
    {
        $this->record = $record?->exists ? $record : null;
        $this->posting_date = now()->format('Y-m-d');
        $this->value_date = now()->format('Y-m-d');
        $this->document_date = now()->format('Y-m-d');
        $this->doc_no = Invoice::nextDocNo();

      if ($this->record) {
    $this->fill([
        'customer_id' => $this->record->customer_id,
        'doc_no' => $this->record->doc_no ?? '',
        'status' => $this->record->status ?? 'Open',
        'posting_date' => optional($this->record->posting_date)->format('Y-m-d'),
        'value_date' => optional($this->record->value_date)->format('Y-m-d'),
        'document_date' => optional($this->record->document_date)->format('Y-m-d'),
        'sales_employee_id' => $this->record->sales_employee_id,
        'remarks' => $this->record->remarks ?? '',
        'qr_code' => $this->record->qr_code ?? '',
        'freight' => $this->record->freight ?? 0,
        'tax' => $this->record->tax ?? 0,
        'discount_percent' => $this->record->discount_percent ?? 0,
        'total_down_payment' => $this->record->total_down_payment ?? 0,
    ]);
    $this->lines = $this->record->lines()->get()->map(fn ($l) => $l->toArray())->toArray();
    $this->loadCustomer($this->customer_id);
    $this->loadSalesEmployee($this->sales_employee_id);
} else {
    $this->addLine();
}

        $this->recalculateTotals();
    }

    // ---- Customer lookup ----
    public function updatedCustomerId($value): void
    {
        $this->loadCustomer($value);
    }

    protected function loadCustomer($customerId): void
        {
            $customer = Customer::find($customerId);
            if (! $customer) {
                return;
            }
            $this->display_name = $customer->display_name ?? '';
            $this->bp_currency = $customer->currency ?? '';
            $this->kra_pin = $customer->kra_pin ?? '';
        }

    protected function loadSalesEmployee($employeeId): void
    {
        $employee = SalesEmployee::find($employeeId);
        $this->owner = $employee?->name ?? '';
    }

    public function updatedSalesEmployeeId($value): void
    {
        $this->loadSalesEmployee($value);
    }

    // ---- Posting date cascades to Value/Document date, mirroring SAP B1 ----
    public function updatedPostingDate($value): void
    {
        $this->value_date = $value;
        $this->document_date = $value;
    }

    // ---- Line management ----
    public function addLine(): void
    {
        $this->lines[] = [
            'item_no' => '',
            'item_description' => '',
            'quantity' => 0,
            'warehouse' => '',
            'qty_in_whse' => 0,
            'uom_code' => '',
            'price_before_discount' => 0,
            'discount' => 0,
            'price_after_discount' => 0,
            'vat_code' => 'O0',
            'gross_price_after_discount' => 0,
            'line_total' => 0,
            'gross_total' => 0,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->recalculateTotals();
    }

    public function updatedLines($value, $key): void
    {
        // $key looks like "3.item_no" — only react to item selection or number fields
        if (str_contains($key, '.item_no')) {
            [$index] = explode('.', $key);
            $item = Item::where('item_no', $this->lines[$index]['item_no'])->first();
            if ($item) {
                $this->lines[$index]['item_description'] = $item->item_description;
                $this->lines[$index]['price_before_discount'] = $item->unit_price;
                $this->lines[$index]['uom_code'] = $item->uom_code;
                $this->lines[$index]['warehouse'] = $item->warehouse;
                $this->lines[$index]['qty_in_whse'] = $item->qty_in_whse;
                $this->lines[$index]['vat_code'] = $item->vat_code;
            }
        }

        $this->recalculateTotals();
    }

    public function updatedDiscountPercent(): void { $this->recalculateTotals(); }
    public function updatedFreight(): void { $this->recalculateTotals(); }
    public function updatedTax(): void { $this->recalculateTotals(); }
    public function updatedTotalDownPayment(): void { $this->recalculateTotals(); }

    /*
    |--------------------------------------------------------------------
    | TOTALS ENGINE — same logic as the original recalculateTotals(),
    | just operating on Livewire properties instead of Get/Set.
    |--------------------------------------------------------------------
    */
    public function recalculateTotals(): void
    {
        $totalBeforeDiscount = 0;
        $grandTotal = 0;

        foreach ($this->lines as $index => $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $price = (float) ($line['price_before_discount'] ?? 0);
            $discount = (float) ($line['discount'] ?? 0);

            $priceAfterDiscount = round($price - ($price * $discount / 100), 3);
            $lineTotal = round($priceAfterDiscount * $qty, 3);

            $this->lines[$index]['price_after_discount'] = $priceAfterDiscount;
            $this->lines[$index]['gross_price_after_discount'] = $priceAfterDiscount;
            $this->lines[$index]['line_total'] = $lineTotal;
            $this->lines[$index]['gross_total'] = $lineTotal;

            $totalBeforeDiscount += round($price * $qty, 3);
            $grandTotal += $lineTotal;
        }

        $this->total_before_discount = round($totalBeforeDiscount, 3);

        $this->total_after_discount = round(
            $grandTotal - ($grandTotal * $this->discount_percent / 100) + $this->freight + $this->tax,
            3
        );

        $this->balance_due = round($this->total_after_discount - $this->total_down_payment, 3);

        // ---- Approval threshold: change here to adjust the rule ----
        $this->needs_approval = $this->total_after_discount > Invoice::APPROVAL_THRESHOLD;
        $this->approval_amount = $this->total_after_discount;
    }

    // ---- Save actions ----
    public function addAndNew(): void
    {
        $this->save('Open');
        $this->redirect(static::getResource()::getUrl('create'));
    }

    public function addDraftAndNew(): void
    {
        $this->save('Draft');
        $this->redirect(static::getResource()::getUrl('create'));
    }

    protected function save(string $status): void
    {
        $this->validate([
            'customer_id' => ['required'],
            'sales_employee_id' => ['required'],
            'remarks' => ['required'],
            'lines.*.discount' => ['numeric', 'max:50'],
        ], [
            'remarks.required' => 'Remarks is mandatory.',
            'lines.*.discount.max' => 'Discount cannot exceed 50%.',
        ]);

        $invoice = $this->record ?? new Invoice();
        $invoice->fill([
            'customer_id' => $this->customer_id,
            'doc_no' => $this->record?->doc_no ?? Invoice::nextDocNo(),
            'status' => $status,
            'posting_date' => $this->posting_date,
            'value_date' => $this->value_date,
            'document_date' => $this->document_date,
            'sales_employee_id' => $this->sales_employee_id,
            'owner' => Auth::user()?->name,
            'remarks' => $this->remarks,
            'qr_code' => $this->qr_code,
            'total_before_discount' => $this->total_before_discount,
            'discount_percent' => $this->discount_percent,
            'total_down_payment' => $this->total_down_payment,
            'freight' => $this->freight,
            'rounding' => $this->rounding,
            'tax' => $this->tax,
            'total_after_discount' => $this->total_after_discount,
            'balance_due' => $this->balance_due,
            'needs_approval' => $this->needs_approval,
        ]);
        $invoice->save();

        $invoice->lines()->delete();
        foreach ($this->lines as $line) {
            $invoice->lines()->create($line);
        }

        $this->record = $invoice;
    }

    public function cancel()
    {
        return redirect(static::getResource()::getUrl('index'));
    }

    public function getTitle(): string
        {
            return '';
        }

        public function getBreadcrumbs(): array
        {
            return [];
        }

        protected function getHeaderActions(): array
        {
            return [];
        }
}