<x-filament-panels::page>

    <div class="ar-dashboard">

        {{-- Welcome Header --}}
        <div class="dashboard-header">
            <div>
                <h1>Accounts Receivable Dashboard</h1>
                <p>
                    Welcome back. Here's an overview of your invoice operations.
                </p>
            </div>

            <div class="dashboard-date">
                {{ now()->format('d M Y') }}
            </div>
        </div>

        {{-- Dashboard content --}}
        <div class="dashboard-content">

            {{-- Stats will go here --}}
            <div class="dashboard-stats">
                {{ $this->getHeaderWidgets() }}
            </div>

            {{-- Main widgets --}}
            <div class="dashboard-widgets">
                {{ $this->getFooterWidgets() }}
            </div>

        </div>

    </div>

</x-filament-panels::page>