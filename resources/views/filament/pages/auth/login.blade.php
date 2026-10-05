<x-filament-panels::page.simple>

    <div class="custom-login-page">

        <div class="login-brand">
            <div class="login-logo">
                A
            </div>

            <h1>Admin Portal</h1>

            <p>Accounts Receivable Management System</p>
        </div>

        <form
            wire:submit="authenticate"
            class="login-form"
        >

            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />

        </form>

        @if (filament()->hasPasswordReset())
            <div class="login-forgot">
                <a href="{{ filament()->getRequestPasswordResetUrl() }}">
                    Forgot your password?
                </a>
            </div>
        @endif

    </div>

</x-filament-panels::page.simple>