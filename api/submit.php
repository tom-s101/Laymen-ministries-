<?php
// Receives an online volunteer application, stores it and emails the team.
declare(strict_types=1);

require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$raw = file_get_contents('php://input', false, null, 0, 200_000);
$payload = json_decode($raw ?: '', true);
if (!is_array($payload) || !isset($payload['data'], $payload['sections']) || !is_array($payload['data']) || !is_array($payload['sections'])) {
    respond(400, ['ok' => false, 'error' => 'Invalid request']);
}

// Honeypot: real people never fill this hidden field. Pretend success.
if (!empty($payload['website'])) {
    respond(200, ['ok' => true, 'id' => 'LMN-' . date('Y') . '-RECEIVED']);
}

$cfg = lmn_config();
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Simple per-IP rate limit.
$rateFile = lmn_storage_dir() . '/.rate-' . hash('sha256', $ip);
$hits = array_filter(
    is_file($rateFile) ? (json_decode((string) file_get_contents($rateFile), true) ?: []) : [],
    fn($t) => $t > time() - 3600
);
if (count($hits) >= (int) $cfg['rate_limit_per_hour']) {
    respond(429, ['ok' => false, 'error' => 'Too many submissions, please try again later']);
}

// ---- Sanitize ----
$clean = function ($v, int $max = 3000) {
    if (is_bool($v)) {
        return $v;
    }
    if (is_array($v)) {
        return array_values(array_map(fn($x) => mb_substr(trim((string) $x), 0, 200), array_slice($v, 0, 30)));
    }
    return mb_substr(trim(str_replace("\0", '', (string) $v)), 0, $max);
};

$data = [];
foreach ($payload['data'] as $k => $v) {
    if (is_string($k) && preg_match('/^[a-z0-9_]{1,40}$/', $k)) {
        $data[$k] = $clean($v);
    }
}

$sections = [];
foreach (array_slice($payload['sections'], 0, 12) as $sec) {
    if (!is_array($sec) || !isset($sec['title'], $sec['fields']) || !is_array($sec['fields'])) {
        continue;
    }
    $fields = [];
    foreach (array_slice($sec['fields'], 0, 60) as $f) {
        if (isset($f['label'], $f['value'])) {
            $fields[] = [
                'name' => $clean($f['name'] ?? '', 40),
                'label' => $clean($f['label'], 200),
                'value' => $clean($f['value']),
            ];
        }
    }
    $sections[] = ['title' => $clean($sec['title'], 100), 'fields' => $fields];
}

// ---- Validate the essentials server-side ----
$required = [
    'track', 'start_date', 'duration', 'first_name', 'last_name', 'dob', 'gender', 'marital_status',
    'email', 'phone', 'address1', 'city', 'state', 'postal', 'country', 'citizenship', 'passport',
    'education_level', 'church_name', 'church_location', 'denomination', 'pastor_name', 'baptized',
    'testimony', 'why_serve', 'health', 'substances', 'convictions',
    'emergency_name', 'emergency_relationship', 'emergency_phone',
    'ref1_name', 'ref1_email', 'ref1_phone', 'ref2_name', 'ref2_email', 'ref2_phone',
    'ref3_name', 'ref3_email', 'ref3_phone', 'signature',
];
$missing = array_values(array_filter($required, fn($k) => empty($data[$k])));
foreach (['agree_truth', 'agree_guidelines', 'agree_contact'] as $k) {
    if (($data[$k] ?? false) !== true) {
        $missing[] = $k;
    }
}
if (!in_array($data['track'] ?? '', ['Student Missionary', 'Long-Term Missionary'], true)) {
    $missing[] = 'track';
}
if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
    $missing[] = 'email';
}
$age = null;
if (!empty($data['dob']) && ($dob = DateTime::createFromFormat('Y-m-d', $data['dob']))) {
    $age = $dob->diff(new DateTime('today'))->y;
    $minAge = ($data['track'] ?? '') === 'Long-Term Missionary' ? 18 : 17;
    if ($age < $minAge) {
        $missing[] = 'dob';
    }
    if ($age < 18 && (empty($data['guardian_name']) || !filter_var($data['guardian_email'] ?? '', FILTER_VALIDATE_EMAIL))) {
        $missing[] = 'guardian';
    }
}
if ($missing) {
    respond(422, ['ok' => false, 'error' => 'Some required answers are missing', 'fields' => array_values(array_unique($missing))]);
}

// ---- Store ----
$app = [
    'id' => lmn_new_id(),
    'submitted_at' => date('c'),
    'status' => 'New',
    'notes' => [],
    'data' => $data,
    'sections' => $sections,
    'meta' => [
        'ip' => $ip,
        'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
        'age_at_submission' => $age,
    ],
];
lmn_save($app);

$hits[] = time();
file_put_contents($rateFile, json_encode(array_values($hits)), LOCK_EX);

// ---- Email ----
$name = lmn_applicant_name($app);
$copy = lmn_render_copy($app);
$attachment = ['name' => $app['id'] . '.html', 'type' => 'text/html; charset=UTF-8', 'content' => $copy];

$adminIntro = '<p style="font-family:Arial,sans-serif;font-size:14px;">New <strong>' . h($data['track']) . '</strong> application from <strong>'
    . h($name) . '</strong> (' . h($data['email']) . ').<br>School preference: ' . h(($data['school_pref'] ?? '') ?: 'Not given')
    . ' &middot; Start: ' . h($data['start_date']) . ' &middot; ' . h($data['duration'])
    . '<br><a href="' . h(rtrim($cfg['admin_url'], '/') . '/?id=' . $app['id']) . '">Open in the review dashboard</a></p><hr>';

foreach ((array) $cfg['admin_emails'] as $to) {
    lmn_mail($to, "New volunteer application: $name ({$app['id']})", $adminIntro . lmn_render_copy($app, false), $attachment, $data['email']);
}

if (!empty($cfg['send_applicant_copy'])) {
    $intro = '<div style="font-family:Arial,sans-serif;font-size:15px;max-width:760px;margin:0 auto 20px;">'
        . '<p>Hi ' . h($data['first_name']) . ',</p>'
        . '<p>Thank you for applying to serve with Laymen Ministries! We\'ve received your application and our team will be in touch within 1–2 weeks. '
        . 'Your reference number is <strong>' . h($app['id']) . '</strong>.</p>'
        . '<p>A copy of your answers is below for your records.</p></div>';
    lmn_mail($data['email'], 'We received your volunteer application', $intro . lmn_render_copy($app, false));
}

respond(200, ['ok' => true, 'id' => $app['id']]);
