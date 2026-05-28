<?php
// ============================================================
// u.php  —  Public profile viewer
// URL:  /u/username   (via .htaccess rewrite)
//  or:  /u.php?u=username
// ============================================================
require_once __DIR__ . '/includes/db.php';

// Base path so links work whether installed at root or in a subdirectory
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$home = $base . '/index.php';

function bannerCSSPhp(string $style, string $c1, string $c2, int $angle): string {
    switch ($style) {
        case 'solid':    return "background:{$c1};";
        case 'mesh':     return "background:radial-gradient(ellipse at 20% 50%,{$c1} 0%,transparent 60%),radial-gradient(ellipse at 80% 20%,{$c2} 0%,transparent 55%),radial-gradient(ellipse at 60% 80%,{$c1}88 0%,transparent 50%);background-color:{$c2}22;";
        case 'wave':     return "background:linear-gradient({$angle}deg,{$c1} 0%,{$c2} 50%,{$c1} 100%);";
        case 'dots':     return "background-color:{$c1}22;background-image:radial-gradient({$c2} 1.5px,transparent 1.5px);background-size:18px 18px;";
        case 'none':     return "background:#1c202e;";
        default:         return "background:linear-gradient({$angle}deg,{$c1},{$c2});"; // gradient
    }
}

$username = trim($_GET['u'] ?? '');
if (!$username || !preg_match('/^[a-zA-Z0-9_]{1,32}$/', $username)) {
    http_response_code(404);
    $error = 'User not found.';
} else {
    $stmt = db()->prepare('SELECT id,username,display_name,bio,avatar_color,avatar_emoji,avatar_url,banner_style,banner_color1,banner_color2,banner_angle,role,created_at FROM users WHERE username=? LIMIT 1');
    $stmt->execute([$username]);
    $profile = $stmt->fetch();
    if (!$profile) {
        http_response_code(404);
        $error = 'User "' . htmlspecialchars($username) . '" not found.';
    }
}

// Pull attempt stats if user found
if (!empty($profile)) {
    $uid = (int)$profile['id'];

    // Total / correct attempts
    $row = db()->prepare('SELECT COUNT(*) AS total, SUM(correct) AS correct FROM attempts WHERE user_id=?');
    $row->execute([$uid]);
    $stats = $row->fetch();
    $total   = (int)$stats['total'];
    $correct = (int)$stats['correct'];
    $acc     = $total ? round($correct / $total * 100) : 0;

    // Unique problems
    $uq = db()->prepare('SELECT COUNT(DISTINCT problem_id) AS unique_p FROM attempts WHERE user_id=?');
    $uq->execute([$uid]);
    $unique = (int)$uq->fetch()['unique_p'];

    // Tests
    $tc = db()->prepare('SELECT COUNT(*) AS cnt, MAX(score) AS best FROM tests WHERE user_id=?');
    $tc->execute([$uid]);
    $testRow  = $tc->fetch();
    $testCount = (int)$testRow['cnt'];
    $bestScore = $testRow['best'];

    // Streak — get all attempt dates
    $dates = db()->prepare('SELECT DISTINCT DATE(attempted_at) AS d FROM attempts WHERE user_id=? ORDER BY d DESC');
    $dates->execute([$uid]);
    $dateset = array_column($dates->fetchAll(), 'd');
    $streak = 0;
    $check  = date('Y-m-d');
    foreach ($dateset as $d) {
        if ($d === $check) { $streak++; $check = date('Y-m-d', strtotime($check . ' -1 day')); }
        elseif ($d < $check) break;
    }

    // Daily counts for heatmap (last 182 days)
    $heatStmt = db()->prepare(
        'SELECT DATE(attempted_at) AS d, COUNT(*) AS cnt
         FROM attempts WHERE user_id=? AND attempted_at >= DATE_SUB(NOW(), INTERVAL 182 DAY)
         GROUP BY DATE(attempted_at)'
    );
    $heatStmt->execute([$uid]);
    $heatData = [];
    foreach ($heatStmt->fetchAll() as $r) $heatData[$r['d']] = (int)$r['cnt'];

    // Accuracy by competition (top 8 with 3+ attempts)
    $compStmt = db()->prepare(
        'SELECT a.problem_id, a.correct
         FROM attempts a WHERE a.user_id=?'
    );
    $compStmt->execute([$uid]);
    $rawAttempts = $compStmt->fetchAll();

    // Achievements
    $achievements = [
        ['icon'=>'🎯','name'=>'First Blood',   'desc'=>'Solved first problem',       'earned'=> $total >= 1],
        ['icon'=>'🔟','name'=>'Getting Started','desc'=>'10 problems attempted',      'earned'=> $total >= 10],
        ['icon'=>'💯','name'=>'Century',        'desc'=>'100 problems attempted',     'earned'=> $total >= 100],
        ['icon'=>'⚡','name'=>'Grind Mode',     'desc'=>'500 problems attempted',     'earned'=> $total >= 500],
        ['icon'=>'🏹','name'=>'Sharpshooter',   'desc'=>'80%+ accuracy (min 20)',     'earned'=> $total >= 20 && $acc >= 80],
        ['icon'=>'✨','name'=>'Perfectionist',  'desc'=>'90%+ accuracy (min 50)',     'earned'=> $total >= 50 && $acc >= 90],
        ['icon'=>'🔥','name'=>'On Fire',        'desc'=>'3-day streak',               'earned'=> $streak >= 3],
        ['icon'=>'🌟','name'=>'Week Warrior',   'desc'=>'7-day streak',               'earned'=> $streak >= 7],
        ['icon'=>'🏆','name'=>'Unstoppable',    'desc'=>'30-day streak',              'earned'=> $streak >= 30],
        ['icon'=>'📝','name'=>'Test Taker',     'desc'=>'Completed a mock test',      'earned'=> $testCount >= 1],
        ['icon'=>'📊','name'=>'Data Driven',    'desc'=>'5 mock tests completed',     'earned'=> $testCount >= 5],
        ['icon'=>'🗺️','name'=>'Explorer',       'desc'=>'50 unique problems seen',    'earned'=> $unique >= 50],
        ['icon'=>'🎖️','name'=>'Veteran',        'desc'=>'250 unique problems seen',   'earned'=> $unique >= 250],
    ];
    $earnedCount = count(array_filter($achievements, fn($a) => $a['earned']));
}

