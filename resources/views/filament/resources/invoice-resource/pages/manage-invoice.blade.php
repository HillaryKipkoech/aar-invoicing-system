<x-filament-panels::page>
    <div class="ar-invoice" style="border:1px solid #b9b9b9;">

        {{-- ===== Title bar ===== --}}
        <div class="titlebar">
            <span>AR Invoice</span>
            <span class="controls">
                <span>&#8211;</span>
                <span>&#9633;</span>
                <span class="close">&#10005;</span>
            </span>
        </div>

        {{-- ===== Header ===== --}}
        <div class="header-wrap">
            <div class="header-left">
                <div class="field-row">
                    <div class="field-label">Customer</div>
                    <div class="field-input-wrap">
                        <select class="field-input" wire:model.live="customer_id">
                            <option value="">-- select --</option>
                            @foreach(\App\Models\Customer::all() as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->customer_code }}</option>
                            @endforeach
                        </select>
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
        <div class="footer-wrap">
            <div class="footer-left">
                <div class="field-row">
                    <div class="field-label" style="width:120px;">Sales Employee</div>
                    <div class="field-input-wrap">
                        <select class="field-input" wire:model.live="sales_employee_id" style="max-width:200px;">
                            <option value="">-- select --</option>
                            @foreach(\App\Models\SalesEmployee::all() as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
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

                <div class="action-buttons">
                    <button type="button" class="btn" wire:click="addAndNew">Add &amp; New</button>
                    <button type="button" class="btn" wire:click="addDraftAndNew">Add Draft &amp; New</button>
                    <button type="button" class="btn secondary" wire:click="cancel">Cancel</button>
                </div>
            </div>

            <div class="footer-right">
                <div class="totals-row">
                    <span class="totals-label">Total Before Discount</span>
                    <span class="totals-value">KES {{ number_format($total_before_discount, 2) }}</span>
                </div>
                <div class="totals-row">
                    <span class="totals-label">Discount</span>
                    <span class="discount-inline">
                        <input type="number" step="0.001" wire:model.live="discount_percent">
                        <span>%</span>
                    </span>
                </div>
                <div class="totals-row">
                    <span class="totals-label">Total Down Payment</span>
                    <input type="number" step="0.001" class="totals-value editable" wire:model.live="total_down_payment">
                </div>
                <div class="totals-row">
                    <span class="totals-label"><span class="orange-arrow">&#10148;</span>Freight</span>
                    <input type="number" step="0.001" class="totals-value editable" wire:model.live="freight">
                </div>
                <div class="totals-row">
                    <span class="totals-label"><input type="checkbox" wire:model="rounding"> Rounding</span>
                    <span class="totals-value">KES 0.00</span>
                </div>
                <div class="totals-row">
                    <span class="totals-label">Tax</span>
                    <input type="number" step="0.001" class="totals-value editable" wire:model.live="tax">
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
            </div>
        </div>
    </div>
</x-filament-panels::page>