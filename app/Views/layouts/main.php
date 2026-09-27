<?php
/**
 * Base storefront layout.
 * Pages extend this and fill the `content` section.
 * SEO data is passed via $meta (see App\Controllers\BaseController usage).
 */
$meta       = $meta ?? [];
$title      = $meta['title']       ?? setting('seo_title', store_name());
$desc       = $meta['description'] ?? setting('seo_description', '');
$canonical  = $meta['canonical']   ?? current_url();
$ogImage    = $meta['og_image']    ?? product_image(setting('og_image'), 'hero.svg');
$ogType     = $meta['og_type']     ?? 'website';
$bodyClass  = $bodyClass ?? '';
$schema     = $schema ?? null; // JSON-LD array or string
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2A231E">
    <meta name="csrf-token" content="<?= csrf_hash() ?>" data-name="<?= esc(csrf_token()) ?>">

    <title><?= esc($title) ?></title>
    <meta name="description" content="<?= esc($desc) ?>">
    <link rel="canonical" href="<?= esc($canonical, 'attr') ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="<?= esc($ogType, 'attr') ?>">
    <meta property="og:title" content="<?= esc($title, 'attr') ?>">
    <meta property="og:description" content="<?= esc($desc, 'attr') ?>">
    <meta property="og:url" content="<?= esc($canonical, 'attr') ?>">
    <meta property="og:image" content="<?= esc($ogImage, 'attr') ?>">
    <meta property="og:site_name" content="<?= esc(store_name(), 'attr') ?>">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" href="<?= base_url('assets/images/placeholder-product.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">

    <?php if ($schema): ?>
        <script type="application/ld+json"><?= is_string($schema) ? $schema : json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif ?>
    <?= $this->renderSection('head') ?>
</head>
<body class="min-h-dvh bg-bg text-ink flex flex-col <?= esc($bodyClass, 'attr') ?>">

<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-[1000] focus:top-4 focus:left-4 focus:bg-ink focus:text-bg focus:px-4 focus:py-2 focus:rounded-md">Skip to content</a>

<?= $this->include('components/header') ?>

<main id="main" class="flex-1">
    <?= $this->renderSection('content') ?>
</main>

<?= $this->include('components/footer') ?>
<?= $this->include('components/search_overlay') ?>
<?= $this->include('components/cart_drawer') ?>

<!-- Floating WhatsApp support (§50) -->
<a href="<?= esc(whatsapp_link(), 'attr') ?>" target="_blank" rel="noopener"
   class="fixed right-4 z-40 bottom-4 sm:bottom-6 flex items-center justify-center w-14 h-14 rounded-full bg-[#25D366] text-white shadow-lift hover:scale-105 active:scale-95 transition-transform"
   aria-label="Chat with us on WhatsApp" data-whatsapp-fab>
    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.2-.6.2-.2.3-.7.9-.8 1-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.4-1.5-.9-.8-1.5-1.8-1.6-2.1-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.2-.6-1.5-.9-2-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.3.3-1 .9-1 2.3s1 2.7 1.2 2.9c.1.2 2 3.1 5 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.7-.7 2-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3M12 2a10 10 0 0 0-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1 0 12 2"/></svg>
</a>

<!-- Toast region (§43 / a11y aria-live) -->
<div id="toast-region" class="fixed z-[1100] top-4 left-1/2 -translate-x-1/2 flex flex-col gap-2 items-center w-full max-w-sm px-4 pointer-events-none" aria-live="polite" aria-atomic="false"></div>

<script>
    window.SG = {
        baseUrl: '<?= rtrim(base_url(), '/') ?>/',
        csrf: { name: '<?= esc(csrf_token()) ?>', hash: '<?= csrf_hash() ?>', header: 'X-CSRF-TOKEN' },
        loggedIn: <?= is_logged_in() ? 'true' : 'false' ?>
    };
</script>
<script type="module" src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
