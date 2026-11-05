<?php
require __DIR__ . '/../api/lib.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

$cfg = lmn_config();
$self = strtok($_SERVER['REQUEST_URI'], '?');
$error = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: $self");
    exit;
}
if (isset($_POST['password'])) {
    if ($cfg['admin_password_hash'] && password_verify($_POST['password'], $cfg['admin_password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header("Location: {$_SERVER['REQUEST_URI']}");
        exit;
    }
    $error = 'Incorrect password.';
}
$authed = !empty($_SESSION['admin']);

if ($authed && isset($_POST['id']) && ($app = lmn_load($_POST['id']))) {
    if (in_array($_POST['status'], LMN_STATUSES) && $app['status'] !== $_POST['status']) {
        $app['notes'][] = ['at' => date('c'), 'text' => 'Status changed to ' . $_POST['status'], 'system' => true];
        $app['status'] = $_POST['status'];
    }
    if (trim($_POST['note']) !== '') {
        $app['notes'][] = ['at' => date('c'), 'text' => trim($_POST['note'])];
    }
    lmn_save($app);
    header("Location: $self?id={$app['id']}");
    exit;
}

if ($authed && isset($_GET['print']) && ($app = lmn_load($_GET['id']))) {
    exit(str_replace('</body>', '<script>window.print()</script></body>', lmn_render_copy($app)));
}

function status_class($s)
{
    return match ($s) {
        'New' => 'new', 'Accepted' => 'ok', 'Declined', 'Withdrawn' => 'muted', default => 'progress',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">
  <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicon-32.png">
  <title>Volunteer Applications · Admin</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Noto+Serif:wght@400;700&display=swap">
  <link rel="stylesheet" href="../assets/css/volunteer.css">
  <style>
    .admin-wrap { padding: 32px 0 80px; }
    .admin-top { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
    .admin-top h1 { font-size: 32px; }
    .counts { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
    .counts a { font-size: 13px; padding: 6px 12px; border-radius: 980px; background: var(--surface); color: var(--text-muted); border: 1px solid var(--border-soft); }
    .counts a.on { background: var(--accent); color: var(--on-accent); border-color: var(--accent); }
    table.list { width: 100%; border-collapse: collapse; background: var(--surface); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
    .list th, .list td { text-align: left; padding: 14px 16px; border-bottom: 1px solid var(--border-soft); font-size: 15px; }
    .list th { font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: var(--text-muted); font-weight: 600; background: var(--bg-alt); }
    .list tr:hover td { background: var(--bg-alt); }
    .list a.row-link { color: var(--text); font-weight: 600; }
    .pill { font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 980px; white-space: nowrap; }
    .pill.new { background: #e8f0fe; color: #1a56c4; }
    .pill.progress { background: var(--accent-soft); color: #8a6212; }
    .pill.ok { background: #e6f4ea; color: #1e7e34; }
    .pill.muted { background: var(--bg-alt); color: var(--text-muted); }
    .detail { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 24px; align-items: start; }
    @media (max-width: 920px) { .detail { grid-template-columns: 1fr; } .list .hide-sm { display: none; } }
    .paper { background: #fff; color: #1d1d1f; border-radius: var(--radius-lg); padding: 36px; box-shadow: var(--shadow); }
    .side .card { margin-bottom: 16px; }
    .side label { display: block; font-size: 13px; color: var(--text-muted); margin: 10px 0 6px; }
    .side select, .side textarea { width: 100%; font: inherit; font-size: 15px; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); color: var(--text); }
    .side textarea { min-height: 90px; }
    .notes { list-style: none; padding: 0; margin: 0; font-size: 14px; }
    .notes li { padding: 10px 0; border-top: 1px solid var(--border-soft); white-space: pre-wrap; }
    .notes time { display: block; font-size: 12px; color: var(--text-muted); }
    .notes .sys { color: var(--text-muted); font-style: italic; }
    .login { max-width: 380px; margin: 12vh auto; }
    .empty { text-align: center; padding: 60px 20px; color: var(--text-muted); }
  </style>
</head>
<body class="apply-page">
  <header class="site-header" style="border-bottom:1px solid var(--border-soft);">
    <div class="container header-bar">
      <a class="brand" href="<?= h($self) ?>" style="gap:14px;"><img class="brand-logo" src="../assets/img/lmn-logo.png" alt="Laymen Ministries" width="686" height="52"> <span style="font-size:13px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);">Volunteer Admin</span></a>
      <?php if ($authed): ?><a href="?logout=1" style="font-size:13px;">Sign out</a><?php endif; ?>
    </div>
  </header>

  <main class="container admin-wrap">
<?php if (!$authed): ?>
    <div class="login form-card">
      <h1 style="font-size:26px;margin-bottom:6px;">Sign in</h1>
      <p class="muted" style="margin-top:0;">Review volunteer applications.</p>
      <?php if ($cfg['admin_password_hash'] === ''): ?>
        <div class="callout">No admin password is set yet. Copy <code>api/config.sample.php</code> to <code>api/config.php</code> and set <code>admin_password_hash</code>.</div>
      <?php endif; ?>
      <?php if ($error): ?><div class="form-alert show"><?= h($error) ?></div><?php endif; ?>
      <form method="post">
        <div class="field" style="margin-bottom:16px;"><input type="password" id="password" name="password" placeholder=" " required autofocus><label for="password">Password</label></div>
        <button class="btn btn-primary" style="width:100%;">Sign in</button>
      </form>
    </div>

<?php elseif (isset($_GET['id']) && ($app = lmn_load($_GET['id']))): ?>
    <div class="admin-top">
      <div>
        <a href="<?= h($self) ?>" style="font-size:14px;">‹ All applications</a>
        <h1><?= h(lmn_applicant_name($app)) ?></h1>
        <span class="pill <?= status_class($app['status']) ?>"><?= h($app['status']) ?></span>
        <span class="muted" style="font-size:14px;margin-left:8px;"><?= h($app['id']) ?> · Age <?= h($app['age'] ?? '—') ?></span>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a class="btn btn-ghost" href="mailto:<?= h($app['data']['email']) ?>">Email applicant</a>
        <a class="btn btn-primary" href="?id=<?= h($app['id']) ?>&amp;print=1" target="_blank" rel="noopener">Print / Save PDF</a>
      </div>
    </div>
    <div class="detail">
      <div class="paper"><?= lmn_render_copy($app, false) ?></div>
      <aside class="side">
        <form class="card" method="post">
          <h3 style="font-size:17px;">Review</h3>
          <input type="hidden" name="id" value="<?= h($app['id']) ?>">
          <label for="status">Status</label>
          <select id="status" name="status">
            <?php foreach (LMN_STATUSES as $s): ?><option<?= $s === $app['status'] ? ' selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?>
          </select>
          <label for="note">Add a note</label>
          <textarea id="note" name="note" placeholder="Interview notes, reference feedback…"></textarea>
          <button class="btn btn-primary btn-sm" style="margin-top:12px;">Save</button>
        </form>
        <div class="card">
          <h3 style="font-size:17px;">History</h3>
          <?php if (!$app['notes']): ?><p class="muted" style="font-size:14px;">No notes yet.</p><?php endif; ?>
          <ul class="notes">
            <?php foreach (array_reverse($app['notes']) as $n): ?>
              <li class="<?= !empty($n['system']) ? 'sys' : '' ?>"><time><?= h(date('M j, Y g:i A', strtotime($n['at']))) ?></time><?= h($n['text']) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </aside>
    </div>

<?php else:
    $all = lmn_all();
    $status = $_GET['status'] ?? '';
    $counts = array_count_values(array_column($all, 'status'));
    $apps = $status ? array_filter($all, fn($a) => $a['status'] === $status) : $all;
?>
    <div class="admin-top">
      <h1>Volunteer Applications</h1>
    </div>
    <div class="counts">
      <a href="<?= h($self) ?>" class="<?= $status === '' ? 'on' : '' ?>">All (<?= count($all) ?>)</a>
      <?php foreach (LMN_STATUSES as $s): if (empty($counts[$s])) continue; ?>
        <a href="?status=<?= urlencode($s) ?>" class="<?= $status === $s ? 'on' : '' ?>"><?= h($s) ?> (<?= (int) $counts[$s] ?>)</a>
      <?php endforeach; ?>
    </div>

    <?php if (!$apps): ?>
      <div class="card empty">No applications<?= $status ? ' with this status' : ' yet' ?>.</div>
    <?php else: ?>
    <table class="list">
      <thead><tr><th>Applicant</th><th class="hide-sm">Program</th><th class="hide-sm">Start</th><th>Status</th><th class="hide-sm">Submitted</th></tr></thead>
      <tbody>
        <?php foreach ($apps as $a): ?>
        <tr>
          <td><a class="row-link" href="?id=<?= h($a['id']) ?>"><?= h(lmn_applicant_name($a)) ?></a><br><span class="muted" style="font-size:13px;"><?= h($a['data']['email']) ?></span></td>
          <td class="hide-sm"><?= h(str_replace(' Missionary', '', $a['data']['track'])) ?></td>
          <td class="hide-sm"><?= h($a['data']['start_date']) ?></td>
          <td><span class="pill <?= status_class($a['status']) ?>"><?= h($a['status']) ?></span></td>
          <td class="hide-sm"><?= h(date('M j, Y', strtotime($a['submitted_at']))) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
<?php endif; ?>
  </main>
</body>
</html>
