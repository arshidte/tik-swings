<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductAttributeModel extends Model
{
    protected $table         = 'product_attributes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'slug', 'sort_order'];
    protected $useTimestamps = false;

    /**
     * Find an attribute by its (unique) slug or create it. Returns the id.
     * Attributes are global and reused by name across products.
     */
    public function findOrCreate(string $name, int $sortOrder = 0): int
    {
        $name = trim($name);
        $slug = url_title($name, '-', true);

        $row = $this->where('slug', $slug)->first();
        if ($row) {
            return (int) $row['id'];
        }

        return (int) $this->insert([
            'name'       => $name,
            'slug'       => $slug,
            'sort_order' => $sortOrder,
        ], true);
    }

    /** All attribute names, for admin autocomplete. */
    public function names(): array
    {
        return array_column($this->orderBy('name')->findAll(), 'name');
    }
}
