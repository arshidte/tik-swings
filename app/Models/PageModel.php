<?php

namespace App\Models;

use CodeIgniter\Model;

class PageModel extends Model
{
    protected $table            = 'pages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'type', 'title', 'slug', 'excerpt', 'body', 'cover_image', 'author',
        'status', 'seo_title', 'seo_description', 'published_at',
    ];
    protected $useTimestamps = true;

    public function findPublished(string $slug, string $type = 'page'): ?array
    {
        return $this->where('slug', $slug)
            ->where('type', $type)
            ->where('status', 'published')
            ->first();
    }

    public function journal(int $limit = 9, int $offset = 0): array
    {
        return $this->where('type', 'journal')
            ->where('status', 'published')
            ->orderBy('published_at', 'DESC')
            ->findAll($limit, $offset);
    }
}
