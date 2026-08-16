<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\SalesEmployee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?string $modelLabel = 'AR Invoice';

    // protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 0;

    public static function recalculateTotals(Get $get, Set $set): void
    {
        $lines = $get('lines') ?? [];
        $totalBeforeDiscount = 0;
        $grandTotal = 0;

        foreach ($lines as $key => $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $price = (float) ($line['price_before_discount'] ?? 0);
            $discount = (float) ($line['discount'] ?? 0);

            $priceAfterDiscount = round($price - ($price * $discount / 100), 3);
            $lineTotal = round($priceAfterDiscount * $qty, 3);

            $set("lines.{$key}.price_after_discount", $priceAfterDiscount);
            $set("lines.{$key}.gross_price_after_discount", $priceAfterDiscount); // to  extend VAT % logic
            $set("lines.{$key}.line_total", $lineTotal);
            $set("lines.{$key}.gross_total", $lineTotal); 

            $totalBeforeDiscount += round($price * $qty, 3);
            $grandTotal += $lineTotal;
        }

        $headerDiscountPercent = (float) ($get('discount_percent') ?? 0);
        $freight = (float) ($get('freight') ?? 0);
        $tax = (float) ($get('tax') ?? 0);
        $downPayment = (float) ($get('total_down_payment') ?? 0);

        $totalAfterDiscount = round(
            $grandTotal - ($grandTotal * $headerDiscountPercent / 100) + $freight + $tax,
            3
        );

        $set('total_before_discount', round($totalBeforeDiscount, 3));
        $set('total_after_discount', $totalAfterDiscount);

        $balanceDue = round($totalAfterDiscount - $downPayment, 3);
        $set('balance_due', $balanceDue);

 
        $set('needs_approval', $totalAfterDiscount > Invoice::APPROVAL_THRESHOLD);
        $set('approval_amount', $totalAfterDiscount);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Grid::make(2)->schema([

                // ---- LEFT block ----
                Forms\Components\Group::make([
                    Forms\Components\Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'customer_code')
                        ->searchable(['customer_code', 'display_name'])
                        ->getOptionLabelFromRecordUsing(
                            fn (Customer $record) =>
                                "{$record->customer_code} — {$record->display_name}"
                        )
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(
                            fn (Set $set, $state) =>
                                self::fillCustomerFields($set, $state)
                        )
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('chooseCustomer')
                                ->icon('heroicon-m-arrow-right-circle')
                                ->label('Choose From List')
                                ->modalHeading('Select Customer')
                                ->form([
                                    Forms\Components\Select::make('selected_customer')
                                        ->label('Customer')
                                        ->relationship('customer', 'customer_code')
                                        ->searchable(['customer_code', 'display_name'])
                                        ->getOptionLabelFromRecordUsing(
                                            fn (Customer $record) =>
                                                "{$record->customer_code} — {$record->display_name}"
                                        )
                                        ->required(),
                                ])
                                ->action(function (array $data, Set $set) {
                                    $set('customer_id', $data['selected_customer']);

                                    self::fillCustomerFields(
                                        $set,
                                        $data['selected_customer']
                                    );
                                })
                        ),

                    Forms\Components\TextInput::make('display_name_display')
                        ->label('Name')
                        ->disabled()->dehydrated(false),

                    Forms\Components\Select::make('contact_person')
                        ->label('Contact Person')
                        ->options([]) 
                        ->searchable(),
                    Forms\Components\Select::make('customer_id_by_name')
                        ->label('Customer Name')
                        ->options(fn () => Customer::query()->pluck('customer_name', 'id'))
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) => Customer::query()
                            ->where('customer_name', 'like', "%{$search}%")
                            ->limit(20)->get()
                            ->mapWithKeys(fn ($c) => [$c->id => "{$c->customer_name} — {$c->customer_code}"]))
                        ->getOptionLabelUsing(fn ($value) => optional(Customer::find($value))->customer_name)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, $state) => self::fillCustomerFields($set, $state))
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('bp_currency_display')
                        ->label('BP Currency')
                        ->disabled()->dehydrated(false),

                    Forms\Components\TextInput::make('kra_pin_display')
                        ->label('KRA PIN')
                        ->disabled()->dehydrated(false),
                ]),

                Forms\Components\Group::make([
                    Forms\Components\TextInput::make('doc_no')
                        ->label('No.')
                        ->default(fn () => Invoice::nextDocNo())
                        ->disabled()->dehydrated(),

                    Forms\Components\TextInput::make('status')
                        ->label('Status')
                        ->default('Open')
                        ->disabled()->dehydrated(),

                    Forms\Components\DatePicker::make('posting_date')
                        ->label('Posting Date')
                        ->default(now())
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            $set('value_date', $state);
                            $set('document_date', $state);
                        }),

                    Forms\Components\DatePicker::make('value_date')
                        ->label('Value Date')
                        ->default(now()),

                    Forms\Components\DatePicker::make('document_date')
                        ->label('Document Date')
                        ->default(now()),
                ]),
            ]),

            // Approval label - hidden unless total exceeds 10,000
            Forms\Components\Placeholder::make('approval_label')
                ->hiddenLabel()
                ->visible(fn (Get $get) => (bool) $get('needs_approval'))
                ->content(fn (Get $get) => new \Illuminate\Support\HtmlString(
                    '<div style="color:#b91c1c;font-weight:600;padding:6px 0;">Invoice will go for approval – Amount: KES ' .
                    number_format((float) $get('approval_amount'), 2) .
                    '</div>'
                )),

            Forms\Components\Hidden::make('needs_approval')->default(false),
            Forms\Components\Hidden::make('approval_amount')->default(0),

            // ================= Contents  =================
            Forms\Components\Tabs::make('body')->tabs([

                Forms\Components\Tabs\Tab::make('Contents')->schema([
                    TableRepeater::make('lines')
                        ->relationship('lines')
                        ->hiddenLabel()
                        ->addActionLabel('+ Add Row')
                        ->headers([
                            Header::make('Item No.')->width('140px'),
                            Header::make('Item Description')->width('260px'),
                            Header::make('Quantity')->width('90px'),
                            Header::make('Whse')->width('90px'),
                            Header::make('Qty in Whse')->width('90px'),
                            Header::make('UoM Code')->width('90px'),
                            Header::make('Unit Price')->width('120px'),
                            Header::make('Discount %')->width('100px'),
                            Header::make('Price after Disc.')->width('120px'),
                            Header::make('VAT Code')->width('90px'),
                            Header::make('Gross Price after Disc.')->width('130px'),
                            Header::make('Total (LC)')->width('120px'),
                            Header::make('Gross Total (LC)')->width('130px'),
                        ])
                        ->schema([
                           Forms\Components\Select::make('item_no')
                                ->label('Item No.')
                                ->options(function () {
                                    return Item::query()
                                        ->orderBy('item_no')
                                        ->get()
                                        ->mapWithKeys(function (Item $item) {
                                            return [
                                                $item->item_no =>
                                                    "{$item->item_no} — {$item->item_description}"
                                            ];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->searchPrompt('Search item number or description...')
                                ->noSearchResultsMessage('No item found.')
                                ->placeholder('Search or select an item...')
                                ->live()
                                ->required()
                                ->afterStateUpdated(
                                    fn (Set $set, $state) =>
                                        self::fillItemFields($set, $state)
                                )
                                ->suffixAction(
                                    Forms\Components\Actions\Action::make('chooseItem')
                                        ->icon('heroicon-m-arrow-right-circle')
                                        ->label('Choose From List')
                                        ->modalHeading('Select Item')
                                        ->form([
                            Forms\Components\Select::make('selected_item')
                                        ->label('Item')
                                        ->options(function () {
                                            return Item::query()
                                                ->orderBy('item_no')
                                                ->get()
                                                ->mapWithKeys(function (Item $item) {
                                                    return [
                                                        $item->item_no =>
                                                            "{$item->item_no} — {$item->item_description}"
                                                    ];
                                                })
                                                ->toArray();
                                        })
                                        ->searchable()
                                        ->searchPrompt('Search item number or description...')
                                        ->noSearchResultsMessage('No item found.')
                                        ->placeholder('Search item...')
                                        ->required(),
                                ])
                                ->action(function (array $data, Set $set) {
                                    $set('item_no', $data['selected_item']);

                                    self::fillItemFields(
                                        $set,
                                        $data['selected_item']
                                    );
                                })
                        ),
    
                            Forms\Components\TextInput::make('item_description')
                                ->required(),

                            Forms\Components\TextInput::make('quantity')
                                ->numeric()->minValue(0)->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),

                            Forms\Components\TextInput::make('warehouse')
                                ->disabled()->dehydrated(),

                            Forms\Components\TextInput::make('qty_in_whse')
                                ->numeric()->disabled()->dehydrated(),

                            Forms\Components\TextInput::make('uom_code')
                                ->disabled()->dehydrated(),

                            Forms\Components\TextInput::make('price_before_discount')
                                ->numeric()->step(0.001)->minValue(0)->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),

                            Forms\Components\TextInput::make('discount')
                                ->numeric()->step(0.001)->minValue(0)->maxValue(50)->default(0)
                                ->live(onBlur: true)
                                ->rules(['numeric', 'max:50'])
                                ->validationMessages(['max' => 'Discount cannot exceed 50%.'])
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),

                            Forms\Components\TextInput::make('price_after_discount')
                                ->numeric()->step(0.001)->disabled()->dehydrated(),

                            Forms\Components\TextInput::make('vat_code')
                                ->default('O0'),

                            Forms\Components\TextInput::make('gross_price_after_discount')
                                ->numeric()->step(0.001)->disabled()->dehydrated(),

                            Forms\Components\TextInput::make('line_total')
                                ->numeric()->step(0.001)->disabled()->dehydrated(),

                            Forms\Components\TextInput::make('gross_total')
                                ->numeric()->step(0.001)->disabled()->dehydrated(),
                        ])
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set))
                        ->deleteAction(fn ($action) => $action->after(
                            fn (Get $get, Set $set) => self::recalculateTotals($get, $set)
                        ))
                        ->defaultItems(1)
                        ->reorderable(false)
                        ->streamlined(),
                ]),
                Forms\Components\Tabs\Tab::make('Logistics')->schema([
                    Forms\Components\Placeholder::make('logistics_placeholder')
                        ->hiddenLabel()
                        ->content('Shipping / delivery fields go here.'),
                ]),
                Forms\Components\Tabs\Tab::make('Accounting')->schema([
                    Forms\Components\Placeholder::make('accounting_placeholder')
                        ->hiddenLabel()
                        ->content('GL account / posting fields go here.'),
                ]),
                Forms\Components\Tabs\Tab::make('Attachments')->schema([
                    Forms\Components\FileUpload::make('attachments')
                        ->hiddenLabel()
                        ->multiple()
                        ->directory('invoice-attachments'),
                ]),
                Forms\Components\Tabs\Tab::make('TIMS')->schema([
                    Forms\Components\Placeholder::make('tims_placeholder')
                        ->hiddenLabel()
                        ->content('KRA TIMS fiscalization fields go here.'),
                ]),
                Forms\Components\Tabs\Tab::make('ETIMS')->schema([
                    Forms\Components\Placeholder::make('etims_placeholder')
                        ->hiddenLabel()
                        ->content('KRA eTIMS fiscalization fields go here.'),
                ]),
            ]),

            // ================= FOOTER =================
            Forms\Components\Grid::make(2)->schema([

                Forms\Components\Group::make([
                    Forms\Components\Select::make('sales_employee_id')
                        ->label('Sales Employee')
                        ->relationship('salesEmployee', 'name')
                        ->searchable()->preload()->required()
                        ->live()
                        ->afterStateUpdated(
                            fn (Set $set, $state) =>
                                self::fillSalesEmployeeFields($set, $state)
                        )
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('chooseSalesEmployee')
                                ->icon('heroicon-m-arrow-right-circle')
                                ->label('Choose From List')
                                ->modalHeading('Select Sales Employee')
                                ->form([
                                    Forms\Components\Select::make('selected_sales_employee')
                                        ->label('Sales Employee')
                                        ->relationship('salesEmployee', 'name')
                                        ->searchable()
                                        ->required(),
                                ])
                                ->action(function (array $data, Set $set) {
                                    $set('sales_employee_id', $data['selected_sales_employee']);

                                    self::fillSalesEmployeeFields(
                                        $set,
                                        $data['selected_sales_employee']
                                    );
                                })
                        ),

                    Forms\Components\TextInput::make('owner')
                        ->label('Owner')
                        ->disabled()->dehydrated(),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Textarea::make('remarks')
                            ->label('Remarks')
                            ->required()
                            ->validationMessages(['required' => 'Remarks is mandatory.']),

                        Forms\Components\TextInput::make('qr_code')
                            ->label('QRCode'),
                    ]),
                ]),
                Forms\Components\Group::make([
                    Forms\Components\TextInput::make('total_before_discount')
                        ->label('Total Before Discount')->numeric()->disabled()->dehydrated(),

                    Forms\Components\TextInput::make('discount_percent')
                        ->label('Discount %')->numeric()->step(0.001)->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),

                    Forms\Components\TextInput::make('total_down_payment')
                        ->label('Total Down Payment')->numeric()->step(0.001)->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),

                    Forms\Components\TextInput::make('freight')
                        ->label('Freight')->numeric()->step(0.001)->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),

                    Forms\Components\Checkbox::make('rounding')
                        ->label('Rounding'),

                    Forms\Components\TextInput::make('tax')
                        ->label('Tax')->numeric()->step(0.001)->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),

                    Forms\Components\TextInput::make('total_after_discount')
                        ->label('Total')->numeric()->disabled()->dehydrated(),

                    Forms\Components\TextInput::make('applied_amount')
                        ->label('Applied Amount')->numeric()->disabled()->dehydrated(),

                    Forms\Components\TextInput::make('balance_due')
                        ->label('Balance Due')->numeric()->disabled()->dehydrated(),
                ]),
            ]),
        ]);
    }

    protected static function fillCustomerFields(Set $set, $customerId): void
    {
        $customer = Customer::find($customerId);
        if (! $customer) {
            return;
        }
        $set('customer_id', $customer->id);
        $set('display_name_display', $customer->display_name);
        $set('bp_currency_display', $customer->currency);
        $set('kra_pin_display', $customer->kra_pin);
    }


    protected static function fillItemFields(Set $set, $itemNo): void
    {
        $item = Item::where('item_no', $itemNo)->first();
        if (! $item) {
            return;
        }
        $set('item_description', $item->item_description);
        $set('price_before_discount', $item->unit_price);
        $set('uom_code', $item->uom_code);
        $set('warehouse', $item->warehouse);
        $set('qty_in_whse', $item->qty_in_whse);
        $set('vat_code', $item->vat_code);
    }

    protected static function fillSalesEmployeeFields(Set $set, $employeeId): void
    {
        $employee = SalesEmployee::find($employeeId);
        $set('owner', $employee?->name);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('doc_no')->label('No.')->searchable(),
                Tables\Columns\TextColumn::make('customer_name')->searchable(),
                Tables\Columns\TextColumn::make('posting_date')->date(),
                Tables\Columns\TextColumn::make('total_after_discount')->label('Total')->numeric(2),
                Tables\Columns\IconColumn::make('needs_approval')->boolean()->label('Needs Approval'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

   public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\ManageInvoice::route('/create'),
            'edit' => Pages\ManageInvoice::route('/{record}/edit'),
        ];
    }
}