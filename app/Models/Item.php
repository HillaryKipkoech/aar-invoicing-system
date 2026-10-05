<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'item_no', 'item_description', 'uom_code', 'unit_price', 'vat_code', 'warehouse', 'qty_in_whse',
    ];
}
