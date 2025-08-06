<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;

class PDFGenerationService
{
    /**
     * Generate PDF for an invoice.
     */
    public function generateInvoicePDF(Invoice $invoice, $template = null)
    {
        // Load invoice with relationships
        $invoice->load(['customer', 'items', 'template']);

        // Get template or use default
        if (!$template) {
            $template = $invoice->template ?: InvoiceTemplate::getDefault($invoice->language);
        }

        // Prepare data for PDF generation
        $data = $this->prepareInvoiceData($invoice, $template);

        // Generate PDF based on language
        $pdf = $this->createPDF($data, $invoice->language);

        // Save PDF to storage
        $filename = $this->generatePDFFilename($invoice);
        $pdfPath = "invoices/pdf/{$filename}";
        
        Storage::put($pdfPath, $pdf->output());

        // Update invoice with PDF path
        $invoice->update(['pdf_path' => $pdfPath]);

        return [
            'path' => $pdfPath,
            'url' => Storage::url($pdfPath),
            'content' => $pdf->output()
        ];
    }

    /**
     * Prepare invoice data for PDF generation.
     */
    protected function prepareInvoiceData(Invoice $invoice, $template)
    {
        $data = [
            'invoice' => $invoice,
            'customer' => $invoice->customer,
            'items' => $invoice->items,
            'template' => $template,
            'template_data' => $template ? $template->template_data : [],
            'company' => $this->getCompanyInfo(),
            'language' => $invoice->language,
            'is_rtl' => $invoice->language === 'fa',
        ];

        // Add calculated fields
        $data['subtotal_formatted'] = $this->formatCurrency($invoice->subtotal, $invoice->language);
        $data['tax_formatted'] = $this->formatCurrency($invoice->tax_amount, $invoice->language);
        $data['discount_formatted'] = $this->formatCurrency($invoice->discount_amount, $invoice->language);
        $data['total_formatted'] = $this->formatCurrency($invoice->total_amount, $invoice->language);

        // Format dates
        $data['issue_date_formatted'] = $this->formatDate($invoice->issue_date, $invoice->language);
        $data['due_date_formatted'] = $this->formatDate($invoice->due_date, $invoice->language);

        // Format item data
        $data['items_formatted'] = $invoice->items->map(function ($item) use ($invoice) {
            return [
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $this->formatNumber($item->quantity, $invoice->language),
                'unit_price' => $this->formatCurrency($item->unit_price, $invoice->language),
                'total_price' => $this->formatCurrency($item->total_price, $invoice->language),
                'gold_purity' => $item->formatted_gold_purity,
                'weight' => $item->weight ? $this->formatNumber($item->weight, $invoice->language) . ($invoice->language === 'fa' ? ' گرم' : ' g') : null,
                'serial_number' => $item->serial_number,
                'category_path' => $item->category_display,
                'main_category' => $item->mainCategory ? $item->mainCategory->localized_name : null,
                'subcategory' => $item->category ? $item->category->localized_name : null,
                'category_image' => $item->category_image_url,
                'category_name_localized' => $item->localized_category_name,
            ];
        });

        return $data;
    }

    /**
     * Create PDF using appropriate template.
     */
    protected function createPDF(array $data, $language)
    {
        // Select appropriate view based on language
        $viewName = $language === 'fa' ? 'invoices.pdf.persian' : 'invoices.pdf.english';

        // Create PDF with proper configuration
        $pdf = Pdf::loadView($viewName, $data);

        // Configure PDF settings
        $pdf->setPaper('A4', 'portrait');
        
        if ($language === 'fa') {
            // RTL configuration for Persian
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);
        }

        return $pdf;
    }

    /**
     * Generate PDF filename.
     */
    protected function generatePDFFilename(Invoice $invoice)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "invoice_{$invoice->invoice_number}_{$timestamp}.pdf";
    }

    /**
     * Get company information.
     */
    protected function getCompanyInfo()
    {
        return [
            'name' => config('app.company_name', 'Jewelry Business'),
            'address' => config('app.company_address', ''),
            'phone' => config('app.company_phone', ''),
            'email' => config('app.company_email', ''),
            'website' => config('app.company_website', ''),
            'logo_path' => config('app.company_logo', ''),
        ];
    }

    /**
     * Format currency based on language.
     */
    protected function formatCurrency($amount, $language)
    {
        if ($language === 'fa') {
            // Persian formatting
            $formatted = number_format($amount, 0, '.', ',');
            return $this->convertToPersianNumbers($formatted) . ' ریال';
        } else {
            // English formatting
            return '$' . number_format($amount, 2);
        }
    }

    /**
     * Format date based on language.
     */
    protected function formatDate($date, $language)
    {
        if ($language === 'fa') {
            // Convert to Jalali calendar
            $jalaliDate = app(CalendarService::class)->gregorianToJalali($date);
            return $this->convertToPersianNumbers($jalaliDate);
        } else {
            return $date->format('M d, Y');
        }
    }

    /**
     * Format number based on language.
     */
    protected function formatNumber($number, $language)
    {
        $formatted = number_format($number, 2);
        
        if ($language === 'fa') {
            return $this->convertToPersianNumbers($formatted);
        }
        
        return $formatted;
    }

    /**
     * Format gold purity based on language.
     */
    protected function formatGoldPurity($purity, $language)
    {
        if ($language === 'fa') {
            $formatted = number_format($purity, 1);
            return $this->convertToPersianNumbers($formatted) . ' عیار';
        } else {
            return number_format($purity, 1) . 'K';
        }
    }

    /**
     * Convert English numbers to Persian numbers.
     */
    protected function convertToPersianNumbers($string)
    {
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        
        return str_replace($englishNumbers, $persianNumbers, $string);
    }

    /**
     * Generate batch PDFs for multiple invoices.
     */
    public function generateBatchPDFs(array $invoiceIds)
    {
        $results = [];
        
        foreach ($invoiceIds as $invoiceId) {
            try {
                $invoice = Invoice::findOrFail($invoiceId);
                $result = $this->generateInvoicePDF($invoice);
                $results[] = [
                    'invoice_id' => $invoiceId,
                    'success' => true,
                    'path' => $result['path'],
                    'url' => $result['url']
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'invoice_id' => $invoiceId,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Create ZIP file containing multiple invoice PDFs.
     */
    public function createBatchPDFZip(array $invoiceIds)
    {
        $zip = new \ZipArchive();
        $zipFilename = 'invoices_batch_' . now()->format('Y-m-d_H-i-s') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFilename);

        // Ensure temp directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            foreach ($invoiceIds as $invoiceId) {
                try {
                    $invoice = Invoice::findOrFail($invoiceId);
                    
                    // Generate PDF if not exists
                    if (!$invoice->pdf_path || !Storage::exists($invoice->pdf_path)) {
                        $this->generateInvoicePDF($invoice);
                    }
                    
                    // Add to ZIP
                    if ($invoice->pdf_path && Storage::exists($invoice->pdf_path)) {
                        $pdfContent = Storage::get($invoice->pdf_path);
                        $zip->addFromString(
                            "invoice_{$invoice->invoice_number}.pdf",
                            $pdfContent
                        );
                    }
                } catch (\Exception $e) {
                    // Log error but continue with other invoices
                    \Log::error("Failed to add invoice {$invoiceId} to batch ZIP: " . $e->getMessage());
                }
            }
            
            $zip->close();
            
            return [
                'path' => $zipPath,
                'filename' => $zipFilename,
                'url' => url('temp/' . $zipFilename)
            ];
        }
        
        throw new \Exception('Failed to create ZIP file');
    }
}