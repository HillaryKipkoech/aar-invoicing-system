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
                ->color('primary')
                 ->extraAttributes([
                    'class' => 'dashboard-stat-card customers-card',
                ]),

            Stat::make(
                'Items',
                Item::count()
            )
                ->description('Products / services')
                ->icon('heroicon-o-cube')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'dashboard-stat-card items-card',
                ]),

            Stat::make(
                'Sales Employees',
                SalesEmployee::count()
            )
                ->description('Active sales employees')
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->extraAttributes([
                    'class' => 'dashboard-stat-card employees-card',
                ]),

            Stat::make(
                'Invoices',
                Invoice::count()
            )
                ->description('Total invoices')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'dashboard-stat-card invoices-card',
                ]),
        ];
    }
}