$viewerUser = current_user(); // logged-in viewer (may be null)
$isOwnProfile = $viewerUser && $profile && (int)$viewerUser['id'] === (int)$profile['id'];

$accent = $profile['avatar_color'] ?? '#6c8ef5';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<?php if (!empty($profile)): ?>
<title><?= htmlspecialchars($profile['display_name'] ?: $profile['username']) ?> — AIMElab</title>
<meta name="description" content="<?= htmlspecialchars($profile['display_name'] ?: $profile['username']) ?>'s AIMElab profile — <?= $total ?> attempts, <?= $acc ?>% accuracy."/>
<meta property="og:title" content="<?= htmlspecialchars($profile['display_name'] ?: $profile['username']) ?> on AIMElab"/>
<meta property="og:description" content="<?= $total ?> attempts · <?= $acc ?>% accuracy · <?= $unique ?> unique problems"/>
<?php else: ?>
<title>Profile not found — AIMElab</title>
<?php endif; ?>
<link rel="icon" type="image/png" sizes="32x32" href="./favicon-32x32.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@400;500&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #0d0f14; --bg2: #141720; --bg3: #1c202e; --bg4: #242838;
  --border: #2e3348;
  --accent: <?= htmlspecialchars($accent) ?>;
  --accent2: #a78bfa;
  --accent-glow: color-mix(in srgb, <?= htmlspecialchars($accent) ?> 18%, transparent);
  --correct: #34d399; --wrong: #f87171; --warn: #fbbf24;
  --text: #e8eaf2; --text2: #9198b5; --text3: #5d6480;
  --font-display: 'DM Serif Display', serif;
  --font-body: 'Outfit', sans-serif;
  --font-mono: 'DM Mono', monospace;
  --radius: 14px; --radius-sm: 8px;
  --shadow: 0 4px 32px rgba(0,0,0,0.5);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: var(--font-body); background: var(--bg); color: var(--text);
  min-height: 100vh; font-size: 16px;
}
a { color: inherit; text-decoration: none; }
a:hover { text-decoration: none; }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg); }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

