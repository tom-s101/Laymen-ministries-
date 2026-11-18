# Laymen Ministries — Volunteer Page & Online Application

A new volunteer page for lmn.org, plus an online application that replaces the print-and-email PDF forms. Applications are saved on the server, emailed to the office, and reviewed in a password-protected admin page.

## What's included

| File / folder | What it is |
| --- | --- |
| `volunteer.html` | The volunteer page: serving in the Philippines, what to expect, programs, how to apply, guidelines, testimonial, gallery, FAQ |
| `volunteer-apply.html` | The step-by-step application form |
| `assets/` | Stylesheet, scripts, logo, icons and photos used by both pages |
| `api/submit.php` | Receives applications, saves them and emails the office |
| `api/lib.php` | Shared code for saving, emailing and the printable copy |
| `api/config.sample.php` | Settings template (copy to `api/config.php`) |
| `admin/` | Admin page to review applications, set a status, add notes and print or save as PDF |
| `Library/site-header.lbi` | Dreamweaver Library item for the top menu, shared by both pages |
| `storage/applications/` | Where submitted applications are saved (one file per application) |

**Requirements:** PHP 8.0 or newer (the store already runs PHP), with PHP's `mail()` function enabled. No database and nothing to install.

> The pages can be previewed on any static host (e.g. Netlify), but **submitting the form and the admin page only work on a PHP server** like the lmn.org host.

## Installation

1. **Back up the live site** before uploading anything.
2. **Check for folder name clashes.** If the site already has folders named `assets`, `api`, `admin` or `storage`, rename this project's folders and update the paths before uploading so nothing gets overwritten.
3. **Upload** everything to the site root, next to `missions.html`. The steps for Dreamweaver are below. If you'd like to review it first, upload to a test folder such as `/volunteer-preview/`. It works from a subfolder too.
4. **Create the settings file.** Copy `api/config.sample.php` to `api/config.php` and fill in:
   - `admin_emails`: who receives new applications, e.g. `['office@lmn.org', 'someone@lmn.org']`
   - `mail_from`: an address on the lmn.org domain, e.g. `Laymen Ministries <no-reply@lmn.org>`
   - `admin_password_hash`: the admin password, scrambled (see below)
   - `storage_dir`: where applications are saved (see step 5)
   - `admin_url`: the full address of the admin page, e.g. `https://www.lmn.org/admin/`
5. **Applications folder.** The folder in `storage_dir` must be writable by the server (permissions 755 or 775). Applications contain personal information, so it's best to point `storage_dir` at a folder **outside** the public website folder. If it stays at `storage/applications`, the included `.htaccess` blocks web access on Apache servers. On Nginx, add an equivalent deny rule.
6. **Use HTTPS** for the volunteer pages and the admin page.

### Creating the admin password hash

The password is stored scrambled, not as plain text. Either:

- **With a terminal** (many hosts have one in cPanel): `php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"`
- **Without a terminal:** upload a temporary file named `hash.php` containing `<?php echo password_hash('your-password', PASSWORD_DEFAULT);`, open it in a browser, copy the result, then **delete the file right away**.

Paste the result between the quotes for `admin_password_hash` in `api/config.php`.

## Adding it in Dreamweaver

No conversion is needed: Dreamweaver opens these HTML, CSS, JavaScript and PHP files directly.

1. **Open the existing lmn.org site** in Dreamweaver (Site → Manage Sites), the same site definition used to publish lmn.org.
2. **Copy the files into the site's local root folder**, the folder that contains `missions.html`: `volunteer.html`, `volunteer-apply.html`, and the `assets`, `api`, `admin`, `storage` and `Library` folders. If the site already has a `Library` folder, just add `site-header.lbi` to it.
3. **Create `api/config.php`**: open `api/config.sample.php`, choose File → Save As, name it `config.php`, then fill in the settings (see step 4 above).
4. **Upload** with the Files panel: select the new files and folders and click **Put** (the up arrow). Make sure these hidden files get uploaded too: `storage/.htaccess`, `admin/.htaccess` and `storage/applications/.gitkeep`. On a Mac, Finder hides files starting with a dot, so check they're in the folders before copying.
5. **Don't download submitted applications** to your computer with Get. Applications are created on the server; leave `storage/applications` there. Dreamweaver's cloaking (right-click the folder → Cloaking → Cloak) keeps Get and Put from touching it after the first upload.

