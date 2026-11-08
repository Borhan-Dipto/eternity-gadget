<p align="center">
  <img src="./docs/cover.svg" alt="Eternity Gadget — gadget e-commerce platform" width="100%" />
</p>

<p align="center">
  <a href="https://eternitygadget.com"><img src="https://img.shields.io/badge/Live-eternitygadget.com-2dd4bf?style=for-the-badge&logo=googlechrome&logoColor=black" alt="Live site" /></a>
  <img src="https://img.shields.io/badge/Laravel-12-ff2d20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12" />
  <img src="https://img.shields.io/badge/PHP-8.2-777bb4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2" />
  <img src="https://img.shields.io/badge/Filament-admin-fdae4b?style=for-the-badge" alt="Filament" />
  <img src="https://img.shields.io/badge/MySQL-4479a1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
</p>

# Eternity Gadget

**Eternity Gadget** is a full e-commerce platform for a gadget and accessories shop in Bangladesh. Customers get a fast, Bangla-friendly storefront; the team gets a powerful **Filament** admin panel to run products, orders, marketing and customer service.

## ✨ Features

**Storefront**
- Home, category, product, search, compare and wishlist pages
- Product options and variants — picking a colour or model shows that exact photo
- Cart, checkout with delivery areas, free-delivery progress bar and a sticky order button
- Customer accounts with social login, order history, reward points and gift cards
- Product reviews and return requests
- Shop in **Bangla or English**, with self-hosted Inter + Hind Siliguri fonts
- Modern frosted-glass design and friendly error pages

**Admin panel (Filament)**
- Products, categories, device models, product options and inventory adjustments
- Orders with status filters, professional PDF invoices and order badges
- Coupons, gift cards, reward points and shipping methods
- Homepage sections, sliders, menus, pages and site settings — no code changes needed
- Newsletter subscribers, contact messages and WhatsApp contact buttons
- Audit logs and **2-step admin login**
- Product import / export

**Automation**
- Abandoned-cart recovery
- Low-stock alerts
- Automatic confirmation of pending orders
- Daily database backups and error alerts

**Marketing & SEO**
- Meta Pixel + **Conversions API** and Google Analytics 4
- XML sitemap and SEO-friendly pages

## 🛠️ Tech stack

| Layer | Technology |
| --- | --- |
| Framework | Laravel 12, PHP 8.2 |
| Admin panel | Filament |
| Database | MySQL |
| Frontend | Blade templates, Tailwind CSS v4, Vite |
| PDFs | mPDF |
| Auth | Laravel auth + Socialite |

## 🚀 Getting started

Requirements: PHP 8.2+, Composer, Node.js and MySQL.

```bash
git clone https://github.com/Borhan-Dipto/eternity-gadget.git
cd eternity-gadget
composer run setup   # installs dependencies, creates .env, migrates and builds assets
composer run dev     # starts the local development environment
```

Before running `setup`, create a MySQL database and fill in the `DB_*` values in `.env`. For production, start from `.env.production.example` and never commit real `.env` files.

### Run tests

```bash
composer run test
```

## 📁 Project structure

```
app/
├── Console/Commands/   # Backups, low-stock alerts, cart recovery, order confirmation
├── Filament/           # Admin panel resources and pages
└── Http/               # Storefront controllers
resources/views/
├── storefront/         # Customer-facing pages
├── invoices/ pdf/      # Invoice and PDF templates
└── emails/             # Email templates
```

---

<p align="center">Built by <a href="https://github.com/Borhan-Dipto">Borhan Dipto</a></p>
