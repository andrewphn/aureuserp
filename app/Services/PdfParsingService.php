<?php

namespace App\Services;

use App\Models\PdfDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

class PdfParsingService
{
    /**
     * Parse cover page to extract customer and project details
     *
     * @param PdfDocument $pdfDocument
     * @return array
     */
    public function parseCoverPage(PdfDocument $pdfDocument): array
    {
        // Try Google Document AI first for better accuracy
        try {
            $googleDocAi = app(GoogleDocumentAiService::class);
            $result = $googleDocAi->extractFromPdf($pdfDocument->file_path);

            if (!empty($result['pages']) && isset($result['pages'][0])) {
                $firstPageText = $result['pages'][0]['text'];
                return $this->extractCoverPageData($firstPageText);
            }
        } catch (\Exception $e) {
            \Log::warning('Google Document AI failed, falling back to PDF parser', [
                'error' => $e->getMessage()
            ]);
        }

        // Fallback to basic PDF parser
        $parser = new PdfParser();
        $filePath = Storage::disk('public')->path($pdfDocument->file_path);

        if (!file_exists($filePath)) {
            throw new \Exception("PDF file not found: {$filePath}");
        }

        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();
        $firstPageText = '';
        if (!empty($pages)) {
            $firstPageText = $pages[0]->getText();
        }

        return $this->extractCoverPageData($firstPageText);
    }

    /**
     * Extract customer and project details from cover page text
     *
     * @param string $text
     * @return array
     */
    protected function extractCoverPageData(string $text): array
    {
        $data = [
            'customer_name' => null,
            'customer_email' => null,
            'customer_phone' => null,
            'project_address' => null,
            'project_name' => null,
            'project_date' => null,
        ];

        // Extract email addresses
        if (preg_match('/[\w\.-]+@[\w\.-]+\.\w+/', $text, $matches)) {
            $data['customer_email'] = $matches[0];
        }

        // Extract phone numbers (various formats)
        // Try formatted phone first: (508) 228-9300, 508-228-9300, 508.228.9300
        if (preg_match('/(\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4})/', $text, $matches)) {
            // Clean and format phone number
            $phone = preg_replace('/[^0-9]/', '', $matches[1]);
            if (strlen($phone) === 10) {
                $data['customer_phone'] = '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
            } else {
                $data['customer_phone'] = $matches[1];
            }
        }

        // Extract addresses (street address pattern)
        if (preg_match('/(\d+\s+[A-Za-z\s]+(?:Street|St|Avenue|Ave|Road|Rd|Lane|Ln|Drive|Dr|Court|Ct|Way|Place|Pl|Boulevard|Blvd))/i', $text, $matches)) {
            $data['project_address'] = trim($matches[1]);

            // Try to get full address with city, state
            if (preg_match('/' . preg_quote($matches[1], '/') . '[,\s]+([A-Za-z\s]+),?\s+([A-Z]{2})/i', $text, $fullMatch)) {
                $data['project_address'] = trim($matches[1]) . ', ' . trim($fullMatch[1]) . ', ' . trim($fullMatch[2]);
            }
        }

        // Extract customer name (look for common patterns like "Client:", "Customer:", "For:", or names before addresses)
        if (preg_match('/(Client|Customer|For|Attn):\s*([A-Za-z\s\.]+)/i', $text, $matches)) {
            $data['customer_name'] = trim($matches[2]);
        }

        // Extract project name (often at the top of document or after "Project:")
        if (preg_match('/(Project|Property|Site):\s*([A-Za-z0-9\s\-]+)/i', $text, $matches)) {
            $data['project_name'] = trim($matches[2]);
        }

        // Extract dates (various formats)
        if (preg_match('/(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/', $text, $matches)) {
            $data['project_date'] = $matches[1];
        }

        return $data;
    }

