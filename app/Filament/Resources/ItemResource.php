<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Items';

    // protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('item_no')
                    ->label('Item No.')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),

                Forms\Components\TextInput::make('item_description')
                    ->label('Item Description')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('uom_code')
                    ->label('UoM Code')
                    ->maxLength(20),

                Forms\Components\TextInput::make('unit_price')
                    ->label('Unit Price')
                    ->numeric()
                    ->step(0.001)
                    ->required(),

                Forms\Components\TextInput::make('vat_code')
                    ->label('VAT Code')
                    ->default('O0')
                    ->maxLength(10),

                Forms\Components\TextInput::make('warehouse')
                    ->label('Warehouse')
                    ->maxLength(20),

                Forms\Components\TextInput::make('qty_in_whse')
                    ->label('Qty in Whse')
                    ->numeric()
                    ->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_no')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('item_description')->searchable(),
                Tables\Columns\TextColumn::make('uom_code')->label('UoM'),
                Tables\Columns\TextColumn::make('unit_price')->numeric(3),
                Tables\Columns\TextColumn::make('vat_code')->label('VAT'),
                Tables\Columns\TextColumn::make('warehouse')->label('Whse'),
                Tables\Columns\TextColumn::make('qty_in_whse')->label('Qty in Whse')->numeric(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
