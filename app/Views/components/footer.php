<?php $cats = nav_categories(); ?>
<footer class="mt-auto bg-ink text-bg/80">
    <!-- Newsletter -->
    <div class="border-b border-white/10">
        <div class="container-page py-12 grid gap-8 md:grid-cols-2 md:items-center">
            <div>
                <h2 class="font-display text-2xl sm:text-3xl text-bg">Make room for moments.</h2>
                <p class="mt-2 max-w-md text-bg/70">Join our list for new collections, workshop stories and the occasional quiet offer. No noise.</p>
            </div>
            <form class="flex flex-col sm:flex-row gap-3" data-newsletter aria-label="Subscribe to newsletter">
                <label for="nl-email" class="sr-only">Email address</label>
                <input id="nl-email" type="email" name="email" required autocomplete="email" placeholder="you@example.com"
                       class="flex-1 rounded-md bg-white/10 border border-white/15 px-4 py-3 min-h-[48px] text-bg placeholder:text-bg/50 focus:border-wood-light focus:ring-2 focus:ring-wood/30 focus:outline-none">
                <button type="submit" class="btn bg-bg text-ink hover:bg-wood-light min-h-[48px] whitespace-nowrap">Subscribe</button>
            </form>
        </div>
    </div>

    <div class="container-page py-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <div class="mb-4">
                <img src="<?= base_url('assets/images/logo.png') ?>" alt="<?= esc(store_name()) ?>" width="600" height="143" class="h-10 w-auto" loading="lazy">
            </div>
            <p class="text-sm text-bg/60 max-w-xs"><?= esc(setting('store_tagline')) ?></p>
            <div class="flex items-center gap-3 mt-5">
                <?php
                $socials = [
                    'social_instagram' => 'M12 8.5A3.5 3.5 0 1 0 12 15.5 3.5 3.5 0 0 0 12 8.5Zm5-1.2a.9.9 0 1 1-1.8 0 .9.9 0 0 1 1.8 0ZM7 4h10a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3Z',
                    'social_facebook'  => 'M13 22v-8h2.5l.5-3H13V9c0-.9.3-1.5 1.6-1.5H16V4.9C15.7 4.9 14.7 4.8 13.6 4.8 11.3 4.8 10 6.1 10 8.6V11H7.5v3H10v8h3Z',
                    'social_pinterest' => 'M12 3a9 9 0 0 0-3.3 17.4c-.1-.7-.1-1.9 0-2.7l1.1-4.6s-.3-.6-.3-1.4c0-1.3.8-2.3 1.7-2.3.8 0 1.2.6 1.2 1.3 0 .8-.5 2-.8 3.2-.2 1 .5 1.7 1.4 1.7 1.7 0 2.9-2.2 2.9-4.7 0-2-1.3-3.4-3.7-3.4a4.2 4.2 0 0 0-4.4 4.2c0 .8.2 1.4.6 1.8.2.2.2.3.1.5l-.2.8c-.1.3-.3.4-.6.2-1.1-.4-1.6-1.7-1.6-3.1 0-2.3 2-5.1 5.8-5.1 3.1 0 5.1 2.2 5.1 4.6 0 3.1-1.7 5.5-4.3 5.5-.9 0-1.7-.5-2-1l-.5 2.1c-.2.7-.6 1.5-.9 2A9 9 0 1 0 12 3Z',
                    'social_youtube'   => 'M21.6 8.2a2.5 2.5 0 0 0-1.7-1.8C18.2 6 12 6 12 6s-6.2 0-7.9.4A2.5 2.5 0 0 0 2.4 8.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 3.8 2.5 2.5 0 0 0 1.7 1.8C5.8 18 12 18 12 18s6.2 0 7.9-.4a2.5 2.5 0 0 0 1.7-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-3.8ZM10 15V9l5 3-5 3Z',
                ];
                foreach ($socials as $key => $path):
                    if (! setting($key)) continue; ?>
                    <a href="<?= esc(setting($key), 'attr') ?>" target="_blank" rel="noopener" class="w-9 h-9 rounded-full bg-white/10 hover:bg-wood inline-flex items-center justify-center transition-colors" aria-label="<?= esc(ucfirst(str_replace('social_', '', $key))) ?>">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="<?= $path ?>"/></svg>
                    </a>
                <?php endforeach ?>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider text-bg/90 mb-4">Shop</h3>
            <ul class="space-y-2.5 text-sm">
                <?php foreach (array_slice($cats, 0, 6) as $c): ?>
                    <li><a href="<?= base_url('category/' . $c['slug']) ?>" class="text-bg/60 hover:text-bg transition-colors"><?= esc($c['name']) ?></a></li>
                <?php endforeach ?>
                <li><a href="<?= base_url('shop') ?>" class="text-bg/60 hover:text-bg transition-colors">All Swings</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider text-bg/90 mb-4">Company</h3>
            <ul class="space-y-2.5 text-sm">
                <li><a href="<?= base_url('about') ?>" class="text-bg/60 hover:text-bg transition-colors">Our Story</a></li>
                <li><a href="<?= base_url('craftsmanship') ?>" class="text-bg/60 hover:text-bg transition-colors">The Craft</a></li>
                <li><a href="<?= base_url('custom-swings') ?>" class="text-bg/60 hover:text-bg transition-colors">Custom Swings</a></li>
                <li><a href="<?= base_url('journal') ?>" class="text-bg/60 hover:text-bg transition-colors">Journal</a></li>
                <li><a href="<?= base_url('faq') ?>" class="text-bg/60 hover:text-bg transition-colors">FAQ</a></li>
                <li><a href="<?= base_url('contact') ?>" class="text-bg/60 hover:text-bg transition-colors">Contact</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wider text-bg/90 mb-4">Visit the Workshop</h3>
            <address class="not-italic text-sm text-bg/60 space-y-2">
                <p><?= nl2br(esc(setting('store_address'))) ?></p>
                <p><a href="mailto:<?= esc(setting('store_email')) ?>" class="hover:text-bg transition-colors"><?= esc(setting('store_email')) ?></a></p>
                <p><a href="tel:<?= esc(preg_replace('/\s+/', '', setting('store_phone'))) ?>" class="hover:text-bg transition-colors"><?= esc(setting('store_phone')) ?></a></p>
            </address>
            <a href="<?= esc(whatsapp_link(), 'attr') ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 mt-4 text-sm text-wood-light hover:text-bg transition-colors">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1 0 12 2Z"/></svg>
                Chat on WhatsApp
            </a>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-page py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-bg/50">
            <p>&copy; <?= date('Y') ?> <?= esc(store_name()) ?>. Handcrafted in India.</p>
            <div class="flex items-center gap-5">
                <span>Solid wood · Made to order</span>
                <span class="inline-flex items-center gap-2">Secure payments
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                </span>
            </div>
        </div>
    </div>
</footer>
