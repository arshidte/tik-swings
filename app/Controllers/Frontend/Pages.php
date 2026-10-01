<?php

namespace App\Controllers\Frontend;

use App\Controllers\BaseController;
use App\Models\ContactMessageModel;
use App\Models\NewsletterModel;
use App\Models\PageModel;
use App\Models\ProductModel;

class Pages extends BaseController
{
    private function renderCms(string $slug, string $view = 'frontend/page'): string
    {
        $page = (new PageModel())->findPublished($slug);
        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view($view, [
            'meta' => [
                'title'       => $page['seo_title'] ?: $page['title'] . ' — ' . store_name(),
                'description' => $page['seo_description'] ?: $page['excerpt'],
            ],
            'page' => $page,
        ]);
    }

    public function about(): string
    {
        return $this->renderCms('about');
    }

    public function craftsmanship(): string
    {
        return $this->renderCms('craftsmanship');
    }

    public function customSwings(): string
    {
        // Customization experience (§15) with attribute options.
        $db = db_connect();
        $attributes = $db->table('product_attributes pa')
            ->select('pa.id, pa.name, pa.slug')
            ->orderBy('pa.sort_order', 'ASC')->get()->getResultArray();
        foreach ($attributes as &$a) {
            $a['values'] = $db->table('product_attribute_values')
                ->where('attribute_id', $a['id'])->orderBy('sort_order', 'ASC')->get()->getResultArray();
        }
        unset($a);

        $page      = (new PageModel())->findPublished('custom-swings');
        $customExamples = (new ProductModel())->cardColumns()->active()->where('is_customizable', 1)->findAll(4);
        $customExamples = (new ProductModel())->withPrimaryImage($customExamples);

        return view('frontend/custom', [
            'meta'       => ['title' => 'Design Your Own Swing — ' . store_name(), 'description' => $page['excerpt'] ?? ''],
            'page'       => $page,
            'attributes' => $attributes,
            'examples'   => $customExamples,
            'wishIds'    => wishlist_ids(),
        ]);
    }

    public function faq(): string
    {
        $page = (new PageModel())->findPublished('faq');
        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        // Parse "Question?\nAnswer" blocks into Q/A pairs.
        $faqs   = [];
        $blocks = preg_split('/\n\s*\n/', trim((string) $page['body']));
        foreach ($blocks as $b) {
            $lines = explode("\n", trim($b), 2);
            if (count($lines) === 2) {
                $faqs[] = ['q' => trim($lines[0]), 'a' => trim($lines[1])];
            }
        }

        return view('frontend/faq', [
            'meta'   => ['title' => 'FAQ — ' . store_name(), 'description' => $page['excerpt']],
            'page'   => $page,
            'faqs'   => $faqs,
            'schema' => $this->faqSchema($faqs),
        ]);
    }

    public function journal(): string
    {
        $posts = (new PageModel())->journal(12);

        return view('frontend/journal_index', [
            'meta'  => ['title' => 'Journal — ' . store_name(), 'description' => 'Stories, guides and workshop notes from ' . store_name() . '.'],
            'posts' => $posts,
        ]);
    }

    public function journalPost(string $slug): string
    {
        $post = (new PageModel())->findPublished($slug, 'journal');
        if (! $post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('frontend/journal_post', [
            'meta' => [
                'title'       => $post['seo_title'] ?: $post['title'],
                'description' => $post['seo_description'] ?: $post['excerpt'],
                'og_type'     => 'article',
                'og_image'    => product_image($post['cover_image'], 'lifestyle.svg'),
            ],
            'post' => $post,
        ]);
    }

    public function contact(): string
    {
        return view('frontend/contact', [
            'meta' => ['title' => 'Contact — ' . store_name(), 'description' => 'Get in touch with the ' . store_name() . ' workshop.'],
        ]);
    }

    public function returnAndRefunds(): string
    {
        return view('frontend/return_and_refunds', [
            'meta' => ['title' => 'Return and Refunds — ' . store_name(), 'description' => 'Carefully read our return and refund policy.'],
        ]);
    }

    public function helpCenter(): string
    {
        return view('frontend/help_center', [
            'meta' => ['title' => 'Help Center — ' . store_name(), 'description' => 'Help Center: Get answers to your general FAQs.'],
        ]);
    }

    public function submitContact()
    {
        $model = new ContactMessageModel();
        $data  = [
            'name'    => trim((string) $this->request->getPost('name')),
            'email'   => trim((string) $this->request->getPost('email')),
            'phone'   => trim((string) $this->request->getPost('phone')),
            'subject' => trim((string) $this->request->getPost('subject')),
            'message' => trim((string) $this->request->getPost('message')),
        ];

        if (! $model->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to(site_url('contact'))->with('success', 'Thank you — we will be in touch shortly.');
    }

    public function subscribe()
    {
        $email = trim((string) $this->input('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->jsonError('Please enter a valid email address.');
        }
        (new NewsletterModel())->subscribe($email);

        return $this->jsonSuccess('You are on the list. Welcome.');
    }

    private function faqSchema(array $faqs): array
    {
        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(static fn ($f) => [
                '@type'          => 'Question',
                'name'           => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ], $faqs),
        ];
    }
}
