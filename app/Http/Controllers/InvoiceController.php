<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\CloudinaryService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function store(Request $request, CloudinaryService $cloudinaryService)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'business_address' => 'required|string',
            'business_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
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
        $pdfUrl = $this->generateAndStorePdf($invoice, $cloudinaryService);


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


    private function generateAndStorePdf(Invoice $invoice, $cloudinaryService): ?string
    {
        try {

            // $dir = 'invoices';
            // $path = public_path() . '/' . $dir;

            // if (!file_exists($path)) {
            //     File::makeDirectory($path, $mode = 0777, true, true);
            // }

            // $full_path = $dir . '/' . $invoice->invoice_id . '__' . uniqid() . '.pdf';
            // $path = public_path($full_path);


            $pdf = Pdf::loadView('invoices.template', ['invoice' => $invoice]);
            // Save the PDF to a temporary file
            $tempFilePath = tempnam(sys_get_temp_dir(), 'invoices') . '.pdf';
            $pdf->save($tempFilePath);
            // upload to cloudinary
            try {
                $uploadResponse = $cloudinaryService->uploadFile($tempFilePath, 'b2b-invoices', 'raw');
                // Get the Cloudinary URL
                $pdfUrl = $uploadResponse['secure_url'];
                // Delete the temporary file
                unlink($tempFilePath);
            } catch (Exception $e) {
                return response()->json([
                    'message' => 'Failed to upload PDF to Cloudinary.',
                    'error' => $e->getMessage(),
                ], 500);
            }

            return $pdfUrl;
        } catch (\Exception $e) {
            Log::error('PDF generation failed: ' . $e->getMessage());
            return null;
        }
    }

    public function show($invoice_id)
    {
        $invoice = Invoice::where('invoice_id', $invoice_id)->firstOrFail();

        return response()->json([
            'invoice' => $invoice,
            'pdf_url' => $invoice->pdf_url
        ]);
    }
}