/* ── Layout ── */
.container { max-width: 860px; margin: 0 auto; padding: 32px 20px 60px; }

/* ── Topbar ── */
.topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 24px; background: var(--bg2); border-bottom: 1px solid var(--border);
  position: sticky; top: 0; z-index: 50;
}
.topbar-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
.topbar-logo-icon {
  width: 32px; height: 32px; border-radius: 8px;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-display); font-size: 16px; color: #fff;
}
.topbar-logo-text { font-family: var(--font-display); font-size: 18px; }
.topbar-logo-text span { color: var(--accent); }
.topbar-actions { display: flex; gap: 10px; align-items: center; }
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 8px 16px; border-radius: var(--radius-sm);
  font-family: var(--font-body); font-size: 13px; font-weight: 600;
  cursor: pointer; border: none; transition: all 0.2s; text-decoration: none;
}
.btn-primary { background: var(--accent); color: #fff; box-shadow: 0 0 16px var(--accent-glow); }
.btn-primary:hover { filter: brightness(1.12); }
.btn-secondary { background: var(--bg3); color: var(--text); border: 1px solid var(--border); }
.btn-secondary:hover { background: var(--bg4); }

/* ── Card ── */
.card {
  background: var(--bg2); border: 1px solid var(--border);
  border-radius: var(--radius); margin-bottom: 16px; box-shadow: var(--shadow);
  overflow: hidden;
}

/* ── Profile banner ── */
.profile-banner {
  height: 140px; position: relative; overflow: hidden;
}
.profile-banner::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(180deg, transparent 40%, var(--bg2) 100%);
}

/* ── Profile card body ── */
.profile-body { padding: 0 28px 28px; }
.profile-avatar {
  width: 90px; height: 90px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 38px; flex-shrink: 0;
  border: 4px solid var(--bg2);
  margin-top: -45px; margin-bottom: 14px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.45);
  position: relative; z-index: 1;
  background: <?= htmlspecialchars($accent) ?>;
}
.profile-top-row { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; }
.profile-name { font-family: var(--font-display); font-size: 1.7rem; color: var(--text); line-height: 1.1; }
.profile-handle { font-size: 13px; color: var(--text3); margin-top: 4px; font-family: var(--font-mono); }
.admin-tag {
  display: inline-block; margin-left: 8px;
  padding: 1px 8px; border-radius: 999px; font-size: 11px; font-weight: 700;
  background: color-mix(in srgb, var(--accent) 20%, transparent);
  color: var(--accent); border: 1px solid var(--accent); font-family: var(--font-body);
}
.profile-bio {
  font-size: 14px; color: var(--text2); margin-top: 12px; line-height: 1.75;
  max-width: 560px; white-space: pre-wrap;
}
.profile-meta { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 14px; }
.meta-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text3); }

/* ── Stats row ── */
.stats-row {
  display: flex; gap: 0; margin-top: 24px;
  background: var(--bg3); border-radius: var(--radius-sm); overflow: hidden;
  border: 1px solid var(--border);
}
.stat-item {
  flex: 1; padding: 16px 12px; text-align: center;
  border-right: 1px solid var(--border);
}
.stat-item:last-child { border-right: none; }
.stat-value { font-family: var(--font-display); font-size: 1.5rem; color: var(--text); line-height: 1; }
.stat-label { font-size: 10px; color: var(--text3); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; }

/* ── Section title ── */
.section-title {
  font-size: 11px; text-transform: uppercase; letter-spacing: 0.12em;
  color: var(--text3); font-weight: 700; margin-bottom: 14px;
  padding-bottom: 8px; border-bottom: 1px solid var(--border);
}

