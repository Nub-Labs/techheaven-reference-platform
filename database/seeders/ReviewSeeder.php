<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
    // ── Rating distribution ───────────────────────────────────────────────────
    // 5★ 45%, 4★ 30%, 3★ 15%, 2★ 7%, 1★ 3%
    private array $ratingPool;

    // ── Review copy pools ─────────────────────────────────────────────────────

    private array $titles = [
        5 => [
            'Exceptional quality',
            'Best purchase this year',
            'Exceeded my expectations',
            'Absolutely love it',
            'Worth every penny',
            'Outstanding product',
            'Highly recommend',
            'Impressive performance',
            'Perfect in every way',
            'Five stars without hesitation',
            'Blown away by the quality',
            'Exactly what I was looking for',
            'Superb build quality',
            'A fantastic buy',
            'Cannot fault it',
            'Top-notch product',
            'Genuinely impressed',
            'Well worth the price',
            'Exceeded all expectations',
            'Incredible value',
        ],
        4 => [
            'Really good, minor niggles',
            'Solid product overall',
            'Very happy with this purchase',
            'Great value for money',
            'Does exactly what it says',
            'Good quality, fast delivery',
            'Impressed with performance',
            'Happy with this buy',
            'Almost perfect',
            'Good but not flawless',
            'Great product, small issues',
            'Mostly excellent',
            'Very pleased overall',
            'Reliable and well-built',
            'Would buy again',
            'Strong performer',
            'Recommended with minor caveats',
            'Good buy for the price',
            'Quality product',
            'Satisfied with my purchase',
        ],
        3 => [
            'Decent but not outstanding',
            'Average — does the job',
            'Mixed feelings on this one',
            'Okay for the price',
            'It is what it is',
            'Room for improvement',
            'Acceptable performance',
            'Not bad, not great',
            'Middle of the road',
            'Met basic expectations',
            'Fine for occasional use',
            'Could be better',
            'Has pros and cons',
            'Serviceable product',
            'Average quality',
        ],
        2 => [
            'Disappointed with this',
            'Not what I expected',
            'Below average quality',
            'Several issues to note',
            'Regret this purchase',
            'Underwhelming product',
            'Expected better for the price',
            'Had high hopes, let down',
            'Needs significant improvement',
            'Would not buy again',
        ],
        1 => [
            'Terrible — avoid',
            'Complete waste of money',
            'Not as described at all',
            'Broke within days',
            'Very disappointed',
            'Worst purchase I have made',
            'Do not buy this',
            'Shocking quality',
            'Arrived damaged',
            'Total letdown',
        ],
    ];

    private array $comments = [
        5 => [
            // Generic excellence
            'I am genuinely impressed by the quality. Everything from the packaging to the product itself screams premium. The performance has been flawless since day one and I use it daily. Could not be happier.',
            'After researching for weeks I finally pulled the trigger on this and I have no regrets. The build quality is excellent, it works exactly as advertised, and shipping was faster than expected. Highly recommended.',
            'This is one of the best purchases I have made in years. The attention to detail is obvious and performance is rock solid. My colleagues keep asking about it. Well worth every cent.',
            'Setup was a breeze and performance immediately blew me away. The build feels robust and premium. I have been using it for two months now with zero issues. This will be my go-to brand going forward.',
            'Fantastic product that punches well above its price point. I was skeptical at first but it has proven itself over the weeks. Reliable, well-built, and does exactly what I need.',
            // Laptop-specific
            'Battery life is incredible — I am getting a solid 10-12 hours under normal workload. The display is bright and colour-accurate. Keyboard feel is premium and the whole machine runs cool and quiet.',
            'Performance is blistering fast. Apps launch instantly, multitasking is effortless, and even demanding workflows feel snappy. The display quality is exceptional and the slim chassis feels great to carry.',
            'The laptop handles everything I throw at it with ease. Gaming performance is top-tier for the price and the cooling system keeps temperatures in check even during long sessions. Superb engineering.',
            // Headphone-specific
            'Sound quality is breathtaking — the bass is tight and punchy, mids are detailed, and the highs are crystal clear without any harshness. Noise cancellation is class-leading. Comfort over long sessions is excellent.',
            'These are the best headphones I have owned. The noise cancellation transforms noisy commutes and open offices. Sound signature is balanced and revealing. Battery easily lasts the entire work day.',
            'Wear comfort is outstanding — I can use them for four or five hours without any fatigue. Sound is rich and detailed across all frequencies. The microphone call quality is clear and natural.',
            // Monitor-specific
            'Colours are incredibly accurate right out of the box. The panel uniformity is excellent and I have noticed no backlight bleed whatsoever. Great for both creative work and gaming thanks to the fast response time.',
            'The image quality on this monitor is stunning. For photo editing the colour accuracy is spot on and the 4K resolution makes fine detail work a pleasure. The ergonomic stand is a bonus.',
            // Keyboard-specific
            'The typing experience is phenomenal. The switches have a satisfying tactile bump without being too loud for the office. Build quality is solid metal and the keycaps feel great. No flex whatsoever.',
            'Mechanical keyboards do not get much better than this at the price. The actuation force is perfect for long typing sessions and the RGB lighting is vibrant and even across all keys.',
            // Mouse-specific
            'The sensor is pin-point accurate and the clicks are crisp and satisfying. Ergonomics are excellent for my grip style and the weight is just right. Zero acceleration or smoothing detected.',
            'Tracking performance is flawless on every surface I have tried. The side buttons are perfectly positioned and the scroll wheel has a satisfying notch feel. Connectivity is instant and rock solid.',
            // Storage-specific
            'Read and write speeds are blazing fast — exactly as specified. My system boot time has been cut in half and large file transfers are a joy. Installation was straightforward with the included hardware.',
            'Upgrading to this SSD transformed my workflow. Video editing timelines scrub without any lag and project loads are almost instant. The difference from my old drive is night and day.',
            // Generic excellence continued
            'Customer service was also excellent when I had a question before purchasing. The product itself has been faultless. Cannot recommend highly enough to anyone in the market for this category.',
            'After using this for three months I can confirm it holds up beautifully. No issues, no quirks, just consistent reliable performance every single day. A genuinely great product.',
        ],
        4 => [
            'Very solid product overall. Performance is strong and build quality is good. My only minor gripe is the instruction manual could be clearer, but it was easy enough to figure out. Would recommend.',
            'Happy with this purchase. Does everything I need it to and the quality feels appropriate for the price. Delivery was prompt and packaging was protective. A good all-round buy.',
            'Good product that does exactly what it promises. Performance is consistent and reliable. It loses one star simply because the cable included is a bit short, but that is a trivial complaint.',
            'I am pleased with this. Build quality is solid and it performs well in day-to-day use. The setup process was straightforward. A minor quirk with the software but nothing that affects core use.',
            'Really good value for the price. Performance meets my needs comfortably and the build feels robust. It is not quite at the premium tier but for this price point it is excellent.',
            'Solid performer with great fundamentals. Battery life is strong, display is crisp, and the build feels quality. Loses a star because the fan can be audible under sustained load but otherwise excellent.',
            'Impressive product for the most part. The sound quality is detailed and engaging, noise cancellation works well, and the comfort is good. The touch controls can be slightly finicky — hence four stars.',
            'Great monitor — colours are vivid and accurate, the stand is sturdy and fully adjustable, and the response time is fast. The on-screen menu could be more intuitive but that is a minor issue.',
            'Excellent typing feel and satisfying key feedback. The build is premium and the RGB is great. Dropped one star because the software companion app is buggy on my system, though the keyboard works fine standalone.',
            'The tracking is precise and the build quality is noticeably premium. Ergonomics work well for my hand size. Dropped a star because the scroll wheel develops a slight rattle over time.',
            'Transfer speeds are great and the drive runs cool. Installation was simple. I give four stars because the warranty registration process online is unnecessarily complex, but the product itself is top quality.',
            'This has been a reliable performer since day one. Performance is consistent and the build is solid. Knocking one star off for the slightly underwhelming default settings — needs a tune out of the box.',
            'Really satisfied with this purchase. Does exactly what I need reliably. Would give five stars but the carry case feels cheap compared to the premium quality of the main product. Small thing but worth noting.',
            'Good product, fast shipping, well packaged. The performance in use is excellent and it has been very reliable. Minus one star because the colour options are limited — I would have preferred black.',
            'This has impressed me for the price. Performance is consistently good and it has been rock solid for several months. One star off because the software interface looks dated, though functionally it is fine.',
        ],
        3 => [
            'It is a decent product that does the job without any drama. However for this price I expected a little more in terms of build quality and finishing. The performance is adequate but not exciting.',
            'Mixed feelings. On the one hand performance is fine for basic tasks. On the other hand the build feels plasticky and the buttons have a cheap feel. Does what it says but could be better executed.',
            'Average experience overall. Nothing technically wrong with it but nothing exceptional either. It gets the job done and that is about all you can say. Might suit someone who just needs something functional.',
            'Okay for the price I suppose. The performance is acceptable and it has not given me any problems. I just feel the premium versions of competing products offer noticeably more for a modest price increase.',
            'Not bad but not great either. Battery life is shorter than the stated figures in real-world use. The display is fine for everyday content but nothing to write home about. A serviceable option.',
            'Sound quality is decent but the noise cancellation is only average — it handles steady background noise fine but struggles with sudden sounds. Comfort is good though and call quality is clear.',
            'The display quality is fine for basic office work but colour accuracy is not great for any creative use. The stand wobbles slightly. At this price point there are better options available.',
            'Keys feel okay but nothing special. The build has some flex in the middle which is disappointing. Typing is perfectly usable but if you have been spoiled by premium boards this will feel a step down.',
            'Tracking is accurate and the click feel is fine. The scroll wheel is a bit mushy for my taste. It is a functional mouse without any significant flaws but also without any standout features.',
            'Read speeds are as advertised but write speeds drop off more than I expected for sustained large writes. For general everyday use it is fine. Not the fastest option in this price bracket though.',
            'It has been reliable so far and does what I need. The companion app is quite basic and the UI feels dated. Performance is consistent but not particularly impressive compared to newer alternatives.',
            'Decent enough product. Setup was simple and it works without any fuss. The quality of materials is average — not premium but not cheap either. I expected slightly more given the price point.',
            'Serves its purpose adequately. Performance has been stable with no issues. I find myself wishing I had spent a bit more for the next tier up, which offers meaningfully better quality. Middling recommendation.',
        ],
        2 => [
            'Disappointed with this purchase. The quality does not match the product photos or the description. Performance is mediocre at best and I have already had to contact support once in the first month.',
            'Expected much better based on reviews I read. In practice the build feels flimsy and performance is inconsistent. I have noticed issues that suggest quality control problems. Would not recommend at this price.',
            'The battery life is nowhere near what is advertised — I am getting roughly half the stated figure under normal use conditions. Overall performance is sluggish compared to competitors in the same price range.',
            'Sound quality is thin and tinny — the bass is almost non-existent and the noise cancellation barely makes a dent in background noise. The ear cups feel cheap and uncomfortable after about an hour.',
            'Colours on the panel are visibly inaccurate out of the box and calibration only partially helps. There is noticeable backlight bleed in the bottom corners. Not acceptable for a monitor at this price.',
            'The switches feel mushy and inconsistent — some keys register at different actuation points. Build quality shows flex across the deck. I expected better from this manufacturer at this price point.',
            'The scroll wheel started squeaking after two weeks and the side buttons require too much force. The sensor stutters very occasionally at high DPI settings. Regret not spending slightly more for a better option.',
            'Write speeds are significantly below the advertised figures in real-world sustained tests. It also runs noticeably hot during heavy use which concerns me for long-term reliability. Disappointing.',
            'Had high hopes but the reality falls short. Performance is below what the specifications suggest and the build quality has some rough edges — literally, around the ports. Not what I expected for this price.',
            'Customer service was unhelpful when I reported an issue within the first week. The product itself works but has a noticeable quirk that the seller does not acknowledge. Below average experience overall.',
        ],
        1 => [
            'Arrived damaged — the packaging was intact but the product had a cracked casing and a stuck button. Returning immediately. Extremely disappointed and the return process has not been smooth.',
            'This broke within three days of normal use. No drops, no spills, just normal everyday use and it stopped working entirely. Quality control is clearly non-existent. Avoid this product.',
            'Nothing like the product description or the photos. The colour is completely different, the materials feel nothing like premium, and one of the advertised features simply does not function at all.',
            'The battery does not charge past 40% despite trying different cables and power adapters. A brand-new product should not behave like this. Complete waste of money and time dealing with the return.',
            'Connectivity drops constantly — every few minutes I have to reconnect. I have tried all troubleshooting steps from the manual and online and nothing fixes it. Unusable in its current state.',
            'Screen has a large dead pixel cluster right in the centre of the panel. This is a manufacturing defect that should have been caught at quality control. Unacceptable for a product at this price.',
            'Keys are sticking after less than a week of use and two of them have stopped registering entirely. I treat my equipment well — this is a clear quality failure. Would not recommend this brand.',
            'The sensor skips and freezes randomly making it completely unusable for precision work. I have updated drivers and tried multiple surfaces with no improvement. Deeply disappointed with this product.',
            'Corrupted two drives worth of data — the drive showed read errors after one week of light use. Lost irreplaceable files. This product should be recalled. Avoid at all costs.',
            'Packaging was clearly opened and the product appeared to have been used. One accessory was missing entirely and the main product has scratches. This was listed as brand new. Shocking.',
        ],
    ];

    // Product-specific sentiment inserts (injected into comments for realism)
    private array $productSentiment = [
        'laptop'    => [
            5 => 'Battery life is fantastic — lasting a full work day with ease. The display is sharp and bright outdoors.',
            4 => 'Performance is strong for everyday tasks. Wish the webcam were slightly better quality.',
            3 => 'Battery life is shorter than advertised in real-world use. Display is acceptable for the price.',
            2 => 'Fan noise is intrusive under any real load. Battery life is disappointing.',
            1 => 'Thermal throttles badly under sustained load, making it useless for anything demanding.',
        ],
        'headphone' => [
            5 => 'Sound stage is wide and detailed. Noise cancellation transforms commutes. Comfort over all-day sessions is superb.',
            4 => 'Sound quality is excellent. Noise cancellation is effective on steady noise. Slightly firm clamp at first.',
            3 => 'Sound is decent but noise cancellation only handles constant background noise adequately.',
            2 => 'Noise cancellation is average and the sound has a harsh top end that causes fatigue.',
            1 => 'Microphone does not work at all and sound cuts out randomly. Unusable for calls.',
        ],
        'monitor'   => [
            5 => 'Colour accuracy is exceptional right out of the box. No backlight bleed. Refresh rate makes everything silky smooth.',
            4 => 'Great image quality and fast response. The OSD menu is a bit unintuitive but image quality makes up for it.',
            3 => 'Colours are decent for general use but not accurate enough for colour-critical work.',
            2 => 'Backlight bleed in the corners is clearly visible on dark content. Colour uniformity is also poor.',
            1 => 'Dead pixels appeared after one week. Manufacturer is unresponsive. Avoid.',
        ],
        'keyboard'  => [
            5 => 'Switch feel is perfect — tactile without being too loud. Build is solid metal with zero flex.',
            4 => 'Great typing feel and solid construction. Software is a bit clunky but the keyboard works great independently.',
            3 => 'Typing feel is okay but the stabilisers on larger keys rattle more than expected at this price.',
            2 => 'Keys feel mushy and inconsistent. Build has noticeable flex. Not what I expected.',
            1 => 'Keys stopped registering after a week of normal use. Clearly a manufacturing defect.',
        ],
        'mouse'     => [
            5 => 'Sensor accuracy is flawless. Click feel is crisp and satisfying. Ergonomics suit extended gaming sessions perfectly.',
            4 => 'Great tracking and comfortable shape. Scroll wheel has a slight wobble but not enough to be a real issue.',
            3 => 'Tracking is accurate but the side buttons feel mushy. Build is average for the price.',
            2 => 'Double-clicking issue appeared within two weeks. Sensor stutters occasionally at high DPI.',
            1 => 'Developed a persistent double-click issue on day three. Brand new product should not do this.',
        ],
        'storage'   => [
            5 => 'Read and write speeds are consistently at the rated figures. Cool, quiet, and fast. System boots in seconds.',
            4 => 'Speeds are close to advertised. Easy to install. Slightly warm under sustained write workloads.',
            3 => 'Speeds are fine for general use but sustained write speed drops noticeably on large files.',
            2 => 'Write speeds drop dramatically after filling around 50% capacity. Not suitable for demanding workloads.',
            1 => 'Showed read errors after one week. Manufacturer was slow to respond. Lost data. Avoid.',
        ],
    ];

    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->command->info('  → Seeding product reviews...');

        $this->buildRatingPool();

        // Only customers who have placed orders write reviews
        $customerIds = DB::table('orders')
            ->whereNotNull('customer_id')
            ->distinct()
            ->pluck('customer_id')
            ->toArray();

        if (empty($customerIds)) {
            // Fall back to all customers
            $customerIds = DB::table('customers')->pluck('id')->toArray();
        }

        $customerCount = count($customerIds);

        // Load customer names keyed by id
        $customers = DB::table('customers')
            ->select('id', 'first_name', 'last_name')
            ->get()
            ->keyBy('id');

        // Load all product IDs
        $productIds = DB::table('products')
            ->where('type', 'simple')
            ->pluck('id')
            ->toArray();

        if (empty($productIds)) {
            $this->command->warn('     No products found — skipping ReviewSeeder.');
            return;
        }

        $productCount = count($productIds);

        // Load product names for category-aware sentiment
        $productNames = DB::table('product_flat')
            ->where('locale', 'en')
            ->where('channel', 'default')
            ->pluck('name', 'product_id');

        $now       = now();
        $total     = 5000;
        $batchSize = 100;
        $batch     = [];

        for ($i = 0; $i < $total; $i++) {
            $rating     = $this->ratingPool[$i % count($this->ratingPool)];
            $productId  = $productIds[$i % $productCount];
            $customerId = $customerIds[$i % $customerCount];

            $customer   = $customers[$customerId] ?? null;
            $reviewName = $customer
                ? $customer->first_name . ' ' . substr($customer->last_name, 0, 1) . '.'
                : 'Verified Buyer';

            $title   = $this->pickTitle($rating, $i);
            $comment = $this->buildComment($rating, $productId, $productNames, $i);

            // Review date: within last 18 months; spread deterministically
            $monthsAgo = ($i * 7 + 3) % 18;
            $daysAgo   = ($i * 11 + 5) % 28;
            $createdAt = $now->copy()
                ->subMonths($monthsAgo)
                ->subDays($daysAgo)
                ->format('Y-m-d H:i:s');

            $batch[] = [
                'title'      => $title,
                'rating'     => $rating,
                'comment'    => $comment,
                'status'     => 'approved',
                'product_id' => $productId,
                'customer_id'=> $customerId,
                'name'       => $reviewName,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('product_reviews')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('product_reviews')->insert($batch);
        }

        $this->command->info('     Created ' . $total . ' product reviews');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildRatingPool(): void
    {
        $this->ratingPool = array_merge(
            array_fill(0, 45, 5),
            array_fill(0, 30, 4),
            array_fill(0, 15, 3),
            array_fill(0, 7,  2),
            array_fill(0, 3,  1),
        );
    }

    private function pickTitle(int $rating, int $seed): string
    {
        $pool = $this->titles[$rating] ?? $this->titles[3];
        return $pool[$seed % count($pool)];
    }

    private function buildComment(int $rating, int $productId, $productNames, int $seed): string
    {
        $productName = $productNames[$productId] ?? '';
        $sentiment   = $this->detectSentimentCategory($productName);

        // Mix product-specific sentiment into the base comment 40% of the time
        $useSentiment = ($seed * 31 + 7) % 10 < 4;

        if ($useSentiment && $sentiment && isset($this->productSentiment[$sentiment][$rating])) {
            $specificLine = $this->productSentiment[$sentiment][$rating];
            $basePool     = $this->comments[$rating] ?? $this->comments[3];
            $baseComment  = $basePool[$seed % count($basePool)];

            // Append the specific sentiment as a second sentence if not too long
            return rtrim($baseComment, '.') . '. ' . $specificLine;
        }

        $pool = $this->comments[$rating] ?? $this->comments[3];
        return $pool[$seed % count($pool)];
    }

    private function detectSentimentCategory(string $productName): ?string
    {
        $lower = strtolower($productName);

        if (str_contains($lower, 'laptop') || str_contains($lower, 'notebook') || str_contains($lower, 'macbook')) {
            return 'laptop';
        }
        if (str_contains($lower, 'headphone') || str_contains($lower, 'headset') || str_contains($lower, 'earbud') || str_contains($lower, 'earphone')) {
            return 'headphone';
        }
        if (str_contains($lower, 'monitor') || str_contains($lower, 'display') || str_contains($lower, 'screen')) {
            return 'monitor';
        }
        if (str_contains($lower, 'keyboard')) {
            return 'keyboard';
        }
        if (str_contains($lower, 'mouse') || str_contains($lower, 'mice')) {
            return 'mouse';
        }
        if (str_contains($lower, 'ssd') || str_contains($lower, 'hdd') || str_contains($lower, 'drive') || str_contains($lower, 'storage') || str_contains($lower, 'nvme')) {
            return 'storage';
        }

        return null;
    }
}
