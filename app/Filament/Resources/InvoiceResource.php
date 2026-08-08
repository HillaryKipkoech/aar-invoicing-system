<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Item;
use App\Models\SalesEmployee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'AR Invoice';

    protected static ?string $modelLabel = 'AR Invoice';

    /**
     * Recalculates all line + footer totals and decides whether the
     * approval label should be shown. Called from every field that can
     * affect the total (quantity, price, discount, line add/remove).
     */
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
            $set("lines.{$key}.line_total", $lineTotal);

            $totalBeforeDiscount += round($price * $qty, 3);
            $grandTotal += $lineTotal;
        }

        $headerDiscountPercent = (float) ($get('discount_percent') ?? 0);
        $totalAfterDiscount = round($grandTotal - ($grandTotal * $headerDiscountPercent / 100), 3);

        $set('total_before_discount', round($totalBeforeDiscount, 3));
        $set('total_after_discount', $totalAfterDiscount);

        // Approval label becomes visible only when total exceeds 10,000
        $set('needs_approval', $totalAfterDiscount > Invoice::APPROVAL_THRESHOLD);
        $set('approval_amount', $totalAfterDiscount);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ---------- HEADER SECTION ----------
            Forms\Components\Section::make()->schema([
                Forms\Components\Grid::make(3)->schema([

                    Forms\Components\Grid::make(1)->columnSpan(2)->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer Code')
                            ->helperText('Choose from list — searchable')
                            ->relationship('customer', 'customer_code')
                            ->searchable()
                            ->preload()
                            ->getSearchResultsUsing(fn (string $search) => Customer::query()
                                ->where('customer_code', 'like', "%{$search}%")
                                ->orWhere('customer_name', 'like', "%{$search}%")
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($c) => [$c->id => "{$c->customer_code} — {$c->customer_name}"]))
                            ->getOptionLabelUsing(fn ($value) => optional(Customer::find($value))->customer_code)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $customer = Customer::find($state);
                                if ($customer) {
                                    $set('customer_name_display', $customer->customer_name);
                                    $set('customer_code_display', $customer->customer_code);
                                }
                            }),

                        // Customer Name — same list, but customer_name is the first column
                        Forms\Components\Select::make('customer_id_by_name')
                            ->label('Customer Name')
                            ->helperText('Choose from list — searchable (name-first)')
                            ->options(fn () => Customer::query()->pluck('customer_name', 'id'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Customer::query()
                                ->where('customer_name', 'like', "%{$search}%")
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($c) => [$c->id => "{$c->customer_name} — {$c->customer_code}"]))
                            ->getOptionLabelUsing(fn ($value) => optional(Customer::find($value))->customer_name)
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                // Keep both selectors and the record in sync
                                $set('customer_id', $state);
                                $customer = Customer::find($state);
                                if ($customer) {
                                    $set('customer_name_display', $customer->customer_name);
                                }
                            })
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('customer_name_display')
                            ->label('Customer Name')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                    Forms\Components\Grid::make(1)->columnSpan(1)->schema([
                        Forms\Components\TextInput::make('doc_no')
                            ->label('No.')
                            ->default(fn () => Invoice::nextDocNo())
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('status')
                            ->label('Status')
                            ->default('Open')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\DatePicker::make('posting_date')
                            ->label('Posting Date')
                            ->default(now())
                            ->required(),
                    ]),
                ]),

                // Approval label — hidden unless total exceeds 10,000
                Forms\Components\Placeholder::make('approval_label')
                    ->hiddenLabel()
                    ->visible(fn (Get $get) => (bool) $get('needs_approval'))
                    ->content(fn (Get $get) => new \Illuminate\Support\HtmlString(
                        '<div style="color:#b91c1c;font-weight:600;">Invoice will go for approval – Amount: KES ' .
                        number_format((float) $get('approval_amount'), 2) .
                        '</div>'
                    )),

                Forms\Components\Hidden::make('needs_approval')->default(false),
                Forms\Components\Hidden::make('approval_amount')->default(0),
            ]),

            // ---------- TABLE SECTION ----------
            Forms\Components\Section::make('Contents')->schema([
                Forms\Components\Repeater::make('lines')
                    ->relationship('lines')
                    ->label('')
                    ->addActionLabel('Add Row')
                    ->columns(7)
                    ->schema([
                        Forms\Components\Select::make('item_no')
                            ->label('Item No.')
                            ->options(fn () => Item::query()->pluck('item_no', 'item_no'))
                            ->searchable()
                            ->allowHtml(false)
                            // "or type manually" -> allow free text as well
                            ->createOptionForm([
                                Forms\Components\TextInput::make('item_no')->required(),
                            ])
                            ->createOptionUsing(fn (array $data) => $data['item_no'])
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $item = Item::where('item_no', $state)->first();
                                if ($item) {
                                    $set('item_description', $item->item_description);
                                    $set('price_before_discount', $item->unit_price);
                                }
                            }),

                        Forms\Components\TextInput::make('item_description')
                            ->label('Item Description')
                            ->required(),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),

                        Forms\Components\TextInput::make('price_before_discount')
                            ->label('Price Before Discount')
                            ->numeric()
                            ->step(0.001)
                            ->minValue(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),

                        Forms\Components\TextInput::make('discount')
                            ->label('Discount %')
                            ->numeric()
                            ->step(0.001)
                            ->minValue(0)
                            ->maxValue(50)
                            ->default(0)
                            ->live(onBlur: true)
                            ->rules(['numeric', 'max:50'])
                            ->validationMessages([
                                'max' => 'Discount cannot exceed 50%.',
                            ])
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),

                        Forms\Components\TextInput::make('price_after_discount')
                            ->label('Price After Discount')
                            ->numeric()
                            ->step(0.001)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('line_total')
                            ->label('Total')
                            ->numeric()
                            ->step(0.001)
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set))
                    ->deleteAction(
                        fn (Forms\Components\Actions\Action $action) => $action->after(
                            fn (Get $get, Set $set) => self::recalculateTotals($get, $set)
                        )
                    )
                    ->defaultItems(1)
                    ->reorderable(false),
            ]),

            // ---------- FOOTER SECTION ----------
            Forms\Components\Section::make()->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Select::make('sales_employee_id')
                        ->label('Sales Employee')
                        ->relationship('salesEmployee', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Textarea::make('remarks')
                        ->label('Remarks')
                        ->required()
                        ->validationMessages([
                            'required' => 'Remarks is mandatory.',
                        ])
                        ->columnSpan(2),
                ]),

                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('total_before_discount')
                        ->label('Total Before Discount')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('discount_percent')
                        ->label('Discount %')
                        ->numeric()
                        ->step(0.001)
                        ->default(0)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateTotals($get, $set)),

                    Forms\Components\TextInput::make('total_after_discount')
                        ->label('Total After Discount')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(),
                ]),
            ]),
        ]);
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
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
