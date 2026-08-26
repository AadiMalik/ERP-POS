<?php

namespace App\Services\Concrete\Admin;

use App\Models\Business;
use App\Models\SocialMediaLink;
use App\Models\WebsiteBenefit;
use App\Models\WebsiteFaq;
use App\Models\WebsiteHeroStat;
use App\Models\WebsitePage;
use App\Models\WebsiteSection;
use App\Models\WebsiteTestimonial;
use Illuminate\Support\Str;

/**
 * Seeds a business's Website CMS tables with starter content on creation
 * (called from BusinessService::save() for new businesses) and via
 * `php artisan db:seed --class=WebsiteCmsDefaultsSeeder` for existing ones.
 * Idempotent - skips any table that already has rows for the business, so
 * re-running never duplicates or clobbers admin-edited content.
 *
 * Reviews, newsletter subscribers and contact messages are intentionally
 * NOT seeded here - they are user-generated, not starter/default content.
 */
class WebsiteCmsDefaultsService
{
    public function seed(string $business_id): void
    {
        $business = Business::find($business_id);
        $name = $business->name ?? 'our store';

        $this->seedPages($business_id, $name);
        $this->seedFaqs($business_id);
        $this->seedSocialLinks($business_id, $name);
        $this->seedSections($business_id, $name);
        $this->seedHeroStats($business_id);
        $this->seedBenefits($business_id);
        $this->seedTestimonials($business_id, $name);
    }

    /**
     * Per-slug, not a blanket "any row exists" check - WebsitePageService
     * auto-creates empty catalog rows (title only, no content) the first
     * time the public pages API is hit, before this ever runs. Only fills
     * in content that's still empty; never overwrites admin-edited content.
     */
    private function seedPages(string $business_id, string $name): void
    {
        foreach ($this->pageDefaults($name) as $slug => $page) {
            $existing = WebsitePage::where('business_id', $business_id)->where('slug', $slug)->first();

            if ($existing) {
                if (empty($existing->content)) {
                    $existing->update(['content' => $page['content']]);
                }
                continue;
            }

            WebsitePage::create([
                'page_id' => generateUuid(),
                'business_id' => $business_id,
                'slug' => $slug,
                'title' => $page['title'],
                'content' => $page['content'],
                'status' => 'active',
                'date_created' => now(),
            ]);
        }
    }

    private function seedFaqs(string $business_id): void
    {
        if (WebsiteFaq::where('business_id', $business_id)->exists()) {
            return;
        }

        foreach ($this->faqDefaults() as $i => $faq) {
            WebsiteFaq::create([
                'faq_id' => generateUuid(),
                'business_id' => $business_id,
                'question' => $faq[0],
                'answer' => $faq[1],
                'sort_order' => $i,
                'status' => 'active',
                'date_created' => now(),
            ]);
        }
    }

    private function seedSocialLinks(string $business_id, string $name): void
    {
        if (SocialMediaLink::where('business_id', $business_id)->exists()) {
            return;
        }

        $slug = Str::slug($name) ?: 'yourstore';
        $links = [
            ['Facebook', "https://facebook.com/{$slug}", 'fa-brands fa-facebook-f', '#1877F2'],
            ['Instagram', "https://instagram.com/{$slug}", 'fa-brands fa-instagram', '#E4405F'],
            ['Twitter / X', "https://twitter.com/{$slug}", 'fa-brands fa-twitter', '#000000'],
            ['YouTube', "https://youtube.com/@{$slug}", 'fa-brands fa-youtube', '#FF0000'],
            ['Pinterest', "https://pinterest.com/{$slug}", 'fa-brands fa-pinterest-p', '#E60023'],
        ];

        foreach ($links as $i => [$platform, $url, $icon, $color]) {
            SocialMediaLink::create([
                'social_media_link_id' => generateUuid(),
                'business_id' => $business_id,
                'platform' => $platform,
                'url' => $url,
                'icon' => $icon,
                'icon_color' => '#ffffff',
                'display_color' => $color,
                'sort_order' => $i,
                'status' => 'active',
                'date_created' => now(),
            ]);
        }
    }