    /**
     * Parse architectural PDF and extract line items for sales order
     *
     * @param PdfDocument $pdfDocument
     * @return array
     */
    public function parseArchitecturalDrawing(PdfDocument $pdfDocument): array
    {
        // Initialize PDF parser
        $parser = new PdfParser();

        // Get file path and parse
        $filePath = Storage::disk('public')->path($pdfDocument->file_path);

        if (!file_exists($filePath)) {
            throw new \Exception("PDF file not found: {$filePath}");
        }

        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        // Extract line items from text
        $lineItems = $this->extractLineItems($text);

        return [
            'line_items' => $lineItems,
            'raw_text' => $text,
            'pdf_document_id' => $pdfDocument->id,
        ];
    }

    /**
     * Extract line items from PDF text
     *
     * Matches patterns like:
     * - "Tier 2 Cabinetry: 11.5 LF"
     * - "Tier 4 Cabinetry: 35.25 LF"
     * - "Floating Shelves: 4 LF"
     * - "Millwork Countertops: 11 SF"
     *
     * @param string $text
     * @return array
     */
    protected function extractLineItems(string $text): array
    {
        $lineItems = [];

        // Pattern: "Product Name: Quantity UNIT"
        // Examples: "Tier 2 Cabinetry: 11.5 LF", "Floating Shelves: 4 LF", "Millwork Countertops: 11 SF"
        preg_match_all(
            '/([A-Za-z0-9\s]+?):\s*(\d+\.?\d*)\s*(LF|SF|EA|FT)/i',
            $text,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $productName = trim($match[1]);
            $quantity = floatval($match[2]);
            $unit = strtoupper($match[3]);

            // Map product name to database product
            $product = $this->mapProductName($productName);

            if ($product) {
                $lineItems[] = [
                    'product_id' => $product['product_id'],
                    'product_name' => $product['product_name'],
                    'raw_name' => $productName,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'attribute_selections' => $product['attributes'] ?? [],
                    'unit_price' => $product['unit_price'] ?? 0,
                ];
            } else {
                // Product not found - add as unmatched for manual review
                $lineItems[] = [
                    'product_id' => null,
                    'product_name' => 'UNMATCHED: ' . $productName,
                    'raw_name' => $productName,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'attribute_selections' => [],
                    'unit_price' => 0,
                    'needs_review' => true,
                ];
            }
        }

        return $lineItems;
    }

    /**
     * Map product name from PDF to database product
     *
     * @param string $productName
     * @return array|null
     */
    protected function mapProductName(string $productName): ?array
    {
        $productName = strtolower(trim($productName));

        // Cabinet product mappings
        if (preg_match('/(tier|level)\s*(\d)/i', $productName, $matches)) {
            $level = intval($matches[2]);
            return $this->getCabinetProduct($level);
        }

        // Floating Shelves
        if (str_contains($productName, 'floating shelf') || str_contains($productName, 'floating shelves')) {
            return $this->getFloatingShelfProduct();
        }

        // Countertops
        if (str_contains($productName, 'countertop')) {
            return $this->getCountertopProduct();
        }

        // Closet Shelf & Rod
        if (str_contains($productName, 'closet') && (str_contains($productName, 'shelf') || str_contains($productName, 'rod'))) {
            return $this->getClosetShelfRodProduct();
        }

        return null;
    }