/* ── Heatmap ── */
.heatmap-wrap { padding: 20px 24px; }
.heatmap-grid {
  display: grid; grid-template-columns: repeat(26, 1fr); gap: 3px; margin-bottom: 8px;
}
.heatmap-cell {
  aspect-ratio: 1; border-radius: 2px; background: var(--bg4);
}
.heatmap-cell[data-level="1"] { background: color-mix(in srgb, var(--accent) 28%, var(--bg4)); }
.heatmap-cell[data-level="2"] { background: color-mix(in srgb, var(--accent) 55%, var(--bg4)); }
.heatmap-cell[data-level="3"] { background: color-mix(in srgb, var(--accent) 78%, var(--bg4)); }
.heatmap-cell[data-level="4"] { background: var(--accent); }
@supports not (color: color-mix(in srgb, red 50%, blue)) {
  .heatmap-cell[data-level="1"] { background: rgba(108,142,245,0.25); }
  .heatmap-cell[data-level="2"] { background: rgba(108,142,245,0.5); }
  .heatmap-cell[data-level="3"] { background: rgba(108,142,245,0.75); }
  .heatmap-cell[data-level="4"] { background: var(--accent); }
}
.heatmap-legend { display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--text3); }
.heatmap-legend-cell { width: 10px; height: 10px; border-radius: 2px; }

/* ── Two-col grid ── */
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.card-inner { padding: 20px 24px; }

/* ── Comp bars ── */
.comp-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.comp-label { font-size: 12px; color: var(--text2); width: 80px; flex-shrink: 0; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.comp-bar-bg { flex: 1; height: 6px; background: var(--bg4); border-radius: 3px; overflow: hidden; }
.comp-bar-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, var(--accent), var(--accent2)); }
.comp-pct { font-size: 11px; color: var(--text3); width: 32px; text-align: right; font-family: var(--font-mono); }

/* ── Achievements ── */
.ach-grid { display: flex; flex-direction: column; gap: 8px; }
.ach-item {
  display: flex; align-items: center; gap: 10px; padding: 9px 12px;
  background: var(--bg3); border: 1px solid var(--border); border-radius: var(--radius-sm);
  opacity: 0.4; transition: opacity 0.2s;
}
.ach-item.earned { opacity: 1; border-color: color-mix(in srgb, var(--accent) 50%, var(--border)); background: color-mix(in srgb, var(--accent) 8%, var(--bg3)); }
.ach-icon { font-size: 18px; flex-shrink: 0; filter: grayscale(1); }
.ach-item.earned .ach-icon { filter: none; }
.ach-name { font-size: 12px; font-weight: 600; color: var(--text); }
.ach-desc { font-size: 11px; color: var(--text3); margin-top: 1px; }

/* ── Not found ── */
.not-found { text-align: center; padding: 80px 20px; }
.not-found h1 { font-family: var(--font-display); font-size: 2rem; color: var(--text); margin-bottom: 12px; }
.not-found p { color: var(--text2); margin-bottom: 24px; }

/* ── Responsive ── */
@media (max-width: 640px) {
  .topbar { padding: 12px 16px; }
  .container { padding: 20px 12px 60px; }
  .profile-banner { height: 100px; }
  .profile-body { padding: 0 16px 20px; }
  .profile-avatar { width: 72px; height: 72px; font-size: 30px; margin-top: -36px; }
  .profile-name { font-size: 1.35rem; }
  .stats-row { flex-wrap: wrap; }
  .stat-item { min-width: 33%; border-bottom: 1px solid var(--border); }
  .two-col { grid-template-columns: 1fr; }
  .heatmap-grid { grid-template-columns: repeat(18, 1fr); }
  .topbar-actions .btn span { display: none; }
}
</style>
</head>
<body>

<!-- ── Topbar ── -->
<header class="topbar">
  <a href="<?= $home ?>" class="topbar-logo">
    <div class="topbar-logo-icon">∑</div>
    <div class="topbar-logo-text">AIME<span>lab</span></div>
  </a>
  <div class="topbar-actions">
    <?php if ($isOwnProfile): ?>
      <a href="<?= $home ?>?page=profile" class="btn btn-secondary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        <span>Edit Profile</span>
      </a>
    <?php elseif ($viewerUser): ?>
      <a href="<?= $home ?>" class="btn btn-secondary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>Home</span>
      </a>
    <?php else: ?>
      <a href="<?= $home ?>?auth=login" class="btn btn-secondary">Sign In</a>
      <a href="<?= $home ?>?auth=register" class="btn btn-primary">Join AIMElab</a>
    <?php endif; ?>
    <button class="btn btn-secondary" id="share-btn" onclick="copyProfileUrl()" title="Copy link">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
      <span id="share-btn-text">Share</span>
    </button>
  </div>
</header>

