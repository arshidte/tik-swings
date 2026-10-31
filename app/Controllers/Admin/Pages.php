<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PageModel;
use App\Services\HtmlSanitizer;
use App\Services\ImageUploadService;

/**
 * CMS: static pages and journal posts (the `pages` table).
 */
class Pages extends BaseController
{
    private PageModel $pages;

    public function __construct()
    {
        $this->pages = new PageModel();
    }

    // ------------------------------------------------------------------ list
    public function index(): string
    {
        $q      = trim((string) $this->request->getGet('q'));
        $type   = (string) $this->request->getGet('type');
        $status = (string) $this->request->getGet('status');

        $model = $this->pages->orderBy('type')->orderBy('updated_at', 'DESC');
        if ($q !== '') {
            $model->groupStart()->like('title', $q)->orLike('slug', $q)->groupEnd();
        }
        if (in_array($type, ['page', 'journal'], true)) {
            $model->where('type', $type);
        }
        if (in_array($status, ['draft', 'published'], true)) {
            $model->where('status', $status);
        }

        $rows  = $model->paginate(20);
        $pager = $this->pages->pager;

        $counts = [];
        foreach ($this->pages->select('type, status, COUNT(*) AS c')->groupBy('type, status')->findAll() as $r) {
            $counts[$r['type']][$r['status']] = (int) $r['c'];
        }

        return view('admin/pages', [
            'title'  => 'Pages & Journal',
            'pages'  => $rows,
            'pager'  => $pager,
            'q'      => $q,
            'type'   => $type,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    // ------------------------------------------------------------ create/edit
    public function create(): string
    {
        $type = $this->request->getGet('type') === 'journal' ? 'journal' : 'page';

        return $this->form([
            'id' => null, 'type' => $type, 'title' => '', 'slug' => '', 'excerpt' => '', 'body' => '',
            'cover_image' => null, 'author' => $type === 'journal' ? store_name() . ' Studio' : '',
            'status' => 'draft', 'seo_title' => '', 'seo_description' => '', 'published_at' => null,
        ], 'New ' . ($type === 'journal' ? 'Journal Post' : 'Page'));
    }

    public function edit(int $id): string
    {
        $page = $this->pages->find($id);
        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->form($page, 'Edit ' . ($page['type'] === 'journal' ? 'Journal Post' : 'Page'));
    }

    private function form(array $page, string $title): string
    {
        $isFaq = ($page['slug'] ?? '') === 'faq' && $page['type'] === 'page';

        return view('admin/pages_form', [
            'title'     => $title,
            'p'         => $page,
            'isSystem'  => PageModel::isSystem($page['slug'] ?? null),
            'isFaq'     => $isFaq,
            'faqs'      => $isFaq ? $this->parseFaq((string) $page['body']) : [],
            // Legacy plain-text bodies open in the editor as paragraphs.
            'bodyHtml'  => $isFaq ? '' : cms_html($page['body'] ?? ''),
            'publicUrl' => $page['id'] ? PageModel::urlFor($page) : null,
        ]);
    }

    // ------------------------------------------------------------------ save
    public function store()
    {
        return $this->save(null);
    }

    public function update(int $id)
    {
        if (! $this->pages->find($id)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->save($id);
    }

    private function save(?int $id)
    {
        $existing = $id ? $this->pages->find($id) : null;
        $locked   = $existing && PageModel::isSystem($existing['slug']);

        $rules = [
            'title'           => 'required|max_length[190]',
            'slug'            => 'permit_empty|max_length[190]',
            'excerpt'         => 'permit_empty|max_length[500]',
            'author'          => 'permit_empty|max_length[120]',
            'seo_title'       => 'permit_empty|max_length[190]',
            'seo_description' => 'permit_empty|max_length[320]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post  = $this->request;
        $title = trim((string) $post->getPost('title'));

        // System pages keep their slug and type: the storefront routes to them by URL.
        $type = $locked ? $existing['type'] : ($post->getPost('type') === 'journal' ? 'journal' : 'page');
        $slug = $locked
            ? $existing['slug']
            : $this->pages->uniqueSlug(trim((string) $post->getPost('slug')) ?: $title, $id);

        $isFaq = $locked && $existing['slug'] === 'faq';
        $body  = $isFaq
            ? $this->buildFaq((array) $post->getPost('faq'))
            : (new HtmlSanitizer())->clean((string) $post->getPost('body'));

        $status = $post->getPost('status') === 'published' ? 'published' : 'draft';

        $data = [
            'type'            => $type,
            'title'           => $title,
            'slug'            => $slug,
            'excerpt'         => trim((string) $post->getPost('excerpt')) ?: null,
            'body'            => $body,
            'author'          => trim((string) $post->getPost('author')) ?: null,
            'status'          => $status,
            'seo_title'       => trim((string) $post->getPost('seo_title')) ?: null,
            'seo_description' => trim((string) $post->getPost('seo_description')) ?: null,
            'published_at'    => $this->publishDate($status, (string) $post->getPost('published_at'), $existing),
        ];

        // Cover image: keep, replace or remove.
        $data['cover_image'] = $existing['cover_image'] ?? null;
        if ($post->getPost('remove_cover')) {
            $data['cover_image'] = null;
        }
        $file = $post->getFile('cover');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            try {
                $data['cover_image'] = (new ImageUploadService())->storeProductImage($file);
            } catch (\RuntimeException $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        if ($id) {
            $this->pages->update($id, $data);
        } else {
            $id = $this->pages->insert($data, true);
        }

        return redirect()->to(site_url('admin/pages/edit/' . $id))->with('success', 'Saved.');
    }

    /**
     * An entered date wins. Otherwise publishing stamps "now" (or keeps the
     * existing date); drafts keep whatever was prepared. A future date on a
     * published page means "scheduled" — the storefront hides it until then.
     */
    private function publishDate(string $status, string $input, ?array $existing): ?string
    {
        $input = trim($input);
        if ($input !== '' && ($ts = strtotime($input)) !== false) {
            return date('Y-m-d H:i:s', $ts);
        }
        if ($status === 'published') {
            return $existing['published_at'] ?? date('Y-m-d H:i:s');
        }

        return $existing['published_at'] ?? null;
    }

    // ---------------------------------------------------------------- delete
    public function delete(int $id)
    {
        $page = $this->pages->find($id);
        if (! $page) {
            return redirect()->to(site_url('admin/pages'))->with('error', 'Page not found.');
        }
        if (PageModel::isSystem($page['slug'])) {
            return redirect()->back()->with('error', 'This page is linked from the site navigation and cannot be deleted. Unpublish it instead.');
        }
        $this->pages->delete($id);

        return redirect()->to(site_url('admin/pages'))->with('success', '"' . $page['title'] . '" deleted.');
    }

    // --------------------------------------------------------------- preview
    /** Render any page (including drafts) exactly as the storefront would. */
    public function preview(int $id)
    {
        $page = $this->pages->find($id);
        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if ($page['slug'] === 'faq' && $page['type'] === 'page') {
            return redirect()->to(base_url('faq'));
        }

        $meta = ['title' => '[Preview] ' . $page['title'], 'description' => $page['excerpt']];

        return $page['type'] === 'journal'
            ? view('frontend/journal_post', ['meta' => $meta, 'post' => $page])
            : view('frontend/page', ['meta' => $meta, 'page' => $page]);
    }

    // ---------------------------------------------------------- editor images
    /** Image upload for the rich-text editor. Returns {url}. */
    public function uploadImage()
    {
        $file = $this->request->getFile('image');
        if (! $file || ! $file->isValid()) {
            return $this->jsonError('Choose an image to upload.');
        }
        try {
            $path = (new ImageUploadService())->storeProductImage($file);
        } catch (\RuntimeException $e) {
            return $this->jsonError($e->getMessage());
        }

        return $this->jsonSuccess('', ['url' => base_url($path)]);
    }

    // ------------------------------------------------------------------- FAQ
    /**
     * The FAQ page stores "Question\nAnswer" blocks separated by blank lines
     * (parsed by Frontend\Pages::faq). Mirror that format exactly.
     *
     * @return list<array{q: string, a: string}>
     */
    private function parseFaq(string $body): array
    {
        $out = [];
        foreach (preg_split('/\n\s*\n/', trim($body)) as $block) {
            $lines = explode("\n", trim($block), 2);
            if (count($lines) === 2) {
                $out[] = ['q' => trim($lines[0]), 'a' => trim($lines[1])];
            }
        }

        return $out;
    }

    private function buildFaq(array $rows): string
    {
        $blocks = [];
        foreach ($rows as $row) {
            $q = trim(preg_replace('/\s+/', ' ', (string) ($row['q'] ?? '')));
            $a = trim(preg_replace("/\r\n?/", "\n", (string) ($row['a'] ?? '')));
            $a = preg_replace("/\n\s*\n+/", "\n", $a); // a blank line would start a new question
            if ($q !== '' && $a !== '') {
                $blocks[] = $q . "\n" . $a;
            }
        }

        return implode("\n\n", $blocks);
    }
}