    /**
     * Get Cabinet product with specified pricing level
     *
     * @param int $level
     * @return array|null
     */
    protected function getCabinetProduct(int $level = 2): ?array
    {
        $product = DB::table('products_products')
            ->where('reference', 'CABINET')
            ->first(['id', 'name', 'price']);

        if (!$product) {
            return null;
        }

        // Get pricing level attribute option
        $pricingLevelAttr = DB::table('products_attributes')
            ->where('name', 'Pricing Level')
            ->first(['id']);

        if (!$pricingLevelAttr) {
            return null;
        }

        $levelOption = DB::table('products_attribute_options')
            ->where('attribute_id', $pricingLevelAttr->id)
            ->where('name', 'LIKE', "Level {$level}%")
            ->first(['id', 'name', 'extra_price']);

        if (!$levelOption) {
            return null;
        }

        $unitPrice = floatval($product->price) + floatval($levelOption->extra_price);

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $unitPrice,
            'attributes' => [
                [
                    'attribute_id' => $pricingLevelAttr->id,
                    'attribute_name' => 'Pricing Level',
                    'option_id' => $levelOption->id,
                    'option_name' => $levelOption->name,
                    'extra_price' => $levelOption->extra_price,
                ]
            ],
        ];
    }

    /**
     * Get Floating Shelf product
     *
     * @return array|null
     */
    protected function getFloatingShelfProduct(): ?array
    {
        $product = DB::table('products_products')
            ->where('reference', 'FLOAT_SHELF')
            ->first(['id', 'name', 'price']);

        if (!$product) {
            return null;
        }

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => floatval($product->price),
            'attributes' => [],
        ];
    }

    /**
     * Get Countertop product
     *
     * @return array|null
     */
    protected function getCountertopProduct(): ?array
    {
        $product = DB::table('products_products')
            ->where('reference', 'COUNTERTOP')
            ->first(['id', 'name', 'price']);

        if (!$product) {
            return null;
        }

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => floatval($product->price),
            'attributes' => [],
        ];
    }

    /**
     * Get Closet Shelf & Rod product
     *
     * @return array|null
     */
    protected function getClosetShelfRodProduct(): ?array
    {
        $product = DB::table('products_products')
            ->where('reference', 'CLOSET_SHELF')
            ->first(['id', 'name', 'price']);

        if (!$product) {
            return null;
        }

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => floatval($product->price),
            'attributes' => [],
        ];
    }

    /**
     * Create sales order from parsed line items
     *
     * @param array $parsedData
     * @param int $projectId
     * @param int $partnerId
     * @return int Sales order ID
     */
    public function createSalesOrderFromParsedData(array $parsedData, int $projectId, int $partnerId): int
    {
        $now = now();

        // Get project details
        $project = DB::table('projects_projects')->where('id', $projectId)->first();

        if (!$project) {
            throw new \Exception("Project not found: {$projectId}");
        }

        // Create sales order
        $salesOrderId = DB::table('sales_orders')->insertGetId([
            'project_id' => $projectId,
            'partner_id' => $partnerId,
            'partner_invoice_id' => $partnerId, // Same as partner for invoice address
            'partner_shipping_id' => $partnerId, // Same as partner for shipping address
            'company_id' => $project->company_id ?? 1,
            'state' => 'draft',
            'invoice_status' => 'no',
            'date_order' => $now,
            'currency_id' => 1, // Default USD
            'creator_id' => auth()->id() ?? 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $subtotal = 0;

        // Create sales order lines
        foreach ($parsedData['line_items'] as $index => $lineItem) {
            if (!$lineItem['product_id']) {
                continue; // Skip unmatched products
            }

            $lineTotal = $lineItem['quantity'] * $lineItem['unit_price'];
            $subtotal += $lineTotal;

            DB::table('sales_order_lines')->insert([
                'order_id' => $salesOrderId,
                'product_id' => $lineItem['product_id'],
                'name' => $lineItem['product_name'],
                'sort' => $index + 1, // Use 'sort' not 'sequence'
                'product_uom_qty' => $lineItem['quantity'],
                'price_unit' => $lineItem['unit_price'],
                'price_subtotal' => $lineTotal,
                'qty_delivered' => 0,
                'qty_to_invoice' => $lineItem['quantity'],
                'qty_invoiced' => 0,
                'creator_id' => auth()->id() ?? 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Update sales order totals
        DB::table('sales_orders')
            ->where('id', $salesOrderId)
            ->update([
                'amount_untaxed' => $subtotal,
                'amount_total' => $subtotal,
                'updated_at' => $now,
            ]);

        return $salesOrderId;
    }
}