<div class="container">
<?php if (!empty($error)): ?>
  <!-- ── Not Found ── -->
  <div class="not-found">
    <h1>404</h1>
    <p><?= $error ?></p>
    <a href="<?= $home ?>" class="btn btn-primary">Go to AIMElab</a>
  </div>

<?php else: ?>

  <!-- ── Profile Card ── -->
  <div class="card">
    <div class="profile-banner" style="<?= !empty($profile) ? bannerCSSPhp($profile['banner_style']??'gradient',$profile['banner_color1']??'#6c8ef5',$profile['banner_color2']??'#a78bfa',(int)($profile['banner_angle']??135)) : '' ?>"></div>
    <div class="profile-body">
      <div class="profile-top-row">
        <div>
          <div class="profile-avatar" style="background:<?= !empty($profile['avatar_url']) ? 'transparent' : htmlspecialchars($profile['avatar_color']) ?>;">
        <?php if (!empty($profile['avatar_url'])): ?>
          <img src="<?= htmlspecialchars($base . '/' . $profile['avatar_url']) ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" alt=""/>
        <?php else: ?>
          <?= htmlspecialchars($profile['avatar_emoji']) ?>
        <?php endif; ?>
      </div>
          <div class="profile-name">
            <?= htmlspecialchars($profile['display_name'] ?: $profile['username']) ?>
            <?php if ($profile['role'] === 'admin'): ?>
              <span class="admin-tag">Admin</span>
            <?php endif; ?>
          </div>
          <div class="profile-handle">@<?= htmlspecialchars($profile['username']) ?></div>
          <?php if (!empty($profile['bio'])): ?>
            <div class="profile-bio"><?= nl2br(htmlspecialchars($profile['bio'])) ?></div>
          <?php endif; ?>
          <div class="profile-meta">
            <div class="meta-item">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              Joined <?= date('F Y', strtotime($profile['created_at'])) ?>
            </div>
            <?php if ($streak > 0): ?>
            <div class="meta-item">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c0 0-4 4-4 9a4 4 0 0 0 8 0c0-5-4-9-4-9z"/><path d="M12 11c0 0-2 2-2 4a2 2 0 0 0 4 0c0-2-2-4-2-4z" fill="currentColor"/></svg>
              <?= $streak ?> day streak
            </div>
            <?php endif; ?>
            <div class="meta-item">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
              <?= $earnedCount ?> / <?= count($achievements) ?> achievements
            </div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-item">
          <div class="stat-value"><?= number_format($total) ?></div>
          <div class="stat-label">Attempts</div>
        </div>
        <div class="stat-item">
          <div class="stat-value"><?= number_format($correct) ?></div>
          <div class="stat-label">Correct</div>
        </div>
        <div class="stat-item">
          <div class="stat-value"><?= $acc ?>%</div>
          <div class="stat-label">Accuracy</div>
        </div>
        <div class="stat-item">
          <div class="stat-value"><?= number_format($unique) ?></div>
          <div class="stat-label">Unique Problems</div>
        </div>
        <div class="stat-item">
          <div class="stat-value"><?= $testCount ?><?= $testCount > 0 && $bestScore !== null ? '<small style="font-size:0.7em;color:var(--text3);"> (best: '.$bestScore.')</small>' : '' ?></div>
          <div class="stat-label">Tests Taken</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Activity Heatmap ── -->
  <div class="card">
    <div class="heatmap-wrap">
      <div class="section-title">Activity — Last 26 Weeks</div>
      <div class="heatmap-grid" id="heatmap">
        <?php
        $today = new DateTime();
        $start = clone $today;
        $start->modify('-181 days');
        // Pad back to Sunday
        $dow = (int)$start->format('w');
        if ($dow > 0) $start->modify('-' . $dow . ' days');

        $cur = clone $start;
        while ($cur <= $today) {
            $ds    = $cur->format('Y-m-d');
            $count = $heatData[$ds] ?? 0;
            $level = $count === 0 ? 0 : ($count <= 2 ? 1 : ($count <= 5 ? 2 : ($count <= 9 ? 3 : 4)));
            $title = $ds . ($count ? ': ' . $count . ' attempt' . ($count > 1 ? 's' : '') : ': no activity');
            echo '<div class="heatmap-cell"' . ($level > 0 ? ' data-level="'.$level.'"' : '') . ' title="'.htmlspecialchars($title).'"></div>';
            $cur->modify('+1 day');
        }
        ?>
      </div>
      <div class="heatmap-legend">
        Less
        <div class="heatmap-legend-cell" style="background:var(--bg4);"></div>
        <div class="heatmap-legend-cell heatmap-cell" data-level="1"></div>
        <div class="heatmap-legend-cell heatmap-cell" data-level="2"></div>
        <div class="heatmap-legend-cell heatmap-cell" data-level="3"></div>
        <div class="heatmap-legend-cell heatmap-cell" data-level="4"></div>
        More
      </div>
    </div>
  </div>

  <!-- ── Comp Accuracy + Achievements ── -->
  <div class="two-col">

    <!-- Comp accuracy bars (need problem data — built client-side from CSV would be heavy,
         so we store comp in a denormalised way via a view query using problem_id prefix logic.
         Instead we show a clean "top competitions attempted" based on attempt counts) -->
    <div class="card">
      <div class="card-inner">
        <div class="section-title">Top Competitions</div>
        <?php
        $compQ = db()->prepare(
            "SELECT
                CASE
                  WHEN problem_id LIKE 'aime%'      THEN 'AIME'
                  WHEN problem_id LIKE 'amc_8%'     THEN 'AMC 8'
                  WHEN problem_id LIKE 'amc_10%'    THEN 'AMC 10'
                  WHEN problem_id LIKE 'amc_12%'    THEN 'AMC 12'
                  WHEN problem_id LIKE 'ahsme%'     THEN 'AHSME'
                  WHEN problem_id LIKE 'ajhsme%'    THEN 'AJHSME'
                  WHEN problem_id LIKE 'usamo%'     THEN 'USAMO'
                  WHEN problem_id LIKE 'usajmo%'    THEN 'USAJMO'
                  ELSE 'Other'
                END AS comp,
                COUNT(*) AS total,
                SUM(correct) AS correct
             FROM attempts WHERE user_id=?
             GROUP BY comp ORDER BY total DESC LIMIT 8"
        );
        $compQ->execute([$uid]);
        $compRows = $compQ->fetchAll();
        if ($compRows):
            foreach ($compRows as $cr):
                $cpct = $cr['total'] > 0 ? round($cr['correct'] / $cr['total'] * 100) : 0;
        ?>
        <div class="comp-row">
          <div class="comp-label"><?= htmlspecialchars($cr['comp']) ?></div>
          <div class="comp-bar-bg"><div class="comp-bar-fill" style="width:<?= $cpct ?>%"></div></div>
          <div class="comp-pct"><?= $cpct ?>%</div>
        </div>
        <?php endforeach; else: ?>
        <p style="color:var(--text3);font-size:13px;">No attempts yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Achievements -->
    <div class="card">
      <div class="card-inner">
        <div class="section-title">Achievements <span style="color:var(--text3);font-weight:400;"><?= $earnedCount ?>/<?= count($achievements) ?></span></div>
        <div class="ach-grid">
          <?php foreach ($achievements as $a): ?>
          <div class="ach-item <?= $a['earned'] ? 'earned' : '' ?>" title="<?= htmlspecialchars($a['desc']) ?>">
            <span class="ach-icon"><?= $a['icon'] ?></span>
            <div>
              <div class="ach-name"><?= htmlspecialchars($a['name']) ?></div>
              <div class="ach-desc"><?= htmlspecialchars($a['desc']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>

  <!-- ── Footer ── -->
  <p style="text-align:center;font-size:12px;color:var(--text3);margin-top:8px;">
    View on
    <a href="<?= $home ?>" style="color:var(--accent);text-decoration:none;">AIMElab</a>
    · Math Olympiad Practice
  </p>

<?php endif; ?>
</div>

<script>
function copyProfileUrl() {
  const url = window.location.href;
  navigator.clipboard.writeText(url).then(() => {
    const btn = document.getElementById('share-btn-text');
    btn.textContent = 'Copied!';
    setTimeout(() => { btn.textContent = 'Share'; }, 2000);
  }).catch(() => {
    prompt('Copy this link:', url);
  });
}
</script>
</body>
</html>