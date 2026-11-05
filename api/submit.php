<?php
require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');

$payload = json_decode(file_get_contents('php://input'), true);
$data = $payload['data'] ?? null;
if (!is_array($data) || !is_array($payload['sections'] ?? null) || !filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit(json_encode(['ok' => false, 'error' => 'Invalid application']));
}

$app = [
    'id' => lmn_new_id(),
    'submitted_at' => date('c'),
    'status' => 'New',
    'notes' => [],
    'data' => $data,
    'sections' => $payload['sections'],
    'age' => (new DateTime($data['dob']))->diff(new DateTime())->y,
];
lmn_save($app);

$cfg = lmn_config();
$name = lmn_applicant_name($app);
$intro = '<p style="font-family:Arial,sans-serif;font-size:14px;">New <strong>' . h($data['track']) . '</strong> application from <strong>'
    . h($name) . '</strong> (' . h($data['email']) . ').<br>Start: ' . h($data['start_date']) . ' &middot; ' . h($data['duration'])
    . '<br><a href="' . h($cfg['admin_url'] . '?id=' . $app['id']) . '">Open in the review dashboard</a></p><hr>';
foreach ($cfg['admin_emails'] as $to) {
    lmn_mail($to, "New volunteer application: $name ({$app['id']})", $intro . lmn_render_copy($app, false), ['name' => "{$app['id']}.html", 'content' => lmn_render_copy($app)], $data['email']);
}

echo json_encode(['ok' => true, 'id' => $app['id']]);
