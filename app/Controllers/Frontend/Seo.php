<?php

namespace App\Controllers\Frontend;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\PageModel;
use App\Models\ProductModel;

class Seo extends BaseController
{
    public function sitemap()
    {
        $urls = [
            ['loc' => base_url('/'), 'priority' => '1.0', 'freq' => 'weekly'],
            ['loc' => base_url('shop'), 'priority' => '0.9', 'freq' => 'daily'],
            ['loc' => base_url('custom-swings'), 'priority' => '0.7', 'freq' => 'monthly'],
            ['loc' => base_url('craftsmanship'), 'priority' => '0.6', 'freq' => 'monthly'],
            ['loc' => base_url('about'), 'priority' => '0.5', 'freq' => 'monthly'],
            ['loc' => base_url('journal'), 'priority' => '0.6', 'freq' => 'weekly'],
            ['loc' => base_url('contact'), 'priority' => '0.4', 'freq' => 'yearly'],
            ['loc' => base_url('faq'), 'priority' => '0.4', 'freq' => 'yearly'],
        ];

        foreach ((new CategoryModel())->active()->findAll() as $c) {
            $urls[] = ['loc' => base_url('category/' . $c['slug']), 'priority' => '0.8', 'freq' => 'weekly'];
        }
        foreach ((new ProductModel())->select('slug, updated_at')->where('status', 'active')->findAll() as $p) {
            $urls[] = ['loc' => base_url('product/' . $p['slug']), 'priority' => '0.8', 'freq' => 'weekly', 'lastmod' => date('Y-m-d', strtotime($p['updated_at'] ?? 'now'))];
        }
        foreach ((new PageModel())->where('type', 'journal')->where('status', 'published')->findAll() as $j) {
            $urls[] = ['loc' => base_url('journal/' . $j['slug']), 'priority' => '0.5', 'freq' => 'monthly'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . esc($u['loc']) . '</loc>';
            if (! empty($u['lastmod'])) {
                $xml .= '<lastmod>' . $u['lastmod'] . '</lastmod>';
            }
            $xml .= '<changefreq>' . $u['freq'] . '</changefreq><priority>' . $u['priority'] . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>';

        return $this->response->setHeader('Content-Type', 'application/xml')->setBody($xml);
    }

    public function robots()
    {
        $body = "User-agent: *\n";
        $body .= "Disallow: /admin\n";
        $body .= "Disallow: /account\n";
        $body .= "Disallow: /checkout\n";
        $body .= "Disallow: /cart\n";
        $body .= "Allow: /\n\n";
        $body .= 'Sitemap: ' . base_url('sitemap.xml') . "\n";

        return $this->response->setHeader('Content-Type', 'text/plain')->setBody($body);
    }
}