### Editing the menu

The top menu on both pages is a **Dreamweaver Library item** (`Library/site-header.lbi`). To change it, open it from the **Assets panel → Library**, edit and save. Dreamweaver offers to update both pages automatically; click **Update**, then Put both pages. Don't edit the menu directly inside `volunteer.html` or `volunteer-apply.html`; Dreamweaver locks that area and will replace it with the Library item.

## Testing after upload

1. Fill out and submit a test application on `volunteer-apply.html`.
2. Check that the email arrives at each address in `admin_emails` (look in spam too). If nothing arrives, the host's email setup needs attention; see "Email" below.
3. Log in at `/admin/` and confirm the test application shows up. Try changing its status and adding a note.
4. Delete the test application's file from the applications folder.

## Linking it from the main site

- Add **"Volunteer & Apply Online"** (linking to `volunteer.html`) under **Mission Projects** in the main site menu. If the site uses a Dreamweaver template, adding it to the template updates every page.
- Add an **"Apply online"** button on `missions.html` next to the PDF applications.

## Code to review or edit

- **Menu:** the top menu copies the lmn.org menu and lives in `Library/site-header.lbi` (see "Editing the menu" above). It's separate from the main site's menu, so **if the main menu changes later, update the Library item too.**
  - **Days of Noah** links to the home page because the real page address wasn't known. Replace `https://www.lmn.org/` on the Days of Noah line with the correct link.
  - **About** only lists About Us and LM Productions History. Add any other pages from the real dropdown.
  - All menu links are full `https://www.lmn.org/...` addresses so they work from a preview copy. They can stay that way.
- **Colors and fonts** are set at the top of `assets/css/volunteer.css`: rust `#893600`, gold `#ffc55f` and the text colors, all taken from lmn.org. Fonts load from Google Fonts.
- **Application questions** live in `volunteer-apply.html`. To add a question, copy an existing field block and give it a new `name` and `id`. It automatically appears in the review step, the email, the admin page and the printable copy.
- **Photos** are in `assets/img/gallery/` (resized for the web). Gallery captions are the `alt` text on each photo in `volunteer.html`.

## Content to confirm before going live

- **Guidelines** and **FAQ answers** (costs, timelines, ages, "apply 3–4 months ahead") were written from general information. Please check them against the official guidelines and PDF applications.
- **"Our schools" card** and the three cards under it (5 elementary schools, 1 academy, teaching focus).
- **Application questions** compared with the paper Student and Long-Term applications.
- **Photo permission:** many photos show children's faces. Please confirm the ministry is comfortable showing them publicly; the page's own guidelines ask missionaries not to post photos of children without permission.

## How it works

- **Applicants** fill out 7 short steps. Their progress saves in their browser, so they can come back later on the same device. Applicants under 18 are asked for parent or guardian consent automatically.
- **On submit,** the application is saved as a file in the applications folder, and each address in `admin_emails` gets an email with the full application and a printable copy attached. The applicant sees a reference number like `LMN-2026-7K3QZP`.
- **Admins** sign in at `/admin/` with the shared password to view applications, set a status (New → Reviewing → References requested → Interview scheduled → Accepted / Declined / Withdrawn), keep notes, and print or save each application as a PDF.

## Things to know

- **Email** uses PHP's built-in `mail()`. If messages go to spam or don't arrive, the host may need its email setup adjusted, or the code can be changed to send through an existing mailbox (SMTP).
- **Spam:** there's no captcha or spam filter. If fake submissions start coming in, adding a hidden honeypot field or a captcha is the simplest fix.
- **Backups:** applications are plain files in the applications folder. Include that folder in the site's regular backups.
- **Never share `api/config.php`.** It holds the email settings and password hash, and it's excluded from the code repository on purpose.
