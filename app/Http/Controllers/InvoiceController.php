<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'business_address' => 'required|string',
            'business_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'customer_name' => 'required|string|max:255',
            'invoice_items' => 'required|array|min:1',
            'invoice_items.*.item_name' => 'required|string|max:255',
            'invoice_items.*.quantity' => 'required|numeric|min:1',
            'invoice_items.*.price_per_item' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'additional_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Process invoice items and calculate totals
        $invoiceItems = $request->invoice_items;
        $grandTotal = 0;

        // Calculate item totals and grand total
        $processedItems = array_map(function ($item) use (&$grandTotal) {
            $item['item_total'] = $item['quantity'] * $item['price_per_item'];
            $grandTotal += $item['item_total'];
            return $item;
        }, $invoiceItems);

        // Upload business logo if provided
        $businessLogoUrl = null;
        if ($request->hasFile('business_logo')) {
            $businessLogoUrl = $this->fileUploadService->storeImage(
                $request->file('business_logo'),
                'business_logos'
            );
        }

        // foreach ($invoiceItems as &$item) {
        //     $itemTotal = $item['quantity'] * $item['price_per_item'];
        //     $item['item_total'] = $itemTotal;
        //     $grandTotal += $itemTotal;
        // }

        // $businessLogoUrl = null;
        // if ($request->hasFile('business_logo')) {
        //     $businessLogoUrl = $this->fileUploadService->uploadImage(
        //         $request->file('business_logo'),
        //         'business_logos'
        //     );

        //     if (!$businessLogoUrl) {
        //         return response()->json([
        //             'message' => 'Failed to upload business logo'
        //         ], 500);
        //     }
        // }

        // Create invoice
        $invoice = Invoice::create([
            'invoice_id' => Invoice::generateInvoiceId(),
            'business_name' => $request->business_name,
            'business_address' => $request->business_address,
            'business_logo_url' => $businessLogoUrl ? Storage::url($businessLogoUrl) : null,
            'customer_name' => $request->customer_name,
            'invoice_items' => $processedItems, // Use the processed items with totals
            'grand_total' => $grandTotal,
            'due_date' => $request->due_date,
            'additional_notes' => $request->additional_notes
        ]);


        // Generate and store PDF
        $pdfUrl = $this->generateAndStorePdf($invoice);


        if (!$pdfUrl) {
            return response()->json([
                'message' => 'Invoice created but PDF generation failed',
                'invoice' => $invoice,
                'pdf_status' => 'failed'
            ], 201);
        }

        $invoice->update(['pdf_url' => $pdfUrl]);

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => $invoice,
            'pdf_url' => $pdfUrl
        ], 201);
    }


    private function generateAndStorePdf(Invoice $invoice): ?string
    {
        try {
            $pdf = Pdf::loadView('invoices.template', ['invoice' => $invoice]);
            $filename = "invoices/{$invoice->invoice_id}.pdf";

            // Store PDF directly without temporary file
            Storage::put("public/{$filename}", $pdf->output());

            return Storage::url($filename);
        } catch (\Exception $e) {
            Log::error('PDF generation failed: ' . $e->getMessage());
            return null;
        }
    }

    public function show($id)
    {
        $invoice = Invoice::findOrFail($id);
        return response()->json([
            'invoice' => $invoice,
            'pdf_url' => $invoice->pdf_url
        ]);
    }
}
