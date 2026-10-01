<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="relative bg-ink text-bg py-20 sm:py-28 text-center">
    <div class="relative container-page">
        <h1 class="text-display font-display max-w-3xl mx-auto reveal">Return &amp; Refunds</h1>
        <p class="mt-5 text-lg text-bg/80 max-w-2xl mx-auto reveal">Carefully read our return and refund policy to understand our process.</p>
    </div>
</section>

<article class="section">
    <div class="container-page max-w-prose">
        <div class="prose-content text-lg leading-relaxed text-ink/90 space-y-6">
            <h3 class="text-2xl font-display text-ink mt-8 mb-4 reveal">How do I Cancel an item purchased?</h3>
            <ul class="list-disc pl-5 space-y-3 reveal">
                <li><strong>Cancellation in Case of Damage:</strong> In case if you receive a damaged or defective product, bring it to the notice of delivery personnel immediately at the time of delivery and report a complaint by raising a ticket at <a href="<?= base_url('help-center') ?>" class="text-wood hover:underline">help center</a>. <br/>Damage &amp; defect will be assessed in 3 days, and a solution will be provided.</li>
                <li><strong>Cancellation in case of Wrong Product:</strong> If the product does not comply with the specifications as per your original order, raise the issue immediately &amp; report it by raising a ticket at the <a href="<?= base_url('help-center') ?>" class="text-wood hover:underline">help center</a>.</li>
                <li><strong>Cancellation in Case of Change in Requirement/Mind</strong>
                <p class="mt-2">You can cancel your order for any product within 24 hours of placing it, and a refund will be initiated. However, cancellation can only be done if the product is not shipped or in the production or pre-production stage. Post the 24-hour window, no cancellation will be entertained.</p>
                <p>Cancellations are only permitted within 24 hours of purchase. Post the 24-hour window, cancellations will be chargeable.</p>
                </li>
                <li>You must address or submit the damage/defect concern within 3 days. Any queries for damage/defect will not be entertained once the timeline is breached.</li>
                <li>For fragile products, customers are requested to share an unboxing video in case of damage/defect.</li>
            </ul>

            <h3 class="text-2xl font-display text-ink mt-10 mb-4 reveal">Return &amp; Replacement Policy:</h3>
            <ol class="list-decimal pl-5 space-y-3 reveal">
                <li><strong>Eligibility for Return or Replacement</strong><br/>Returns or replacements can be requested only if the product received is damaged, defective, or different from what was ordered. Any such concern must be reported within 3 days of delivery, along with relevant images or videos of the issue. The request for return or replacement must be initiated within 7 (seven) days from the date of delivery (Return Window). Products that have been used, modified, assembled, or damaged after delivery will not be eligible under this Policy.</li>
                <li><strong>Condition of Product for Return</strong><br/>The product must be kept in its original, unused condition, complete with all accompanying accessories, manuals, price tags, and original packaging.</li>
                <li><strong>Resolution Process &amp; Refund Policy</strong><br/>Upon successful verification, we will, at our sole discretion, repair or replace the affected part or the entire product, depending on the nature of the issue and product availability. Refunds are not applicable under this policy. Please note that every accepted return is addressed through a resolution &mdash; ensuring your furniture is restored or replaced to meet the quality standards you expect.</li>
            </ol>

            <p class="mt-4 reveal">If you are eligible for any refund, the same shall be given to you as per the following guidelines:</p>
            <ul class="list-disc pl-5 space-y-3 reveal">
                <li>All refunds &amp; replacement process initiation shall be subject to pick up of all cancelled items from your/customer's premises.</li>
                <li>Post receiving the products back to the warehouse, a refund shall be initiated within 2-3 days.</li>
                <li>Refund will be initiated via NEFT, cash or by the way payment was originally made.</li>
                <li>All returns and refunds shall be processed free of charge, with no deductions applicable.</li>
            </ul>

            <h3 class="text-2xl font-display text-ink mt-10 mb-4 reveal">Warranties:</h3>
            <p class="reveal">Our products come with 36 months* warranty period, which covers manufacturing/workmanship defects issues that occur during the warranty period. The warranty applies to furniture used under normal household conditions.</p>
            <ul class="list-disc pl-5 space-y-3 mt-4 reveal">
                <li>Normal wear and tear of the product over prolonged use is not covered.</li>
                <li>Small cuts, scratches or damages due to wrong cleaning methods or impacts/accidents are not covered.</li>
                <li>Damage caused due to incorrect installation/assembly by the customer is not covered.</li>
                <li>In response to seasonal climate variations, solid wood will contract and expand throughout the life of the product, and it does not cover under the warranty section (for solid wood furniture).</li>
            </ul>

            <h3 class="text-2xl font-display text-ink mt-10 mb-4 reveal">Delivery:</h3>
            <p class="reveal">Our support and delivery team will be in coordination with you for a hassle-free installation process.</p>
            <p class="reveal mt-3">Free delivery is only applicable for the very first attempt on a visit to your ship-to address. In case of a missed delivery, an extra visiting charge would be applicable for later installation.</p>
            <p class="reveal mt-3">Once the order has reached your nearest delivery center and you fail to receive the products, we would hold the products for 10 days, after this time line, we shall be free to charge for holding the products for longer.</p>
        </div>

        <div class="mt-12 flex flex-wrap gap-3 reveal">
            <a href="<?= base_url('shop') ?>" class="btn-primary">Explore the Collection</a>
            <a href="<?= base_url('contact') ?>" class="btn-outline">Contact Us</a>
        </div>
    </div>
</article>
<?= $this->endSection() ?>
