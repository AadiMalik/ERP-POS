<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$views = [
  'admin.intro.modules.index',
  'admin.intro.blog_categories.index',
  'admin.intro.blog_tags.index',
  'admin.intro.blogs.index',
  'admin.intro.testimonials.index',
  'admin.intro.navigation.index',
  'admin.intro.homepage_sections.index',
  'admin.intro.pages.index',
  'admin.intro.media.index',
  'admin.intro.blog_comments.index',
  'admin.intro.contact_inquiries.index',
  'admin.intro.business_registrations.index',
  'admin.intro.website_settings.index',
];
foreach ($views as $v) {
    echo (view()->exists($v) ? 'OK ' : 'MISSING ') . $v . PHP_EOL;
}
