<?php

namespace Webkul\Product\Traits;

use Webkul\Product\Models\Category;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ReferenceTypeCode;

trait GeneratesReferenceCode
{
    /**
     * Generate a reference code for a product
     */
    public static function generateReferenceCode(?int $categoryId, ?int $typeCodeId, ?int $excludeProductId = null): ?string
    {
        if (!$categoryId || !$typeCodeId) {
            return null;
        }

        $category = Category::find($categoryId);
        $typeCode = ReferenceTypeCode::find($typeCodeId);

        if (!$category || !$typeCode) {
            return null;
        }

        $catCode = $category->code ?? 'UNK';
        $typeCodeStr = $typeCode->code ?? 'UNK';

        // Build the prefix
        $prefix = "TCS-{$catCode}-{$typeCodeStr}-";

        // Find the next sequence number
        $query = Product::where('reference', 'like', $prefix . '%');

        if ($excludeProductId) {
            $query->where('id', '!=', $excludeProductId);
        }

        $existingRefs = $query->pluck('reference')->toArray();

        $maxSeq = 0;
        foreach ($existingRefs as $ref) {
            $seqPart = substr($ref, strlen($prefix));
            $seq = (int) $seqPart;
            if ($seq > $maxSeq) {
                $maxSeq = $seq;
            }
        }

        $nextSeq = $maxSeq + 1;

        return $prefix . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Auto-populate reference if empty and type code is selected
     */
    protected function mutateFormDataWithReferenceCode(array $data): array
    {
        // Only auto-generate if reference is empty and we have category + type code
        if (empty($data['reference']) && !empty($data['category_id']) && !empty($data['reference_type_code_id'])) {
            $excludeId = $this->record?->id ?? null;
            $data['reference'] = static::generateReferenceCode(
                $data['category_id'],
                $data['reference_type_code_id'],
                $excludeId
            );
        }

        // Store the type code value
        if (!empty($data['reference_type_code_id'])) {
            $typeCode = ReferenceTypeCode::find($data['reference_type_code_id']);
            $data['type_code'] = $typeCode?->code;
        }

        return $data;
    }
}
