<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ContactButton;
use App\Models\HomeSection;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImagineGadgetSeeder extends Seeder
{
    public function run(): void
    {
        $html = file_get_contents(base_path('../index.html')) ?: '';

        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin000'),
                'is_admin' => true,
            ]
        );

        $this->settings($html);
        $this->pages();
        $this->categories($html);
        $this->menus();
        $this->sliders($html);
        $this->products($html);
        $this->homeSections();
        $this->contactButtons();
    }

    private function settings(string $html): void
    {
        preg_match('/id="logo".*?<img src="([^"]+)"/s', $html, $logo);
        preg_match('/rel="icon".*?href="([^"]+)"/s', $html, $favicon);
        preg_match('/<span class="hidden-xs">\s*([0-9 ]+)<\/span>/', $html, $phone);
        preg_match('/class="facebook"[^>]*href="([^"]+)"/', $html, $facebook);
        preg_match('/class="instagram"[^>]*href="([^"]+)"/', $html, $instagram);

        $settings = [
            ['branding', 'site_name', 'Website Name', 'text', 'Eternity Gadget'],
            ['branding', 'logo', 'Website Logo', 'image', $this->url($logo[1] ?? '')],
            ['branding', 'footer_logo', 'Footer Logo', 'image', $this->url($logo[1] ?? '')],
            ['branding', 'favicon', 'Favicon', 'image', $this->url($favicon[1] ?? '')],
            ['theme', 'accent_color', 'Accent Color', 'color', '#83B735'],
            ['theme', 'button_color', 'Button Color', 'color', '#000000'],
            ['theme', 'header_background', 'Header Background', 'color', '#000000'],
            ['theme', 'footer_background', 'Footer Background', 'color', '#000000'],
            ['contact', 'email', 'Public Email', 'email', 'support@eternitygadget.com'],
            ['contact', 'phone', 'Phone Number', 'phone', '01867-683114'],
            ['contact', 'address', 'Store Address', 'text', 'Dhaka, Bangladesh'],
            ['contact', 'facebook_url', 'Facebook URL', 'url', 'https://www.facebook.com/share/18mQeu5yMY/?mibextid=wwXIfr'],
            ['contact', 'instagram_url', 'Instagram URL', 'url', $instagram[1] ?? 'https://www.instagram.com/imagine.gadget'],
            ['contact', 'youtube_url', 'Youtube URL', 'url', '#'],
            ['footer', 'footer_about', 'Footer About Text', 'textarea', 'Welcome to Eternity Gadget — premium gadgets and accessories in Dhaka, Bangladesh.'],
            ['footer', 'copyright_text', 'Copyright Text', 'text', 'Eternity Gadget © ' . date('Y')],
            ['seo', 'meta_title', 'Homepage SEO Title', 'text', 'Eternity Gadget | Dhaka, Bangladesh'],
            ['seo', 'meta_description', 'Homepage SEO Description', 'textarea', 'Eternity Gadget — shop premium phone cases, chargers, and tech accessories in Dhaka, Bangladesh.'],
            ['general', 'shipping_cost', 'Default Shipping Cost', 'text', '0'],
        ];

        foreach ($settings as $index => [$group, $key, $label, $type, $value]) {
            SiteSetting::updateOrCreate(['key' => $key], compact('group', 'label', 'type', 'value') + ['sort_order' => $index]);
        }
    }

    private function pages(): void
    {
        $pages = [
            'About Us' => 'Learn more about Eternity Gadget and our commitment to quality accessories in Dhaka, Bangladesh.',
            'Delivery Information' => 'Delivery rules, timelines, and available delivery areas can be edited from this page.',
            'Privacy Policy' => 'Your privacy policy content can be edited here from the admin panel.',
            'Terms & Conditions' => 'Terms and conditions for your store can be edited from the admin panel.',
            'Contact Us' => 'Phone: 01867-683114. Location: Dhaka, Bangladesh. Use the floating buttons to contact Eternity Gadget.',
            'Returns' => 'Return policy content can be edited here.',
        ];

        foreach ($pages as $title => $content) {
            Page::updateOrCreate(
                ['slug' => Str::slug($title)],
                ['title' => $title, 'content' => '<p>' . e($content) . '</p>', 'is_published' => true]
            );
        }
    }

    private function categories(string $html): void
    {
        $top = [
            'iPhone Case' => ['iphone-1.html', 'iPhone cases and covers.'],
            'Samsung Case' => ['samsung.html', 'Samsung cases and covers.'],
            'Charger' => ['charger.html', 'Chargers, adapters, and power products.'],
            'Glass Protector' => ['cover-amp-protector.html', 'Screen and glass protectors.'],
            'Lens Protector' => ['lens-protector.html', 'Camera lens protectors.'],
            'Accessories' => ['accessories.html', 'Phone, laptop, and tech accessories.'],
            'AirPods' => ['airpods/airpods-pro.html', 'AirPods accessories.'],
            'Adapters' => ['cable.html', 'Adapters and cables.'],
        ];

        preg_match_all('/<img src="([^"]+)" alt="([^"]+)" loading="lazy"\/>.*?<h4 class="name"><a href="([^"]+)">([^<]+)<\/a><\/h4>/s', $html, $tiles, PREG_SET_ORDER);
        $tileImages = [];
        foreach ($tiles as $tile) {
            $tileImages[trim(html_entity_decode($tile[4]))] = $this->url($tile[1]);
        }

        $order = 0;
        foreach ($top as $name => [$url, $description]) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'image' => $tileImages[$name] ?? null,
                    'description' => $description,
                    'sort_order' => $order++,
                    'is_featured' => true,
                    'is_active' => true,
                ]
            );
        }

        preg_match_all('/<span class="a-second-title">([^<]+)<\/span>/', $html, $children);
        foreach (array_unique(array_map(fn ($name) => trim(html_entity_decode($name)), $children[1] ?? [])) as $name) {
            $parent = $this->guessCategory($name);
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'parent_id' => $parent?->id,
                    'name' => $name,
                    'sort_order' => $order++,
                    'is_active' => true,
                ]
            );
        }
    }

    private function menus(): void
    {
        $menu = Menu::updateOrCreate(['location' => 'main'], ['name' => 'Main Menu', 'is_active' => true]);
        MenuItem::where('menu_id', $menu->id)->delete();

        $order = 0;
        foreach (Category::whereNull('parent_id')->orderBy('sort_order')->get() as $category) {
            $parent = MenuItem::create([
                'menu_id' => $menu->id,
                'label' => $category->name,
                'url' => route('category.show', $category->slug, false),
                'sort_order' => $order++,
                'is_active' => true,
            ]);

            foreach ($category->children()->limit(12)->get() as $child) {
                MenuItem::create([
                    'menu_id' => $menu->id,
                    'parent_id' => $parent->id,
                    'label' => $child->name,
                    'url' => route('category.show', $child->slug, false),
                    'sort_order' => $order++,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function sliders(string $html): void
    {
        preg_match_all('/class="top-silder-image"[^>]*src="([^"]+)"/', $html, $matches);
        if (empty($matches[1])) {
            preg_match_all('/<img[^>]+src="([^"]+slider[^"]+)"/', $html, $matches);
        }

        Slider::truncate();
        foreach (array_unique($matches[1] ?? []) as $index => $image) {
            Slider::create([
                'title' => 'Homepage Banner ' . ($index + 1),
                'image' => $this->url($image),
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }

    private function products(string $html): void
    {
        preg_match_all('/<a href="[^"]*product_id=(\d+)[^"]*"[^>]*>\s*<picture>.*?<img[^>]+data-src="([^"]+)"[^>]+title="([^"]+)".*?<\/a><div class="swiper-lazy-preloader"><\/div><\/div><div class="caption">.*?<h4><a[^>]*>(.*?)<\/a><\/h4><div><p class="price">\s*<span class="price-new">৳([\d,]+)<\/span>(?:\s*<span class="price-old">৳([\d,]+)<\/span>)?/s', $html, $matches, PREG_SET_ORDER);

        $seen = [];
        foreach ($matches as $match) {
            [$all, $externalId, $image, $titleAttr, $nameHtml, $newPrice, $oldPrice] = array_pad($match, 7, null);
            if (isset($seen[$externalId])) {
                continue;
            }
            $seen[$externalId] = true;

            $name = trim(html_entity_decode(strip_tags($nameHtml ?: $titleAttr)));
            $regular = (float) str_replace(',', '', $oldPrice ?: $newPrice);
            $sale = $oldPrice ? (float) str_replace(',', '', $newPrice) : null;
            $category = $this->guessCategory($name) ?: Category::first();

            $product = Product::updateOrCreate(
                ['sku' => 'IG-' . $externalId],
                [
                    'category_id' => $category?->id,
                    'name' => $name,
                    'slug' => Str::slug($name . '-' . $externalId),
                    'short_description' => $name,
                    'description' => '<p>' . e($name) . '</p>',
                    'price' => $regular,
                    'sale_price' => $sale,
                    'stock' => 100,
                    'is_active' => true,
                    'is_featured' => true,
                    'is_new' => ((int) $externalId) >= 190,
                    'is_clearance' => $sale !== null,
                ]
            );

            ProductImage::updateOrCreate(
                ['product_id' => $product->id, 'sort_order' => 0],
                ['image' => $this->url($image), 'alt_text' => $name, 'is_primary' => true]
            );
        }
    }

    private function homeSections(): void
    {
        $sections = [
            'New Arrival' => Product::where('is_new', true)->limit(12)->pluck('id'),
            'Trading Products' => Product::limit(12)->pluck('id'),
            'Apple Authentic' => Product::where('name', 'like', '%iPhone%')->orWhere('name', 'like', '%Apple%')->limit(12)->pluck('id'),
            'Screen & Lens Protector' => Product::where('name', 'like', '%Protector%')->orWhere('name', 'like', '%Lens%')->limit(12)->pluck('id'),
            'Samsung Accessories' => Product::where('name', 'like', '%Samsung%')->limit(12)->pluck('id'),
            'Clearances Sale' => Product::where('is_clearance', true)->limit(12)->pluck('id'),
        ];

        $order = 0;
        foreach ($sections as $title => $ids) {
            $section = HomeSection::updateOrCreate(
                ['slug' => Str::slug($title)],
                ['title' => $title, 'type' => 'products', 'sort_order' => $order++, 'is_active' => true]
            );

            $sync = [];
            foreach ($ids as $index => $id) {
                $sync[$id] = ['sort_order' => $index];
            }
            $section->products()->sync($sync);
        }
    }

    private function contactButtons(): void
    {
        ContactButton::truncate();
        foreach ([
            ['Call us', 'telephone', 'tel:01867683114'],
            ['Facebook', 'messenger', 'https://www.facebook.com/share/18mQeu5yMY/?mibextid=wwXIfr'],
            ['WhatsApp', 'whatsapp', 'https://wa.me/8801867683114'],
        ] as $index => [$label, $type, $url]) {
            ContactButton::create(compact('label', 'type', 'url') + ['sort_order' => $index, 'is_active' => true]);
        }
    }

    private function guessCategory(string $name): ?Category
    {
        $lower = Str::lower($name);
        $map = [
            'Samsung Case' => ['samsung', 'galaxy'],
            'Charger' => ['charger', 'adapter', 'power', 'gan', 'wireless'],
            'Glass Protector' => ['screen', 'glass', 'tempered'],
            'Lens Protector' => ['lens', 'camera'],
            'AirPods' => ['airpods'],
            'Adapters' => ['adapter', 'cable'],
            'Accessories' => ['hub', 'dock', 'mouse', 'keyboard', 'stand', 'watch', 'router', 'tracking'],
            'iPhone Case' => ['iphone', 'magsafe', 'case', 'apple'],
        ];

        foreach ($map as $category => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($lower, $needle)) {
                    return Category::where('name', $category)->first();
                }
            }
        }

        return Category::where('name', 'iPhone Case')->first();
    }

    private function url(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $url = html_entity_decode($url);
        $url = str_replace('../storola-client-space.sgp1.cdn.digitaloceanspaces.com', 'https://storola-client-space.sgp1.cdn.digitaloceanspaces.com', $url);

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        return $url;
    }
}
