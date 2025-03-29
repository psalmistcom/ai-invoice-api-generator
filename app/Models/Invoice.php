<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_id',
        'business_name',
        'business_address',
        'business_logo_url',
        'customer_name',
        'invoice_items',
        'grand_total',
        'due_date',
        'additional_notes',
        'pdf_url'
    ];

    protected $casts = [
        'invoice_items' => 'array',
        'due_date' => 'date'
    ];

    public static function generateInvoiceId()
    {
        return 'INV-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
