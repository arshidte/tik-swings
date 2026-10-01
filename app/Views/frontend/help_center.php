<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="relative bg-ink text-bg py-20 sm:py-28 text-center">
    <div class="relative container-page">
        <h1 class="text-display font-display max-w-3xl mx-auto reveal">Help Center</h1>
        <p class="mt-5 text-lg text-bg/80 max-w-2xl mx-auto reveal">Get answers to your general FAQs related to products, purchasing, and more.</p>
    </div>
</section>

<article class="section">
    <div class="container-page max-w-prose">
        
        <div class="reveal bg-stone text-ink p-8 shadow-sm mb-12 text-center rounded-sm">
            <h2 class="text-3xl font-display mb-4">How can we help you?</h2>
            <p class="mb-6 text-lg">Do you still have any questions or complaints? If you cannot find what you are looking for, you can contact us, we will get back to you shortly!</p>
            <div class="flex justify-center flex-wrap gap-4">
                <a href="<?= base_url('contact') ?>" class="btn-primary">Contact Us</a>
                <a href="<?= base_url('faq') ?>" class="btn-outline">Read FAQs</a>
            </div>
        </div>

        <div class="prose-content text-lg leading-relaxed text-ink/90 space-y-6 reveal">
            <h3 class="text-2xl font-display text-ink mb-4">Quick Links</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="<?= base_url('about') ?>" class="block p-4 border border-wood-light/20 hover:border-wood transition-colors rounded-sm group">
                    <h4 class="font-display text-xl group-hover:text-wood transition-colors">About Us</h4>
                    <p class="text-sm mt-1 text-ink/70">Learn more about our craftsmanship and story.</p>
                </a>
                <a href="<?= base_url('return-and-refunds') ?>" class="block p-4 border border-wood-light/20 hover:border-wood transition-colors rounded-sm group">
                    <h4 class="font-display text-xl group-hover:text-wood transition-colors">Return &amp; Refund</h4>
                    <p class="text-sm mt-1 text-ink/70">Read our comprehensive return and refund policy.</p>
                </a>
                <a href="<?= base_url('contact') ?>" class="block p-4 border border-wood-light/20 hover:border-wood transition-colors rounded-sm group">
                    <h4 class="font-display text-xl group-hover:text-wood transition-colors">Contact Workshop</h4>
                    <p class="text-sm mt-1 text-ink/70">Get in touch directly with our support team.</p>
                </a>
                <a href="<?= base_url('shop') ?>" class="block p-4 border border-wood-light/20 hover:border-wood transition-colors rounded-sm group">
                    <h4 class="font-display text-xl group-hover:text-wood transition-colors">Our Collection</h4>
                    <p class="text-sm mt-1 text-ink/70">Explore our premium range of crafted furniture.</p>
                </a>
            </div>
        </div>

    </div>
</article>
<?= $this->endSection() ?>
