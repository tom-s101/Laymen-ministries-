<?php
// Copy to config.php. Password hash: php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
return [
    'admin_emails' => ['office@lmn.org'],
    'mail_from' => 'Laymen Ministries <no-reply@lmn.org>',
    'admin_password_hash' => '',
    'storage_dir' => __DIR__ . '/../storage/applications',
    'admin_url' => 'https://www.lmn.org/admin/',
];
