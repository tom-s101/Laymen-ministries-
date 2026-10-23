<?php
// Copy this file to config.php and fill in real values.
// config.php is git-ignored so passwords never end up in the repository.

return [
    // Where application notifications are sent. Add as many reviewers as you like.
    'admin_emails' => ['office@lmn.org'],

    // "From" address for outgoing mail. Use an address on the lmn.org domain
    // so messages aren't flagged as spam.
    'mail_from' => 'Laymen Ministries <no-reply@lmn.org>',

    // Email the applicant a confirmation with a copy of their answers.
    'send_applicant_copy' => true,

    // Admin dashboard password hash. Generate one with:
    //   php -r "echo password_hash('your-strong-password', PASSWORD_DEFAULT), PHP_EOL;"
    'admin_password_hash' => '',

    // Where submitted applications are stored. Ideally a folder OUTSIDE the
    // public web root, e.g. '/home/lmn/volunteer-applications'.
    'storage_dir' => __DIR__ . '/../storage/applications',

    // Max submissions per IP address per hour (basic spam protection).
    'rate_limit_per_hour' => 5,

    // Public URL of the admin dashboard, used for links in notification emails.
    'admin_url' => 'https://www.lmn.org/admin/',
];