    /**
     * Per-type, not a blanket "any row exists" check - new section types
     * (e.g. page headers) can be added to this list after businesses were
     * already seeded once. Skips any type that already has a row for the
     * business, so admin-edited content is never touched.
     */
    private function seedSections(string $business_id, string $name): void
    {
        $existingTypes = WebsiteSection::where('business_id', $business_id)->pluck('type')->all();

        $sections = [
            [
                'type' => 'hero',
                'heading' => 'Fresh groceries, delivered right to your door.',
                'description' => "Shop fruits, vegetables, dairy, bakery and everyday essentials from {$name} — hand-picked quality, unbeatable prices, delivered in as fast as 2 hours.",
                'image' => 'default_hero.jpg',
                'button_text' => 'Shop Now',
                'link_type' => 'shop',
            ],
            [
                'type' => 'about_us',
                'heading' => 'Fresh groceries, without the hassle.',
                'description' => "{$name} started with a simple idea: grocery shopping should be quick, honest and pleasant. What began as a single neighbourhood store has grown into a network of branches, each hand-picking fresh produce daily and getting it to your door in as little as two hours.",
                'image' => 'default_about.jpg',
                'button_text' => 'Start Shopping',
                'link_type' => 'shop',
            ],
            [
                'type' => 'why_shop_with_us',
                'heading' => 'Why Shop With Us',
                'description' => 'Fast delivery, hand-inspected fresh produce, secure encrypted checkout, easy 24-hour returns, and real support available every day of the week.',
                'heading_icon' => 'fa-solid fa-heart',
            ],
            [
                'type' => 'contact_us',
                'heading' => 'Contact Us',
                'description' => "Questions, feedback or need a hand with an order? We're here to help.",
                'heading_icon' => 'fa-solid fa-headset',
            ],
            [
                'type' => 'promo_banner',
                'heading' => 'Weekend Grocery Deals — Up to 40% Off',
                'description' => 'Stock up on fresh produce, pantry staples and household essentials before the deal ends. New discounts every day.',
                'image' => 'default_promo_banner.jpg',
                'button_text' => 'Shop Fresh Deals',
                'link_type' => 'shop',
            ],
            [
                'type' => 'shop',
                'heading' => 'Shop All Products',
            ],
            [
                'type' => 'categories',
                'heading' => 'Shop by Category',
            ],
            [
                'type' => 'cart',
                'heading' => 'Your Shopping Cart',
            ],
            [
                'type' => 'checkout',
                'heading' => 'Checkout',
            ],
            [
                'type' => 'wishlist',
                'heading' => 'Your Wishlist',
            ],
            [
                'type' => 'newsletter',
                'heading' => 'Get Fresh Deals in Your Inbox',
                'description' => 'Subscribe for weekly offers, new arrivals and exclusive discounts.',
                'button_text' => 'Subscribe',
            ],
            [
                'type' => 'footer',
                'tagline' => $name,
                'description' => "Your everyday store, online. Quality you can trust, delivered fast.",
            ],
            [
                'type' => 'about_cta',
                'heading' => 'Ready to shop smarter?',
                'description' => "Browse our full range of products from {$name}.",
                'button_text' => 'Start Shopping',
                'link_type' => 'shop',
            ],
            [
                'type' => 'editorial_banner',
                'tagline' => 'Seasonal Picks',
                'tagline_icon' => 'fa-solid fa-sun',
                'heading' => "This Season's Freshest Collection",
                'description' => "Hand-picked seasonal favourites from {$name}, while they last.",
                'image' => 'default_editorial.jpg',
                'button_text' => 'Explore Collection',
                'link_type' => 'shop',
            ],
            [
                'type' => 'login_promo',
                'heading' => 'Welcome Back',
                'description' => "Sign in to continue shopping with {$name}.",
            ],
            [
                'type' => 'signup_promo',
                'heading' => 'Create Your Account',
                'description' => 'It only takes a minute to get started.',
            ],
            [
                'type' => 'testimonials',
                'heading' => 'What Our Customers Say',
                'heading_icon' => 'fa-solid fa-quote-left',
            ],
        ];

        foreach ($sections as $i => $section) {
            if (in_array($section['type'], $existingTypes, true)) {
                continue;
            }

            WebsiteSection::create(array_merge([
                'section_id' => generateUuid(),
                'business_id' => $business_id,
                'sort_order' => $i,
                'status' => 'active',
                'date_created' => now(),
            ], $section));
        }
    }

