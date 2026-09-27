<?php

/**
 * Formatting helpers — money, discounts, ratings, image paths.
 * Keeps presentation logic out of views and controllers (§60).
 */

if (! function_exists('price')) {
    /**
     * Format an amount as Indian Rupees with thousands grouping.
     */
    function price($amount, bool $withSymbol = true): string
    {
        $amount   = (float) $amount;
        $rounded  = round($amount);
        $isWhole  = abs($amount - $rounded) < 0.005;
        $number   = $isWhole
            ? number_format($rounded, 0)
            : number_format($amount, 2);

        // Indian digit grouping (lakh/crore) for whole rupee amounts.
        if ($isWhole) {
            $number = format_indian_number($rounded);
        }

        return $withSymbol ? '₹' . $number : $number;
    }
}

if (! function_exists('format_indian_number')) {
    function format_indian_number($number): string
    {
        $number = (string) (int) $number;
        $neg    = str_starts_with($number, '-');
        $number = ltrim($number, '-');

        if (strlen($number) <= 3) {
            return ($neg ? '-' : '') . $number;
        }
        $last3 = substr($number, -3);
        $rest  = substr($number, 0, -3);
        $rest  = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);

        return ($neg ? '-' : '') . $rest . ',' . $last3;
    }
}

if (! function_exists('discount_percent')) {
    function discount_percent($price, $comparePrice): int
    {
        $price        = (float) $price;
        $comparePrice = (float) $comparePrice;
        if ($comparePrice <= 0 || $comparePrice <= $price) {
            return 0;
        }

        return (int) round((($comparePrice - $price) / $comparePrice) * 100);
    }
}

if (! function_exists('product_image')) {
    /**
     * Resolve a stored image path to a public URL. Centralised so real
     * images can replace placeholders without touching templates (§52).
     */
    function product_image(?string $path, string $fallback = 'placeholder-product.svg'): string
    {
        if (! $path) {
            return base_url('assets/images/' . $fallback);
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, 'assets/') || str_starts_with($path, 'uploads/')) {
            return base_url($path);
        }

        return base_url('assets/images/' . ltrim($path, '/'));
    }
}

if (! function_exists('star_row')) {
    /**
     * Render accessible star rating markup (filled / half / empty).
     */
    function star_row(float $rating, string $size = 'w-4 h-4'): string
    {
        $rating = max(0, min(5, $rating));
        $full   = (int) floor($rating);
        $half   = ($rating - $full) >= 0.5;
        $html   = '<span class="inline-flex items-center gap-0.5" role="img" aria-label="'
            . esc(number_format($rating, 1)) . ' out of 5 stars">';

        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $full) {
                $cls = 'text-wood';
                $fill = 'currentColor';
            } elseif ($i === $full + 1 && $half) {
                $cls = 'text-wood';
                $fill = 'url(#half)';
            } else {
                $cls = 'text-line';
                $fill = 'currentColor';
            }
            $html .= '<svg class="' . $size . ' ' . $cls . '" viewBox="0 0 20 20" fill="' . $fill . '" aria-hidden="true">'
                . '<defs><linearGradient id="half"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="rgb(var(--color-line))"/></linearGradient></defs>'
                . '<path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 15.9 4.8 17.6l1-5.8L1.5 7.7l5.9-.9z"/></svg>';
        }

        return $html . '</span>';
    }
}

if (! function_exists('order_status_label')) {
    function order_status_label(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}

if (! function_exists('order_status_class')) {
    /**
     * Tailwind classes for an order-status pill.
     */
    function order_status_class(string $status): string
    {
        return match ($status) {
            'delivered', 'shipped', 'out_for_delivery' => 'bg-success/10 text-success',
            'cancelled', 'refunded'                    => 'bg-danger/10 text-danger',
            default                                    => 'bg-sand text-wood-dark',
        };
    }
}

if (! function_exists('truncate_words')) {
    function truncate_words(?string $text, int $words = 24): string
    {
        $text = trim(strip_tags((string) $text));
        if ($text === '') {
            return '';
        }
        $parts = preg_split('/\s+/', $text);
        if (count($parts) <= $words) {
            return $text;
        }

        return implode(' ', array_slice($parts, 0, $words)) . '…';
    }
}
