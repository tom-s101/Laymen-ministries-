# Laymen Ministries — Volunteer pages

A modern volunteer page and online application for [lmn.org](https://www.lmn.org), replacing the print-fill-scan-email PDF workflow.

| Page | What it does |
| --- | --- |
| `volunteer.html` | What it's like to volunteer, where you can serve, the two programs, how the process works, guidelines, FAQ |
| `volunteer-apply.html` | Step-by-step application form, styled like an online checkout: progress bar, autosave, review screen and typed e-signature |
| `api/submit.php` | Receives applications, checks the required answers, saves them and emails the team and the applicant |
| `admin/` | Password-protected dashboard to review applications, set a status, add notes, print or save as PDF, and export to CSV |

There is no build step and nothing to install: plain HTML/CSS/JS with a small PHP backend, so it runs on the same PHP hosting as the existing site (the store already runs PHP).

## How an application flows

1. The applicant picks **Student Missionary** (17+) or **Long-Term Missionary** (18+) and works through 7 short steps. Answers save in their browser, so they can leave and come back.
2. Questions appear only when they apply. For example, if the applicant is under 18, the parent/guardian consent section shows up automatically.
3. On submit, the server checks the answers again, saves the application as JSON and builds a **digital copy** that mirrors the paper form.
4. The team gets an email with the full application and the copy attached. The applicant gets a confirmation with their answers and a reference number such as `LMN-2026-7K3QZP`.
5. Reviewers open `/admin/` to read the application, change its status (New → Reviewing → References requested → Interview scheduled → Accepted / Declined) and keep notes. Use **Print / Save PDF** to file a PDF copy.

## Installing on lmn.org

1. Upload all files to the site root next to `missions.html`. The page links (`index.html`, `aboutus.html`, `missions.html`, `catalog/…`) already match the existing site.
2. Copy `api/config.sample.php` to `api/config.php` and edit it:
   - `admin_emails`: who gets notified
   - `mail_from`: an `@lmn.org` address
   - `admin_password_hash`: run `php -r "echo password_hash('choose-a-password', PASSWORD_DEFAULT), PHP_EOL;"` and paste the result
   - `storage_dir`: **best placed outside the public web folder**, because applications contain personal information. If it stays at `storage/applications`, the included `.htaccess` blocks web access on Apache.
3. Make sure PHP can write to `storage_dir`.
4. Add a **Volunteer** link to the main site navigation and a "Apply online" button on `missions.html`.
5. Use HTTPS (the dashboard sets secure cookies when HTTPS is on).

Email goes through PHP's built-in `mail()`. If the host's mail ends up in spam, switch to SMTP (for example, PHPMailer with the domain's mailbox).

### Testing locally

```bash
cp api/config.sample.php api/config.php   # then set a password hash
php -S 127.0.0.1:8000
# open http://127.0.0.1:8000/volunteer.html  and  /admin/
```

## Matching the main site

All colors are defined at the top of `assets/css/volunteer.css` (`--brand` navy, `--accent` gold). Change those values to match lmn.org's exact colors and logo. Dark mode is supported automatically.

## Content to confirm before launch

I couldn't reach lmn.org while building this, so the text was drafted from what's public about the ministry. Please check these against the official PDF applications and guidelines:

- **Guidelines** (`volunteer.html#guidelines`): lifestyle, dress, relationships, vegetarian meals, support raising
- **FAQ answers**: costs, timelines, ages, the "apply 3–4 months ahead" advice
- **Country descriptions** and which countries currently take volunteers
- **Application questions**: compare with the paper Student and Long-Term applications and add or remove fields as needed. Any field added to the form automatically appears in the review step, the emails, the admin view and the PDF copy.
- The **hero stats** (7 countries, 120+ workers, since 1988)
