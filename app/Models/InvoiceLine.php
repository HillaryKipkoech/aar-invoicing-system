<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    protected $fillable = [
        'invoice_id', 'item_no', 'item_description', 'warehouse', 'qty_in_whse',
        'uom_code', 'quantity', 'price_before_discount', 'discount',
        'price_after_discount', 'vat_code', 'gross_price_after_discount',
        'line_total', 'gross_total', 'sort_order',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
