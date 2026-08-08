<x-filament-panels::page>
    <div class="ar-invoice" style="border:1px solid #b9b9b9;">
        <div class="ar-invoice"
     style="border:1px solid #b9b9b9;"
     x-data
     @click.outside="$wire.showCustomerDropdown = false; $wire.showCustomerNameDropdown = false; $wire.showSalesEmployeeDropdown = false;">

        {{-- ===== Title bar ===== --}}
        <!-- <div class="titlebar">
            <span>AR Invoice</span>
            <span class="controls">
                <span>&#8211;</span>
                <span>&#9633;</span>
                <span class="close">&#10005;</span>
            </span>
        </div> -->

        {{-- ===== Header ===== --}}
        <div class="header-wrap">
            <div class="header-left">
                <div class="field-row" style="position:relative;">
                    <div class="field-label">Customer</div>
                    <div class="field-input-wrap" style="position:relative;">
                        <input
                            class="field-input"
                            wire:model.live.debounce.300ms="customerSearch"
                            @focus="$wire.showCustomerDropdown = true"
                            autocomplete="off"
                            placeholder="Type to search...">

                        @if($showCustomerDropdown && count($customerResults))
                            <div class="ac-dropdown">
                                @foreach($customerResults as $c)
                                    <div class="ac-dropdown-item" wire:click="selectCustomer({{ $c['id'] }})">
                                        <span class="ac-primary">{{ $c['customer_code'] }}</span>
                                        <span class="ac-secondary">{{ $c['display_name'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">Name</div>
                    <div class="field-input-wrap">
                        <input class="field-input readonly" value="{{ $display_name }}" disabled>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label">Contact Person</div>
                    <div class="field-input-wrap">
                        <input class="field-input" wire:model="contact_person">
                    </div>
                </div>
                <div class="field-row" style="position:relative;">
    <div class="field-label">Customer Name</div>
    <div class="field-input-wrap" style="position:relative;">
        <input
            class="field-input"
            wire:model.live.debounce.300ms="customerNameSearch"
            @focus="$wire.showCustomerNameDropdown = true"
            autocomplete="off"
            placeholder="Type to search...">

        @if($showCustomerNameDropdown && count($customerNameResults))
            <div class="ac-dropdown">
                @foreach($customerNameResults as $c)
                    <div class="ac-dropdown-item" wire:click="selectCustomer({{ $c['id'] }})">
                        <span class="ac-primary">{{ $c['display_name'] }}</span>
                        <span class="ac-secondary">{{ $c['customer_code'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
                <div class="field-row">
                    <div class="field-label bold">BP Currency</div>
                    <div class="field-input-wrap">
                        <input class="field-input readonly" value="{{ $bp_currency }}" disabled style="max-width:70px;">
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label bold">KRA PIN</div>
                    <div class="field-input-wrap">
                        <input class="field-input readonly" value="{{ $kra_pin }}" disabled>
                    </div>
                </div>
            </div>

            <div class="header-right">
                <div class="field-row">
                    <div class="field-label" style="width:80px;">No.</div>
                    <div class="no-field-group">
                        <div class="no-prefix">IN &#9662;</div>
                        <input class="field-input readonly" value="{{ $doc_no }}" disabled>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-label" style="width:80px;">Status</div>
                    <input class="field-input readonly" value="{{ $status }}" disabled>
                </div>
                <div class="field-row">
                    <div class="field-label" style="width:80px;">Posting Date</div>
                    <input type="date" class="field-input" wire:model.live="posting_date">
                </div>
                <div class="field-row">
                    <div class="field-label" style="width:80px;">Value Date</div>
                    <input type="date" class="field-input value-date-highlight" wire:model="value_date">
                    <span class="calendar-icon">&#128197;</span>
                </div>
                <div class="field-row">
                    <div class="field-label" style="width:80px;">Document Date</div>
                    <input type="date" class="field-input" wire:model="document_date">
                </div>
            </div>
        </div>

        {{-- ===== Approval label ===== --}}
        @if($needs_approval)
            <div class="approval-label">
                Invoice will go for approval &ndash; Amount: KES {{ number_format($approval_amount, 2) }}
            </div>
        @endif

        {{-- ===== Tabs ===== --}}
        <div class="tabs">
            @foreach(['Contents', 'Logistics', 'Accounting', 'Attachments', 'TIMS', 'ETIMS'] as $tabName)
                <div class="tab {{ $activeTab === $tabName ? 'active' : '' }}"
                     wire:click="$set('activeTab', '{{ $tabName }}')">
                    {{ $tabName }}
                </div>
            @endforeach
        </div>

        @if($activeTab === 'Contents')
            <div class="table-toolbar">
                <div>Item/Service Type <select><option>Item</option><option>Service</option></select></div>
                <div>Summary Type <select><option>No Summary</option></select></div>
            </div>

            <div class="table-wrap">
                <table class="grid">
                    {{-- Explicit column widths + table-layout:fixed (see CSS) keep the
                         header and body columns aligned as Livewire re-renders rows. --}}
                    <colgroup>
                        <col style="width:30px;">
                        <col style="width:130px;">
                        <col style="width:220px;">
                        <col style="width:80px;">
                        <col style="width:80px;">
                        <col style="width:90px;">
                        <col style="width:80px;">
                        <col style="width:110px;">
                        <col style="width:90px;">
                        <col style="width:120px;">
                        <col style="width:80px;">
                        <col style="width:130px;">
                        <col style="width:110px;">
                        <col style="width:120px;">
                        <col style="width:36px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="row-num">#</th>
                            <th>Item No.</th>
                            <th>Item Description</th>
                            <th>Quantity</th>
                            <th>Whse</th>
                            <th>Qty in Whse</th>
                            <th>UoM Code</th>
                            <th>Unit Price</th>
                            <th>Discount %</th>
                            <th>Price after Discount</th>
                            <th>VAT Code</th>
                            <th>Gross Price after Disc.</th>
                            <th>Total (LC)</th>
                            <th>Gross Total (LC)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lines as $index => $line)
                            <tr wire:key="line-{{ $index }}">
                                <td class="row-num">{{ $index + 1 }}</td>
                                <td>
                                    <div class="go-cell">
                                        <span class="go-arrow">&#10148;</span>
                                        <select wire:model="lines.{{ $index }}.item_no">
                                            <option value="">--</option>
                                            @foreach(\App\Models\Item::pluck('item_no') as $itemNo)
                                                <option value="{{ $itemNo }}">{{ $itemNo }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td><input wire:model="lines.{{ $index }}.item_description"></td>
                                <td class="num-cell"><input type="number" wire:model.live="lines.{{ $index }}.quantity"></td>
                                <td><input wire:model="lines.{{ $index }}.warehouse" disabled></td>
                                <td class="num-cell"><input wire:model="lines.{{ $index }}.qty_in_whse" disabled></td>
                                <td><input wire:model="lines.{{ $index }}.uom_code" disabled></td>
                                <td class="num-cell blue-text"><input type="number" step="0.001" wire:model.live="lines.{{ $index }}.price_before_discount"></td>
                                <td class="num-cell blue-text"><input type="number" step="0.001" wire:model.live="lines.{{ $index }}.discount"></td>
                                <td class="num-cell blue-text"><input wire:model="lines.{{ $index }}.price_after_discount" disabled></td>
                                <td><input wire:model="lines.{{ $index }}.vat_code"></td>
                                <td class="num-cell blue-text"><input wire:model="lines.{{ $index }}.gross_price_after_discount" disabled></td>
                                <td class="num-cell"><input wire:model="lines.{{ $index }}.line_total" disabled></td>
                                <td class="num-cell"><input wire:model="lines.{{ $index }}.gross_total" disabled></td>
                                <td><button type="button" class="row-remove" wire:click="removeLine({{ $index }})">&#10005;</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div style="padding:8px 0;">
                    <button type="button" class="btn secondary" wire:click="addLine">+ Add Row</button>
                </div>
            </div>
        @elseif($activeTab === 'Logistics')
            <div style="padding:14px;">Shipping / delivery fields go here.</div>
        @elseif($activeTab === 'Accounting')
            <div style="padding:14px;">GL account / posting fields go here.</div>
        @elseif($activeTab === 'Attachments')
            <div style="padding:14px;">Attachments go here.</div>
        @elseif($activeTab === 'TIMS')
            <div style="padding:14px;">KRA TIMS fiscalization fields go here.</div>
        @elseif($activeTab === 'ETIMS')
            <div style="padding:14px;">KRA eTIMS fiscalization fields go here.</div>
        @endif

        {{-- ===== Footer ===== --}}
       {{-- ===== Footer ===== --}}
<div class="footer-wrap">
    <div class="footer-left">
        <div class="field-row" style="position:relative;">
            <div class="field-label" style="width:120px;">Sales Employee</div>
            <div class="field-input-wrap" style="position:relative;">
                <input
                    class="field-input"
                    wire:model.live.debounce.300ms="salesEmployeeSearch"
                    @focus="$wire.showSalesEmployeeDropdown = true"
                    autocomplete="off"
                    placeholder="Type to search..."
                    style="max-width:200px;">

                @if($showSalesEmployeeDropdown && count($salesEmployeeResults))
                    <div class="ac-dropdown" style="max-width:200px;">
                        @foreach($salesEmployeeResults as $e)
                            <div class="ac-dropdown-item" wire:click="selectSalesEmployee({{ $e['id'] }})">
                                <span class="ac-primary">{{ $e['name'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="field-row">
            <div class="field-label" style="width:120px;">Owner</div>
            <div class="field-input-wrap">
                <input class="field-input readonly" value="{{ $owner }}" disabled style="max-width:200px;">
            </div>
        </div>

        <div class="checkbox-row">
            <input type="checkbox" wire:model="payment_order_run">
            <span>Payment Order Run</span>
        </div>

        <div class="remarks-qr-row">
            <div class="remarks-box">
                <div class="footer-label">Remarks</div>
                <textarea wire:model="remarks"></textarea>
            </div>
            <div class="qr-box">
                <div class="footer-label">QRCode</div>
                <input wire:model="qr_code">
            </div>
        </div>

        @error('remarks')
            <div style="color:#b91c1c; font-size:12px; margin-top:4px;">{{ $message }}</div>
        @enderror
        @error('customer_id')
            <div style="color:#b91c1c; font-size:12px; margin-top:4px;">Please choose a customer.</div>
        @enderror
        @error('sales_employee_id')
            <div style="color:#b91c1c; font-size:12px; margin-top:4px;">Please choose a sales employee.</div>
        @enderror
        @foreach($errors->get('lines.*.discount') as $messages)
            @foreach($messages as $message)
                <div style="color:#b91c1c; font-size:12px; margin-top:4px;">{{ $message }}</div>
            @endforeach
        @endforeach

        <div class="action-buttons">
            <button type="button" class="btn" wire:click="addAndNew">Add &amp; New</button>
            <button type="button" class="btn" wire:click="addDraftAndNew">Add Draft &amp; New</button>
            <button type="button" class="btn" wire:click="cancel">Cancel</button>
        </div>
    </div>

    <div class="footer-right">
        <div class="totals-row">
            <span class="totals-label">Total Before Discount</span>
            <span class="totals-value">KES {{ number_format($total_before_discount, 2) }}</span>
        </div>

        <div class="totals-row">
            <span class="totals-label">Discount</span>
            <span class="totals-value-group">
                <input type="number" step="0.001"
                    class="totals-value editable"
                    wire:model.live="discount_percent"
                    x-on:focus="$event.target.select()">
                <span class="unit-suffix">%</span>
            </span>
        </div>

        <div class="totals-row">
            <span class="totals-label">Total Down Payment</span>
            <span class="totals-value-group">
                <span class="currency-prefix">KES</span>
                <input type="number" step="0.001"
                    class="totals-value editable"
                    wire:model.live="total_down_payment"
                    x-on:focus="$event.target.select()">
            </span>
        </div>

        <div class="totals-row">
            <span class="totals-label"><span class="orange-arrow">&#10148;</span>Freight</span>
            <span class="totals-value-group">
                <span class="currency-prefix">KES</span>
                <input type="number" step="0.001"
                    class="totals-value editable"
                    wire:model.live="freight"
                    x-on:focus="$event.target.select()">
            </span>
        </div>

        <div class="totals-row">
            <span class="totals-label"><input type="checkbox" wire:model="rounding"> Rounding</span>
            <span class="totals-value">KES 0.00</span>
        </div>

        <div class="totals-row">
            <span class="totals-label">Tax</span>
            <span class="totals-value-group">
                <span class="currency-prefix">KES</span>
                <input type="number" step="0.001"
                    class="totals-value editable"
                    wire:model.live="tax"
                    x-on:focus="$event.target.select()">
            </span>
        </div>

        <div class="totals-row">
            <span class="totals-label" style="font-weight:700;">Total</span>
            <span class="totals-value" style="font-weight:700;">KES {{ number_format($total_after_discount, 2) }}</span>
        </div>

        <div class="totals-row">
            <span class="totals-label">Applied Amount</span>
            <span class="totals-value">KES {{ number_format($applied_amount, 2) }}</span>
        </div>

        <div class="totals-row">
            <span class="totals-label">Balance Due</span>
            <span class="totals-value blue">KES {{ number_format($balance_due, 2) }}</span>
        </div>

         <div class="action-buttons">
            <button type="button" class="btn" wire:click="addAndNew">Copy From</button>
            <button type="button" class="btn secondary" wire:click="cancel">Copy To</button>
        </div>
    </div>
</div>
    </div>
</x-filament-panels::page>
