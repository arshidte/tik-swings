<?php

namespace App\Database\Seeds\Concerns;

/**
 * Shared variant builder for the SW-01/02/05 swing products.
 *
 * Generates the full Wood Finish × Size × Polish matrix (5 × 3 × 3 = 45
 * variants) and prices each one straight from the official pricelist, where
 * price depends only on size × polish — wood finish is cosmetic. Prices are not
 * additive across size/polish, so they are stored as explicit per-variant
 * prices rather than option adjustments.
 */
trait BuildsSwingVariants
{
    /** Size value label => SKU abbreviation. */
    private array $sizeAbbr = ['3 Feet' => '3F', '4 Feet' => '4F', '5 Feet' => '5F'];

    /** Polish value label => SKU abbreviation. */
    private array $polishAbbr = ['Standard Polish' => 'SP', 'Premium Polish' => 'PP', 'Luxury Polish' => 'LP'];

    /**
     * Insert the full Wood × Size × Polish variant set for a product. Default
     * variant is <$defaultWood> / 3 Feet / Standard Polish (the cheapest).
     *
     * @param array<string,int>                $woodValues   wood label   => attribute_value_id
     * @param array<string,int>                $sizeValues   size label   => attribute_value_id
     * @param array<string,int>                $polishValues polish label => attribute_value_id
     * @param array<string,array<string,int>>  $priceMatrix  [size label][polish label] => price
     *
     * @return int number of variants created
     */
    private function buildSwingVariants(
        $db,
        int $pid,
        string $sku,
        string $namePrefix,
        string $image,
        int $stock,
        string $now,
        int $woodAttrId,
        int $sizeAttrId,
        int $polishAttrId,
        array $woodValues,
        array $sizeValues,
        array $polishValues,
        array $priceMatrix,
        string $defaultWood
    ): int {
        $abbr  = static fn (string $s): string => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $s), 0, 2));
        $count = 0;

        foreach ($woodValues as $wood => $wid) {
            foreach ($sizeValues as $size => $sid) {
                foreach ($polishValues as $polish => $pvid) {
                    $isDefault = ($wood === $defaultWood && $size === '3 Feet' && $polish === 'Standard Polish') ? 1 : 0;

                    $db->table('product_variants')->insert([
                        'product_id'    => $pid,
                        'sku'           => "{$sku}-" . $abbr($wood) . '-' . $this->sizeAbbr[$size] . '-' . $this->polishAbbr[$polish],
                        'name'          => "{$namePrefix} — {$wood} / {$size} / {$polish}",
                        'price'         => (float) $priceMatrix[$size][$polish],
                        'compare_price' => null,
                        'stock'         => $stock,
                        'image'         => $image,
                        'is_default'    => $isDefault,
                        'status'        => 'active',
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                    $vid = (int) $db->insertID();

                    foreach ([$woodAttrId => $wid, $sizeAttrId => $sid, $polishAttrId => $pvid] as $aid => $avid) {
                        $db->table('product_variant_attributes')->insert([
                            'variant_id' => $vid, 'attribute_id' => $aid, 'attribute_value_id' => $avid,
                        ]);
                    }
                    $count++;
                }
            }
        }

        return $count;
    }
}
