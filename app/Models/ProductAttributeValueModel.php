<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductAttributeValueModel extends Model
{
    protected $table         = 'product_attribute_values';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['attribute_id', 'value', 'slug', 'swatch', 'sort_order'];
    protected $useTimestamps = false;

    /**
     * Find a value within an attribute by slug or create it; keeps the swatch
     * hex in sync. Returns the value id.
     */
    public function findOrCreate(int $attributeId, string $value, ?string $swatch = null, int $sortOrder = 0): int
    {
        $value = trim($value);
        $slug  = url_title($value, '-', true);
        $swatch = $swatch !== null && $swatch !== '' ? $swatch : null;

        $row = $this->where('attribute_id', $attributeId)->where('slug', $slug)->first();
        if ($row) {
            $this->update($row['id'], ['value' => $value, 'swatch' => $swatch, 'sort_order' => $sortOrder]);

            return (int) $row['id'];
        }

        return (int) $this->insert([
            'attribute_id' => $attributeId,
            'value'        => $value,
            'slug'         => $slug,
            'swatch'       => $swatch,
            'sort_order'   => $sortOrder,
        ], true);
    }
}