    private function seedHeroStats(string $business_id): void
    {
        if (WebsiteHeroStat::where('business_id', $business_id)->exists()) {
            return;
        }

        $stats = [
            ['12k+', 'Happy Customers'],
            ['500+', 'Fresh Products'],
            ['2 hrs', 'Avg. Delivery Time'],
        ];

        foreach ($stats as $i => [$value, $label]) {
            WebsiteHeroStat::create([
                'hero_stat_id' => generateUuid(),
                'business_id' => $business_id,
                'value' => $value,
                'label' => $label,
                'sort_order' => $i,
                'status' => 'active',
                'date_created' => now(),
            ]);
        }
    }

    /**
     * Per-group, not a blanket "any row exists" check - new groups (product
     * trust badges, delivery options, etc.) can be added to this list after
     * businesses were already seeded once. Skips any group that already has
     * a row for the business, so admin-edited content is never touched.
     */
    private function seedBenefits(string $business_id): void
    {
        $existingGroups = WebsiteBenefit::where('business_id', $business_id)->pluck('group')->all();

        $groups = [
            'why_shop_with_us' => [
                ['Fast Delivery', 'Same-day and 2-hour express slots across the city.', null, null, 'fa-solid fa-truck-fast'],
                ['Fresh & Quality', 'Hand-inspected produce, sourced daily from trusted farms.', null, null, 'fa-solid fa-leaf'],
                ['Secure Payment', 'Encrypted checkout with every major payment method.', null, null, 'fa-solid fa-lock'],
                ['Easy Returns', 'Not happy? Free returns within 24 hours, no questions asked.', null, null, 'fa-solid fa-rotate-left'],
                ['24/7 Support', 'Real humans ready to help, any day of the week.', null, null, 'fa-solid fa-headset'],
            ],
            'product_trust' => [
                ['Free Delivery', 'Free delivery on orders over the store minimum — arrives within 2 hours in your area.', null, null, 'fa-solid fa-truck-fast'],
                ['Easy Returns', "Easy 24-hour returns if you're not fully satisfied.", null, null, 'fa-solid fa-rotate-left'],
                ['Quality Guarantee', 'Freshness guaranteed, or your money back.', null, null, 'fa-solid fa-shield-heart'],
            ],
            'cart_trust' => [
                ['Secure Checkout', null, null, null, 'fa-solid fa-lock'],
                ['Fast Delivery', null, null, null, 'fa-solid fa-truck-fast'],
                ['Easy Returns', null, null, null, 'fa-solid fa-rotate-left'],
            ],
            'login_promo' => [
                ['Track every order in real time', null, null, null, 'fa-solid fa-truck-fast'],
                ['Sync your wishlist across visits', null, null, null, 'fa-solid fa-heart'],
                ['One-tap reorder from history', null, null, null, 'fa-solid fa-rotate'],
            ],
            'signup_promo' => [
                ['Member-only offers & coupons', null, null, null, 'fa-solid fa-tags'],
                ['Save multiple delivery addresses', null, null, null, 'fa-solid fa-location-dot'],
                ['Full order history, anytime', null, null, null, 'fa-solid fa-clock-rotate-left'],
            ],
            'about_values' => [
                ['Quality First', 'Every product is checked before it ships.', null, null, 'fa-solid fa-award'],
                ['Customer Focused', 'Support that actually helps, every day of the week.', null, null, 'fa-solid fa-heart'],
                ['Always Improving', "We're always refining how we serve you.", null, null, 'fa-solid fa-arrow-trend-up'],
            ],
            'delivery_options' => [
                ['Standard Delivery', 'Arrives within 2-4 hours', 'Free over minimum, otherwise a flat fee applies', 'standard', 'fa-solid fa-truck-fast'],
                ['Express Delivery', 'Arrives within 60 minutes', '$7.99', 'express', 'fa-solid fa-bolt'],
                ['Scheduled Delivery', 'Pick a convenient time slot', '$2.99', 'scheduled', 'fa-solid fa-calendar-check'],
            ],
            'payment_methods' => [
                ['Credit / Debit Card', 'Visa, Mastercard, Amex', null, 'card', 'fa-solid fa-credit-card'],
                ['PayPal', 'Pay securely via PayPal', null, 'paypal', 'fa-brands fa-paypal'],
                ['Cash on Delivery', 'Pay when your order arrives', null, 'cod', 'fa-solid fa-money-bill-wave'],
            ],
            'payment_icons' => [
                ['Visa', null, null, null, 'fa-brands fa-cc-visa'],
                ['Mastercard', null, null, null, 'fa-brands fa-cc-mastercard'],
                ['PayPal', null, null, null, 'fa-brands fa-cc-paypal'],
            ],
            'announcement_bar' => [
                ['Free delivery on orders over the store minimum', null, null, null, null],
            ],
        ];

        foreach ($groups as $group => $items) {
            if (in_array($group, $existingGroups, true)) {
                continue;
            }

            foreach ($items as $i => [$title, $description, $value, $code, $icon]) {
                WebsiteBenefit::create([
                    'benefit_id' => generateUuid(),
                    'business_id' => $business_id,
                    'group' => $group,
                    'title' => $title,
                    'description' => $description,
                    'value' => $value,
                    'code' => $code,
                    'icon' => $icon,
                    'sort_order' => $i,
                    'status' => 'active',
                    'date_created' => now(),
                ]);
            }
        }
    }

