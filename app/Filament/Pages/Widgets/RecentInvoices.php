<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentInvoices extends BaseWidget
{
    protected static ?string $heading = 'Recent Invoices';

    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Invoice #')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('posting_date')
                    ->label('Posting Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_after_discount')
                    ->label('Total')
                    ->money('KES')
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5]);
    }
}