<?php
const LMN_STATUSES = ['New', 'Reviewing', 'References requested', 'Interview scheduled', 'Accepted', 'Declined', 'Withdrawn'];

function lmn_config()
{
    static $config;
    return $config ??= require __DIR__ . '/config.php';
}

function lmn_storage_dir()
{
    $dir = rtrim(lmn_config()['storage_dir'], '/');
    is_dir($dir) || mkdir($dir, 0750, true);
    return $dir;
}

function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function lmn_new_id()
{
    return 'LMN-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function lmn_load($id)
{
    $file = lmn_storage_dir() . "/$id.json";
    return preg_match('/^LMN-\d{4}-[A-Z0-9]{6}$/', $id) && is_file($file) ? json_decode(file_get_contents($file), true) : null;
}

function lmn_save($app)
{
    file_put_contents(lmn_storage_dir() . "/{$app['id']}.json", json_encode($app, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function lmn_all()
{
    $apps = array_map(fn($f) => json_decode(file_get_contents($f), true), glob(lmn_storage_dir() . '/LMN-*.json'));
    usort($apps, fn($a, $b) => strcmp($b['submitted_at'], $a['submitted_at']));
    return $apps;
}

function lmn_applicant_name($app)
{
    return trim(($app['data']['first_name'] ?? '') . ' ' . ($app['data']['last_name'] ?? ''));
}

function lmn_render_copy($app, $standalone = true)
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
        $sections .= '<h2 style="font-size:16px;margin:28px 0 6px;color:#893600;">' . h($sec['title']) . '</h2>'
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
        . '<div style="border-bottom:3px solid #893600;padding-bottom:12px;margin-bottom:8px;">'
        . '<div style="font-size:12px;letter-spacing:.08em;color:#893600;font-weight:600;">LAYMEN MINISTRIES</div>'
        . '<h1 style="font-size:24px;margin:4px 0;">' . h($d['track'] ?? 'Volunteer') . ' Application</h1>'
        . '<div style="font-size:13px;color:#6e6e73;">Reference <strong>' . h($app['id']) . '</strong> &middot; Submitted ' . h($submitted) . '</div>'
        . '</div>'
        . $sections
        . '<h2 style="font-size:16px;margin:28px 0 6px;color:#893600;">Agreement &amp; signature</h2>'
        . '<ul style="list-style:none;padding:0;margin:0 0 12px;font-size:14px;line-height:1.8;">' . $agreements . '</ul>'
        . '<p style="font-size:14px;margin:0;">Signed electronically: <strong style="font-family:Georgia,serif;font-size:18px;font-style:italic;">' . h($d['signature'] ?? '') . '</strong></p>'
        . '<p style="font-size:12px;color:#6e6e73;margin-top:4px;">Date: ' . h($d['signed_date'] ?? '') . '</p>'
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

function lmn_mail($to, $subject, $html, $attachment = null, $replyTo = null)
{
    $headers = ['From: ' . lmn_config()['mail_from'], 'MIME-Version: 1.0'];
    if ($replyTo) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    if ($attachment) {
        $boundary = 'lmn_' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
        $body = "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--$boundary\r\nContent-Type: text/html; charset=UTF-8; name=\"{$attachment['name']}\"\r\n"
            . "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($attachment['content']))
            . "--$boundary--";
    } else {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $body = chunk_split(base64_encode($html));
    }
    @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
}
