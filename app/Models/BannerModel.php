<?php

namespace App\Models;

use CodeIgniter\Model;

class BannerModel extends Model
{
    protected $table            = 'banners';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'title', 'subtitle', 'image', 'link', 'cta_label',
        'position', 'sort_order', 'status',
    ];
    protected $useTimestamps = true;

    public function byPosition(string $position): array
    {
        return $this->where('position', $position)
            ->where('status', 'active')
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }
}
