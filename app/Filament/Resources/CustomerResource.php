<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Customer Information')
                ->description('Enter the customer identification and contact details.')
                ->schema([

                    Forms\Components\TextInput::make('customer_code')
                        ->label('Customer Code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(20),

                    Forms\Components\TextInput::make('display_name')
                        ->label('Display Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('customer_name')
                        ->label('Customer Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('contact_person')
                        ->label('Contact Person')
                        ->maxLength(255),

                    Forms\Components\Select::make('currency')
                        ->label('BP Currency')
                        ->options([
                            'KES' => 'KES',
                            'USD' => 'USD',
                            'EUR' => 'EUR',
                            'GBP' => 'GBP',
                        ])
                        ->default('KES')
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('kra_pin')
                        ->label('KRA PIN')
                        ->maxLength(20),

                ])
                ->columns(3)
                ->compact(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('customer_code')
                    ->label('Customer Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Contact Person')
                    ->searchable(),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Currency')
                    ->badge(),

                Tables\Columns\TextColumn::make('kra_pin')
                    ->label('KRA PIN'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([])

            ->actions([

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->disabled(function (Customer $record): bool {
                        return $record->invoices()->exists();
                    })
                    ->tooltip(function (Customer $record): string {
                        return $record->invoices()->exists()
                            ? 'This customer cannot be deleted because they have invoices.'
                            : 'Delete customer';
                    }),

            ])

            ->bulkActions([

                Tables\Actions\BulkActionGroup::make([

                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records, $action) {

                            $customersWithInvoices = $records->filter(
                                fn (Customer $customer) =>
                                    $customer->invoices()->exists()
                            );

                            if ($customersWithInvoices->isNotEmpty()) {

                                $action->cancel();

                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Customers cannot be deleted')
                                    ->body(
                                        'One or more selected customers have invoices associated with them.'
                                    )
                                    ->send();
                            }

                        }),

                ]),

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}