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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_items' => 'array',
            'due_date' => 'datetime'
        ];
    }
    // protected $casts = [
    //     'invoice_items' => 'array',
    //     'due_date' => 'date'
    // ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'updated_at'
    ];

    public static function generateInvoiceId()
    {
        return 'INV-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
