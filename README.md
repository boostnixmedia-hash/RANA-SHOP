# Rana Shop

Premium men's clothing store (Shirts, Pants, T-Shirts, Shoes) with an admin panel.
Plain PHP 8 + MySQL, made for normal cPanel hosting. No framework, no build step.

## Install (cPanel)
1. Upload all files to `public_html` (or a subfolder).
2. cPanel → MySQL Databases: create a database and a user, give the user ALL PRIVILEGES.
3. Open `https://your-domain.com/install.php` in the browser and fill in the form
   (database details, store name, admin email and password). Tick "demo products" to load sample data.
4. Log in at `https://your-domain.com/admin`.

Requirements: PHP 8.0+ with PDO MySQL, GD (WebP), cURL, mbstring. MySQL 5.7+ / MariaDB 10.3+.

## Admin
- Dashboard with sales and order stats
- Products: add / edit / duplicate / delete, photos (drag to sort, choose main photo), sizes × colours stock matrix, sale price, SEO, video
- Orders: status, payment status, tracking number, notes

## Storefront
Home page, categories, search, product page with size/colour picker, cart, checkout (Cash on Delivery, bKash, Nagad, card via SSLCommerz), order tracking, customer accounts, wishlist.

`app/config.php` and `app/secrets.php` are created by the installer and must never be committed.
