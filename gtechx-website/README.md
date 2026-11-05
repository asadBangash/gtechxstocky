# Global TechX Solutions — updated website

Modern responsive static HTML website with PHP contact and quote endpoints.

## Pages
- `index.html`, `about.html`, `contact.html`, `quote.html`, `privacy.html`, `terms.html`
- `projects/` four project detail pages and portfolio index
- `product/accounting.html` dedicated subscription accounting/POS page
- `services/` index plus twelve service detail pages
- `industries/` index plus seven industry detail pages
- `images/globaltechx-logo.jpg` supplied branded logo; favicon and touch icon generated from its symbol.

## Installation
1. Upload the entire website folder **contents** into your PHP-enabled hosting `public_html` / document root. All site URLs start with `/`, so they expect installation at your domain root.
2. The site requires PHP 8.1+ for the contact and get-a-quote form handlers in `api/`.
3. Configure sending email. **Preferred:** run `composer install --no-dev` to install PHPMailer, copy `config.example.php` to `config.php`, then set real SMTP mailbox host, port, username and password. Do **not** place credentials in JavaScript or public documentation. `config.php` is denied by Apache `.htaccess`, but on Nginx you must also deny direct access to it. Better: keep secrets in actual server environment variables.
4. If SMTP is not configured, the code uses native PHP `mail()`. This will only work when the hosting server supports and correctly configures PHP outgoing email, SPF/DKIM/DMARC and a legitimate from address.
5. Test the contact form and quote form on a live or local PHP server with email delivery configured. Browser file:// or Python static server cannot execute PHP.
6. Social links are **intentionally placeholders**. Replace the href="#" values in `js/site.js` once you know the official Facebook/LinkedIn/Instagram/YouTube URLs.
7. Most illustrative photos load remotely from Unsplash; browser internet access is required for those photos. For fully offline deployment, download and replace them with properly licensed assets.
8. Project descriptions are based on publicly visible site features. Illustrative photos are not represented as screenshots or proof of project outcomes.

## Security
- Previous package contained credentials directly in the PHP handler. The handler is replaced, the `.git` history excluded, and credentials are **not included** in this package. Rotate the old SMTP password immediately if it was real or in use.
- CSRF tokens and basic form throttling, input validation and honeypot are included. For high-volume sites, add server-side rate limits and spam protection.
- Keep `config.php` and `vendor/` managed appropriately on the production server; avoid including private files in public downloads.
- Verify that HTTPS is enabled and both form submissions arrive in `info@globaltechxsolutions.com` before calling the forms production-ready.

## Contacts
Qaiser: +92 315 9823601 / Qaiskhan8080@gmail.com
Asad: +92 333 9966130 / asadghalib6@gmail.com
Office: Darmangi Garden, Warsak Road, Peshawar, Pakistan.


## Opening the website locally (Windows)
Unzip the full archive, preserving the `css`, `js`, `images`, `projects`, `services` and other folders.
Double-click `globaltechx-website/index.html`. Pages, styles, and the JavaScript header/footer now use paths compatible with `file://` preview and also hosting from a subdirectory.

**Important:** Local preview does not execute PHP. Contact and Get a Quote submissions need PHP hosting with the email settings configured. The forms will only work when deployed on a PHP-capable server. External fonts, icon libraries, and photos require internet access.
