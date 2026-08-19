<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'doc_no', 'customer_id', 'customer_code', 'customer_name', 'contact_person', 'posting_date',
        'value_date', 'document_date', 'sales_employee_id', 'owner', 'remarks',
        'qr_code', 'total_before_discount', 'discount_percent', 'total_down_payment',
        'freight', 'rounding', 'tax', 'total_after_discount', 'applied_amount',
        'balance_due', 'needs_approval', 'status',
    ];

 // Invoice model
        protected $casts = [
            'posting_date' => 'date',
            'value_date' => 'date',
            'document_date' => 'date',
            'needs_approval' => 'boolean',
            'rounding' => 'boolean',
            'total_before_discount' => 'decimal:3',
            'discount_percent' => 'decimal:3',
            'total_down_payment' => 'decimal:3',
            'freight' => 'decimal:3',
            'tax' => 'decimal:3',
            'total_after_discount' => 'decimal:3',
            'applied_amount' => 'decimal:3',
            'balance_due' => 'decimal:3',
        ];

    // Threshold above which the invoice requires approval
    public const APPROVAL_THRESHOLD = 10000;

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesEmployee()
    {
        return $this->belongsTo(SalesEmployee::class);
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('sort_order');
    }

    /**
     * Generate the next sequential document number, e.g. IN00001, IN00002 ...
     * Uses a DB transaction-safe max lookup; for high concurrency, swap for
     * a dedicated sequence table with SELECT ... WITH (UPDLOCK) on SQL Server.
     */
    public static function nextDocNo(): string
    {
        $last = static::query()->orderByDesc('id')->value('doc_no');

        if (! $last) {
            return 'IN00001';
        }

        $numericPart = (int) substr($last, 2);

        return 'IN' . str_pad((string) ($numericPart + 1), 5, '0', STR_PAD_LEFT);
    }
}