    private function seedTestimonials(string $business_id, string $name): void
    {
        if (WebsiteTestimonial::where('business_id', $business_id)->exists()) {
            return;
        }

        $testimonials = [
            ['Sarah M.', 'Verified Customer', "Ordering from {$name} has become part of my weekly routine — always fresh, always on time.", 5],
            ['James T.', 'Verified Customer', 'The delivery is genuinely fast and the quality is consistently great.', 5],
            ['Priya K.', 'Verified Customer', 'Easy to reorder my usual list in a couple of taps. Support was great when I had a question.', 4],
        ];

        foreach ($testimonials as $i => [$author, $title, $quote, $rating]) {
            WebsiteTestimonial::create([
                'testimonial_id' => generateUuid(),
                'business_id' => $business_id,
                'author_name' => $author,
                'author_title' => $title,
                'quote' => $quote,
                'rating' => $rating,
                'sort_order' => $i,
                'status' => 'active',
                'date_created' => now(),
            ]);
        }
    }

    private function pageDefaults(string $name): array
    {
        return [
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'content' => "At {$name}, we take your privacy seriously. This policy explains what information we collect, how we use it, and the choices you have.\n\n"
                    . "1. Information We Collect\nWhen you create an account, place an order or contact support, we collect information such as your name, email address, phone number, delivery addresses and order history. We also collect basic usage data (pages visited, items viewed) to improve our service.\n\n"
                    . "2. How We Use Your Information\n- To process and deliver your orders\n- To send order confirmations, shipping updates and account notifications\n- To provide customer support and respond to inquiries\n- To personalise product recommendations and promotions\n- To improve our website and services\n\n"
                    . "3. Information Sharing\nWe do not sell your personal information. We may share necessary details with delivery partners and payment processors solely to fulfil your orders, and only to the extent required by law or to protect our rights.\n\n"
                    . "4. Data Security\nWe use industry-standard safeguards to protect your data, including encrypted checkout and secure storage practices. No online service can guarantee absolute security, but we work continuously to protect your information.\n\n"
                    . "5. Your Choices\nYou can review and update your personal information at any time from your Profile page, manage saved addresses, and opt out of marketing emails via the unsubscribe link in any newsletter.\n\n"
                    . "6. Cookies\nWe use essential cookies and local storage to keep you signed in, remember your cart, wishlist and selected store branch between visits. These are required for the site to function correctly.\n\n"
                    . "7. Contact Us\nIf you have questions about this policy or how your data is handled, reach out via our Contact Us page.",
            ],
            'terms-conditions' => [
                'title' => 'Terms & Conditions',
                'content' => "These Terms & Conditions govern your use of the {$name} website and any orders placed through it. By using our site, you agree to these terms.\n\n"
                    . "1. Using Our Service\nYou must be at least 18 years old, or using the site under the supervision of a parent or guardian, to place an order. You agree to provide accurate account and delivery information.\n\n"
                    . "2. Orders & Pricing\nAll prices include applicable taxes unless stated otherwise. We reserve the right to correct pricing errors and to cancel orders affected by an incorrect price, stock unavailability, or suspected fraud.\n\n"
                    . "3. Delivery\nDelivery times shown at checkout are estimates based on your selected branch and delivery method. We are not liable for delays caused by circumstances outside our control (severe weather, courier disruptions, etc.).\n\n"
                    . "4. Payments\nPayment is processed at the time of order for card and digital wallet methods. Cash on Delivery orders are payable in full to the courier upon receipt.\n\n"
                    . "5. Cancellations & Returns\nOrders can be cancelled free of charge while still in \"Processing\" status. Once shipped, please refer to our Returns & Refund Policy and Cancellation Policy for details.\n\n"
                    . "6. Account Responsibility\nYou are responsible for maintaining the confidentiality of your account credentials and for all activity under your account. Notify us immediately of any unauthorised use.\n\n"
                    . "7. Limitation of Liability\nOur service is provided \"as is.\" To the fullest extent permitted by law, we are not liable for indirect or consequential damages arising from your use of the site.\n\n"
                    . "8. Changes to These Terms\nWe may update these terms from time to time. Continued use of the site after changes take effect constitutes acceptance of the revised terms.\n\n"
                    . "9. Contact\nQuestions about these terms? Reach out via our Contact Us page.",
            ],
            'shipping-information' => [
                'title' => 'Shipping Information',
                'content' => "1. Delivery Areas\nWe currently deliver within the service radius of each branch. Use the store selector in the header to choose your nearest branch and see accurate delivery estimates for your area.\n\n"
                    . "2. Order Processing\nOrders are processed as soon as they're placed. You'll see live status updates — Processing, Shipped, Out for Delivery, and Delivered — from My Orders or via Track My Order.\n\n"
                    . "3. Delivery Attempts\nOur courier will attempt delivery to the address provided at checkout. If you're unavailable, we'll contact you using the phone number on file to arrange redelivery.\n\n"
                    . "4. Packaging\nFresh and frozen items are packed in insulated bags with ice packs to maintain quality during transit. Fragile items are individually wrapped for protection.\n\n"
                    . "5. Questions?\nFor delivery issues on an existing order, visit Track My Order or reach out via Contact Us.",
            ],
            'cancellation-policy' => [
                'title' => 'Cancellation Policy',
                'content' => "Plans change — here's how order cancellations work.\n\n"
                    . "1. When You Can Cancel\nOrders can be cancelled free of charge while they're still in the Processing stage — before they've been picked and handed to a courier. Once an order moves to Shipped, it can no longer be cancelled.\n\n"
                    . "2. How to Cancel\nGo to My Orders, open the order you'd like to cancel, and select Cancel Order. The cancellation is applied immediately.\n\n"
                    . "3. Refunds for Cancelled Orders\nIf payment was already captured (card, PayPal), a full refund is issued to your original payment method within 3–5 business days. Cash on Delivery orders simply require no payment.\n\n"
                    . "4. After Shipping\nIf your order has already shipped and you no longer want it, you can request a return once it's delivered — see our Returns & Refund Policy for details.\n\n"
                    . "5. Cancellations by Us\nOccasionally we may need to cancel part or all of an order due to stock unavailability or a pricing error. In this case, you'll be notified and refunded in full for the affected items.",
            ],
            'return-policy' => [
                'title' => 'Return Policy',
                'content' => "We want you to be completely satisfied with every order. If something isn't right, here's how returns and refunds work.\n\n"
                    . "1. Eligibility\n- Most items can be returned within 24 hours of delivery.\n- Items must be unused, unopened and in their original packaging where applicable.\n- Perishable items (fresh produce, dairy, meat, bakery) can only be returned if damaged, spoiled, or incorrect at the time of delivery.\n\n"
                    . "2. How to Request a Return\nOpen the relevant order in My Orders and select Return Item. Our support team will confirm pickup details or provide return instructions within one business day.\n\n"
                    . "3. Refund Method\nApproved refunds are issued to your original payment method. Cash on Delivery orders are refunded via store credit or bank transfer, as arranged with our support team.\n\n"
                    . "4. Refund Timeline\nOnce a return is received and inspected (or approved for perishable issues without pickup), refunds are processed within 3–5 business days. It may take a few extra days to reflect on your statement depending on your bank.\n\n"
                    . "5. Non-Returnable Items\nFor hygiene and safety reasons, opened personal care items and used household products cannot be returned unless defective.\n\n"
                    . "6. Need Help?\nIf you have questions about a specific order, visit Help Center or Contact Us directly.",
            ],
        ];
    }

    private function faqDefaults(): array
    {
        return [
            ['How do I place an order?', 'Browse the shop, add items to your cart, then head to checkout. Sign in (or continue as a guest) to enter your delivery details and confirm payment.'],
            ['Can I change or cancel my order after placing it?', 'You can cancel an order from My Orders as long as it has not moved past the "Processing" stage. Once an order ships, cancellation is no longer available — you can request a return instead after delivery.'],
            ['How do I track my order?', 'Go to My Orders and select a specific order, or use Track My Order with your order number and email to see live status without signing in.'],
            ['Do you offer reordering?', 'Yes — open any past order in My Orders and select Reorder to add the exact same items back to your cart.'],
            ['What are the delivery options?', 'We offer Standard (2–4 hours), Express (within 60 minutes) and Scheduled delivery so you can pick a convenient time slot.'],
            ['Which areas do you deliver to?', 'We currently deliver within the service radius of each branch. Choose your nearest branch using the store selector to see accurate delivery estimates.'],
            ['Is delivery free?', 'Free delivery is available above a minimum order amount set by the store — check the delivery banner in your cart for the current threshold.'],
            ['What payment methods are accepted?', 'We accept major cards, digital wallets, and Cash on Delivery.'],
            ['Is my payment information secure?', 'Yes — all payments are processed over an encrypted, secure checkout.'],
            ['Can I use a coupon code?', 'Yes, enter your coupon code in the Cart page before proceeding to checkout to see the discount applied to your order.'],
            ['What is your return policy?', 'Most items can be returned within 24 hours of delivery if you are not satisfied. See our full Returns & Refund Policy for perishable-item exceptions.'],
            ['How do I request a return?', 'Open the relevant order in My Orders and select Return Item. Our support team will reach out to arrange pickup or a refund.'],
            ['How long do refunds take?', 'Approved refunds are issued to your original payment method within 3–5 business days.'],
            ['How do I create an account?', 'Select Create Account from the header menu, enter your details, and verify your email with the OTP code shown on screen.'],
            ['I forgot my password — what do I do?', 'Use Forgot Password on the sign-in page, verify the OTP sent to your email, and set a new password.'],
            ['Can I save multiple delivery addresses?', 'Yes — manage saved addresses anytime from your Profile page.'],
        ];
    }
}
