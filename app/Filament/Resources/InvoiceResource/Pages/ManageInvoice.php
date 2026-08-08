<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\SalesEmployee;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageInvoice extends Page
{

    
    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.resources.invoice-resource.pages.manage-invoice';

    public ?Invoice $record = null;

    public string $activeTab = 'Contents';

    // ---- Header fields ----
    public ?int $customer_id = null;
    public string $display_name = '';
    public string $customer_name = '';
    public string $contact_person = '';
    public array $contact_persons = [];
    public string $bp_currency = '';
    public string $kra_pin = '';
    public string $doc_no = '';
    public string $status = 'Open';
    public ?string $posting_date = null;
    public ?string $value_date = null;
    public ?string $document_date = null;
    public string $customer_code = '';

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
    public float $discount_amount = 0;
    public float $total_down_payment = 0;
    public float $freight = 0;
    public bool $rounding = false;
    public float $tax = 0;
    public float $total_after_discount = 0;
    public float $applied_amount = 0;
    public float $balance_due = 0;
    // ---- Customer search ----
    public string $customerSearch = '';
    public array $customerResults = [];
    public bool $showCustomerDropdown = false;

    public string $customerNameSearch = '';
    public array $customerNameResults = [];
    public bool $showCustomerNameDropdown = false;

    // ---- Sales employee search ----
    public string $salesEmployeeSearch = '';
    public array $salesEmployeeResults = [];
    public bool $showSalesEmployeeDropdown = false;

    public bool $needs_approval = false;
    public float $approval_amount = 0;

    public function mount(?Invoice $record = null): void
    {
        $this->record = $record?->exists ? $record : null;
        $this->posting_date = now()->format('Y-m-d');
        $this->value_date = now()->format('Y-m-d');
        $this->document_date = now()->format('Y-m-d');
        
        if (! $this->record) {
            $this->doc_no = Invoice::nextDocNo();
        }

        if ($this->record) {
            $this->fill([
                'customer_id' => $this->record->customer_id,
                'customer_name' => $this->record->customer_name ?? '',
                'contact_person' => $this->record->contact_person ?? '',
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
                'applied_amount' => $this->record->applied_amount ?? 0,
            ]);
            $this->customerSearch = Customer::find($this->customer_id)?->customer_code ?? '';
            $this->customerSearch = $this->customer_code;
            $this->customerNameSearch = Customer::find($this->customer_id)?->display_name ?? '';
            $this->salesEmployeeSearch = SalesEmployee::find($this->sales_employee_id)?->name ?? '';
            $this->lines = $this->record->lines()->get()->map(fn ($l) => $l->toArray())->toArray();
            $this->loadCustomer($this->customer_id, keepContactPerson: true, keepCustomerName: true);
            $this->loadSalesEmployee($this->sales_employee_id);
        } else {
            $this->addLine();
        }
     

        $this->recalculateTotals();
    }
    // ---- Customer lookup ----
    public function updatedCustomerId($value): void
    {
        $this->loadCustomer($value, keepContactPerson: false, keepCustomerName: false);
    }

    protected function loadCustomer($customerId, bool $keepContactPerson = false, bool $keepCustomerName = false): void
    {
        $customer = Customer::find($customerId);
        if (! $customer) {
            $this->contact_persons = [];
            return;
        }
        $this->display_name = $customer->display_name ?? '';
        $this->bp_currency = $customer->currency ?? '';
        $this->kra_pin = $customer->kra_pin ?? '';

        if (! $keepContactPerson) {
            $this->contact_person = $customer->contact_person ?? '';
        }

        // "Customer Name" is the bold, editable field on the invoice — it
        // defaults from the BP master but the user can override the printed
        // name per document, so we only seed it, never force it.
        if (! $keepCustomerName) {
            $this->customer_name = $customer->display_name ?? $customer->name ?? '';
        }

        // Populate the Contact Person dropdown. Adjust this relation name
        // to whatever your Customer model actually exposes — falls back to
        // an empty list so the field just renders as "-- select --".
        $this->contact_persons = method_exists($customer, 'contactPersons')
            ? $customer->contactPersons()->get(['id', 'name'])->toArray()
            : [];
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
    | TOTALS ENGINE
    |--------------------------------------------------------------------
    */
   public function recalculateTotals(): void
{
    bcscale(6); // internal precision; we round for display/storage below

    $totalBeforeDiscount = '0';
    $grandTotal = '0';

    foreach ($this->lines as $index => $line) {
        $qty = (string) ($line['quantity'] ?? 0);
        $price = (string) ($line['price_before_discount'] ?? 0);
        $discount = (string) ($line['discount'] ?? 0);

        $discountFactor = bcsub('1', bcdiv($discount, '100'));
        $priceAfterDiscount = bcmul($price, $discountFactor);
        $lineTotal = bcmul($priceAfterDiscount, $qty);

        $this->lines[$index]['price_after_discount'] = (float) bcadd($priceAfterDiscount, '0', 3);
        $this->lines[$index]['gross_price_after_discount'] = (float) bcadd($priceAfterDiscount, '0', 3);
        $this->lines[$index]['line_total'] = (float) bcadd($lineTotal, '0', 3);
        $this->lines[$index]['gross_total'] = (float) bcadd($lineTotal, '0', 3);

        $totalBeforeDiscount = bcadd($totalBeforeDiscount, bcmul($price, $qty));
        $grandTotal = bcadd($grandTotal, $lineTotal);
    }

    $this->total_before_discount = (float) bcadd($totalBeforeDiscount, '0', 3);

    $this->discount_amount = (float) bcadd(
        bcmul($grandTotal, bcdiv((string) $this->discount_percent, '100')), '0', 3
    );

    $afterDiscount = bcsub($grandTotal, (string) $this->discount_amount);
    $afterDiscount = bcadd($afterDiscount, (string) $this->freight);
    $afterDiscount = bcadd($afterDiscount, (string) $this->tax);
    $this->total_after_discount = (float) bcadd($afterDiscount, '0', 3);

    $balance = bcsub((string) $this->total_after_discount, (string) $this->total_down_payment);
    $balance = bcsub($balance, (string) $this->applied_amount);
    $this->balance_due = (float) bcadd($balance, '0', 3);

    $this->needs_approval = $this->total_after_discount > Invoice::APPROVAL_THRESHOLD;
    $this->approval_amount = $this->total_after_discount;
}

    public function copyFrom(): void
    {
        // TODO: open a document picker (e.g. Filament modal action) letting
        // the user choose a Sales Order / Delivery to copy lines & header
        // fields from, then populate $this->lines and header properties.
    }

    public function copyTo(): void
    {
        // TODO: only meaningful once this invoice is saved — typically
        // copies this invoice forward into a Credit Memo, etc.
    }

    // ---- Save actions ----
    public function addAndNew(): void
    {
        $this->save('Open');
        // navigate: false forces a genuine fresh page load/mount, so the
        // "No." field is guaranteed to recompute against the row we just
        // inserted rather than reusing any client-side cached state.
        $this->redirect(static::getResource()::getUrl('create'), navigate: false);
    }

    public function addDraftAndNew(): void
    {
        $this->save('Draft');
        $this->redirect(static::getResource()::getUrl('create'), navigate: false);
    }

    protected function save(string $status): void
    {
        $this->validate([
             'customer_id' => ['required'],
            'customer_code' => ['required'],
            'sales_employee_id' => ['required'],
            'remarks' => ['required'],
            'lines.*.discount' => ['numeric', 'max:50'],
        ], [
            'remarks.required' => 'Remarks is mandatory.',
            'customer_code.required' => 'Please select a valid customer.',
            'lines.*.discount.max' => 'Discount cannot exceed 50%.',
        ]);

        $invoice = $this->record ?? new Invoice();
        $docNo = $this->record?->doc_no ?? DB::transaction(function () {
            return Invoice::nextDocNo();
        });

        $invoice->fill([
            'customer_id' => $this->customer_id,
            'customer_code' => $this->customer_code,
            'customer_name' => $this->customer_name,
            'contact_person' => $this->contact_person,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'contact_person' => $this->contact_person,
            'doc_no' => $docNo,
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
            'applied_amount' => $this->applied_amount,
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
  


    public function updatedCustomerSearch($value): void
{
    $this->showCustomerDropdown = true;
    if (strlen($value) < 1) {
        $this->customerResults = [];
        return;
    }
    $this->customerResults = Customer::query()
        ->where('customer_code', 'like', "%{$value}%")
        ->orWhere('display_name', 'like', "%{$value}%")
        ->limit(20)
        ->get(['id', 'customer_code', 'display_name'])
        ->toArray();
}

public function updatedCustomerNameSearch($value): void
{
    $this->showCustomerNameDropdown = true;
    if (strlen($value) < 1) {
        $this->customerNameResults = [];
        return;
    }
    $this->customerNameResults = Customer::query()
        ->where('display_name', 'like', "%{$value}%")
        ->orWhere('customer_code', 'like', "%{$value}%")
        ->limit(20)
        ->get(['id', 'customer_code', 'display_name'])
        ->toArray();
}

// public function selectCustomer(int $customerId): void
// {
//     $this->customer_id = $customerId;
//     $this->loadCustomer($customerId, keepContactPerson: false, keepCustomerName: false);

//     $customer = Customer::find($customerId);
//     $this->customerSearch = $customer?->customer_code ?? '';
//     $this->customerNameSearch = $customer?->display_name ?? '';

//     $this->showCustomerDropdown = false;
//     $this->showCustomerNameDropdown = false;
//     $this->customerResults = [];
//     $this->customerNameResults = [];
// }
        public function selectCustomer(int $customerId): void
        {
            $this->customer_id = $customerId;
            $this->loadCustomer($customerId, keepContactPerson: false, keepCustomerName: false);

            $customer = Customer::find($customerId);
            $this->customer_code = $customer?->customer_code ?? '';
            $this->customerSearch = $customer?->customer_code ?? '';
            $this->customerNameSearch = $customer?->display_name ?? '';

            $this->showCustomerDropdown = false;
            $this->showCustomerNameDropdown = false;
            $this->customerResults = [];
            $this->customerNameResults = [];
        }
        public function updatedSalesEmployeeSearch($value): void
        {
            $this->showSalesEmployeeDropdown = true;
            if (strlen($value) < 1) {
                $this->salesEmployeeResults = [];
                return;
            }
            $this->salesEmployeeResults = SalesEmployee::query()
                ->where('name', 'like', "%{$value}%")
                ->limit(20)
                ->get(['id', 'name'])
                ->toArray();
        }

        public function selectSalesEmployee(int $employeeId): void
        {
            $this->sales_employee_id = $employeeId;
            $this->loadSalesEmployee($employeeId);

            $this->salesEmployeeSearch = SalesEmployee::find($employeeId)?->name ?? '';
            $this->showSalesEmployeeDropdown = false;
            $this->salesEmployeeResults = [];
        }


        public function cancel()
        {
            return redirect(static::getResource()::getUrl('index'));
        }

        public function getTitle(): string
        {
            return 'AR Invoice';
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