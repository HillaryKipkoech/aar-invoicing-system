<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\SalesEmployee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(
                'Customers',
                Customer::count()
            )
                ->description('Registered customers')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make(
                'Items',
                Item::count()
            )
                ->description('Products / services')
                ->icon('heroicon-o-cube')
                ->color('warning'),

            Stat::make(
                'Sales Employees',
                SalesEmployee::count()
            )
                ->description('Active sales employees')
                ->icon('heroicon-o-user-group')
                ->color('success'),

            Stat::make(
                'Invoices',
                Invoice::count()
            )
                ->description('Total invoices')
                ->icon('heroicon-o-document-text')
                ->color('primary'),
        ];
    }
}