<?php

namespace Database\Seeders;

use App\Models\ContactButton;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\Slider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EternityGadgetBrandingSeeder extends Seeder
{
    private const ASSETS_DIR = 'Assets U Must Use';

    private const BRAND_NAME = 'Eternity Gadget';

    private const PHONE = '01867-683114';

    private const PHONE_E164 = '8801867683114';

    private const EMAIL = 'support@eternitygadget.com';

    private const ADDRESS = 'Dhaka, Bangladesh';

    private const FACEBOOK_URL = 'https://www.facebook.com/share/18mQeu5yMY/?mibextid=wwXIfr';

    public function run(): void
    {
        $paths = $this->publishBrandAssets();

        $this->updateSiteSettings($paths);
        $this->updateSliders($paths['covers']);
        $this->updateContactButtons();
        $this->updatePages();

        SiteSetting::clearCacheFor('site_name');
        SiteSetting::clearCacheFor('logo');
        SiteSetting::clearCacheFor('footer_logo');
        SiteSetting::clearCacheFor('favicon');
    }

    /**
     * @return array{logo: string, favicon: string, covers: array<int, string>}
     */
    private function publishBrandAssets(): array
    {
        $source = $this->resolveAssetsDirectory();

        Storage::disk('public')->makeDirectory('branding/covers');

        $logoSource = $source . DIRECTORY_SEPARATOR . 'Eternity-Logo.png';
        $logoPath = 'branding/eternity-logo.png';
        Storage::disk('public')->put($logoPath, File::get($logoSource));

        $covers = [];
        foreach (range(1, 4) as $index) {
            $file = "Eternity-Cover-{$index}.jpeg";
            $sourceFile = $source . DIRECTORY_SEPARATOR . $file;
            $dest = "branding/covers/eternity-cover-{$index}.jpeg";
            Storage::disk('public')->put($dest, File::get($sourceFile));
            $covers[] = $dest;
        }

        return [
            'logo' => $logoPath,
            'favicon' => $logoPath,
            'covers' => $covers,
        ];
    }

    private function updateSiteSettings(array $paths): void
    {
        $settings = [
            ['branding', 'site_name', 'Website Name', 'text', self::BRAND_NAME],
            ['branding', 'logo', 'Website Logo', 'image', $paths['logo']],
            ['branding', 'footer_logo', 'Footer Logo', 'image', $paths['logo']],
            ['branding', 'favicon', 'Favicon', 'image', $paths['favicon']],
            ['contact', 'email', 'Public Email', 'email', self::EMAIL],
            ['contact', 'phone', 'Phone Number', 'phone', self::PHONE],
            ['contact', 'address', 'Store Address', 'text', self::ADDRESS],
            ['contact', 'facebook_url', 'Facebook URL', 'url', self::FACEBOOK_URL],
            ['footer', 'footer_about', 'Footer About Text', 'textarea', 'Welcome to Eternity Gadget — premium gadgets and accessories in Dhaka, Bangladesh.'],
            ['footer', 'copyright_text', 'Copyright Text', 'text', self::BRAND_NAME . ' © ' . date('Y')],
            ['seo', 'meta_title', 'Homepage SEO Title', 'text', self::BRAND_NAME . ' | Dhaka, Bangladesh'],
            ['seo', 'meta_description', 'Homepage SEO Description', 'textarea', self::BRAND_NAME . ' — shop premium phone cases, chargers, and tech accessories in ' . self::ADDRESS . '.'],
        ];

        foreach ($settings as $index => [$group, $key, $label, $type, $value]) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                compact('group', 'label', 'type', 'value') + ['sort_order' => $index]
            );
        }
    }

    private function updateSliders(array $coverPaths): void
    {
        Slider::query()->delete();

        foreach ($coverPaths as $index => $image) {
            Slider::create([
                'title' => self::BRAND_NAME . ' Banner ' . ($index + 1),
                'subtitle' => self::ADDRESS,
                'description' => 'Shop the latest gadgets and accessories at ' . self::BRAND_NAME . '.',
                'button_text' => 'Shop Now',
                'button_url' => url('/search'),
                'image' => $image,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }

    private function updateContactButtons(): void
    {
        ContactButton::query()->delete();

        foreach ([
            ['Call us', 'telephone', 'tel:' . str_replace('-', '', self::PHONE)],
            ['Facebook', 'messenger', self::FACEBOOK_URL],
            ['WhatsApp', 'whatsapp', 'https://wa.me/' . self::PHONE_E164],
        ] as $index => [$label, $type, $url]) {
            ContactButton::create([
                'label' => $label,
                'type' => $type,
                'url' => $url,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }

    private function updatePages(): void
    {
        $replacements = [
            'Imagine Gadget' => self::BRAND_NAME,
            'imaginegadget.com' => 'eternitygadget.com',
            '01811443375' => self::PHONE,
        ];

        Page::query()->each(function (Page $page) use ($replacements) {
            $content = $page->content ?? '';
            $title = $page->title;

            foreach ($replacements as $from => $to) {
                $content = str_replace($from, $to, $content);
                $title = str_replace($from, $to, $title);
            }

            $page->update([
                'title' => $title,
                'content' => $content,
                'meta_title' => Str::replace('Imagine Gadget', self::BRAND_NAME, $page->meta_title ?? $title),
            ]);
        });

        Page::updateOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => 'About Us',
                'content' => '<p>' . e(self::BRAND_NAME) . ' is based in ' . e(self::ADDRESS) . '. We provide high-quality gadgets and accessories with reliable service.</p>',
                'is_published' => true,
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'contact-us'],
            [
                'title' => 'Contact Us',
                'content' => '<p>Phone: ' . e(self::PHONE) . '<br>Location: ' . e(self::ADDRESS) . '<br>Facebook: <a href="' . e(self::FACEBOOK_URL) . '" target="_blank" rel="noopener">' . e(self::FACEBOOK_URL) . '</a></p>',
                'is_published' => true,
            ]
        );
    }

    private function resolveAssetsDirectory(): string
    {
        foreach ([
            base_path('../' . self::ASSETS_DIR),
            storage_path('app/branding-source'),
        ] as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        throw new \RuntimeException('Brand assets folder not found. Expected ../' . self::ASSETS_DIR . ' or storage/app/branding-source');
    }
}
