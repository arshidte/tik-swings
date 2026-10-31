<?php

namespace App\Models;

use CodeIgniter\Model;

class PageModel extends Model
{
    /**
     * Pages the storefront routes to by fixed URL. Their slug and type are
     * locked in the admin, and they cannot be deleted (it would 404 the site
     * navigation).
     */
    public const SYSTEM_SLUGS = ['about', 'craftsmanship', 'faq'];

    protected $table            = 'pages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'type', 'title', 'slug', 'excerpt', 'body', 'cover_image', 'author',
        'status', 'seo_title', 'seo_description', 'published_at',
    ];
    protected $useTimestamps = true;

    /** Published and not scheduled for the future. */
    private function live(): self
    {
        return $this->where('status', 'published')
            ->groupStart()
            ->where('published_at', null)
            ->orWhere('published_at <=', date('Y-m-d H:i:s'))
            ->groupEnd();
    }

    public function findPublished(string $slug, string $type = 'page'): ?array
    {
        return $this->where('slug', $slug)
            ->where('type', $type)
            ->live()
            ->first();
    }

    /** Builder-style: live journal posts, newest first — chain ->paginate(). */
    public function journalQuery(): self
    {
        return $this->where('type', 'journal')
            ->live()
            ->orderBy('published_at', 'DESC')
            ->orderBy('id', 'DESC');
    }

    public function journal(int $limit = 9, int $offset = 0): array
    {
        return $this->where('type', 'journal')
            ->live()
            ->orderBy('published_at', 'DESC')
            ->findAll($limit, $offset);
    }

    /** Live content pages not covered by a fixed route (served at /page/{slug}). */
    public function customPages(): array
    {
        return $this->select('slug, updated_at')
            ->where('type', 'page')
            ->whereNotIn('slug', self::SYSTEM_SLUGS)
            ->live()
            ->findAll();
    }

    public static function isSystem(?string $slug): bool
    {
        return in_array($slug, self::SYSTEM_SLUGS, true);
    }

    /** Public URL for a page row. */
    public static function urlFor(array $page): string
    {
        if ($page['type'] === 'journal') {
            return base_url('journal/' . $page['slug']);
        }

        return self::isSystem($page['slug'])
            ? base_url($page['slug'])
            : base_url('page/' . $page['slug']);
    }

    /** URL-safe slug that is unique across all pages (the column is globally unique). */
    public function uniqueSlug(string $wanted, ?int $ignoreId = null): string
    {
        helper('text');
        $base = url_title($wanted, '-', true) ?: 'page';
        $slug = $base;
        for ($i = 2; $i < 100; $i++) {
            $q = $this->builder()->where('slug', $slug);
            if ($ignoreId) {
                $q->where('id !=', $ignoreId);
            }
            if ($q->countAllResults() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $i;
        }

        return $base . '-' . bin2hex(random_bytes(2));
    }
}
