<?php
// Shared helpers for the volunteer application backend.

const LMN_STATUSES = ['New', 'Reviewing', 'References requested', 'Interview scheduled', 'Accepted', 'Declined', 'Withdrawn'];

function lmn_config(): array
{
    static $config;
    if ($config === null) {
        $file = __DIR__ . '/config.php';
        if (!is_file($file)) {
            $file = __DIR__ . '/config.sample.php';
        }
        $config = require $file;
    }
    return $config;
}

function lmn_storage_dir(): string
{
    $dir = rtrim(lmn_config()['storage_dir'], '/');
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    return $dir;
}

function h($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function lmn_valid_id(string $id): bool
{
    return (bool) preg_match('/^LMN-\d{4}-[A-Z0-9]{6}$/', $id);
}

function lmn_new_id(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $suffix = '';
        for ($i = 0; $i < 6; $i++) {
            $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $id = 'LMN-' . date('Y') . '-' . $suffix;
    } while (is_file(lmn_storage_dir() . "/$id.json"));
    return $id;
}

function lmn_load(string $id): ?array
{
    if (!lmn_valid_id($id)) {
        return null;
    }
    $file = lmn_storage_dir() . "/$id.json";
    if (!is_file($file)) {
        return null;
    }
    return json_decode(file_get_contents($file), true) ?: null;
}

function lmn_save(array $app): void
{
    $file = lmn_storage_dir() . '/' . $app['id'] . '.json';
    file_put_contents($file, json_encode($app, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    @chmod($file, 0640);
}

/** @return array<int, array> newest first */
function lmn_all(): array
{
    $apps = [];
    foreach (glob(lmn_storage_dir() . '/LMN-*.json') ?: [] as $file) {
        $app = json_decode(file_get_contents($file), true);
        if ($app) {
            $apps[] = $app;
        }
    }
    usort($apps, fn($a, $b) => strcmp($b['submitted_at'], $a['submitted_at']));
    return $apps;
}

function lmn_applicant_name(array $app): string
{
    $d = $app['data'];
    return trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
}

/**
 * The "digital copy" of an application: a self-contained HTML document that
 * mirrors the paper form. Used in emails, the admin view and for printing to PDF.
 */
function lmn_render_copy(array $app, bool $standalone = true): string
{
    $d = $app['data'];
    $submitted = date('F j, Y \a\t g:i A T', strtotime($app['submitted_at']));
    $sections = '';
    foreach ($app['sections'] as $sec) {
        $rows = '';
        foreach ($sec['fields'] as $f) {
            $rows .= '<tr><th style="text-align:left;vertical-align:top;width:36%;padding:8px 12px 8px 0;border-top:1px solid #e8e8ed;color:#6e6e73;font-weight:500;font-size:13px;">'
                . h($f['label']) . '</th><td style="padding:8px 0;border-top:1px solid #e8e8ed;font-size:14px;white-space:pre-wrap;">'
                . h($f['value']) . '</td></tr>';
        }
        $sections .= '<h2 style="font-size:16px;margin:28px 0 6px;color:#1f3a5f;">' . h($sec['title']) . '</h2>'
            . '<table style="width:100%;border-collapse:collapse;">' . $rows . '</table>';
    }

    $agreements = '';
    foreach ([
        'agree_truth' => 'Information is true and complete',
        'agree_guidelines' => 'Agrees to follow volunteer guidelines',
        'agree_contact' => 'Authorizes reference contact / background check',
    ] as $k => $label) {
        $agreements .= '<li>' . (!empty($d[$k]) ? '&#9745;' : '&#9744;') . ' ' . h($label) . '</li>';
    }

    $body = '<div style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Arial,sans-serif;color:#1d1d1f;max-width:760px;margin:0 auto;">'
        . '<div style="border-bottom:3px solid #1f3a5f;padding-bottom:12px;margin-bottom:8px;">'
        . '<div style="font-size:12px;letter-spacing:.08em;color:#c8952e;font-weight:600;">LAYMEN MINISTRIES</div>'
        . '<h1 style="font-size:24px;margin:4px 0;">' . h($d['track'] ?? 'Volunteer') . ' Application</h1>'
        . '<div style="font-size:13px;color:#6e6e73;">Reference <strong>' . h($app['id']) . '</strong> &middot; Submitted ' . h($submitted) . '</div>'
        . '</div>'
        . $sections
        . '<h2 style="font-size:16px;margin:28px 0 6px;color:#1f3a5f;">Agreement &amp; signature</h2>'
        . '<ul style="list-style:none;padding:0;margin:0 0 12px;font-size:14px;line-height:1.8;">' . $agreements . '</ul>'
        . '<p style="font-size:14px;margin:0;">Signed electronically: <strong style="font-family:Georgia,serif;font-size:18px;font-style:italic;">' . h($d['signature'] ?? '') . '</strong></p>'
        . '<p style="font-size:12px;color:#6e6e73;margin-top:4px;">Date: ' . h($d['signed_date'] ?? '') . ' &middot; IP ' . h($app['meta']['ip'] ?? '') . '</p>'
        . '<p style="font-size:11px;color:#86868b;margin-top:28px;border-top:1px solid #e8e8ed;padding-top:10px;">Laymen Ministries &middot; 414 Zapada Rd., St. Maries, ID 83861 &middot; (208) 245-5388 &middot; office@lmn.org</p>'
        . '</div>';

    if (!$standalone) {
        return $body;
    }
    return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . h($app['id']) . ' - ' . h(lmn_applicant_name($app)) . '</title>'
        . '<style>body{margin:0;padding:32px 20px;background:#fff}@media print{body{padding:0}}</style></head><body>'
        . $body . '</body></html>';
}

/** Send an HTML email, optionally with one attachment. Uses PHP mail(). */
function lmn_mail(string $to, string $subject, string $html, ?array $attachment = null, ?string $replyTo = null): bool
{
    $cfg = lmn_config();
    $boundary = 'lmn_' . bin2hex(random_bytes(12));
    $headers = [
        'From: ' . $cfg['mail_from'],
        'MIME-Version: 1.0',
    ];
    if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    if ($attachment) {
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
        $body = "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--$boundary\r\nContent-Type: {$attachment['type']}; name=\"{$attachment['name']}\"\r\n"
            . "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($attachment['content']))
            . "--$boundary--";
    } else {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $body = chunk_split(base64_encode($html));
    }

    $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}
