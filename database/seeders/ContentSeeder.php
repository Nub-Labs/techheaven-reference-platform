<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('  → Seeding CMS pages...');

        $this->fixThemeCustomizations();

        $channelId = DB::table('channels')->value('id') ?? 1;

        foreach ($this->getPages() as $page) {
            // url_key is in cms_page_translations in Bagisto 2.4.x
            $existing = DB::table('cms_page_translations')->where('url_key', $page['url_key'])->first();

            if ($existing) {
                // Bagisto installer pre-creates some pages with placeholder content — overwrite
                DB::table('cms_page_translations')->where('url_key', $page['url_key'])->update([
                    'page_title'       => $page['title'],
                    'html_content'     => $page['content'],
                    'meta_title'       => $page['title'] . ' — TechHeaven',
                    'meta_description' => $page['meta_description'],
                    'meta_keywords'    => $page['meta_keywords'],
                ]);
                // Ensure channel association exists (installer-created pages may be missing it)
                DB::table('cms_page_channels')
                    ->insertOrIgnore(['cms_page_id' => $existing->cms_page_id, 'channel_id' => $channelId]);
                continue;
            }

            $pageId = DB::table('cms_pages')->insertGetId([
                'layout'     => 'page.blade.php',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('cms_page_translations')->insert([
                'locale'           => 'en',
                'url_key'          => $page['url_key'],
                'page_title'       => $page['title'],
                'html_content'     => $page['content'],
                'meta_title'       => $page['title'] . ' — TechHeaven',
                'meta_description' => $page['meta_description'],
                'meta_keywords'    => $page['meta_keywords'],
                'cms_page_id'      => $pageId,
            ]);

            // Bagisto requires pages to be associated with a channel to be accessible
            DB::table('cms_page_channels')->insert([
                'cms_page_id' => $pageId,
                'channel_id'  => $channelId,
            ]);
        }

        $this->command->info('     ✓ CMS pages seeded');
    }

    private function fixThemeCustomizations(): void
    {
        // Bagisto installer seeds fashion/clothing demo content. Replace with tech-focused content.
        // id=1 image_carousel, id=2 Offer Information, id=3 Top Collections, id=4 Bold Collections
        // id=5 Game Container (fashion), id=6 Bold Collections (fashion)

        DB::table('theme_customizations')->whereIn('id', [1, 5, 6])->update(['status' => 0]);

        DB::table('theme_customization_translations')
            ->where('theme_customization_id', 2)
            ->update(['options' => json_encode([
                'css'  => '.home-offer h1{display:block;font-weight:500;text-align:center;font-size:22px;background-color:#0F172A;color:#F8FAFC;padding:14px 20px;}.home-offer span{color:#60A5FA;}',
                'html' => '<div class="home-offer"><h1>Free shipping on orders over <span>$99</span> — Use code <span>FREESHIP</span> at checkout</h1></div>',
            ])]);

        DB::table('theme_customization_translations')
            ->where('theme_customization_id', 3)
            ->update(['options' => json_encode([
                'css'  => '.tc-container{overflow:hidden;margin-top:60px}.tc-header{text-align:center;font-size:42px;color:#0F172A;margin-bottom:40px;font-family:system-ui,-apple-system,sans-serif;font-weight:700}.tc-grid{display:flex;flex-wrap:wrap;gap:20px;justify-content:center;padding:0 60px}.tc-card{background:#F1F5F9;border-radius:16px;padding:32px 24px;text-align:center;min-width:160px;cursor:pointer;transition:transform 0.2s}.tc-card:hover{transform:translateY(-4px)}.tc-card h3{color:#1E3A5F;font-size:16px;font-weight:600;margin:12px 0 0;font-family:system-ui,sans-serif}.tc-icon{font-size:48px}',
                'html' => '<div class="tc-container"><div class="container"><h2 class="tc-header">Shop by Category</h2><div class="tc-grid"><div class="tc-card"><a href="/laptops"><div class="tc-icon">💻</div><h3>Laptops</h3></a></div><div class="tc-card"><a href="/monitors"><div class="tc-icon">🖥️</div><h3>Monitors</h3></a></div><div class="tc-card"><a href="/gaming-mice"><div class="tc-icon">🎮</div><h3>Gaming</h3></a></div><div class="tc-card"><a href="/headphones"><div class="tc-icon">🎧</div><h3>Audio</h3></a></div><div class="tc-card"><a href="/nvme-ssds"><div class="tc-icon">💾</div><h3>Drives</h3></a></div><div class="tc-card"><a href="/graphics-cards"><div class="tc-icon">🔲</div><h3>Components</h3></a></div><div class="tc-card"><a href="/smart-home"><div class="tc-icon">🏠</div><h3>Smart Home</h3></a></div><div class="tc-card"><a href="/wearables"><div class="tc-icon">⌚</div><h3>Wearables</h3></a></div></div></div></div>',
            ])]);

        DB::table('theme_customization_translations')
            ->where('theme_customization_id', 4)
            ->update(['options' => json_encode([
                'css'  => '.th-promo{margin:60px auto;max-width:900px;padding:48px 40px;background:linear-gradient(135deg,#0F172A 0%,#1E3A5F 100%);border-radius:24px;display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center}.th-promo-text h2{color:#F8FAFC;font-size:36px;font-weight:700;font-family:system-ui,sans-serif;margin:0 0 16px}.th-promo-text p{color:#94A3B8;font-size:16px;margin:0 0 24px}.th-promo-cta{display:inline-block;background:#3B82F6;color:#fff;padding:14px 32px;border-radius:10px;font-weight:600;text-decoration:none}.th-promo-stats{display:grid;gap:16px}.th-stat{background:rgba(255,255,255,.08);border-radius:12px;padding:20px 24px}.th-stat h3{color:#60A5FA;font-size:28px;font-weight:700;margin:0 0 4px}.th-stat p{color:#CBD5E1;margin:0;font-size:14px}@media(max-width:768px){.th-promo{grid-template-columns:1fr;padding:32px 24px}}',
                'html' => '<div class="th-promo container"><div class="th-promo-text"><h2>The TechHeaven Promise</h2><p>291 premium products. Expert-curated selection of gaming gear, pro laptops, audio equipment, and smart home devices — all in one place.</p><a href="/laptops" class="th-promo-cta">Shop Now</a></div><div class="th-promo-stats"><div class="th-stat"><h3>291</h3><p>Premium Products</p></div><div class="th-stat"><h3>15</h3><p>Categories</p></div><div class="th-stat"><h3>5★</h3><p>Customer Rated</p></div></div></div>',
            ])]);

        // id=7: footer_links — point to our seeded CMS pages (Bagisto default has placeholder URLs)
        // sort_order is required by the footer blade template (usort on each column array)
        DB::table('theme_customization_translations')
            ->where('theme_customization_id', 7)
            ->update(['options' => json_encode([
                'column_1' => [
                    ['url' => 'http://localhost/page/about-us',        'title' => 'About TechHeaven',  'sort_order' => 0],
                    ['url' => 'http://localhost/page/contact-us',       'title' => 'Contact Us',         'sort_order' => 1],
                    ['url' => 'http://localhost/page/customer-service', 'title' => 'Customer Service',   'sort_order' => 2],
                    ['url' => 'http://localhost/page/whats-new',        'title' => "What's New",         'sort_order' => 3],
                    ['url' => 'http://localhost/page/loyalty-program',  'title' => 'TechHeaven Rewards', 'sort_order' => 4],
                ],
                'column_2' => [
                    ['url' => 'http://localhost/page/privacy-policy',   'title' => 'Privacy Policy',        'sort_order' => 0],
                    ['url' => 'http://localhost/page/terms-conditions',  'title' => 'Terms & Conditions',    'sort_order' => 1],
                    ['url' => 'http://localhost/page/shipping-policy',   'title' => 'Shipping Policy',       'sort_order' => 2],
                    ['url' => 'http://localhost/page/return-policy',     'title' => 'Return & Refund Policy','sort_order' => 3],
                    ['url' => 'http://localhost/page/payment-policy',    'title' => 'Payment Policy',        'sort_order' => 4],
                ],
            ])]);
    }

    private function getPages(): array
    {
        return [
            [
                'url_key'          => 'shipping-policy',
                'title'            => 'Shipping Policy',
                'meta_description' => 'TechHeaven shipping policy — delivery times, carriers, free shipping threshold and international shipping.',
                'meta_keywords'    => 'shipping, delivery, TechHeaven shipping policy, free shipping',
                'content'          => '<h1>Shipping Policy</h1>
<p>At TechHeaven, we work hard to get your order to you as quickly as possible. Here\'s everything you need to know about how we ship.</p>
<h2>Free Standard Shipping</h2>
<p>We offer <strong>free standard shipping</strong> on all orders over <strong>$49</strong> within the contiguous United States. Orders under $49 incur a flat $5.99 shipping fee.</p>
<h2>Shipping Methods &amp; Estimated Delivery Times</h2>
<table><thead><tr><th>Method</th><th>Estimated Delivery</th><th>Cost</th></tr></thead><tbody>
<tr><td>Standard (UPS / USPS)</td><td>5–7 business days</td><td>Free over $49 / $5.99</td></tr>
<tr><td>Expedited (UPS 2-Day)</td><td>2 business days</td><td>$12.99</td></tr>
<tr><td>Overnight (UPS Next Day)</td><td>1 business day</td><td>$24.99</td></tr>
</tbody></table>
<h2>Order Processing</h2>
<p>Orders placed before <strong>2:00 PM EST</strong> on business days (Monday–Friday) are processed the same day. Orders placed after 2:00 PM, or on weekends and holidays, are processed the next business day. You will receive a shipping confirmation email with a tracking number once your order ships.</p>
<h2>Carrier Information</h2>
<p>We ship via UPS and USPS for domestic orders. The carrier is selected based on package weight, destination, and selected shipping speed. For large items (monitors, printers), UPS Ground is used exclusively.</p>
<h2>Tracking Your Order</h2>
<p>Once your order ships, you\'ll receive a shipping confirmation email with a tracking number. You can track your shipment directly on the carrier\'s website or in your TechHeaven account under <strong>My Orders</strong>.</p>
<h2>International Shipping</h2>
<p>We currently ship to Canada, United Kingdom, Australia, and Germany. International shipping rates and estimated delivery times are calculated at checkout based on destination and package weight.</p>
<table><thead><tr><th>Country</th><th>Estimated Delivery</th><th>Starting From</th></tr></thead><tbody>
<tr><td>Canada</td><td>7–12 business days</td><td>$14.99</td></tr>
<tr><td>United Kingdom</td><td>10–15 business days</td><td>$19.99</td></tr>
<tr><td>Australia</td><td>12–18 business days</td><td>$24.99</td></tr>
<tr><td>Germany</td><td>10–15 business days</td><td>$19.99</td></tr>
</tbody></table>
<p>Import duties, taxes, and customs fees are the responsibility of the recipient and are not included in the checkout total.</p>
<h2>Damaged or Lost Packages</h2>
<p>If your package arrives damaged or is lost in transit, contact us at support@techheaven.com within 48 hours of the expected delivery date. We will file a claim with the carrier and send a replacement or issue a full refund at your preference.</p>',
            ],
            [
                'url_key'          => 'return-policy',
                'title'            => 'Return & Refund Policy',
                'meta_description' => 'TechHeaven 30-day return policy — easy returns, exchanges and full refund process explained.',
                'meta_keywords'    => 'return policy, refund, exchange, TechHeaven returns',
                'content'          => '<h1>Return &amp; Refund Policy</h1>
<p>We want you to love your TechHeaven purchase. If something isn\'t right, we make it easy to return or exchange within <strong>30 days of delivery</strong>.</p>
<h2>What Can Be Returned</h2>
<ul>
<li>Most items in their original, unopened packaging</li>
<li>Opened items that are defective or damaged on arrival</li>
<li>Items that do not match the product description on our website</li>
</ul>
<h2>What Cannot Be Returned</h2>
<ul>
<li>Software, digital downloads, or items with activated licence codes</li>
<li>Items damaged through misuse, accident, or unauthorised modification</li>
<li>Consumables (ink cartridges, printer paper) once opened</li>
<li>Custom or special-order items marked as non-returnable at time of purchase</li>
</ul>
<h2>How to Start a Return</h2>
<ol>
<li>Log into your TechHeaven account and go to <strong>My Orders</strong></li>
<li>Select the order and click <strong>Request Return</strong></li>
<li>Choose your reason and preferred resolution (refund or exchange)</li>
<li>Print the prepaid return label we email you within 1 business day</li>
<li>Drop the package at any authorised UPS location within 7 days of receiving the label</li>
</ol>
<h2>Refund Processing</h2>
<p>Refunds are processed within <strong>3–5 business days</strong> of receiving your returned item at our warehouse. The refund will be issued to your original payment method. Shipping costs are refunded only if the return is due to our error or a defective product.</p>
<h2>Exchanges</h2>
<p>To exchange for a different model or colour, start a return and place a new order for the replacement item. If the replacement is the same price or lower, the price difference will be refunded. For a higher-priced replacement, you will be charged the difference.</p>
<h2>Large Item Returns (Monitors &amp; Printers)</h2>
<p>For items over 30 lbs or with special packaging requirements, contact us before initiating a return. We will arrange a UPS scheduled pickup at no charge for defective items. For change-of-mind returns on large items, a $15.00 return shipping fee applies.</p>
<h2>International Returns</h2>
<p>International customers are responsible for return shipping costs. We recommend using a tracked service as we cannot process refunds for items lost in return transit. Import duties paid are non-refundable.</p>',
            ],
            [
                'url_key'          => 'warranty-policy',
                'title'            => 'Warranty Policy',
                'meta_description' => 'TechHeaven warranty policy — manufacturer warranties, extended coverage and how to file a claim.',
                'meta_keywords'    => 'warranty, product warranty, TechHeaven warranty policy, extended warranty',
                'content'          => '<h1>Warranty Policy</h1>
<p>All products sold by TechHeaven come with the manufacturer\'s standard warranty. We also offer optional extended warranty plans through our TechHeaven Protection programme.</p>
<h2>Manufacturer Warranties</h2>
<p>Every product includes the manufacturer\'s original warranty. Typical durations:</p>
<table><thead><tr><th>Category</th><th>Typical Warranty</th></tr></thead><tbody>
<tr><td>Laptops &amp; Computers</td><td>1 year limited</td></tr>
<tr><td>Monitors</td><td>3 years (IPS panel warranty on premium models)</td></tr>
<tr><td>Storage (SSDs, HDDs)</td><td>3–5 years</td></tr>
<tr><td>Memory (RAM)</td><td>Lifetime limited</td></tr>
<tr><td>Headphones &amp; Audio</td><td>1–2 years</td></tr>
<tr><td>Printers</td><td>1 year</td></tr>
</tbody></table>
<h2>TechHeaven Protection Plans</h2>
<p>Extend your coverage with a TechHeaven Protection Plan, available for most products above $99:</p>
<ul>
<li><strong>2-Year Protection:</strong> Extends manufacturer warranty by 1 additional year — 9.9% of purchase price</li>
<li><strong>3-Year Protection:</strong> Extends coverage to 3 total years including accidental damage — 14.9% of purchase price</li>
</ul>
<h2>Filing a Warranty Claim</h2>
<p>To file a warranty claim, contact our support team via live chat or email with your order number and a description of the issue. We\'ll coordinate directly with the manufacturer on your behalf to minimise hassle.</p>',
            ],
            [
                'url_key'          => 'privacy-policy',
                'title'            => 'Privacy Policy',
                'meta_description' => 'TechHeaven Privacy Policy — how we collect, use and protect your personal data.',
                'meta_keywords'    => 'privacy policy, data protection, TechHeaven privacy',
                'content'          => '<h1>Privacy Policy</h1>
<p>Last updated: January 1, 2025</p>
<p>TechHeaven ("we", "us", "our") is committed to protecting your personal data. This Privacy Policy explains what information we collect, how we use it, and your rights regarding that information.</p>
<h2>Information We Collect</h2>
<ul>
<li><strong>Account Information:</strong> Name, email address, phone number, billing and shipping address</li>
<li><strong>Order Information:</strong> Products purchased, payment method (we never store full card numbers), order history</li>
<li><strong>Usage Data:</strong> Pages visited, product searches, time on site, device and browser information</li>
<li><strong>Communications:</strong> Customer service emails and chat transcripts</li>
</ul>
<h2>How We Use Your Information</h2>
<ul>
<li>Process and fulfil orders</li>
<li>Send order confirmations and shipping notifications</li>
<li>Provide customer support</li>
<li>Improve our website and product catalogue</li>
<li>Send marketing emails (only with your consent, and you can unsubscribe anytime)</li>
</ul>
<h2>Data Sharing</h2>
<p>We do not sell your personal data. We share information only with service providers necessary to operate our business (shipping carriers, payment processors, customer support tools) and only as required to fulfil your orders.</p>
<h2>Cookies</h2>
<p>We use cookies to keep you signed in, remember your cart, and understand how visitors use our site. You can control cookies through your browser settings. Disabling cookies may affect checkout functionality.</p>
<h2>Data Retention</h2>
<p>We retain your account data for as long as your account is active. Order records are retained for 7 years for tax and legal compliance. You may request deletion of non-legally-required data at any time.</p>
<h2>Your Rights</h2>
<p>You have the right to access, correct, or delete your personal data at any time. Contact us at <a href="mailto:privacy@techheaven.com">privacy@techheaven.com</a> to exercise these rights. EU/UK residents also have the right to data portability and the right to lodge a complaint with their supervisory authority.</p>
<h2>Changes to This Policy</h2>
<p>We may update this Privacy Policy from time to time. We will notify you of significant changes by email or by posting a prominent notice on our website.</p>',
            ],
            [
                'url_key'          => 'terms-conditions',
                'title'            => 'Terms & Conditions',
                'meta_description' => 'TechHeaven Terms and Conditions — rules governing use of the TechHeaven website and purchasing.',
                'meta_keywords'    => 'terms and conditions, legal, TechHeaven terms',
                'content'          => '<h1>Terms &amp; Conditions</h1>
<p>Last updated: January 1, 2025</p>
<p>By using TechHeaven ("the Site") or placing an order, you agree to these Terms &amp; Conditions. Please read them carefully.</p>
<h2>Account Registration</h2>
<p>You must provide accurate information when creating an account. You are responsible for maintaining the confidentiality of your password and for all activity under your account. Notify us immediately at support@techheaven.com if you suspect unauthorised access to your account.</p>
<h2>Pricing &amp; Availability</h2>
<p>All prices are displayed in USD and are subject to change without notice. TechHeaven reserves the right to cancel orders where a pricing error has occurred. We will notify you promptly and offer a full refund if your order is cancelled for this reason. Product availability is subject to stock levels and is confirmed at the time of order processing.</p>
<h2>Payment</h2>
<p>We accept Visa, Mastercard, American Express, Discover, PayPal, Apple Pay, Google Pay, and TechHeaven Gift Cards. Payment is collected at the time of order placement. See our <a href="/page/payment-policy">Payment Policy</a> for full details.</p>
<h2>Order Confirmation</h2>
<p>You will receive an email confirming your order within minutes of placing it. This confirmation is not a guarantee of availability. In the rare event that a product becomes unavailable after you order, we will contact you within 24 hours to offer an alternative or full refund.</p>
<h2>Shipping</h2>
<p>Orders are shipped as described in our <a href="/page/shipping-policy">Shipping Policy</a>. Risk of loss and title for products pass to you upon delivery to the carrier.</p>
<h2>Returns</h2>
<p>Returns are governed by our <a href="/page/return-policy">Return &amp; Refund Policy</a>. Most items may be returned within 30 days of delivery in original condition.</p>
<h2>Limitation of Liability</h2>
<p>TechHeaven\'s total liability for any claim arising from the use of this site or from any product purchased shall not exceed the purchase price of the applicable product. We are not liable for indirect, incidental, or consequential damages.</p>
<h2>Governing Law</h2>
<p>These Terms are governed by the laws of the State of Delaware, United States, without regard to its conflict of law provisions. Any disputes shall be resolved in the courts of Delaware.</p>
<h2>Changes to Terms</h2>
<p>We reserve the right to modify these Terms at any time. Changes become effective immediately upon posting. Your continued use of the site constitutes acceptance of the updated Terms.</p>',
            ],
            [
                'url_key'          => 'faq',
                'title'            => 'Frequently Asked Questions',
                'meta_description' => 'TechHeaven FAQ — shipping, returns, warranty, payment, and account questions answered.',
                'meta_keywords'    => 'FAQ, frequently asked questions, TechHeaven help',
                'content'          => '<h1>Frequently Asked Questions</h1>
<h2>Orders &amp; Shipping</h2>
<h3>When will my order ship?</h3>
<p>Orders placed before 2:00 PM EST on business days typically ship the same day. You\'ll receive a tracking number by email as soon as your order ships.</p>
<h3>Do you offer free shipping?</h3>
<p>Yes — free standard shipping on all orders over $49 within the contiguous US.</p>
<h3>Can I change or cancel my order after placing it?</h3>
<p>Orders can be modified or cancelled within 1 hour of placement. After that, the order has usually entered processing. Contact support immediately via live chat for urgent changes.</p>
<h2>Returns &amp; Refunds</h2>
<h3>How long do I have to return an item?</h3>
<p>30 days from the date of delivery for most items in original condition. See our full Return Policy for exclusions.</p>
<h3>Who pays for return shipping?</h3>
<p>TechHeaven provides a prepaid return label for defective or incorrectly sent items. For change-of-mind returns, a $6.99 return shipping fee is deducted from your refund.</p>
<h2>Products &amp; Compatibility</h2>
<h3>How do I know which RAM is compatible with my laptop?</h3>
<p>Use our Compatibility Tool (available on each RAM product page) and enter your laptop model number. Alternatively, contact our tech support team via live chat and we\'ll confirm compatibility before you order.</p>
<h3>Do products come with US plugs?</h3>
<p>All products ship with US plugs. International customers may need a plug adapter depending on their country.</p>
<h2>Account &amp; Payment</h2>
<h3>How do I track my order?</h3>
<p>Log into your account, go to Orders, and click the tracking number in your order details. You can also use the tracking link in your shipping confirmation email.</p>',
            ],
            [
                'url_key'          => 'about-us',
                'title'            => 'About TechHeaven',
                'meta_description' => 'About TechHeaven — who we are, our mission, and why we\'re the best consumer electronics retailer.',
                'meta_keywords'    => 'about TechHeaven, consumer electronics, tech retailer',
                'content'          => '<h1>About TechHeaven</h1>
<p>TechHeaven is a consumer electronics retailer specialising in laptops, monitors, peripherals, components, and accessories from the world\'s leading technology brands.</p>
<h2>Our Mission</h2>
<p>We believe everyone deserves access to great technology at fair prices, with expert guidance to help them choose the right product for their needs. Whether you\'re a student buying your first laptop, a professional building a creative workstation, or a gamer upgrading to the latest GPU, TechHeaven has the expertise and product range to help.</p>
<h2>Why Shop at TechHeaven?</h2>
<ul>
<li><strong>Expert Product Selection:</strong> Every product in our catalogue is curated by our team of hardware specialists — we don\'t sell anything we wouldn\'t recommend to a friend.</li>
<li><strong>Real Specifications:</strong> All product listings include complete, accurate specifications. No marketing fluff, no hidden asterisks.</li>
<li><strong>Fast, Reliable Shipping:</strong> Free shipping on orders over $49, with expedited options for when you need it tomorrow.</li>
<li><strong>30-Day Returns:</strong> Changed your mind? No problem. Most items can be returned within 30 days, no questions asked.</li>
<li><strong>Technical Support:</strong> Our team of hardware experts is available 7 days a week via live chat to answer compatibility questions and help you make the right choice.</li>
</ul>
<h2>Our Brands</h2>
<p>We partner directly with over 40 of the world\'s leading technology brands including Apple, Dell, HP, Lenovo, ASUS, Samsung, Sony, Logitech, Corsair, Razer, and many more — ensuring you receive genuine, warranty-supported products.</p>
<h2>Our Story</h2>
<p>TechHeaven was founded in 2018 by a team of engineers and tech enthusiasts frustrated by the gap between what was available in big-box stores and what serious users actually needed. We started with a curated range of 50 products and have grown to over 290 across 15 categories, all hand-selected for quality, value, and genuine usefulness.</p>
<h2>Contact Us</h2>
<p>Have a question? Our support team is available 7 days a week. Visit our <a href="/page/contact-us">Contact Us</a> page or start a live chat at the bottom of any page.</p>',
            ],
            [
                'url_key'          => 'contact-us',
                'title'            => 'Contact Us',
                'meta_description' => 'Contact TechHeaven — live chat, email, and phone support 7 days a week.',
                'meta_keywords'    => 'contact TechHeaven, customer support, help',
                'content'          => '<h1>Contact Us</h1>
<p>We\'re here to help. Reach out to TechHeaven\'s support team through any of the channels below.</p>
<h2>Live Chat</h2>
<p>The fastest way to get help. Our live chat team is available:</p>
<ul>
<li>Monday–Friday: 8 AM – 10 PM EST</li>
<li>Saturday–Sunday: 9 AM – 8 PM EST</li>
</ul>
<h2>Email Support</h2>
<p>Email us at <a href="mailto:support@techheaven.com">support@techheaven.com</a>. We respond to all emails within 24 hours on business days.</p>
<p>For order-specific enquiries, include your order number in the subject line for faster service.</p>
<h2>Phone Support</h2>
<p>Call us at <strong>1-800-TECH-HVN</strong> (1-800-832-4486)</p>
<ul>
<li>Monday–Friday: 9 AM – 7 PM EST</li>
<li>Closed on US federal holidays</li>
</ul>
<h2>Returns Address</h2>
<p>TechHeaven Returns Processing<br>
1234 Commerce Drive, Suite 100<br>
Austin, TX 78701<br>
United States</p>
<p><em>Please do not ship returns to this address without first requesting a return label through your account. Unauthorised returns may be refused.</em></p>',
            ],
            [
                'url_key'          => 'laptop-buying-guide',
                'title'            => 'Laptop Buying Guide 2025',
                'meta_description' => '2025 Laptop Buying Guide — how to choose the right laptop for work, gaming, creativity and student use.',
                'meta_keywords'    => 'laptop buying guide 2025, best laptop, how to buy a laptop',
                'content'          => '<h1>Laptop Buying Guide 2025</h1>
<p>Buying a laptop is a significant investment. This guide will help you understand the key specifications and choose the right machine for your needs and budget.</p>
<h2>Processor (CPU)</h2>
<p><strong>Intel vs AMD:</strong> Both are excellent in 2025. Intel\'s Core Ultra 100 series leads in single-threaded gaming performance. AMD\'s Ryzen 7000 series offers competitive multithreaded performance, often with better battery efficiency.</p>
<p><strong>Apple Silicon:</strong> For MacBook buyers, M3 and M4 chips deliver industry-leading performance-per-watt. The M4 Pro is the choice for professional creative workloads.</p>
<h2>Memory (RAM)</h2>
<ul>
<li><strong>8GB:</strong> Minimum for basic tasks — web browsing, document editing, video streaming</li>
<li><strong>16GB:</strong> Recommended for most users — comfortable multitasking, light creative work</li>
<li><strong>32GB+:</strong> Power users, video editors, 3D artists, developers running multiple VMs</li>
</ul>
<h2>Storage</h2>
<p>Always choose SSD over HDD. 256GB is the absolute minimum — 512GB or 1TB is strongly recommended to avoid running out of space within a year of purchase.</p>
<h2>Display</h2>
<ul>
<li><strong>Resolution:</strong> 1080p minimum, 1440p or 4K for creative work</li>
<li><strong>Panel type:</strong> IPS for colour accuracy, OLED for contrast and colour vibrancy</li>
<li><strong>Refresh rate:</strong> 60Hz for office use, 120Hz or higher for gaming and smooth scrolling</li>
</ul>
<h2>Battery Life</h2>
<p>Claims vary wildly. Real-world battery life for mainstream laptops: MacBook Air (12–15 hours), Windows ultrabooks (7–12 hours), gaming laptops (2–5 hours on battery).</p>',
            ],
            [
                'url_key'          => 'monitor-buying-guide',
                'title'            => 'Monitor Buying Guide 2025',
                'meta_description' => '2025 Monitor Buying Guide — resolution, panel type, refresh rate and how to choose the right monitor.',
                'meta_keywords'    => 'monitor buying guide 2025, best monitor, how to choose a monitor',
                'content'          => '<h1>Monitor Buying Guide 2025</h1>
<h2>Resolution</h2>
<ul>
<li><strong>1080p (FHD):</strong> Budget gaming and basic office use. Acceptable on 24" or smaller.</li>
<li><strong>1440p (QHD):</strong> The sweet spot. Sharp enough to see detail, high refresh rates achievable.</li>
<li><strong>4K (UHD):</strong> Best for 27"+ displays, professional photo/video work, and productivity.</li>
</ul>
<h2>Panel Technology</h2>
<ul>
<li><strong>IPS:</strong> Wide viewing angles, accurate colours. Best all-rounder for most users.</li>
<li><strong>VA:</strong> Higher native contrast (3000:1 vs 1000:1 for IPS). Better for dark rooms.</li>
<li><strong>OLED:</strong> Infinite contrast, fastest response time. Best for gaming and colour work. Burn-in risk for static content.</li>
<li><strong>Mini LED:</strong> Thousands of dimming zones for near-OLED HDR without burn-in risk.</li>
</ul>
<h2>Refresh Rate</h2>
<ul>
<li><strong>60Hz:</strong> Office, web browsing, document work. Completely fine for non-gaming use.</li>
<li><strong>144–165Hz:</strong> Ideal gaming range. Significantly smoother than 60Hz.</li>
<li><strong>240–360Hz:</strong> Competitive gaming. Requires a high-end GPU to push enough frames.</li>
</ul>
<h2>USB-C Connectivity</h2>
<p>A monitor with USB-C (65W+ Power Delivery) can replace your laptop charger and act as a docking station — one cable connects your MacBook or Windows ultrabook for video, data, and power simultaneously.</p>',
            ],
            [
                'url_key'          => 'gift-cards',
                'title'            => 'TechHeaven Gift Cards',
                'meta_description' => 'TechHeaven Gift Cards — the perfect gift for tech enthusiasts. Available in $25, $50, $100, $200 and $500 denominations.',
                'meta_keywords'    => 'gift card, TechHeaven gift card, tech gift',
                'content'          => '<h1>TechHeaven Gift Cards</h1>
<p>Not sure what to buy? A TechHeaven Gift Card lets your recipient choose exactly the tech they want.</p>
<h2>Denominations Available</h2>
<ul>
<li>$25</li>
<li>$50</li>
<li>$100</li>
<li>$200</li>
<li>$500</li>
</ul>
<h2>How Gift Cards Work</h2>
<ol>
<li>Choose your denomination and purchase it like any other product</li>
<li>A unique gift card code is emailed to you (or directly to the recipient)</li>
<li>The recipient enters the code at checkout to apply the balance</li>
<li>Balances can be split across multiple orders</li>
<li>Unused balances never expire</li>
</ol>
<h2>Important Information</h2>
<ul>
<li>Gift cards are non-refundable and cannot be exchanged for cash</li>
<li>Lost or stolen gift cards cannot be replaced</li>
<li>Gift cards are valid for purchases on TechHeaven only and cannot be applied to previous orders</li>
</ul>',
            ],
            [
                'url_key'          => 'loyalty-program',
                'title'            => 'TechHeaven Rewards',
                'meta_description' => 'TechHeaven Rewards loyalty programme — earn points on every purchase and redeem for discounts.',
                'meta_keywords'    => 'loyalty programme, rewards, TechHeaven points',
                'content'          => '<h1>TechHeaven Rewards</h1>
<p>Earn points on every purchase and redeem them for discounts on future orders.</p>
<h2>How to Earn Points</h2>
<table><thead><tr><th>Action</th><th>Points Earned</th></tr></thead><tbody>
<tr><td>Every $1 spent on products</td><td>10 points</td></tr>
<tr><td>Writing a verified product review</td><td>100 points</td></tr>
<tr><td>Referring a friend who makes a purchase</td><td>500 points</td></tr>
<tr><td>Birthday bonus (once per year)</td><td>250 points</td></tr>
</tbody></table>
<h2>Redeeming Points</h2>
<p>Every <strong>100 points = $1 discount</strong> at checkout. You can redeem any whole-dollar amount up to the value of your cart.</p>
<h2>Membership Tiers</h2>
<ul>
<li><strong>Silver (0–999 points/year):</strong> Base earn rate</li>
<li><strong>Gold (1,000–4,999 points/year):</strong> 1.25× earn rate + free expedited shipping on orders over $99</li>
<li><strong>Platinum (5,000+ points/year):</strong> 1.5× earn rate + free expedited shipping on all orders + priority support</li>
</ul>
<p>Tier status is calculated based on points earned in the last 12 months. Points expire 24 months after they are earned.</p>',
            ],
            [
                'url_key'          => 'customer-service',
                'title'            => 'Customer Service',
                'meta_description' => 'TechHeaven Customer Service — fast help for orders, returns, compatibility questions and warranty claims.',
                'meta_keywords'    => 'customer service, TechHeaven support, help centre, returns, orders',
                'content'          => '<h1>Customer Service</h1>
<p>Welcome to TechHeaven\'s Customer Service hub. Our support team is here to help 7 days a week — choose the channel that works best for you.</p>
<h2>Contact Options</h2>
<table><thead><tr><th>Channel</th><th>Hours</th><th>Best For</th></tr></thead><tbody>
<tr><td><strong>Live Chat</strong></td><td>Mon–Fri 8 AM–10 PM EST, Sat–Sun 9 AM–8 PM EST</td><td>Quick questions, order status, compatibility checks</td></tr>
<tr><td><strong>Email</strong> — support@techheaven.com</td><td>24/7 (reply within 24 h)</td><td>Detailed issues, warranty claims, complex enquiries</td></tr>
<tr><td><strong>Phone</strong> — 1-800-TECH-HVN</td><td>Mon–Fri 9 AM–7 PM EST</td><td>Urgent issues, large business orders</td></tr>
</tbody></table>
<h2>Self-Service Resources</h2>
<ul>
<li><a href="/page/faq">Frequently Asked Questions</a> — answers to the most common questions about shipping, returns, and products</li>
<li><a href="/page/return-policy">Return &amp; Refund Policy</a> — 30-day returns, how to start a return, refund timelines</li>
<li><a href="/page/shipping-policy">Shipping Policy</a> — delivery times, carriers, free shipping threshold</li>
<li><a href="/page/warranty-policy">Warranty Policy</a> — manufacturer warranties and TechHeaven Protection plans</li>
<li><a href="/page/payment-policy">Payment Methods</a> — accepted cards, PayPal, Buy Now Pay Later options</li>
</ul>
<h2>Order Tracking</h2>
<p>Log into your TechHeaven account and go to <strong>My Account → Orders</strong> to view real-time tracking for any shipment. You can also use the tracking link in your shipping confirmation email without logging in.</p>
<h2>Business &amp; Bulk Orders</h2>
<p>Schools, businesses, and IT departments purchasing 5 or more units of the same product qualify for volume pricing. Email <a href="mailto:business@techheaven.com">business@techheaven.com</a> with your requirements and we\'ll send a custom quote within one business day.</p>',
            ],
            [
                'url_key'          => 'whats-new',
                'title'            => "What's New at TechHeaven",
                'meta_description' => "See what's new at TechHeaven — new product arrivals, latest releases from top brands, and seasonal promotions.",
                'meta_keywords'    => "new arrivals, new products, latest tech, TechHeaven new releases",
                'content'          => '<h1>What\'s New at TechHeaven</h1>
<p>We add new products every week. Here\'s a look at our most recent arrivals and brand highlights — updated monthly.</p>
<h2>Just Arrived — Q1 2025</h2>
<h3>Laptops &amp; Computers</h3>
<ul>
<li><strong>Apple MacBook Pro 14" M4 Pro</strong> — The fastest thin laptop we\'ve ever stocked. 24 GB unified memory, 14-core GPU, up to 22 hours battery life.</li>
<li><strong>Dell XPS 15 (2025)</strong> — Intel Core Ultra 9, 2.8K OLED display, premium build. Our top seller in professional workstations.</li>
<li><strong>ASUS ROG Zephyrus G14 (2025)</strong> — AMD Ryzen 9, NVIDIA RTX 4070, 1600-nit mini-LED display in a 14" gaming chassis.</li>
</ul>
<h3>Monitors</h3>
<ul>
<li><strong>LG 27GX790A</strong> — 27" QHD 480 Hz IPS gaming monitor. The highest-refresh-rate monitor we carry; optimised for competitive FPS.</li>
<li><strong>Samsung ViewFinity S9 27" 5K</strong> — Professional-grade 5K IPS with Thunderbolt 4, targeting creative professionals and developers.</li>
</ul>
<h3>Audio</h3>
<ul>
<li><strong>Sony WH-1000XM6</strong> — Market-leading ANC headphones, now with LDAC + multipoint + USB-C audio.</li>
<li><strong>Apple AirPods Pro (3rd Gen)</strong> — Upgraded hearing-aid mode, cleaner ambient audio, lossless wireless audio over iPhone 16.</li>
</ul>
<h2>Upcoming Releases (Pre-Order)</h2>
<ul>
<li><strong>NVIDIA GeForce RTX 5090</strong> — Pre-orders open Q2 2025. Contact us to be added to the waitlist.</li>
<li><strong>Garmin Forerunner 970</strong> — Next-generation running watch with triathlon mode and amoled display. Ships April 2025.</li>
</ul>
<h2>Promotions This Month</h2>
<p>Check our <a href="/promotions">Promotions</a> page for current deals, bundle offers, and student discounts. Use code <strong>FREESHIP</strong> for free expedited shipping on orders over $99.</p>',
            ],
            [
                'url_key'          => 'payment-policy',
                'title'            => 'Payment Methods & Security',
                'meta_description' => 'TechHeaven accepted payment methods — credit cards, PayPal, Buy Now Pay Later, and payment security information.',
                'meta_keywords'    => 'payment methods, accepted cards, PayPal, buy now pay later, TechHeaven payment',
                'content'          => '<h1>Payment Methods &amp; Security</h1>
<p>TechHeaven accepts a wide range of payment methods to make checkout as convenient as possible. All transactions are encrypted with TLS 1.3 and processed through PCI DSS Level 1 certified payment gateways.</p>
<h2>Accepted Payment Methods</h2>
<table><thead><tr><th>Method</th><th>Notes</th></tr></thead><tbody>
<tr><td>Visa</td><td>Credit and debit</td></tr>
<tr><td>Mastercard</td><td>Credit and debit</td></tr>
<tr><td>American Express</td><td>Credit only</td></tr>
<tr><td>Discover</td><td>Credit only</td></tr>
<tr><td>PayPal</td><td>Balance, bank, or card via PayPal</td></tr>
<tr><td>PayPal Pay Later</td><td>Pay in 4 interest-free installments</td></tr>
<tr><td>Apple Pay</td><td>Safari and iOS Safari only</td></tr>
<tr><td>Google Pay</td><td>Chrome on Android</td></tr>
<tr><td>TechHeaven Gift Card</td><td>Enter code at checkout; stackable with one other payment method</td></tr>
</tbody></table>
<h2>Buy Now, Pay Later</h2>
<p>Qualifying orders over $100 can be split into 4 equal payments, charged every two weeks, at 0% interest. Available through <strong>PayPal Pay Later</strong>. No hard credit check is required.</p>
<h2>Payment Security</h2>
<ul>
<li>We never store your full card number — only a tokenised reference provided by our payment processor.</li>
<li>3D Secure (Verified by Visa / Mastercard SecureCode) is enabled on all card transactions.</li>
<li>Suspicious orders are automatically held for manual review — you will be contacted within 4 hours if your order is flagged.</li>
</ul>
<h2>Taxes</h2>
<p>Sales tax is calculated automatically at checkout based on your shipping address and applicable state/local tax rates. International orders may be subject to import duties and VAT, which are the responsibility of the recipient and are not included in the checkout total.</p>
<h2>Billing Address</h2>
<p>The billing address you provide must match the address on file with your card issuer. Mismatches may result in a declined transaction. Contact your bank if your card is declined unexpectedly.</p>',
            ],
            [
                'url_key'          => 'refund-policy',
                'title'            => 'Refund Policy',
                'meta_description' => 'TechHeaven Refund Policy — how refunds are processed, timelines by payment method, and non-refundable items.',
                'meta_keywords'    => 'refund policy, TechHeaven refund, money back, refund timeline',
                'content'          => '<h1>Refund Policy</h1>
<p>If you\'re not fully satisfied with your TechHeaven purchase, we aim to make the refund process as straightforward as possible. This policy covers refund eligibility, timelines, and the process for each payment method.</p>
<h2>Refund Eligibility</h2>
<p>To be eligible for a full refund:</p>
<ul>
<li>The item must be returned within <strong>30 days</strong> of the delivery date</li>
<li>The item must be in its original condition and original packaging</li>
<li>All included accessories, manuals, and warranty cards must be present</li>
</ul>
<p>Opened software, activated licence keys, and digital downloads are non-refundable. See our <a href="/page/return-policy">Return Policy</a> for a full list of exclusions.</p>
<h2>How to Request a Refund</h2>
<ol>
<li>Log in to your account and navigate to <strong>My Orders</strong></li>
<li>Select the order and click <strong>Request Return / Refund</strong></li>
<li>Choose <strong>Refund</strong> as your preferred resolution</li>
<li>Print the prepaid return label and drop off the package at any UPS location</li>
<li>We will process your refund within 3–5 business days of receiving the return</li>
</ol>
<h2>Refund Timelines by Payment Method</h2>
<table><thead><tr><th>Payment Method</th><th>Refund Timeline (after we process)</th></tr></thead><tbody>
<tr><td>Credit / Debit Card</td><td>3–7 business days (depends on your bank)</td></tr>
<tr><td>PayPal</td><td>1–3 business days</td></tr>
<tr><td>Apple Pay / Google Pay</td><td>3–7 business days (reflected on card)</td></tr>
<tr><td>TechHeaven Gift Card</td><td>Immediate — balance restored to original gift card</td></tr>
</tbody></table>
<h2>Partial Refunds</h2>
<p>A partial refund (deducting a $6.99 restocking/return shipping fee) may apply to:</p>
<ul>
<li>Items returned in opened packaging where the product is not defective</li>
<li>Change-of-mind returns where free return shipping was not offered</li>
</ul>
<h2>Damaged or Defective Items</h2>
<p>If you received a damaged or defective item, we\'ll issue a <strong>full refund including original shipping costs</strong> with no restocking fee. Contact us within 48 hours of delivery with photos of the damage and we\'ll expedite the resolution.</p>
<h2>Questions?</h2>
<p>Contact our support team via <a href="/page/contact-us">live chat or email</a>. We respond within 24 hours on business days.</p>',
            ],
            [
                'url_key'          => 'terms-of-use',
                'title'            => 'Terms of Use',
                'meta_description' => 'TechHeaven Terms of Use — rules governing access to and use of the TechHeaven website and services.',
                'meta_keywords'    => 'terms of use, website terms, TechHeaven legal, acceptable use',
                'content'          => '<h1>Terms of Use</h1>
<p>Last updated: January 1, 2025</p>
<p>These Terms of Use govern your access to and use of the TechHeaven website located at techheaven.com ("the Site"). By accessing the Site, you agree to be bound by these terms. If you do not agree, please do not use the Site.</p>
<p>For the terms that apply to purchases, see our <a href="/page/terms-conditions">Terms &amp; Conditions</a>.</p>
<h2>Acceptable Use</h2>
<p>You agree to use the Site only for lawful purposes. You must not:</p>
<ul>
<li>Attempt to access restricted areas of the Site without authorisation</li>
<li>Use automated tools (bots, scrapers, crawlers) to extract pricing or product data at scale without our written permission</li>
<li>Submit false, misleading, or fraudulent orders or reviews</li>
<li>Interfere with the Site\'s operation, including by distributing malware or conducting denial-of-service attacks</li>
<li>Impersonate TechHeaven, its employees, or other users</li>
</ul>
<h2>Intellectual Property</h2>
<p>All content on the Site — including product descriptions, images, copy, logos, and software — is the property of TechHeaven or its licensors and is protected by US and international copyright law. You may not reproduce, distribute, or create derivative works from Site content without prior written permission.</p>
<h2>User-Generated Content</h2>
<p>When you submit a product review, question, or other content to the Site, you grant TechHeaven a non-exclusive, royalty-free, perpetual licence to use, display, and distribute that content in connection with our business. You remain responsible for ensuring your content is accurate and does not violate any third party\'s rights.</p>
<h2>Third-Party Links</h2>
<p>The Site may contain links to third-party websites. TechHeaven has no control over the content of those sites and accepts no responsibility for them. The inclusion of a link does not imply endorsement.</p>
<h2>Disclaimer of Warranties</h2>
<p>The Site is provided on an "as is" and "as available" basis. TechHeaven makes no warranties, express or implied, regarding the availability, accuracy, or reliability of the Site or its content.</p>
<h2>Changes to These Terms</h2>
<p>We may update these Terms of Use at any time. Changes take effect immediately upon posting. Your continued use of the Site after changes are posted constitutes acceptance of the updated Terms.</p>
<h2>Contact</h2>
<p>Questions about these Terms? Email <a href="mailto:legal@techheaven.com">legal@techheaven.com</a>.</p>',
            ],
        ];
    }
}
