<?php
// ============================================================
// index.php  — AIMElab with PHP auth + MySQL persistence
// ============================================================
require_once __DIR__ . '/includes/db.php';

$user = current_user();

// Redirect unauthenticated users to auth page
$auth_mode = $_GET['auth'] ?? '';
if (!$user && !in_array($auth_mode, ['login','register'])) {
    header('Location: index.php?auth=login');
    exit;
}

// If logged-in user hits auth page → redirect home
if ($user && in_array($auth_mode, ['login','register'])) {
    header('Location: index.php');
    exit;
}

// Pre-load settings for logged-in user
$settings = $user ? get_settings((int)$user['id']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>AIMElab ~ Math Olympiad Practice</title>
<meta name="description" content="Practice AMC 8, AMC 10, AMC 12, and AIME problems. Filter by competition, track your history, run mock tests, and analyze your performance across 3600+ unique problems."/>

<link rel="apple-touch-icon" sizes="180x180" href="./apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="./favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="./favicon-16x16.png">
<link rel="manifest" href="./site.webmanifest">

<script>
window.MathJax = {
  tex: { inlineMath: [['$','$'],['\\(','\\)']], displayMath: [['$$','$$'],['\\[','\\]']], processEscapes: true },
  options: { skipHtmlTags: ['script','noscript','style','textarea'] },
  startup: { typeset: false }
};
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/3.2.2/es5/tex-chtml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>

<style>
/* ===== FONTS ===== */
@import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@400;500&family=Outfit:wght@300;400;500;600;700&display=swap');
:root {
  --bg: #0d0f14;
  --bg2: #141720;
  --bg3: #1c202e;
  --bg4: #242838;
  --border: #2e3348;
  --accent: #6c8ef5;
  --accent2: #a78bfa;
  --accent-glow: rgba(108,142,245,0.18);
  --correct: #34d399;
  --wrong: #f87171;
  --warn: #fbbf24;
  --text: #e8eaf2;
  --text2: #9198b5;
  --text3: #5d6480;
  --font-display: 'DM Serif Display', serif;
  --font-body: 'Outfit', sans-serif;
  --font-mono: 'DM Mono', monospace;
  --radius: 14px;
  --radius-sm: 8px;
  --shadow: 0 4px 32px rgba(0,0,0,0.5);
  --transition: 0.2s ease;
}

[data-theme="light"] {
  --bg: #f4f5fa;
  --bg2: #ffffff;
  --bg3: #eef0f8;
  --bg4: #e4e7f5;
  --border: #d0d5ec;
  --text: #1a1d2e;
  --text2: #4a5080;
  --text3: #8890b5;
  --accent-glow: rgba(108,142,245,0.1);
  --shadow: 0 4px 24px rgba(0,0,0,0.1);
}

[data-theme="sepia"] {
  --bg: #1a1410;
  --bg2: #221c15;
  --bg3: #2a231a;
  --bg4: #332b20;
  --border: #4a3d2e;
  --accent: #d4a85a;
  --accent2: #e8c87a;
  --accent-glow: rgba(212,168,90,0.15);
  --text: #f0e6d0;
  --text2: #b8a888;
  --text3: #7a6a50;
}

[data-theme="forest"] {
  --bg: #0d1410;
  --bg2: #141c16;
  --bg3: #1a2420;
  --bg4: #222e28;
  --border: #2e4035;
  --accent: #5ecf80;
  --accent2: #89e8a8;
  --accent-glow: rgba(94,207,128,0.15);
  --text: #e0f0e8;
  --text2: #88b898;
  --text3: #507060;
}

[data-theme="midnight"] {
  --bg: #070818;
  --bg2: #0d0f2a;
  --bg3: #131535;
  --bg4: #1a1d42;
  --border: #252850;
  --accent: #818cf8;
  --accent2: #c4b5fd;
  --accent-glow: rgba(129,140,248,0.18);
  --text: #e2e8ff;
  --text2: #8b92c8;
  --text3: #4a5080;
}

[data-theme="rose"] {
  --bg: #130a0e;
  --bg2: #1c0f15;
  --bg3: #25141c;
  --bg4: #2e1a23;
  --border: #4a2535;
  --accent: #f472b6;
  --accent2: #fb7185;
  --accent-glow: rgba(244,114,182,0.18);
  --text: #fce7f3;
  --text2: #c084a0;
  --text3: #7a4060;
}

[data-theme="ocean"] {
  --bg: #060d18;
  --bg2: #0a1628;
  --bg3: #0e1f36;
  --bg4: #132844;
  --border: #1e3a58;
  --accent: #38bdf8;
  --accent2: #7dd3fc;
  --accent-glow: rgba(56,189,248,0.18);
  --text: #e0f4ff;
  --text2: #7aabcc;
  --text3: #3a6080;
}

[data-theme="dracula"] {
  --bg: #1e1e2e;
  --bg2: #252535;
  --bg3: #2c2c3e;
  --bg4: #343448;
  --border: #44475a;
  --accent: #bd93f9;
  --accent2: #ff79c6;
  --accent-glow: rgba(189,147,249,0.18);
  --text: #f8f8f2;
  --text2: #a0a8c8;
  --text3: #6272a4;
}

[data-theme="sunset"] {
  --bg: #12080a;
  --bg2: #1e0e10;
  --bg3: #281418;
  --bg4: #321a20;
  --border: #4a2830;
  --accent: #fb923c;
  --accent2: #fbbf24;
  --accent-glow: rgba(251,146,60,0.18);
  --text: #fff0e8;
  --text2: #c8906a;
  --text3: #7a5040;
}

[data-theme="arctic"] {
  --bg: #f0f4f8;
  --bg2: #ffffff;
  --bg3: #e8eef5;
  --bg4: #dde5f0;
  --border: #c5d0e0;
  --accent: #0ea5e9;
  --accent2: #6366f1;
  --accent-glow: rgba(14,165,233,0.12);
  --text: #0f1c2e;
  --text2: #3a5070;
  --text3: #7a98b8;
  --shadow: 0 4px 24px rgba(0,0,0,0.08);
}

[data-theme="candy"] {
  --bg: #0f0a18;
  --bg2: #170f22;
  --bg3: #1f142e;
  --bg4: #271a3a;
  --border: #3d2860;
  --accent: #e879f9;
  --accent2: #22d3ee;
  --accent-glow: rgba(232,121,249,0.18);
  --text: #fdf4ff;
  --text2: #c090d0;
  --text3: #705090;
}

[data-theme="mocha"] {
  --bg: #1c1612;
  --bg2: #251e18;
  --bg3: #2e261e;
  --bg4: #382e24;
  --border: #5a4535;
  --accent: #cba87a;
  --accent2: #e8c87a;
  --accent-glow: rgba(203,168,122,0.18);
  --text: #f5ede0;
  --text2: #b89870;
  --text3: #7a6048;
}

[data-font-size="small"] { font-size: 14px; }
[data-font-size="medium"] { font-size: 16px; }
[data-font-size="large"] { font-size: 18px; }
[data-font-size="xl"] { font-size: 20px; }

/* ===== RESET ===== */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: var(--font-body);
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  font-size: 16px;
  transition: background 0.3s, color 0.3s;
  overflow-x: hidden;
}

/* ===== SCROLLBAR ===== */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg); }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

/* ===== LAYOUT ===== */
#app { display: flex; height: 100vh; overflow: hidden; }

/* ===== SIDEBAR ===== */
#sidebar {
  width: 240px;
  min-width: 240px;
  background: var(--bg2);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  z-index: 10;
  transition: width 0.3s, min-width 0.3s;
}
#sidebar.collapsed { width: 64px; min-width: 64px; }

.sidebar-logo {
  padding: 24px 20px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--border);
}
.logo-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-display); font-size: 18px; color: #fff;
  flex-shrink: 0;
  box-shadow: 0 0 20px var(--accent-glow);
}
.logo-text { font-family: var(--font-display); font-size: 20px; white-space: nowrap; overflow: hidden; }
.logo-text span { color: var(--accent); }

.sidebar-nav { padding: 12px 8px; flex: 1; }
.nav-item {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 12px; border-radius: var(--radius-sm);
  cursor: pointer; color: var(--text2); font-size: 14px; font-weight: 500;
  transition: all var(--transition); white-space: nowrap; overflow: hidden;
  margin-bottom: 2px; user-select: none;
}
.nav-item:hover { background: var(--bg3); color: var(--text); }
.nav-item.active { background: var(--accent-glow); color: var(--accent); }
.nav-icon { display: flex; align-items: center; justify-content: center; flex-shrink: 0; width: 22px; }

.sidebar-footer { padding: 12px 8px 16px; border-top: 1px solid var(--border); }

/* ===== MAIN CONTENT ===== */
#main {
  flex: 1; overflow-y: auto; overflow-x: hidden;
  background: var(--bg);
}

/* ===== PAGES ===== */
.page { display: none; padding: 32px; max-width: 960px; margin: 0 auto; animation: fadeIn 0.3s ease; }
.page.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

.page-header { margin-bottom: 28px; }
.page-title { font-family: var(--font-display); font-size: 2rem; color: var(--text); }
.page-subtitle { color: var(--text2); margin-top: 4px; font-size: 0.95rem; }

/* ===== CARDS ===== */
.card {
  background: var(--bg2); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 24px;
  margin-bottom: 20px; box-shadow: var(--shadow);
}
.card-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text3); margin-bottom: 12px; font-weight: 600; }

/* ===== BUTTONS ===== */
.btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 20px; border-radius: var(--radius-sm);
  font-family: var(--font-body); font-size: 14px; font-weight: 600;
  cursor: pointer; border: none; transition: all var(--transition);
  text-decoration: none; white-space: nowrap;
}

.answer-area .btn {
  height: 50px;
}

.btn-primary { background: var(--accent); color: #fff; box-shadow: 0 0 20px var(--accent-glow); }
.btn-primary:hover { filter: brightness(1.15); transform: translateY(-1px); }
.btn-secondary { background: var(--bg3); color: var(--text); border: 1px solid var(--border); }
.btn-secondary:hover { background: var(--bg4); }
.btn-success { background: var(--correct); color: #fff; }
.btn-danger { background: var(--wrong); color: #fff; }
.btn-lg { padding: 14px 28px; font-size: 16px; border-radius: var(--radius); }
.btn-sm { padding: 6px 14px; font-size: 13px; }

/* ===== FORM ELEMENTS ===== */
select, input[type="number"], input[type="text"], input[type="password"], input[type="email"] {
  background: var(--bg3); border: 1px solid var(--border);
  color: var(--text); border-radius: var(--radius-sm);
  padding: 10px 14px; font-family: var(--font-body); font-size: 14px;
  outline: none; transition: border-color var(--transition);
  width: 100%;
}
select:focus, input:focus { border-color: var(--accent); }
select option { background: var(--bg2); }

label { font-size: 13px; color: var(--text2); font-weight: 500; display: block; margin-bottom: 6px; }
.form-group { margin-bottom: 16px; }

/* ===== TOGGLE ===== */
.toggle-group { display: flex; gap: 8px; flex-wrap: wrap; }
.toggle-btn {
  padding: 8px 16px; border-radius: 999px; border: 1px solid var(--border);
  background: var(--bg3); color: var(--text2); font-size: 13px; font-weight: 500;
  cursor: pointer; transition: all var(--transition);
}
.toggle-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; box-shadow: 0 0 12px var(--accent-glow); }

/* ===== STATS GRID ===== */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat-card {
  background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius);
  padding: 20px; text-align: center; position: relative; overflow: hidden;
}
.stat-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, var(--accent), var(--accent2));
}
.stat-value { font-family: var(--font-display); font-size: 2.2rem; color: var(--text); line-height: 1; }
.stat-label { font-size: 12px; color: var(--text2); margin-top: 6px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600; }
.stat-sub { font-size: 11px; color: var(--text3); margin-top: 4px; }
.stat-correct { --accent: var(--correct); }
.stat-wrong { --accent: var(--wrong); }
.stat-accent2 { --accent: var(--accent2); }

/* ===== QUESTION CARD ===== */
#question-card { display: none; }
#question-card.active { display: block; }

.question-meta {
  display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; align-items: center;
}
.badge {
  padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600;
  background: var(--bg3); border: 1px solid var(--border); color: var(--text2);
}
.badge-accent { background: var(--accent-glow); border-color: var(--accent); color: var(--accent); }

.question-body {
  font-size: 1.05rem; line-height: 1.85; color: var(--text);
  background: var(--bg3); border-radius: var(--radius-sm); padding: 24px;
  border: 1px solid var(--border); margin-bottom: 24px;
  font-family: var(--font-body);
}

.answer-area { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
#answer-input {
  width: 200px; font-size: 1.2rem; font-family: var(--font-mono);
  text-align: center; padding: 12px; flex-shrink: 0;
}

.result-banner {
  display: none; padding: 16px 20px; border-radius: var(--radius-sm);
  font-weight: 600; font-size: 1rem; margin-top: 16px; align-items: center; gap: 12px;
  flex-wrap: wrap; word-break: break-word;
}
.result-banner.show { display: flex; animation: slideIn 0.3s ease; }
.result-banner.correct { background: rgba(52,211,153,0.12); border: 1px solid var(--correct); color: var(--correct); }
.result-banner.wrong { background: rgba(248,113,113,0.12); border: 1px solid var(--wrong); color: var(--wrong); }
@keyframes slideIn { from { opacity: 0; transform: translateX(-8px); } to { opacity: 1; transform: translateX(0); } }

/* ===== PROGRESS BAR ===== */
.progress-bar-wrap { background: var(--bg3); border-radius: 999px; height: 8px; margin-bottom: 6px; overflow: hidden; }
.progress-bar-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--accent), var(--accent2)); transition: width 0.4s ease; }

/* ===== CHART CONTAINERS ===== */
.chart-wrap { position: relative; height: 260px; }
.chart-wrap canvas { max-height: 260px; }

/* ===== FILTER ROW ===== */
.filter-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 20px; }
.filter-row .form-group { margin-bottom: 0; flex: 1; min-width: 120px; }

/* ===== SETTINGS ===== */
.settings-section { margin-bottom: 28px; }
.settings-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text3); font-weight: 700; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border); }
.setting-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border); }
.setting-info { flex: 1; }
.setting-name { font-size: 14px; color: var(--text); font-weight: 500; }
.setting-desc { font-size: 12px; color: var(--text3); margin-top: 2px; }
.setting-control { flex-shrink: 0; margin-left: 20px; }

/* Switch */
.switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; inset: 0; background: var(--bg4); border-radius: 999px; cursor: pointer; transition: 0.2s; }
.slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; top: 3px; background: white; border-radius: 50%; transition: 0.2s; }
input:checked + .slider { background: var(--accent); }
input:checked + .slider::before { transform: translateX(20px); }

/* Theme swatches */
.theme-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.theme-swatch {
  border-radius: var(--radius-sm);
  padding: 0;
  text-align: center;
  cursor: pointer;
  border: 2px solid var(--border);
  transition: all var(--transition);
  font-size: 11px;
  font-weight: 600;
  overflow: hidden;
  background: var(--bg3);
  color: var(--text2);
}
.theme-swatch:hover { border-color: var(--text3); color: var(--text); transform: translateY(-1px); }
.theme-swatch.active { border-color: var(--accent); color: var(--text); box-shadow: 0 0 0 2px var(--accent-glow); }
.theme-swatch-bar {
  height: 28px;
  width: 100%;
  display: flex;
}
.theme-swatch-bar span {
  flex: 1;
  display: block;
}
.theme-swatch-label {
  padding: 6px 4px 7px;
}

/* Bar colours per theme: bg | accent | accent2 */
.theme-dark     .theme-swatch-bar { background: #0d0f14; }
.theme-dark     .theme-swatch-bar span:nth-child(1) { background: #0d0f14; }
.theme-dark     .theme-swatch-bar span:nth-child(2) { background: #6c8ef5; }
.theme-dark     .theme-swatch-bar span:nth-child(3) { background: #a78bfa; }

.theme-light    .theme-swatch-bar { background: #f4f5fa; }
.theme-light    .theme-swatch-bar span:nth-child(1) { background: #f4f5fa; }
.theme-light    .theme-swatch-bar span:nth-child(2) { background: #6c8ef5; }
.theme-light    .theme-swatch-bar span:nth-child(3) { background: #a78bfa; }

.theme-sepia    .theme-swatch-bar span:nth-child(1) { background: #1a1410; }
.theme-sepia    .theme-swatch-bar span:nth-child(2) { background: #d4a85a; }
.theme-sepia    .theme-swatch-bar span:nth-child(3) { background: #e8c87a; }

.theme-forest   .theme-swatch-bar span:nth-child(1) { background: #0d1410; }
.theme-forest   .theme-swatch-bar span:nth-child(2) { background: #5ecf80; }
.theme-forest   .theme-swatch-bar span:nth-child(3) { background: #89e8a8; }

.theme-midnight .theme-swatch-bar span:nth-child(1) { background: #070818; }
.theme-midnight .theme-swatch-bar span:nth-child(2) { background: #818cf8; }
.theme-midnight .theme-swatch-bar span:nth-child(3) { background: #c4b5fd; }

.theme-rose     .theme-swatch-bar span:nth-child(1) { background: #130a0e; }
.theme-rose     .theme-swatch-bar span:nth-child(2) { background: #f472b6; }
.theme-rose     .theme-swatch-bar span:nth-child(3) { background: #fb7185; }

.theme-ocean    .theme-swatch-bar span:nth-child(1) { background: #060d18; }
.theme-ocean    .theme-swatch-bar span:nth-child(2) { background: #38bdf8; }
.theme-ocean    .theme-swatch-bar span:nth-child(3) { background: #7dd3fc; }

.theme-dracula  .theme-swatch-bar span:nth-child(1) { background: #1e1e2e; }
.theme-dracula  .theme-swatch-bar span:nth-child(2) { background: #bd93f9; }
.theme-dracula  .theme-swatch-bar span:nth-child(3) { background: #ff79c6; }

.theme-sunset   .theme-swatch-bar span:nth-child(1) { background: #12080a; }
.theme-sunset   .theme-swatch-bar span:nth-child(2) { background: #fb923c; }
.theme-sunset   .theme-swatch-bar span:nth-child(3) { background: #fbbf24; }

.theme-arctic   .theme-swatch-bar span:nth-child(1) { background: #dde5f0; }
.theme-arctic   .theme-swatch-bar span:nth-child(2) { background: #0ea5e9; }
.theme-arctic   .theme-swatch-bar span:nth-child(3) { background: #6366f1; }

.theme-candy    .theme-swatch-bar span:nth-child(1) { background: #0f0a18; }
.theme-candy    .theme-swatch-bar span:nth-child(2) { background: #e879f9; }
.theme-candy    .theme-swatch-bar span:nth-child(3) { background: #22d3ee; }

.theme-mocha    .theme-swatch-bar span:nth-child(1) { background: #1c1612; }
.theme-mocha    .theme-swatch-bar span:nth-child(2) { background: #cba87a; }
.theme-mocha    .theme-swatch-bar span:nth-child(3) { background: #e8c87a; }

/* Font size selector */
.font-size-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.font-size-btn {
  padding: 10px; border-radius: var(--radius-sm); text-align: center;
  border: 1px solid var(--border); background: var(--bg3); cursor: pointer;
  transition: all var(--transition); color: var(--text2); font-weight: 600;
}
.font-size-btn.active { background: var(--accent-glow); border-color: var(--accent); color: var(--accent); }
.font-size-btn:nth-child(1) { font-size: 12px; }
.font-size-btn:nth-child(2) { font-size: 14px; }
.font-size-btn:nth-child(3) { font-size: 16px; }
.font-size-btn:nth-child(4) { font-size: 18px; }

/* ===== HISTORY TABLE ===== */
.history-table { width: 100%; border-collapse: collapse; font-size: 13px; table-layout: fixed; }
.history-table th { text-align: left; padding: 10px 8px; color: var(--text3); font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid var(--border); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: default; }
.history-table td { padding: 9px 8px; border-bottom: 1px solid var(--border); color: var(--text2); font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: default; }
.history-table td:first-child { font-family: var(--font-mono); color: var(--accent); font-size: 11px; }
.history-table tr:hover td { background: var(--bg3); }
.history-table .delete-attempt-btn { opacity: 0; background: none; border: none; cursor: pointer; color: var(--wrong); padding: 2px 5px; border-radius: 4px; line-height: 1; transition: opacity 0.15s, background 0.15s; }
.history-table tr:hover .delete-attempt-btn { opacity: 1; }
.history-table .delete-attempt-btn:hover { background: rgba(248,113,113,0.15); }
.history-table .open-problem-btn { opacity: 0; background: none; border: none; cursor: pointer; color: var(--accent); padding: 2px 5px; border-radius: 4px; line-height: 1; transition: opacity 0.15s, background 0.15s; }
.history-table tr:hover .open-problem-btn { opacity: 1; }
.history-table .open-problem-btn:hover { background: var(--accent-glow); }

/* Cell tap tooltip */
#cell-tooltip {
  position: fixed;
  background: var(--bg4);
  border: 1px solid var(--border);
  color: var(--text);
  font-size: 13px;
  font-weight: 500;
  padding: 7px 12px;
  border-radius: var(--radius-sm);
  box-shadow: 0 4px 20px rgba(0,0,0,0.35);
  pointer-events: none;
  z-index: 999;
  max-width: 360px;
  word-break: break-all;
  font-family: var(--font-mono);
  opacity: 0;
  transform: translateY(4px);
  transition: opacity 0.15s ease, transform 0.15s ease;
  white-space: normal;
}
#cell-tooltip.show {
  opacity: 1;
  transform: translateY(0);
}
.pill { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
.pill-correct { background: rgba(52,211,153,0.15); color: var(--correct); }
.pill-wrong { background: rgba(248,113,113,0.15); color: var(--wrong); }

/* ===== EMPTY STATE ===== */
.empty-state { text-align: center; padding: 60px 20px; color: var(--text3); }
.empty-state-icon { font-size: 3rem; margin-bottom: 16px; }
.empty-state-text { font-size: 1rem; color: var(--text2); margin-bottom: 8px; }

/* ===== LOADING ===== */
#loading-overlay {
  position: fixed; inset: 0; background: var(--bg); z-index: 9999;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px;
}
.loader { width: 48px; height: 48px; border: 3px solid var(--border); border-top-color: var(--accent); border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
#loading-overlay p { color: var(--text2); font-size: 14px; }

/* ===== PROBLEM NUMBER GRID ===== */
.prob-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(44px, 1fr)); gap: 8px; }
.prob-btn {
  height: 44px; border-radius: var(--radius-sm); border: 1px solid var(--border);
  background: var(--bg3); color: var(--text2); font-weight: 700; font-size: 13px;
  cursor: pointer; transition: all var(--transition); display: flex; align-items: center; justify-content: center;
}
.prob-btn:hover { border-color: var(--accent); color: var(--accent); }
.prob-btn.done-correct { background: rgba(52,211,153,0.15); border-color: var(--correct); color: var(--correct); }
.prob-btn.done-wrong { background: rgba(248,113,113,0.15); border-color: var(--wrong); color: var(--wrong); }

/* ===== MOBILE BOTTOM NAV ===== */
#mobile-nav {
  display: none;
  position: fixed;
  bottom: 0; left: 0; right: 0;
  background: var(--bg2);
  border-top: 1px solid var(--border);
  z-index: 100;
  padding-bottom: env(safe-area-inset-bottom);
  box-shadow: 0 -4px 24px rgba(0,0,0,0.3);
}
.mobile-nav-inner { display: flex; justify-content: space-around; align-items: stretch; }
.mobile-nav-item {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 3px; padding: 10px 2px 8px; cursor: pointer; color: var(--text3);
  font-size: 10px; font-weight: 600; letter-spacing: 0.02em;
  transition: color var(--transition); border: none; background: none;
  font-family: var(--font-body); -webkit-tap-highlight-color: transparent; position: relative;
}
.mobile-nav-item .mobile-nav-icon { display: flex; align-items: center; justify-content: center; line-height: 1; transition: transform var(--transition); }
.mobile-nav-item.active { color: var(--accent); }
.mobile-nav-item.active .mobile-nav-icon { transform: translateY(-2px); }
.mobile-nav-item.active::before {
  content: ''; position: absolute; top: 0; left: 15%; right: 15%;
  height: 2px; background: var(--accent); border-radius: 0 0 2px 2px;
}
/* ===== MOBILE MORE MENU ===== */
#mobile-more-menu {
  display: none; position: fixed; bottom: 0; left: 0; right: 0;
  background: var(--bg2); border-top: 1px solid var(--border);
  border-radius: 20px 20px 0 0; z-index: 200; padding: 12px 16px 32px;
  box-shadow: 0 -8px 40px rgba(0,0,0,0.5);
  transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.32,0.72,0,1);
}
#mobile-more-menu.open { transform: translateY(0); }
#mobile-more-menu.visible { display: block; }
.mobile-menu-handle { width: 36px; height: 4px; background: var(--border); border-radius: 2px; margin: 0 auto 16px; }
.mobile-menu-title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text3); font-weight: 700; margin-bottom: 8px; padding: 0 4px; }
.mobile-menu-item {
  display: flex; align-items: center; gap: 14px; padding: 14px 8px;
  border-radius: var(--radius-sm); cursor: pointer; color: var(--text2);
  font-size: 15px; font-weight: 500; transition: all var(--transition); -webkit-tap-highlight-color: transparent;
}
.mobile-menu-item:hover, .mobile-menu-item:active { background: var(--bg3); color: var(--text); }
.mobile-menu-item.active { color: var(--accent); background: var(--accent-glow); }
.mobile-menu-item .menu-icon { display: flex; align-items: center; justify-content: center; width: 28px; flex-shrink: 0; }
#mobile-overlay-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 199; }

/* ===== RESPONSIVE ===== */
@media (max-width: 700px) {
  #sidebar { display: none; }
  #mobile-nav { display: block; }
  #app { height: auto; min-height: 100vh; overflow: visible; }
  #main { overflow: visible; padding-bottom: 72px; }
  .page { padding: 16px; }
  .page-title { font-size: 1.5rem; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  [style*="grid-template-columns:1fr 1fr"],
  [style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
  .filter-row { flex-direction: column; gap: 8px; }
  .filter-row .form-group { min-width: unset; width: 100%; margin-bottom: 0; }
  #practice-config .flex-row { flex-direction: column; align-items: flex-start; gap: 10px; }
  .answer-area { flex-direction: column; align-items: stretch; gap: 10px; }
  .answer-area .form-group { width: 100%; }
  #answer-input { width: 100%; font-size: 1.1rem; }
  .answer-area .btn { width: 100%; justify-content: center; height: 48px; }
  #post-answer-area .flex-row { flex-direction: column; gap: 10px; }
  #post-answer-area .btn { width: 100%; justify-content: center; }
  #question-card .flex-between { flex-wrap: wrap; gap: 8px; }
  #question-card .question-meta { flex: 1; min-width: 0; }
  #browse-problem-card .flex-between { flex-direction: column; align-items: flex-start; gap: 10px; }
  #browse-problem-card .flex-between .btn { width: 100%; justify-content: center; }
  #browse-problem-card .mt-4 { display: flex; flex-direction: column; gap: 10px; }
  #browse-problem-card #browse-reveal-ans { width: 100%; }
  #browse-answer-reveal { margin-left: 0 !important; }
  #page-home .card .flex-row { flex-direction: column; gap: 10px; }
  #page-home .card .flex-row .btn { width: 100%; justify-content: center; }
  #page-history .flex-between { flex-direction: column; align-items: flex-start; gap: 10px; }
  #page-history .flex-between .btn { align-self: flex-end; }
  .result-banner { font-size: 0.88rem; padding: 12px 14px; gap: 8px; }
  .page, .card, .question-body { max-width: 100%; overflow-wrap: break-word; word-break: break-word; }
  .col-id, .col-correct-ans { width: 0 !important; padding: 0 !important; overflow: hidden; white-space: nowrap; border: none; font-size: 0; }
  .chart-wrap { height: 200px; }
  .chart-wrap canvas { max-height: 200px; }
}

/* ===== MISC ===== */
.flex-row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.flex-between { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.mt-4 { margin-top: 16px; }
.mt-2 { margin-top: 8px; }
.text-accent { color: var(--accent); }
.text-muted { color: var(--text3); font-size: 13px; }
.divider { border: none; border-top: 1px solid var(--border); margin: 20px 0; }


/* ===== TEST MODE ===== */
.test-progress-grid {
  display: grid;
  grid-template-columns: repeat(15, 1fr);
  gap: 4px;
  margin-bottom: 16px;
}
.test-prog-dot {
  height: 6px;
  border-radius: 3px;
  background: var(--bg4);
  transition: background 0.3s;
}
.test-prog-dot.answered-correct { background: var(--correct); }
.test-prog-dot.answered-wrong { background: var(--wrong); }
.test-prog-dot.current { background: var(--accent); }

.test-question-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.test-q-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  transition: background var(--transition);
  border-radius: 0;
}
.test-q-row:first-child { border-radius: var(--radius-sm) var(--radius-sm) 0 0; }
.test-q-row:last-child { border-bottom: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm); }
.test-q-row:hover { background: var(--bg3); }
.test-q-row.current-q { background: var(--accent-glow); }
.test-q-num {
  width: 28px; height: 28px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; flex-shrink: 0;
  background: var(--bg4); color: var(--text2);
}
.test-q-num.status-correct { background: rgba(52,211,153,0.2); color: var(--correct); }
.test-q-num.status-wrong { background: rgba(248,113,113,0.2); color: var(--wrong); }
.test-q-num.status-skipped { background: rgba(251,191,36,0.2); color: var(--warn); }
.test-q-year { font-size: 12px; color: var(--text3); flex: 1; }
.test-q-status-icon { flex-shrink: 0; }

.test-results-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
  gap: 6px;
  margin-bottom: 16px;
}
.test-res-cell {
  height: 36px; border-radius: 6px;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 700;
}
.test-res-cell.correct { background: rgba(52,211,153,0.2); color: var(--correct); border: 1px solid var(--correct); }
.test-res-cell.wrong { background: rgba(248,113,113,0.2); color: var(--wrong); border: 1px solid var(--wrong); }
.test-res-cell.skipped { background: rgba(251,191,36,0.15); color: var(--warn); border: 1px solid var(--warn); }

/* Test stats section on stats page */
.test-history-row {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 0; border-bottom: 1px solid var(--border);
}
.test-history-row:last-child { border-bottom: none; }
.test-score-badge {
  font-family: var(--font-mono); font-size: 1.1rem; font-weight: 700;
  color: var(--accent); min-width: 48px;
}
.test-history-bar {
  flex: 1; height: 6px; background: var(--bg4); border-radius: 3px; overflow: hidden;
}
.test-history-bar-fill {
  height: 100%; border-radius: 3px;
  background: linear-gradient(90deg, var(--accent), var(--accent2));
  transition: width 0.4s;
}
.test-history-meta { font-size: 11px; color: var(--text3); min-width: 80px; text-align: right; }

@media (max-width: 700px) {
  .test-progress-grid { grid-template-columns: repeat(15, 1fr); gap: 3px; }
  #test-active > div[style*="grid-template-columns:200px"] { grid-template-columns: 1fr !important; }
  #test-question-nav { display: flex; flex-direction: row; flex-wrap: wrap; padding: 8px; gap: 6px; }
  .test-q-row { flex: none; padding: 6px 10px; gap: 6px; border-bottom: none; border-radius: var(--radius-sm) !important; border: 1px solid var(--border); }
  .test-q-year { display: none; }
}

.asy-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
  min-height: 80px;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid rgba(0,0,0,0.1);
  border-top: 3px solid #4f46e5;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ===== AUTH MODAL ===== */
.auth-overlay {
  position: fixed; inset: 0; background: var(--bg);
  display: flex; align-items: center; justify-content: center;
  z-index: 9999; padding: 20px;
}
.auth-box {
  background: var(--bg2); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 40px;
  width: 100%; max-width: 440px; box-shadow: var(--shadow);
  animation: fadeIn 0.3s ease;
}
.auth-logo {
  display: flex; align-items: center; gap: 12px; margin-bottom: 32px; justify-content: center;
}
.auth-logo .logo-icon { width: 44px; height: 44px; border-radius: 12px; font-size: 22px; }
.auth-logo .logo-text { font-family: var(--font-display); font-size: 26px; }
.auth-title { font-family: var(--font-display); font-size: 1.6rem; color: var(--text); margin-bottom: 6px; text-align: center; }
.auth-sub { color: var(--text3); font-size: 13px; text-align: center; margin-bottom: 28px; }
.auth-input {
  background: var(--bg3); border: 1px solid var(--border); color: var(--text);
  border-radius: var(--radius-sm); padding: 12px 16px;
  font-family: var(--font-body); font-size: 15px; width: 100%;
  outline: none; transition: border-color var(--transition); margin-bottom: 14px; display: block;
}
.auth-input:focus { border-color: var(--accent); }
.auth-btn {
  width: 100%; padding: 13px; border-radius: var(--radius-sm);
  background: var(--accent); color: #fff; font-family: var(--font-body); font-size: 15px;
  font-weight: 700; border: none; cursor: pointer; transition: all var(--transition);
  margin-top: 4px; box-shadow: 0 0 20px var(--accent-glow);
}
.auth-btn:hover { filter: brightness(1.12); }
.auth-switch { text-align: center; margin-top: 20px; font-size: 13px; color: var(--text3); }
.auth-switch a { color: var(--accent); text-decoration: none; cursor: pointer; }
.auth-switch a:hover { text-decoration: underline; }
.auth-error {
  background: rgba(248,113,113,0.1); border: 1px solid var(--wrong); color: var(--wrong);
  border-radius: var(--radius-sm); padding: 10px 14px; font-size: 13px;
  margin-bottom: 14px; display: none;
}
.auth-error.show { display: block; }

/* ===== PROFILE PAGE ===== */
.profile-avatar {
  width: 80px; height: 80px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 32px; flex-shrink: 0;
}
.avatar-picker { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
.avatar-color-btn {
  width: 28px; height: 28px; border-radius: 50%; border: 2px solid transparent; cursor: pointer;
  transition: border-color 0.2s, transform 0.2s;
}
.avatar-color-btn.active, .avatar-color-btn:hover { border-color: var(--text); transform: scale(1.15); }
.avatar-emoji-btn {
  width: 36px; height: 36px; border-radius: var(--radius-sm); border: 1px solid var(--border);
  background: var(--bg3); cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center;
  transition: all var(--transition);
}
.avatar-emoji-btn.active, .avatar-emoji-btn:hover { border-color: var(--accent); background: var(--accent-glow); }

/* ===== PROFILE CARD ===== */
.profile-banner {
  position: relative; height: 140px; border-radius: var(--radius) var(--radius) 0 0;
  overflow: hidden; flex-shrink: 0;
}
.profile-banner-inner {
  width: 100%; height: 100%;
  background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 100%);
  opacity: 0.25;
}
.profile-banner-pattern {
  position: absolute; inset: 0;
  background-image: radial-gradient(circle at 20% 50%, var(--accent) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, var(--accent2) 0%, transparent 40%);
  opacity: 0.3;
}
.profile-card-body {
  padding: 0 28px 28px; position: relative;
}
.profile-avatar-large {
  width: 88px; height: 88px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 36px; flex-shrink: 0;
  border: 4px solid var(--bg2);
  margin-top: -44px; margin-bottom: 14px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.4);
  position: relative; z-index: 1;
}
.profile-username {
  font-family: var(--font-display); font-size: 1.6rem; color: var(--text); line-height: 1.1;
}
.profile-handle { font-size: 13px; color: var(--text3); margin-top: 3px; font-family: var(--font-mono); }
.profile-bio-text {
  font-size: 14px; color: var(--text2); margin-top: 12px; line-height: 1.7;
  max-width: 540px;
}
.profile-meta-row {
  display: flex; flex-wrap: wrap; gap: 18px; margin-top: 16px;
}
.profile-meta-item {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--text3);
}
.profile-meta-item svg { flex-shrink: 0; }
.profile-stats-row {
  display: flex; gap: 28px; margin-top: 20px; flex-wrap: wrap;
}
.profile-stat {
  text-align: center;
}
.profile-stat-value {
  font-family: var(--font-display); font-size: 1.5rem; color: var(--text); line-height: 1;
}
.profile-stat-label {
  font-size: 11px; color: var(--text3); margin-top: 3px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 600;
}
.profile-divider { width: 1px; background: var(--border); align-self: stretch; }
.profile-section-title {
  font-size: 11px; text-transform: uppercase; letter-spacing: 0.12em; color: var(--text3);
  font-weight: 700; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid var(--border);
}
.profile-comp-bar-row {
  display: flex; align-items: center; gap: 10px; margin-bottom: 10px;
}
.profile-comp-label { font-size: 12px; color: var(--text2); width: 80px; flex-shrink: 0; font-weight: 500; }
.profile-comp-bar-bg { flex: 1; height: 6px; background: var(--bg4); border-radius: 3px; overflow: hidden; }
.profile-comp-bar-fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg, var(--accent), var(--accent2)); transition: width 0.6s ease; }
.profile-comp-pct { font-size: 11px; color: var(--text3); width: 32px; text-align: right; font-family: var(--font-mono); }
.profile-badge-row { display: flex; flex-wrap: wrap; gap: 8px; }
.profile-achievement {
  display: flex; align-items: center; gap: 8px; padding: 10px 14px;
  background: var(--bg3); border: 1px solid var(--border); border-radius: var(--radius-sm);
  font-size: 12px; color: var(--text2);
}
.profile-achievement.earned { border-color: var(--accent); background: var(--accent-glow); color: var(--text); }
.profile-achievement.earned .ach-icon { filter: none; }
.ach-icon { font-size: 20px; filter: grayscale(1) opacity(0.4); }
.profile-achievement.earned .ach-icon { filter: none; }
.ach-info { display: flex; flex-direction: column; }
.ach-name { font-weight: 600; font-size: 12px; }
.ach-desc { font-size: 11px; color: var(--text3); margin-top: 1px; }
.profile-heatmap {
  display: grid; grid-template-columns: repeat(26, 1fr); gap: 3px;
}
.heatmap-cell {
  aspect-ratio: 1; border-radius: 2px; background: var(--bg4);
  transition: background 0.2s;
}
.heatmap-cell[data-count="1"] { background: color-mix(in srgb, var(--accent) 30%, var(--bg4)); }
.heatmap-cell[data-count="2"] { background: color-mix(in srgb, var(--accent) 55%, var(--bg4)); }
.heatmap-cell[data-count="3"] { background: color-mix(in srgb, var(--accent) 75%, var(--bg4)); }
.heatmap-cell[data-count="4"] { background: var(--accent); }
@supports not (color: color-mix(in srgb, red 50%, blue)) {
  .heatmap-cell[data-count="1"] { background: rgba(108,142,245,0.25); }
  .heatmap-cell[data-count="2"] { background: rgba(108,142,245,0.5); }
  .heatmap-cell[data-count="3"] { background: rgba(108,142,245,0.75); }
  .heatmap-cell[data-count="4"] { background: var(--accent); }
}
@media (max-width: 700px) {
  .profile-banner { height: 100px; }
  .profile-card-body { padding: 0 16px 20px; }
  .profile-avatar-large { width: 70px; height: 70px; font-size: 28px; margin-top: -35px; }
  .profile-stats-row { gap: 16px; }
  .profile-heatmap { grid-template-columns: repeat(18, 1fr); }
}

/* ===== BANNER DESIGNER ===== */
.banner-style-picker { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
.banner-style-btn {
  width: 72px; height: 40px; border-radius: var(--radius-sm);
  border: 2px solid var(--border); cursor: pointer; overflow: hidden;
  transition: border-color 0.2s, transform 0.2s; position: relative;
}
.banner-style-btn:hover { border-color: var(--text2); transform: scale(1.05); }
.banner-style-btn.active { border-color: var(--accent); }
.banner-style-btn span {
  position: absolute; bottom: 3px; left: 0; right: 0;
  text-align: center; font-size: 9px; color: rgba(255,255,255,0.85);
  font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.5); letter-spacing: 0.04em;
}
.color-pair { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.color-pair label { font-size: 12px; color: var(--text3); white-space: nowrap; }
.color-input-wrap { position: relative; display: flex; align-items: center; gap: 6px; }
.color-swatch {
  width: 28px; height: 28px; border-radius: 6px; border: 2px solid var(--border);
  cursor: pointer; flex-shrink: 0; transition: border-color 0.2s;
}
.color-swatch:hover { border-color: var(--text2); }
input[type="color"] { position: absolute; opacity: 0; width: 28px; height: 28px; cursor: pointer; }
.angle-row { display: flex; align-items: center; gap: 10px; margin-top: 8px; }
.angle-row input[type="range"] { flex: 1; accent-color: var(--accent); }
.angle-row span { font-size: 12px; color: var(--text3); font-family: var(--font-mono); width: 36px; }
.banner-preview {
  width: 100%; height: 80px; border-radius: var(--radius-sm);
  border: 1px solid var(--border); margin-bottom: 12px; overflow: hidden;
  position: relative;
}

/* ===== AVATAR UPLOAD ===== */
.avatar-upload-zone {
  position: relative; width: 88px; height: 88px; flex-shrink: 0; cursor: pointer;
}

.avatar-upload-overlay {
  position: absolute; inset: 0; border-radius: 50%;
  background: rgba(0,0,0,0.55); display: flex; align-items: center; justify-content: center;
  opacity: 0; transition: opacity 0.2s;
}
.avatar-upload-zone:hover .avatar-upload-overlay { opacity: 1; }
.avatar-upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; border-radius: 50%; }
.avatar-edit-badge {
  position: absolute; bottom: 2px; right: 2px;
  width: 22px; height: 22px; border-radius: 50%;
  background: var(--accent); border: 2px solid var(--bg2);
  display: flex; align-items: center; justify-content: center;
}

.avatar-edit-badge {
    z-index:999;
}

/* ===== USER CHIP ===== */
.user-chip {
  display: flex; align-items: center; gap: 8px; padding: 8px 12px;
  border-radius: var(--radius-sm); cursor: pointer; transition: background var(--transition);
  position: relative; user-select: none;
}
.user-chip:hover { background: var(--bg3); }
.user-chip-avatar {
  width: 28px; height: 28px; border-radius: 50%; font-size: 13px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.user-chip-name { font-size: 13px; color: var(--text2); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
.user-menu {
  position: absolute; bottom: calc(100% + 6px); left: 0; right: 0;
  background: var(--bg2); border: 1px solid var(--border);
  border-radius: var(--radius-sm); padding: 4px;
  box-shadow: var(--shadow); display: none; z-index: 100; min-width: 160px;
}
.user-menu.open { display: block; }
.user-menu-item {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 12px; border-radius: 6px; cursor: pointer;
  font-size: 13px; color: var(--text2); transition: all var(--transition);
}
.user-menu-item:hover { background: var(--bg3); color: var(--text); }
.user-menu-item.danger { color: var(--wrong); }

/* ===== ADMIN PANEL ===== */
.admin-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.admin-table th, .admin-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border); }
.admin-table th { color: var(--text3); font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; font-size: 11px; }
.admin-table tr:hover td { background: var(--bg3); }
.role-badge { padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
.role-badge.admin { background: rgba(108,142,245,0.2); color: var(--accent); }
.role-badge.user { background: var(--bg4); color: var(--text3); }
</style>
</head>
<body>
<?php if (!$user): ?>
<!-- ===== AUTH OVERLAY ===== -->
<div class="auth-overlay" id="auth-overlay" data-theme="dark">
  <div class="auth-box">
    <div class="auth-logo">
      <div class="logo-icon" style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#6c8ef5,#a78bfa);display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;font-size:22px;color:#fff;">∑</div>
      <div class="logo-text" style="font-family:'DM Serif Display',serif;font-size:26px;">AIME<span style="color:#6c8ef5;">lab</span></div>
    </div>

    <!-- LOGIN FORM -->
    <div id="login-form">
      <div class="auth-title">Welcome back</div>
      <div class="auth-sub">Sign in to your account to continue</div>
      <div class="auth-error" id="login-error"></div>
      <input class="auth-input" type="text" id="login-user" placeholder="Username or email" autocomplete="username"/>
      <input class="auth-input" type="password" id="login-pass" placeholder="Password" autocomplete="current-password"/>
      <button class="auth-btn" id="login-btn">Sign In</button>
      <div class="auth-switch">Don't have an account? <a onclick="showRegister()">Create one →</a></div>
    </div>

    <!-- REGISTER FORM -->
    <div id="register-form" style="display:none;">
      <div class="auth-title">Create account</div>
      <div class="auth-sub">Start tracking your progress today</div>
      <div class="auth-error" id="register-error"></div>
      <input class="auth-input" type="text" id="reg-username" placeholder="Username (3–32 chars, no spaces)" autocomplete="username"/>
      <input class="auth-input" type="email" id="reg-email" placeholder="Email address" autocomplete="email"/>
      <input class="auth-input" type="password" id="reg-pass" placeholder="Password (8+ characters)" autocomplete="new-password"/>
      <button class="auth-btn" id="register-btn">Create Account</button>
      <div class="auth-switch">Already have an account? <a onclick="showLogin()">Sign in →</a></div>
    </div>
  </div>
</div>
<script>
// Minimal auth JS (runs before app init)
function showLogin() {
  document.getElementById('login-form').style.display = '';
  document.getElementById('register-form').style.display = 'none';
}
function showRegister() {
  document.getElementById('login-form').style.display = 'none';
  document.getElementById('register-form').style.display = '';
}
<?php if ($auth_mode === 'register'): ?>showRegister();<?php endif; ?>

async function authPost(action, body) {
  const r = await fetch('api/api.php?action=' + action, {
    method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body)
  });
  return r.json();
}

document.getElementById('login-btn').addEventListener('click', async () => {
  const err = document.getElementById('login-error');
  err.classList.remove('show');
  const res = await authPost('login', {
    login: document.getElementById('login-user').value,
    password: document.getElementById('login-pass').value
  });
  if (res.ok) { location.href = 'index.php'; }
  else { err.textContent = (res.errors||['Login failed']).join(' '); err.classList.add('show'); }
});
document.getElementById('register-btn').addEventListener('click', async () => {
  const err = document.getElementById('register-error');
  err.classList.remove('show');
  const res = await authPost('register', {
    username: document.getElementById('reg-username').value,
    email: document.getElementById('reg-email').value,
    password: document.getElementById('reg-pass').value
  });
  if (res.ok) { location.href = 'index.php'; }
  else { err.textContent = (res.errors||['Registration failed']).join(' '); err.classList.add('show'); }
});
['login-user','login-pass','reg-username','reg-email','reg-pass'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      const btn = document.getElementById('login-form').style.display !== 'none'
        ? document.getElementById('login-btn')
        : document.getElementById('register-btn');
      btn.click();
    }
  });
});
</script>
</body>
</html>
<?php exit; endif; // End unauthenticated block ?>

<div id="loading-overlay">
  <div class="loader"></div>
  <p>Loading dataset…</p>
</div>

<div id="app">

<nav id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">∑</div>
    <div class="logo-text">AIME<span>lab</span></div>
  </div>
  <div class="sidebar-nav">
    <div class="nav-item active" data-page="home">
      <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
      <span>Home</span>
    </div>
    <div class="nav-item" data-page="practice">
      <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></span>
      <span>Practice</span>
    </div>
    <div class="nav-item" data-page="browse">
      <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span>
      <span>Browse</span>
    </div>
    <div class="nav-item" data-page="stats">
      <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
      <span>Statistics</span>
    </div>
    <div class="nav-item" data-page="history">
      <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5"/></svg></span>
      <span>History</span>
    </div>
    <div class="nav-item" data-page="tests">
      <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
      <span>Tests</span>
    </div>
    <div class="nav-item" data-page="profile">
      <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
      <span>Profile</span>
    </div>
    <div class="nav-item" data-page="settings">
      <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
      <span>Settings</span>
    </div>
    <?php if ($user['role'] === 'admin'): ?>
    <div class="nav-item" data-page="admin">
      <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
      <span>Admin</span>
    </div>
    <?php endif; ?>
  </div>
  <div class="sidebar-footer">
    <a href="https://github.com/Dragjon/AIMElab" target="_blank" rel="noopener" class="nav-item" style="text-decoration:none;">
      <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg></span>
      <span>GitHub</span>
    </a>
    <!-- User chip -->
    <div class="user-chip" id="user-chip-btn">
      <div class="user-chip-avatar" id="sidebar-avatar" style="background:<?= htmlspecialchars($user['avatar_color']) ?>;"><?= htmlspecialchars($user['avatar_emoji']) ?></div>
      <div class="user-chip-name"><?= htmlspecialchars($user['display_name'] ?: $user['username']) ?></div>
      <div class="user-menu" id="user-menu">
        <div class="user-menu-item" onclick="navigate('profile')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Profile
        </div>
        <div class="user-menu-item danger" id="logout-btn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Sign Out
        </div>
      </div>
    </div>
    <div class="nav-item" id="reset-progress-btn" style="color: var(--wrong);">
      <span class="nav-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg></span>
      <span>Reset Data</span>
    </div>
  </div>
</nav>
<!-- Mobile bottom navigation -->
<nav id="mobile-nav">
  <div class="mobile-nav-inner">
    <button class="mobile-nav-item active" data-page="home">
      <span class="mobile-nav-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
      <span>Home</span>
    </button>
    <button class="mobile-nav-item" data-page="practice">
      <span class="mobile-nav-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></span>
      <span>Practice</span>
    </button>
    <button class="mobile-nav-item" data-page="browse">
      <span class="mobile-nav-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span>
      <span>Browse</span>
    </button>
    <button class="mobile-nav-item" data-page="stats">
      <span class="mobile-nav-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
      <span>Stats</span>
    </button>
    <button class="mobile-nav-item" id="mobile-more-btn">
      <span class="mobile-nav-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="1" fill="currentColor"/><circle cx="12" cy="12" r="1" fill="currentColor"/><circle cx="12" cy="19" r="1" fill="currentColor"/></svg></span>
      <span>More</span>
    </button>
  </div>
</nav>
<div id="mobile-overlay-backdrop"></div>
<div id="mobile-more-menu">
  <div class="mobile-menu-handle"></div>
  <div class="mobile-menu-title">More</div>
  <div class="mobile-menu-item" data-page="history">
    <span class="menu-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5"/></svg></span>History
  </div>
  <div class="mobile-menu-item" data-page="tests">
    <span class="menu-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>Tests
  </div>
  <div class="mobile-menu-item" data-page="settings">
    <span class="menu-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>Settings
  </div>
  <a href="https://github.com/Dragjon/AIMElab" target="_blank" rel="noopener" class="mobile-menu-item" style="text-decoration:none; color:var(--text2);">
    <span class="menu-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg></span>
    GitHub
  </a>
  <div class="mobile-menu-item" id="mobile-reset-btn" style="color:var(--wrong);">
    <span class="menu-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg></span>Reset Data
  </div>
</div>

<main id="main">
  <div class="page active" id="page-home">
    <div class="page-header">
      <div class="page-title">Welcome back <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-5px;color:var(--accent)"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg></div>
      <div class="page-subtitle">Practice AMC 8, AMC 10, AMC 12, and AIME problems. Filter by competition, track your history, run mock tests, and analyze your performance across 3600+ unique problems.</div>
    </div>

    <div class="stats-grid" id="home-stats"></div>

    <div class="card">
      <div class="card-title">Quick Start</div>
      <div class="flex-row" style="flex-wrap:wrap; gap:12px;">
        <button class="btn btn-primary btn-lg" id="quick-random"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/></svg> Random Problem</button>
        <button class="btn btn-secondary btn-lg" id="quick-unseen"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Unseen Problem</button>
        <button class="btn btn-secondary btn-lg" id="quick-wrong"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg> Retry a Wrong One</button>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
      <div class="card">
        <div class="card-title">Accuracy by Year Range</div>
        <div class="chart-wrap"><canvas id="chart-year-acc"></canvas></div>
      </div>
      <div class="card">
        <div class="card-title">Recent Activity (last 14 days)</div>
        <div class="chart-wrap"><canvas id="chart-recent"></canvas></div>
      </div>
    </div>
  </div>

  <div class="page" id="page-practice">
    <div class="page-header">
      <div class="page-title">Practice</div>
      <div class="page-subtitle">Configure your session and start solving.</div>
    </div>

    <div class="card" id="practice-config">
      <div class="card-title">Session Settings</div>
      <div class="filter-row">
        <div class="form-group">
          <label>Competition</label>
          <select id="filter-comp">
            <option value="all">All</option>
          </select>
        </div>
        <div class="form-group">
          <label>Year</label>
          <select id="filter-year">
            <option value="all">All Years</option>
          </select>
        </div>
        <div class="form-group">
          <label>Problem #</label>
          <select id="filter-prob">
            <option value="all">Any</option>
          </select>
        </div>
      </div>
      <div class="flex-row mt-2" style="margin-bottom:16px;">
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin:0;">
          <input type="checkbox" id="no-repeat" checked style="width:16px;height:16px;accent-color:var(--accent)"/>
          <span style="font-size:13px; color:var(--text2);">Skip already-seen problems</span>
        </label>
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin:0;">
          <input type="checkbox" id="only-wrong" style="width:16px;height:16px;accent-color:var(--accent)"/>
          <span style="font-size:13px; color:var(--text2);">Only wrong/unseen problems</span>
        </label>
      </div>
      <button class="btn btn-primary" id="start-practice">▶ Start Session</button>
    </div>

    <div class="card" id="question-card">
      <div class="flex-between" style="margin-bottom:16px;">
        <div class="question-meta" id="question-meta"></div>
        <button class="btn btn-secondary btn-sm" id="skip-btn">Skip →</button>
      </div>

      <div class="progress-bar-wrap" style="margin-bottom:16px;">
        <div class="progress-bar-fill" id="session-progress" style="width:0%"></div>
      </div>
      <div class="text-muted" id="session-counter" style="margin-bottom:20px;"></div>

      <div class="question-body" id="question-body"></div>

      <div class="answer-area">
        <div class="form-group" style="margin:0;">
          <label id="answer-input-label">Your Answer</label>
          <input type="text" id="answer-input" placeholder="e.g. 42"/>
        </div>
        <button class="btn btn-primary" id="submit-btn">Submit</button>
        <button class="btn btn-secondary" id="reveal-btn">Reveal</button>
      </div>

      <div class="result-banner" id="result-banner"></div>

      <div id="post-answer-area" style="display:none; margin-top:20px;">
        <div class="flex-row">
          <button class="btn btn-primary" id="next-btn">Next Problem →</button>
          <button class="btn btn-secondary" id="end-session-btn">End Session</button>
        </div>
      </div>
    </div>
  </div>

  <div class="page" id="page-browse">
    <div class="page-header">
      <div class="page-title">Browse Problems</div>
      <div class="page-subtitle">Explore by year and part. Green = correct, Red = wrong, Grey = unseen.</div>
    </div>
    <div class="card">
      <div class="filter-row">
        <div class="form-group">
          <label>Competition</label>
          <select id="browse-comp"></select>
        </div>
        <div class="form-group">
          <label>Year</label>
          <select id="browse-year"></select>
        </div>
        <div class="form-group">
          <label>Sort By</label>
          <select id="browse-sort">
            <option value="num">Problem #</option>
            <option value="status">Status</option>
          </select>
        </div>
      </div>
      <div class="prob-grid" id="browse-grid"></div>
    </div>
    <div class="card" id="browse-problem-card" style="display:none;">
      <div class="flex-between" style="margin-bottom:16px;">
        <div class="question-meta" id="browse-question-meta"></div>
        <button class="btn btn-primary btn-sm" id="browse-practice-btn">Practice This →</button>
      </div>
      <div class="question-body" id="browse-question-body"></div>
      <div class="mt-4">
        <button class="btn btn-secondary btn-sm" id="browse-reveal-ans">Show Answer</button>
        <span id="browse-answer-reveal" style="display:none; margin-left:12px; font-family:var(--font-mono); font-size:1.1rem; color:var(--accent); font-weight:700;"></span>
      </div>
    </div>
  </div>

  <div class="page" id="page-stats">
    <div class="page-header">
      <div class="page-title">Statistics</div>
      <div class="page-subtitle">Your performance over time.</div>
    </div>

    <div class="toggle-group" style="margin-bottom:24px;" id="stats-time-filter">
      <div class="toggle-btn active" data-range="week">This Week</div>
      <div class="toggle-btn" data-range="month">This Month</div>
      <div class="toggle-btn" data-range="year">This Year</div>
      <div class="toggle-btn" data-range="all">All Time</div>
    </div>

    <div class="stats-grid" id="stats-summary"></div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
      <div class="card">
        <div class="card-title">Correct vs Wrong Over Time</div>
        <div class="chart-wrap"><canvas id="chart-timeline"></canvas></div>
      </div>
      <div class="card">
        <div class="card-title">Accuracy by Competition</div>
        <div class="chart-wrap"><canvas id="chart-by-part"></canvas></div>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
      <div class="card">
        <div class="card-title">Accuracy by Problem Number</div>
        <div class="chart-wrap"><canvas id="chart-by-probnum"></canvas></div>
      </div>
      <div class="card">
        <div class="card-title">Accuracy Heatmap by Year</div>
        <div class="chart-wrap"><canvas id="chart-by-year"></canvas></div>
      </div>
    </div>

    <div class="card" style="margin-top:20px;">
      <div class="card-title">Test Performance</div>
      <div class="stats-grid" id="test-stats-summary" style="margin-bottom:20px;"></div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
        <div>
          <div class="card-title" style="margin-bottom:10px;">Score Distribution</div>
          <div class="chart-wrap"><canvas id="chart-test-scores"></canvas></div>
        </div>
        <div>
          <div class="card-title" style="margin-bottom:10px;">Accuracy by Problem Position</div>
          <div class="chart-wrap"><canvas id="chart-test-by-pos"></canvas></div>
        </div>
      </div>
      <div class="card-title" style="margin-bottom:10px;">Recent Tests</div>
      <div id="test-history-list"></div>
    </div>
  </div>

  <div class="page" id="page-tests">
    <div class="page-header">
      <div class="page-title">Mock Test</div>
      <div class="page-subtitle">One question per problem number, difficulty preserved by position. Works for AIME and AMC competitions.</div>
    </div>

    <!-- Config card -->
    <div class="card" id="test-config">
      <div class="card-title">Test Settings</div>
      <div class="filter-row" style="margin-bottom:16px;">
        <div class="form-group">
          <label>Competition</label>
          <select id="test-comp">
            <option value="AIME">AIME (all)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Year</label>
          <select id="test-year">
            <option value="all">All Years</option>
          </select>
        </div>
      </div>
      <div class="flex-row mt-2" style="margin-bottom:20px; flex-wrap:wrap; gap:16px;">
        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin:0;">
          <input type="checkbox" id="test-no-repeat" checked style="width:16px;height:16px;accent-color:var(--accent)"/>
          <span style="font-size:13px; color:var(--text2);">Skip already-attempted problems</span>
        </label>
      </div>
      <p class="text-muted" style="margin-bottom:16px;">One question is randomly selected for each problem number. Questions may come from different years but difficulty is preserved by position.</p>
      <button class="btn btn-primary btn-lg" id="start-test-btn">▶ Start Test</button>
    </div>

    <!-- Active test UI -->
    <div id="test-active" style="display:none;">
      <!-- Question navigator -->
      <div class="card" style="padding:16px 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
          <span style="font-size:12px; color:var(--text3); font-weight:600; text-transform:uppercase; letter-spacing:0.06em;">Question <span id="test-q-label">1</span> of <span id="test-q-total">?</span></span>
          <span style="font-size:12px; color:var(--text3);"><span id="test-answered-count">0</span> answered</span>
        </div>
        <div class="test-progress-grid" id="test-progress-dots"></div>
      </div>

      <div style="display:grid; grid-template-columns:200px 1fr; gap:16px; align-items:start;">
        <!-- Question list sidebar -->
        <div class="card" style="padding:0; overflow:hidden;">
          <div id="test-question-nav" class="test-question-list"></div>
        </div>

        <!-- Question display -->
        <div class="card" id="test-question-card">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
            <div class="question-meta" id="test-question-meta"></div>
            <button class="btn btn-secondary btn-sm" id="test-submit-btn">Submit</button>
          </div>

          <div class="question-body" id="test-question-body"></div>

          <div class="answer-area" style="margin-top:20px; align-items:flex-end;">
            <div class="form-group" style="margin:0;">
              <label id="test-answer-input-label">Your Answer</label>
              <input type="text" id="test-answer-input" placeholder="e.g. 42" style="height:50px; font-size:1.2rem; font-family:var(--font-mono); text-align:center;"/>
            </div>
            <button class="btn btn-primary" id="test-submit-btn2" style="height:50px;">Submit</button>
            <button class="btn btn-secondary" id="test-next-btn" style="height:50px;">Next →</button>
          </div>

          <div class="result-banner" id="test-result-banner"></div>
        </div>
      </div>

      <div style="display:flex; justify-content:flex-end; margin-top:16px; gap:12px;">
        <button class="btn btn-secondary" id="test-abandon-btn">Abandon Test</button>
        <button class="btn btn-primary" id="test-finish-btn">Finish &amp; Score →</button>
      </div>
    </div>

    <!-- Results card -->
    <div class="card" id="test-results" style="display:none;">
      <div class="page-header" style="margin-bottom:20px;">
        <div class="page-title" id="test-score-title"></div>
        <div class="page-subtitle" id="test-score-subtitle"></div>
      </div>
      <div class="stats-grid" id="test-result-stats" style="margin-bottom:20px;"></div>
      <div class="card-title" style="margin-bottom:10px;">Per-Question Breakdown</div>
      <div class="test-results-grid" id="test-results-grid"></div>
      <div id="test-results-detail"></div>
      <div class="flex-row mt-4">
        <button class="btn btn-primary" id="test-again-btn">New Test</button>
        <button class="btn btn-secondary" id="test-review-btn">Review Mistakes</button>
      </div>
    </div>
  </div>

  <div class="page" id="page-history">
    <div class="page-header">
      <div class="flex-between">
        <div>
          <div class="page-title">Attempt History</div>
          <div class="page-subtitle">Every problem you've attempted.</div>
        </div>
        <button class="btn btn-secondary btn-sm" id="export-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Export CSV</button>
      </div>
    </div>
    <div class="filter-row">
      <div class="form-group">
        <label>Competition</label>
        <select id="hist-comp"><option value="all">All</option></select>
      </div>
      <div class="form-group">
        <label>Year</label>
        <select id="hist-year"><option value="all">All</option></select>
      </div>
      <div class="form-group">
        <label>Result</label>
        <select id="hist-result">
          <option value="all">All</option>
          <option value="correct">Correct</option>
          <option value="wrong">Wrong</option>
        </select>
      </div>
      <div class="form-group">
        <label>Sort By</label>
        <select id="hist-sort">
          <option value="date">Date</option>
          <option value="comp">Competition</option>
          <option value="year">Year</option>
        </select>
      </div>
    </div>
    <div class="card" style="padding:0; overflow:hidden;">
      <div id="history-col-toggle" style="display:none; padding:8px 12px; border-bottom:1px solid var(--border);">
        <button id="history-expand-btn" class="btn btn-secondary btn-sm" style="width:100%; justify-content:center; gap:6px;">
          <svg id="history-expand-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
          Show all columns
        </button>
      </div>
      <div id="history-container" style="max-width:100%;">
        <table class="history-table">
          <thead>
            <tr>
              <th class="col-id">ID</th>
              <th>Competition</th>
              <th>Year</th>
              <th>Prob #</th>
              <th>Your Ans</th>
              <th class="col-correct-ans">Correct Ans</th>
              <th>Result</th>
              <th>Date</th>
              <th style="width:32px;"></th>
              <th style="width:32px;"></th>
            </tr>
          </thead>
          <tbody id="history-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="page" id="page-settings">
    <div class="page-header">
      <div class="page-title">Settings</div>
      <div class="page-subtitle">Customise your AIMElab experience.</div>
    </div>

    <div class="card settings-section">
      <div class="settings-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-3px;margin-right:7px"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>Appearance</div>

      <div class="form-group">
        <label>Theme</label>
        <div class="theme-grid">
          <div class="theme-swatch theme-dark active" data-theme="dark"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Dark</div></div>
          <div class="theme-swatch theme-light" data-theme="light"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Light</div></div>
          <div class="theme-swatch theme-sepia" data-theme="sepia"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Sepia</div></div>
          <div class="theme-swatch theme-forest" data-theme="forest"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Forest</div></div>
          <div class="theme-swatch theme-midnight" data-theme="midnight"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Midnight</div></div>
          <div class="theme-swatch theme-rose" data-theme="rose"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Rose</div></div>
          <div class="theme-swatch theme-ocean" data-theme="ocean"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Ocean</div></div>
          <div class="theme-swatch theme-dracula" data-theme="dracula"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Dracula</div></div>
          <div class="theme-swatch theme-sunset" data-theme="sunset"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Sunset</div></div>
          <div class="theme-swatch theme-arctic" data-theme="arctic"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Arctic</div></div>
          <div class="theme-swatch theme-candy" data-theme="candy"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Candy</div></div>
          <div class="theme-swatch theme-mocha" data-theme="mocha"><div class="theme-swatch-bar"><span></span><span></span><span></span></div><div class="theme-swatch-label">Mocha</div></div>
        </div>
      </div>

      <div class="form-group mt-4">
        <label>Font Size</label>
        <div class="font-size-grid">
          <div class="font-size-btn" data-size="small">Aa</div>
          <div class="font-size-btn active" data-size="medium">Aa</div>
          <div class="font-size-btn" data-size="large">Aa</div>
          <div class="font-size-btn" data-size="xl">Aa</div>
        </div>
      </div>
    </div>

    <div class="card settings-section">
      <div class="settings-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-3px;margin-right:7px"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Practice</div>
      <div class="setting-row">
        <div class="setting-info">
          <div class="setting-name">Auto-advance after correct answer</div>
          <div class="setting-desc">Automatically move to next problem when you answer correctly</div>
        </div>
        <div class="setting-control">
          <label class="switch"><input type="checkbox" id="setting-autoadvance"><span class="slider"></span></label>
        </div>
      </div>
      <div class="setting-row">
        <div class="setting-info">
          <div class="setting-name">Show problem ID badge</div>
          <div class="setting-desc">Display the AIME problem ID while practicing</div>
        </div>
        <div class="setting-control">
          <label class="switch"><input type="checkbox" id="setting-showid" checked><span class="slider"></span></label>
        </div>
      </div>
      <div class="setting-row">
        <div class="setting-info">
          <div class="setting-name">Confirm before skipping</div>
          <div class="setting-desc">Ask for confirmation before skipping a problem</div>
        </div>
        <div class="setting-control">
          <label class="switch"><input type="checkbox" id="setting-confirm-skip"><span class="slider"></span></label>
        </div>
      </div>
    </div>

    <div class="card settings-section">
      <div class="settings-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-3px;margin-right:7px"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>Accessibility</div>
      <div class="setting-row">
        <div class="setting-info">
          <div class="setting-name">High contrast mode</div>
          <div class="setting-desc">Increase contrast for better readability</div>
        </div>
        <div class="setting-control">
          <label class="switch"><input type="checkbox" id="setting-hcontrast"><span class="slider"></span></label>
        </div>
      </div>
      <div class="setting-row">
        <div class="setting-info">
          <div class="setting-name">Reduce motion</div>
          <div class="setting-desc">Minimize animations and transitions</div>
        </div>
        <div class="setting-control">
          <label class="switch"><input type="checkbox" id="setting-reducemotion"><span class="slider"></span></label>
        </div>
      </div>
      <div class="setting-row">
        <div class="setting-info">
          <div class="setting-name">Large click targets</div>
          <div class="setting-desc">Increase button sizes for easier clicking</div>
        </div>
        <div class="setting-control">
          <label class="switch"><input type="checkbox" id="setting-largetargets"><span class="slider"></span></label>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="settings-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-3px;margin-right:7px"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>Data</div>
      <p class="text-muted" style="margin-bottom:16px;">All your data is stored locally in your browser. Nothing is sent to any server.</p>
      <div class="flex-row">
        <button class="btn btn-secondary" id="export-data-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Export Data (JSON)</button>
        <button class="btn btn-danger" id="clear-data-btn"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg> Clear All Data</button>
      </div>
    </div>
  </div>

  <!-- ===== PROFILE PAGE ===== -->
  <div class="page" id="page-profile">

    <!-- ══ PUBLIC PROFILE CARD ══ -->
    <div class="card" style="padding:0;overflow:hidden;margin-bottom:20px;" id="profile-card-view">
      <div class="profile-banner">
        <div class="profile-banner-inner"></div>
        <div class="profile-banner-pattern"></div>
      </div>
      <div class="profile-card-body">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
          <div>
            <div class="profile-avatar-large" id="profile-preview-avatar" style="background:<?= htmlspecialchars($user['avatar_color']) ?>;"><?= htmlspecialchars($user['avatar_emoji']) ?></div>
            <div class="profile-username" id="profile-view-name"><?= htmlspecialchars($user['display_name'] ?: $user['username']) ?></div>
            <div class="profile-handle">@<?= htmlspecialchars($user['username']) ?><?php if($user['role']==='admin'): ?> <span style="color:var(--accent);font-family:var(--font-body);font-weight:700;">· Admin</span><?php endif; ?></div>
            <div class="profile-bio-text" id="profile-view-bio"><?= nl2br(htmlspecialchars($user['bio'] ?? '')) ?: '<span style="color:var(--text3);font-style:italic;">No bio yet.</span>' ?></div>
            <div class="profile-meta-row">
              <div class="profile-meta-item">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Joined <?= date('M Y', strtotime($user['created_at'])) ?>
              </div>
              <div class="profile-meta-item" id="profile-meta-streak">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c0 0-4 4-4 9a4 4 0 0 0 8 0c0-5-4-9-4-9z"/><path d="M12 11c0 0-2 2-2 4a2 2 0 0 0 4 0c0-2-2-4-2-4z" fill="currentColor"/></svg>
                <span id="profile-streak-val">—</span> day streak
              </div>
            </div>
          </div>
          <div class="flex-row" style="margin-top:8px;flex-wrap:wrap;">
            <button class="btn btn-secondary btn-sm" id="profile-edit-toggle">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              Edit Profile
            </button>
            <button class="btn btn-secondary btn-sm" id="share-profile-btn">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
              <span id="share-profile-btn-text">Share Profile</span>
            </button>
          </div>
        </div>

        <!-- Stats row -->
        <div class="profile-stats-row" id="profile-stats-row">
          <div class="profile-stat">
            <div class="profile-stat-value" id="pstat-attempts">—</div>
            <div class="profile-stat-label">Attempts</div>
          </div>
          <div class="profile-divider"></div>
          <div class="profile-stat">
            <div class="profile-stat-value" id="pstat-correct">—</div>
            <div class="profile-stat-label">Correct</div>
          </div>
          <div class="profile-divider"></div>
          <div class="profile-stat">
            <div class="profile-stat-value" id="pstat-acc">—%</div>
            <div class="profile-stat-label">Accuracy</div>
          </div>
          <div class="profile-divider"></div>
          <div class="profile-stat">
            <div class="profile-stat-value" id="pstat-unique">—</div>
            <div class="profile-stat-label">Unique Problems</div>
          </div>
          <div class="profile-divider"></div>
          <div class="profile-stat">
            <div class="profile-stat-value" id="pstat-tests">—</div>
            <div class="profile-stat-label">Tests Taken</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ ACTIVITY HEATMAP ══ -->
    <div class="card" style="margin-bottom:20px;">
      <div class="profile-section-title">Activity — Last 26 Weeks</div>
      <div class="profile-heatmap" id="profile-heatmap"></div>
      <div style="display:flex;align-items:center;gap:6px;margin-top:10px;font-size:11px;color:var(--text3);">
        Less
        <div style="width:10px;height:10px;border-radius:2px;background:var(--bg4);"></div>
        <div style="width:10px;height:10px;border-radius:2px;" class="heatmap-cell" data-count="1"></div>
        <div style="width:10px;height:10px;border-radius:2px;" class="heatmap-cell" data-count="2"></div>
        <div style="width:10px;height:10px;border-radius:2px;" class="heatmap-cell" data-count="3"></div>
        <div style="width:10px;height:10px;border-radius:2px;" class="heatmap-cell" data-count="4"></div>
        More
      </div>
    </div>

    <!-- ══ ACCURACY BY COMPETITION ══ -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
      <div class="card">
        <div class="profile-section-title">Accuracy by Competition</div>
        <div id="profile-comp-bars"></div>
      </div>
      <div class="card">
        <div class="profile-section-title">Achievements</div>
        <div class="profile-badge-row" id="profile-achievements"></div>
      </div>
    </div>

    <!-- ══ EDIT FORM (hidden by default) ══ -->
    <div id="profile-edit-section" style="display:none;">
      <div class="card" style="margin-bottom:16px;overflow:visible;">
        <div class="profile-section-title" style="padding:20px 24px 0;">Edit Profile</div>

        <!-- Banner Preview -->
        <div style="padding:16px 24px 0;">
          <label style="font-size:13px;color:var(--text2);font-weight:500;display:block;margin-bottom:8px;">Banner Preview</label>
          <div class="banner-preview" id="banner-preview-box"></div>
        </div>

        <!-- Banner Style -->
        <div style="padding:0 24px;">
          <div class="form-group">
            <label>Banner Style</label>
            <div class="banner-style-picker" id="banner-style-picker">
              <?php
              $bstyles = ['gradient'=>'Gradient','solid'=>'Solid','mesh'=>'Mesh','wave'=>'Wave','dots'=>'Dots','none'=>'None'];
              $cur_bs  = $user['banner_style'] ?? 'gradient';
              $bc1     = $user['banner_color1'] ?? '#6c8ef5';
              $bc2     = $user['banner_color2'] ?? '#a78bfa';
              foreach ($bstyles as $val => $label):
              ?>
              <div class="banner-style-btn <?= $cur_bs===$val?'active':'' ?>" data-style="<?= $val ?>"
                   style="background:linear-gradient(135deg,<?= $bc1 ?>,<?= $bc2 ?>);">
                <span><?= $label ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-group">
            <label>Banner Colours</label>
            <div class="color-pair">
              <div class="color-input-wrap">
                <div class="color-swatch" id="bc1-swatch" style="background:<?= htmlspecialchars($bc1) ?>;"></div>
                <input type="color" id="banner-color1" value="<?= htmlspecialchars($bc1) ?>"/>
                <span style="font-size:12px;color:var(--text3);">Primary</span>
              </div>
              <div class="color-input-wrap">
                <div class="color-swatch" id="bc2-swatch" style="background:<?= htmlspecialchars($bc2) ?>;"></div>
                <input type="color" id="banner-color2" value="<?= htmlspecialchars($bc2) ?>"/>
                <span style="font-size:12px;color:var(--text3);">Secondary</span>
              </div>
            </div>
            <div class="angle-row" id="banner-angle-row">
              <span style="font-size:12px;color:var(--text3);">Angle</span>
              <input type="range" id="banner-angle" min="0" max="360" value="<?= (int)($user['banner_angle'] ?? 135) ?>"/>
              <span id="banner-angle-val"><?= (int)($user['banner_angle'] ?? 135) ?>°</span>
            </div>
          </div>
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:4px 0 16px;">

        <!-- Avatar + basic info -->
        <div style="padding:0 24px 24px;display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
        

          <!-- Avatar upload -->
          <div style="display:flex;flex-direction:column;align-items:center;gap:10px;">
            <div class="avatar-upload-zone" id="auz">
              <div class="profile-avatar-large" id="edit-avatar-preview"
                   style="background:<?= htmlspecialchars($user['avatar_color']) ?>;margin-top:0;border:3px solid var(--border);">
                <?php if (!empty($user['avatar_url'])): ?>
                  <img src="<?= htmlspecialchars($user['avatar_url']) ?>?v=<?= time() ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" id="avatar-img-preview"/>
                <?php else: ?>
                  <span id="avatar-emoji-display"><?= htmlspecialchars($user['avatar_emoji']) ?></span>
                <?php endif; ?>
              </div>
              <div class="avatar-upload-overlay">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              </div>
              <div class="avatar-edit-badge">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              </div>
              <input
                  type="file"
                  id="avatar-upload-input"
                  accept="image/jpeg,image/png,image/webp,image/gif"
                  style="
                    position:absolute;
                    width:1px;
                    height:1px;
                    opacity:0;
                    pointer-events:none;
                  "
                >
            </div>
            <span style="font-size:11px;color:var(--text3);text-align:center;">JPG/PNG/WebP<br>Max 2 MB</span>
            <button class="btn btn-secondary btn-sm" id="delete-avatar-btn" style="font-size:11px;<?= empty($user['avatar_url'])?'display:none;':'' ?>">Remove Photo</button>
          </div>

          <div style="flex:1;min-width:240px;">
            <div class="form-group">
              <label>Display Name</label>
              <input type="text" id="profile-display-name" value="<?= htmlspecialchars($user['display_name']) ?>" placeholder="Your display name" maxlength="64"/>
            </div>
            <div class="form-group">
              <label>Bio <span style="color:var(--text3);font-size:11px;">(max 500 chars)</span></label>
              <textarea id="profile-bio" style="background:var(--bg3);border:1px solid var(--border);color:var(--text);border-radius:var(--radius-sm);padding:10px 14px;font-family:var(--font-body);font-size:14px;outline:none;width:100%;min-height:80px;resize:vertical;" maxlength="500" placeholder="Tell the world about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label>Avatar Colour <span style="color:var(--text3);font-size:11px;">(shown when no photo)</span></label>
              <div class="avatar-picker" id="avatar-color-picker">
                <?php foreach (['#6c8ef5','#a78bfa','#34d399','#f87171','#fbbf24','#38bdf8','#f472b6','#fb923c','#e879f9','#4ade80','#22d3ee','#818cf8'] as $c): ?>
                <div class="avatar-color-btn <?= $user['avatar_color']===$c?'active':'' ?>" data-color="<?= $c ?>" style="background:<?= $c ?>;"></div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="form-group">
              <label>Avatar Symbol <span style="color:var(--text3);font-size:11px;">(shown when no photo)</span></label>
              <div class="avatar-picker" id="avatar-emoji-picker">
                <?php foreach (['∑','π','√','∞','∫','Δ','θ','λ','φ','ψ','Ω','α','β','γ','μ','σ','🔢','📐','🧮','⭐'] as $e): ?>
                <div class="avatar-emoji-btn <?= $user['avatar_emoji']===$e?'active':'' ?>" data-emoji="<?= htmlspecialchars($e) ?>"><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="flex-row">
              <button class="btn btn-primary" id="save-profile-btn">Save Changes</button>
              <button class="btn btn-secondary" id="profile-cancel-btn">Cancel</button>
              <span id="profile-save-msg" style="display:none;color:var(--correct);font-size:13px;">✓ Saved!</span>
            </div>
            <div id="avatar-upload-progress" style="display:none;margin-top:10px;">
              <div style="background:var(--bg4);border-radius:4px;height:4px;overflow:hidden;">
                <div id="avatar-upload-bar" style="height:100%;width:0%;background:var(--accent);transition:width 0.3s;border-radius:4px;"></div>
              </div>
              <p style="font-size:11px;color:var(--text3);margin-top:4px;" id="avatar-upload-status">Uploading…</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card settings-section">
        <div class="settings-title">Account Info</div>
        <div class="setting-row">
          <div class="setting-info"><div class="setting-name">Username</div><div class="setting-desc"><?= htmlspecialchars($user['username']) ?></div></div>
        </div>
        <div class="setting-row">
          <div class="setting-info"><div class="setting-name">Email</div><div class="setting-desc"><?= htmlspecialchars($user['email']) ?></div></div>
        </div>
        <div class="setting-row">
          <div class="setting-info"><div class="setting-name">Member Since</div><div class="setting-desc"><?= date('F j, Y', strtotime($user['created_at'])) ?></div></div>
        </div>
        <div class="setting-row">
          <div class="setting-info"><div class="setting-name">Role</div><div class="setting-desc" style="text-transform:capitalize;"><?= htmlspecialchars($user['role']) ?></div></div>
        </div>
      </div>

      <div class="card settings-section" style="margin-top:16px;">
        <div class="settings-title">Change Password</div>
        <div id="pw-error" class="auth-error" style="margin-bottom:14px;"></div>
        <div class="form-group"><label>Current Password</label><input type="password" id="pw-current" placeholder="Your current password"/></div>
        <div class="form-group"><label>New Password</label><input type="password" id="pw-new" placeholder="At least 8 characters"/></div>
        <button class="btn btn-secondary" id="change-pw-btn">Update Password</button>
        <span id="pw-save-msg" style="display:none;margin-left:12px;color:var(--correct);font-size:13px;">Password updated!</span>
      </div>
    </div>

  </div>

  <?php if ($user['role'] === 'admin'): ?>
  <!-- ===== ADMIN PAGE ===== -->
  <div class="page" id="page-admin">
    <div class="page-header">
      <div class="page-title">Admin Panel</div>
      <div class="page-subtitle">Manage users and view platform statistics.</div>
    </div>
    <div class="stats-grid" id="admin-stats-grid"></div>
    <div class="card">
      <div class="flex-between" style="margin-bottom:16px;">
        <div class="card-title" style="margin:0;">All Users</div>
        <input type="text" id="admin-search" placeholder="Search users…" autocomplete="off" style="width:200px;padding:8px 12px;font-size:13px;"/>
      </div>
      <div style="overflow-x:auto;">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th><th>Username</th><th>Email</th><th>Display Name</th>
              <th>Role</th><th>Attempts</th><th>Joined</th><th>Last Login</th><th>Actions</th>
            </tr>
          </thead>
          <tbody id="admin-users-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

</main>
</div>

<div id="cell-tooltip"></div>

<script>
// ============================================================
// PHP-INJECTED USER DATA
// ============================================================
const CURRENT_USER = <?= json_encode([
  'id'            => (int)$user['id'],
  'username'      => $user['username'],
  'display_name'  => $user['display_name'] ?? '',
  'bio'           => $user['bio'] ?? '',
  'avatar_color'  => $user['avatar_color']  ?? '#6c8ef5',
  'avatar_emoji'  => $user['avatar_emoji']  ?? '∑',
  'avatar_url'    => $user['avatar_url']    ?? null,
  'banner_style'  => $user['banner_style']  ?? 'gradient',
  'banner_color1' => $user['banner_color1'] ?? '#6c8ef5',
  'banner_color2' => $user['banner_color2'] ?? '#a78bfa',
  'banner_angle'  => (int)($user['banner_angle'] ?? 135),
  'role'          => $user['role'],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

const INITIAL_SETTINGS = <?= json_encode($settings ?: (object)[], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

// ============================================================
// API HELPER
// ============================================================
async function api(action, body = null) {
  const opts = { method: body ? 'POST' : 'GET', headers: {} };
  if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
  const r = await fetch('api/api.php?action=' + action, opts);
  if (r.status === 401) { location.href = 'index.php?auth=login'; return null; }
  return r.json();
}

// ============================================================
// DATA & STATE (DB-backed)
// ============================================================
let problems = [];
let filteredPool = [];
let sessionQueue = [];
let currentProblem = null;
let sessionIndex = 0;
let charts = {};

// In-memory cache of attempts (refreshed from DB on load)
let _attemptsCache = null;
let _testsCache = null;

async function loadAttempts() {
  if (_attemptsCache !== null) return _attemptsCache;
  const data = await api('get_attempts');
  _attemptsCache = data || {};
  return _attemptsCache;
}

async function saveAttempts() { /* handled server-side per attempt */ }

function loadSettings() {
  return { ...INITIAL_SETTINGS };
}
async function saveSettings(s) {
  // Map JS keys → PHP keys
  const mapped = {
    theme: s.theme, font_size: s.fontSize,
    autoadvance: s.autoadvance ? 1 : 0,
    showid: s.showid ? 1 : 0,
    confirm_skip: s.confirmSkip ? 1 : 0,
    hcontrast: s.hcontrast ? 1 : 0,
    reducemotion: s.reducemotion ? 1 : 0,
    largetargets: s.largetargets ? 1 : 0
  };
  await api('save_settings', mapped);
  Object.assign(INITIAL_SETTINGS, s);
}

async function loadTests() {
  if (_testsCache !== null) return _testsCache;
  const data = await api('get_tests');
  _testsCache = data || [];
  return _testsCache;
}

async function recordTest(score, total, results) {
  const res = await api('add_test', { score, total, results });
  if (res?.ok) _testsCache = null; // invalidate
}

async function recordAttempt(id, correct, userAnswer, correctAnswer) {
  const res = await api('add_attempt', {
    problem_id: id, correct, userAnswer, correctAnswer
  });
  if (res?.ok && _attemptsCache) {
    if (!_attemptsCache[id]) _attemptsCache[id] = [];
    _attemptsCache[id].push({ correct, userAnswer, correctAnswer, date: new Date().toISOString() });
  }
}

function getLastAttempt(id) {
  if (!_attemptsCache) return null;
  const arr = _attemptsCache[id];
  return arr && arr.length ? arr[arr.length - 1] : null;
}

function getBestAttempt(id) {
  if (!_attemptsCache) return null;
  const arr = _attemptsCache[id];
  if (!arr || !arr.length) return null;
  return arr.find(x => x.correct) || arr[arr.length - 1];
}
// ============================================================
// NAVIGATION SETUP (navigate() defined later)
// ============================================================
const moreBtn = document.getElementById('mobile-more-btn');
const moreMenu = document.getElementById('mobile-more-menu');
const moreBackdrop = document.getElementById('mobile-overlay-backdrop');
function openMoreMenu() { moreMenu.classList.add('visible'); moreBackdrop.style.display = 'block'; requestAnimationFrame(() => moreMenu.classList.add('open')); }
function closeMoreMenu() { moreMenu.classList.remove('open'); moreBackdrop.style.display = 'none'; setTimeout(() => moreMenu.classList.remove('visible'), 300); }
moreBtn.addEventListener('click', openMoreMenu);
moreBackdrop.addEventListener('click', closeMoreMenu);
document.getElementById('mobile-reset-btn').addEventListener('click', () => {
  closeMoreMenu();
  if (confirm('Reset ALL data? This cannot be undone.')) {
    api('clear_attempts').then(() => {
      _attemptsCache = null; _testsCache = null;
      alert('Progress reset.'); navigate('home');
    });
  }
});
// ============================================================
// PROBLEM FILTERS & POOL


function buildPool({ comp, year, prob, noRepeat, onlyWrong }) {
  // Use cached attempts (loaded at init)
  const attempts = _attemptsCache || {};
  let pool = [...problems];
  if (comp && comp !== 'all') pool = pool.filter(p => p.Comp === comp);
  if (year && year !== 'all') pool = pool.filter(p => p.Year === year);
  if (prob && prob !== 'all') pool = pool.filter(p => p.PN === prob);
  if (noRepeat) pool = pool.filter(p => !attempts[p.ID] || !attempts[p.ID].length);
  if (onlyWrong) pool = pool.filter(p => {
    const hist = attempts[p.ID];
    if (!hist || !hist.length) return true;
    return !hist.some(a => a.correct);
  });
  for (let i = pool.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [pool[i], pool[j]] = [pool[j], pool[i]];
  }
  return pool;
}

// ============================================================
// PRACTICE SESSION
// ============================================================
async function startSession() {
  await loadAttempts(); // ensure cache populated
  const comp = document.getElementById('filter-comp').value;
  const year = document.getElementById('filter-year').value;
  const prob = document.getElementById('filter-prob').value;
  const noRepeat = document.getElementById('no-repeat').checked;
  const onlyWrong = document.getElementById('only-wrong').checked;

  sessionQueue = buildPool({ comp, year, prob, noRepeat, onlyWrong });
  sessionIndex = 0;

  if (!sessionQueue.length) {
    alert('No problems match your filters! Try relaxing the criteria.');
    return;
  }

  document.getElementById('practice-config').style.display = 'none';
  document.getElementById('question-card').classList.add('active');
  showQuestion(sessionQueue[0]);
}
async function renderProblemText(text) {
  if (!text) return '';

  const asyBlocks = [];

  // 1. ASY BLOCKS → placeholders
  text = text.replace(
    /\[asy\]([\s\S]*?)\[\/asy\]/g,
    (_, asyCode) => {
      const cleanedAsyCode = asyCode
        .split('\n')
        .map(line => line.trim())
        .filter(Boolean)
        .join('\n');

      const id = crypto.randomUUID();
      const placeholder = `asy_${id}`;

      asyBlocks.push({ id, code: cleanedAsyCode });

      return `
        <div id="${placeholder}" class="asy-container" style="text-align:center;margin:16px 0;">
          <div class="asy-loader">
            <div class="spinner"></div>
            <div style="margin-top:8px;font-size:13px;opacity:0.7;">
              Rendering diagram...
            </div>
          </div>
        </div>
      `;
    }
  );

  // 2. line breaks
  text = text.replace(/\n/g, '<br>');

  // 3. render ASY AFTER DOM paint
  setTimeout(async () => {
    for (const block of asyBlocks) {
      try {
        const res = await fetch("https://asymptote-renderer-2.onrender.com/render", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ code: block.code })
        });

        const contentType = res.headers.get("content-type") || "";

        // 🔥 ALWAYS parse JSON first (because your API now returns JSON errors)
        let data;
        if (contentType.includes("application/json")) {
          data = await res.json();
        }

        // ❌ Handle API-level error (your new format)
        if (data?.status === "error") {
          console.error("Asy error:", data);

          const el = document.getElementById(`asy_${block.id}`);
          if (el) {
            el.innerHTML = `
              <div style="
                padding:12px;
                border-radius:10px;
                background:#fee2e2;
                color:#991b1b;
                font-size:13px;
                margin:16px 0;
              ">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Diagram failed: ${data.type}<br>
                <span style="opacity:0.8">${data.message}</span>
              </div>
            `;
          }

          continue;
        }

        // ❌ fallback if response is not ok AND not JSON
        if (!res.ok) {
          throw new Error("Network error");
        }

        // 🖼️ success path (image blob)
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);

        const el = document.getElementById(`asy_${block.id}`);

        if (el) {
          el.innerHTML = `
            <img src="${url}"
                style="width:40%;border-radius:12px;margin:16px 0;opacity:0;transition:opacity 0.3s;" />
          `;

          const img = el.querySelector("img");
          requestAnimationFrame(() => {
            img.style.opacity = "1";
          });
        }

      } catch (err) {
        console.error("Asy render failed:", err);

        const el = document.getElementById(`asy_${block.id}`);
        if (el) {
          el.innerHTML = `
            <div style="
              padding:12px;
              border-radius:10px;
              background:#f3f4f6;
              color:#374151;
              font-size:13px;
              margin:16px 0;
            ">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;margin-right:4px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Network error while rendering diagram
            </div>
          `;
        }
      }
    }
  }, 0);
  return text;
}


async function showQuestion(problem) {
  currentProblem = problem;
  const settings = loadSettings();

  // Meta badges
  const meta = document.getElementById('question-meta');
  let metaHTML = `<span class="badge badge-accent">${problem.Comp}</span>`;
  metaHTML += `<span class="badge">${problem.Year}</span>`;
  metaHTML += `<span class="badge">Problem ${problem.PN}</span>`;
  if (settings.showid)
    metaHTML += `<span class="badge" style="opacity:0.6;font-family:var(--font-mono);font-size:11px;cursor:default;" data-tooltip="${problem.ID}">${problem.ID.slice(0,8)}…</span>`;
  meta.innerHTML = metaHTML;

  // Update answer input label based on competition type
  const ansLabel = document.getElementById('answer-input-label');
  if (ansLabel) {
    ansLabel.textContent = problem.IsAIME ? 'Your Answer (numeric)' : 'Your Answer (A–E)';
  }
  document.getElementById('answer-input').placeholder = problem.IsAIME ? 'e.g. 42' : 'e.g. B';

  // ⚠️ wait for render BEFORE continuing
  const html = await renderProblemText(problem.Q);
  const body = document.getElementById('question-body');

  body.innerHTML = html;

  // Progress
  const pct = sessionQueue.length > 1
    ? (sessionIndex / (sessionQueue.length - 1)) * 100
    : 0;

  document.getElementById('session-progress').style.width = pct + '%';
  document.getElementById('session-counter').textContent =
    `Problem ${sessionIndex + 1} of ${sessionQueue.length}`;

  // Reset UI
  document.getElementById('answer-input').value = '';
  document.getElementById('result-banner').className = 'result-banner';
  document.getElementById('result-banner').innerHTML = '';
  document.getElementById('post-answer-area').style.display = 'none';
  document.getElementById('answer-input').disabled = false;
  document.getElementById('submit-btn').disabled = false;
  document.getElementById('reveal-btn').style.display = '';
  document.getElementById('answer-input').focus();

  // LaTeX render AFTER DOM update
  requestAnimationFrame(() => {
    typeset('#question-body');
  });
}


function getAoPSUrl(problem) {
  if (problem.Link) return problem.Link;
  // Fallback construction
  const year = parseInt(problem.Year, 10);
  const pn = problem.PN;
  const comp = (problem.Comp || 'AIME').replace(/ /g, '_');
  return `https://artofproblemsolving.com/wiki/index.php/${year}_${comp}_Problems/Problem_${pn}`;
}

function showSolutionLink(problem) {
  document.getElementById('solution-link-btn')?.remove();
  const a = document.createElement('a');
  a.id = 'solution-link-btn';
  a.href = getAoPSUrl(problem);
  a.target = '_blank';
  a.rel = 'noopener';
  a.className = 'btn btn-secondary';
  a.style.cssText = 'display:inline-flex;align-items:center;gap:6px;text-decoration:none;';
  a.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0"><path d="M14 3h7v7h-2V6.41l-9.29 9.3-1.42-1.42L17.59 5H14V3zM5 5h6v2H7v10h10v-4h2v6H5V5z"/></svg>Solution`;
  document.getElementById('post-answer-area').querySelector('.flex-row').appendChild(a);
}

function checkAnswer(userVal, problem) {
  const userStr = String(userVal).trim().toUpperCase();
  if (problem.IsAIME) {
    return parseFloat(userStr) === parseFloat(String(problem.A).trim());
  }
  // MCQ: letter answer only (A–E)
  if (!problem.Letter) return false;
  return userStr === String(problem.Letter).trim().toUpperCase();
}

function getAnswerLabel(problem) {
  if (problem.IsAIME) return 'Your Answer (numeric)';
  return 'Your Answer (A–E)';
}

function submitAnswer() {
  const val = document.getElementById('answer-input').value.trim();
  if (val === '') { alert('Please enter an answer.'); return; }
  if (!currentProblem.IsAIME && !/^[A-Ea-e]$/.test(val)) {
    alert('Please enter a letter answer (A–E).'); return;
  }
  const correctAns = currentProblem.A;
  const correct = checkAnswer(val, currentProblem);
  const displayCorrect = currentProblem.Letter
    ? `${currentProblem.Letter} (${correctAns})`
    : correctAns;

  recordAttempt(currentProblem.ID, correct, val, correctAns);

  const banner = document.getElementById('result-banner');
  banner.className = 'result-banner show ' + (correct ? 'correct' : 'wrong');
  banner.innerHTML = correct
    ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg> Correct! The answer is <strong>${displayCorrect}</strong>.`
    : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg><span style="white-space:nowrap">Incorrect.</span><span style="white-space:nowrap">Your answer: <strong>${val}</strong></span><span style="white-space:nowrap">Correct: <strong>${displayCorrect}</strong></span>`;

  document.getElementById('answer-input').disabled = true;
  document.getElementById('submit-btn').disabled = true;
  document.getElementById('reveal-btn').style.display = 'none';
  document.getElementById('post-answer-area').style.display = 'block';
  showSolutionLink(currentProblem);

  const settings = loadSettings();
  if (correct && settings.autoadvance) {
    setTimeout(() => nextQuestion(), 1200);
  }
}

function revealAnswer() {
  const correctAns = currentProblem.A;
  const displayCorrect = currentProblem.Letter
    ? `${currentProblem.Letter} (${correctAns})`
    : correctAns;
  const banner = document.getElementById('result-banner');
  banner.className = 'result-banner show';
  banner.style.background = 'rgba(108,142,245,0.1)';
  banner.style.borderColor = 'var(--accent)';
  banner.style.color = 'var(--accent)';
  banner.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> The answer is <strong>${displayCorrect}</strong>`;
  recordAttempt(currentProblem.ID, false, null, correctAns);
  document.getElementById('answer-input').disabled = true;
  document.getElementById('submit-btn').disabled = true;
  document.getElementById('reveal-btn').style.display = 'none';
  document.getElementById('post-answer-area').style.display = 'block';
  showSolutionLink(currentProblem);
}

function nextQuestion() {
  sessionIndex++;
  if (sessionIndex >= sessionQueue.length) {
    endSession();
    return;
  }
  showQuestion(sessionQueue[sessionIndex]);
}

function endSession() {
  document.getElementById('question-card').classList.remove('active');
  document.getElementById('practice-config').style.display = '';
  currentProblem = null;
  // Show a quick summary
  const attempts = loadAttempts();
  const done = sessionQueue.slice(0, sessionIndex + 1);
  const correct = done.filter(p => {
    const hist = attempts[p.ID];
    return hist && hist.length && hist[hist.length-1].correct;
  }).length;
  if (done.length > 0) {
    alert(`Session complete!\nCorrect: ${correct} / ${done.length}\nAccuracy: ${Math.round(correct/done.length*100)}%`);
  }
}

function skipQuestion() {
  const settings = loadSettings();
  if (settings.confirmSkip && !confirm('Skip this problem?')) return;
  nextQuestion();
}

document.getElementById('start-practice').addEventListener('click', startSession);
document.getElementById('submit-btn').addEventListener('click', submitAnswer);
document.getElementById('reveal-btn').addEventListener('click', revealAnswer);
document.getElementById('next-btn').addEventListener('click', nextQuestion);
document.getElementById('end-session-btn').addEventListener('click', endSession);
document.getElementById('skip-btn').addEventListener('click', skipQuestion);
document.getElementById('answer-input').addEventListener('keydown', e => {
  if (e.key === 'Enter') submitAnswer();
});
document.getElementById('start-practice').addEventListener('click', startSession);
document.getElementById('submit-btn').addEventListener('click', submitAnswer);
document.getElementById('reveal-btn').addEventListener('click', revealAnswer);
document.getElementById('next-btn').addEventListener('click', nextQuestion);
document.getElementById('end-session-btn').addEventListener('click', endSession);
document.getElementById('skip-btn').addEventListener('click', skipQuestion);
document.getElementById('answer-input').addEventListener('keydown', e => {
  if (e.key === 'Enter') submitAnswer();
});

// Quick start buttons
document.getElementById('quick-random').addEventListener('click', () => {
  navigate('practice');
  document.getElementById('filter-comp').value = 'all';
  document.getElementById('filter-year').value = 'all';
  document.getElementById('filter-prob').value = 'all';
  document.getElementById('no-repeat').checked = false;
  document.getElementById('only-wrong').checked = false;
  startSession();
});
document.getElementById('quick-unseen').addEventListener('click', () => {
  navigate('practice');
  document.getElementById('no-repeat').checked = true;
  document.getElementById('only-wrong').checked = false;
  startSession();
});
document.getElementById('quick-wrong').addEventListener('click', () => {
  navigate('practice');
  document.getElementById('no-repeat').checked = false;
  document.getElementById('only-wrong').checked = true;
  startSession();
});
document.getElementById('quick-random').addEventListener('click', async () => {
  navigate('practice');
  document.getElementById('filter-comp').value = 'all';
  document.getElementById('filter-year').value = 'all';
  document.getElementById('filter-prob').value = 'all';
  document.getElementById('no-repeat').checked = false;
  document.getElementById('only-wrong').checked = false;
  await startSession();
});
document.getElementById('quick-unseen').addEventListener('click', async () => {
  navigate('practice');
  document.getElementById('no-repeat').checked = true;
  document.getElementById('only-wrong').checked = false;
  await startSession();
});
document.getElementById('quick-wrong').addEventListener('click', async () => {
  navigate('practice');
  document.getElementById('no-repeat').checked = false;
  document.getElementById('only-wrong').checked = true;
  await startSession();
});
// ============================================================
// BROWSE
// ============================================================
function renderBrowse() {
  const comp = document.getElementById('browse-comp').value;
  const year = document.getElementById('browse-year').value;
  const sortBy = document.getElementById('browse-sort').value;

  let pool = problems.filter(p => p.Comp === comp);
  if (year && year !== 'all') pool = pool.filter(p => p.Year === year);

  // Sort
  if (sortBy === 'num') {
    pool.sort((a, b) => (parseInt(a.PN) || 0) - (parseInt(b.PN) || 0) || a.Year.localeCompare(b.Year));
  } else if (sortBy === 'status') {
    const attempts = loadAttempts();
    pool.sort((a, b) => {
      const sa = getStatus(a.ID, attempts), sb = getStatus(b.ID, attempts);
      const order = { correct: 0, wrong: 1, unseen: 2 };
      return (order[sa] ?? 3) - (order[sb] ?? 3);
    });
  }

  const attempts = loadAttempts();
  const grid = document.getElementById('browse-grid');
  grid.innerHTML = '';

  pool.forEach(p => {
    const btn = document.createElement('div');
    btn.className = 'prob-btn';
    btn.textContent = `#${p.PN}`;
    btn.title = `${p.Comp} ${p.Year} #${p.PN}`;
    const status = getStatus(p.ID, attempts);
    if (status === 'correct') btn.classList.add('done-correct');
    else if (status === 'wrong') btn.classList.add('done-wrong');
    btn.addEventListener('click', () => showBrowseProblem(p));
    grid.appendChild(btn);
  });

  document.getElementById('browse-problem-card').style.display = 'none';
}

function getStatus(id, attempts) {
  const hist = attempts[id];
  if (!hist || !hist.length) return 'unseen';
  if (hist.some(a => a.correct)) return 'correct';
  return 'wrong';
}

function showBrowseProblem(p) {
  const card = document.getElementById('browse-problem-card');
  card.style.display = 'block';

  const meta = document.getElementById('browse-question-meta');
  meta.innerHTML = `
    <span class="badge badge-accent">${p.Comp}</span>
    <span class="badge">${p.Year}</span>
    <span class="badge">Problem ${p.PN}</span>
    <span class="badge" style="font-family:var(--font-mono);font-size:11px;cursor:default;" data-tooltip="${p.ID}">${p.ID.slice(0,8)}…</span>
    <a href="${p.Link || getAoPSUrl(p)}" target="_blank" rel="noopener" class="badge" style="background:var(--accent-glow);color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M14 3h7v7h-2V6.41l-9.29 9.3-1.42-1.42L17.59 5H14V3zM5 5h6v2H7v10h10v-4h2v6H5V5z"/></svg>AoPS
    </a>
  `;

  // FIX: Run text parser to handle [asy] and layout formatting on browse page
  (async () => {
    document.getElementById('browse-question-body').innerHTML =
      await renderProblemText(p.Q);

    typeset('#browse-question-body');
  })();

  document.getElementById('browse-answer-reveal').style.display = 'none';
  document.getElementById('browse-answer-reveal').textContent = p.A;
  document.getElementById('browse-reveal-ans').style.display = '';

  document.getElementById('browse-practice-btn').onclick = () => {
    navigate('practice');
    sessionQueue = [p];
    sessionIndex = 0;
    document.getElementById('practice-config').style.display = 'none';
    document.getElementById('question-card').classList.add('active');
    showQuestion(p);
  };

  card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  typeset('#browse-question-body');
}

document.getElementById('browse-reveal-ans').addEventListener('click', () => {
  document.getElementById('browse-answer-reveal').style.display = 'inline';
  document.getElementById('browse-reveal-ans').style.display = 'none';
});

document.getElementById('browse-comp').addEventListener('change', () => { updateBrowseYears(); renderBrowse(); });
document.getElementById('filter-comp').addEventListener('change', () => { updateProbFilter(); });
document.getElementById('browse-year').addEventListener('change', renderBrowse);
document.getElementById('browse-sort').addEventListener('change', renderBrowse);

// ============================================================
// HOME PAGE
// ============================================================
async function renderHome() {
  const attempts = await loadAttempts();
  const allAttempts = [];
  Object.entries(attempts).forEach(([id, arr]) => arr.forEach(a => allAttempts.push({ id, ...a })));

  const total = allAttempts.length;
  const correct = allAttempts.filter(a => a.correct).length;
  const seen = new Set(allAttempts.map(a => a.id)).size;
  const acc = total ? Math.round(correct / total * 100) : 0;

  document.getElementById('home-stats').innerHTML = `
    <div class="stat-card">
      <div class="stat-value">${total}</div>
      <div class="stat-label">Total Attempts</div>
    </div>
    <div class="stat-card stat-correct">
      <div class="stat-value">${correct}</div>
      <div class="stat-label">Correct</div>
    </div>
    <div class="stat-card stat-wrong">
      <div class="stat-value">${total - correct}</div>
      <div class="stat-label">Wrong</div>
    </div>
    <div class="stat-card stat-accent2">
      <div class="stat-value">${acc}%</div>
      <div class="stat-label">Accuracy</div>
    </div>
    <div class="stat-card">
      <div class="stat-value">${seen}</div>
      <div class="stat-label">Unique Problems</div>
      <div class="stat-sub">of ${problems.length}</div>
    </div>
  `;

  renderYearAccChart(allAttempts);
  renderRecentChart(allAttempts);
}
function renderYearAccChart(allAttempts) {
  const ctx = document.getElementById('chart-year-acc');
  if (!ctx) return;
  if (charts['year-acc']) charts['year-acc'].destroy();

  const decades = {};
  allAttempts.forEach(a => {
    const prob = problems.find(p => p.ID === a.id);
    if (!prob) return;
    const decade = Math.floor(parseInt(prob.Year) / 10) * 10 + 's';
    if (!decades[decade]) decades[decade] = { c: 0, t: 0 };
    decades[decade].t++;
    if (a.correct) decades[decade].c++;
  });

  const labels = Object.keys(decades).sort();
  const data = labels.map(d => decades[d].t ? Math.round(decades[d].c / decades[d].t * 100) : 0);

  charts['year-acc'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label: 'Accuracy %', data, backgroundColor: getAccentColor(0.7), borderColor: getAccentColor(1), borderWidth: 2, borderRadius: 6 }]
    },
    options: chartOptions({ max: 100, unit: '%' })
  });
}

function renderRecentChart(allAttempts) {
  const ctx = document.getElementById('chart-recent');
  if (!ctx) return;
  if (charts['recent']) charts['recent'].destroy();

  const days = 14;
  const labels = [];
  const correctData = [];
  const wrongData = [];
  for (let i = days - 1; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    const ds = d.toISOString().slice(0, 10);
    labels.push(ds.slice(5));
    const dayAttempts = allAttempts.filter(a => a.date?.slice(0, 10) === ds);
    correctData.push(dayAttempts.filter(a => a.correct).length);
    wrongData.push(dayAttempts.filter(a => !a.correct).length);
  }

  charts['recent'] = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: 'Correct', data: correctData, borderColor: '#34d399', backgroundColor: 'rgba(52,211,153,0.1)', fill: true, tension: 0.4, borderWidth: 2 },
        { label: 'Wrong', data: wrongData, borderColor: '#f87171', backgroundColor: 'rgba(248,113,113,0.1)', fill: true, tension: 0.4, borderWidth: 2 }
      ]
    },
    options: chartOptions({})
  });
}

// ============================================================
// STATS PAGE (async)
// ============================================================
let statsRange = 'week';

async function renderStats() {
  const attempts = await loadAttempts();
  const now = new Date();

  function cutoff(range) {
    const d = new Date();
    if (range === 'week') d.setDate(d.getDate() - 7);
    else if (range === 'month') d.setMonth(d.getMonth() - 1);
    else if (range === 'year') d.setFullYear(d.getFullYear() - 1);
    else return new Date(0);
    return d;
  }

  const cut = cutoff(statsRange);
  const allAttempts = [];
  Object.entries(attempts).forEach(([id, arr]) => arr.forEach(a => allAttempts.push({ id, ...a })));
  const filtered = allAttempts.filter(a => new Date(a.date) >= cut);

  const total = filtered.length;
  const correct = filtered.filter(a => a.correct).length;
  const acc = total ? Math.round(correct / total * 100) : 0;
  const streak = calcStreak(allAttempts);

  document.getElementById('stats-summary').innerHTML = `
    <div class="stat-card">
      <div class="stat-value">${total}</div>
      <div class="stat-label">Attempts</div>
    </div>
    <div class="stat-card stat-correct">
      <div class="stat-value">${correct}</div>
      <div class="stat-label">Correct</div>
    </div>
    <div class="stat-card stat-wrong">
      <div class="stat-value">${total - correct}</div>
      <div class="stat-label">Wrong</div>
    </div>
    <div class="stat-card stat-accent2">
      <div class="stat-value">${acc}%</div>
      <div class="stat-label">Accuracy</div>
    </div>
    <div class="stat-card">
      <div class="stat-value">${streak}</div>
      <div class="stat-label">Day Streak <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:-2px;color:var(--accent)"><path d="M12 2c0 0-4 4-4 9a4 4 0 0 0 8 0c0-5-4-9-4-9z"/><path d="M12 11c0 0-2 2-2 4a2 2 0 0 0 4 0c0-2-2-4-2-4z" fill="currentColor"/></svg></div>
    </div>
  `;

  renderTimelineChart(filtered);
  renderByPartChart(filtered);
  renderByProbNumChart(filtered);
  renderByYearChart(filtered);
  renderTestStatsSection();
}

function calcStreak(allAttempts) {
  if (!allAttempts.length) return 0;
  const days = new Set(allAttempts.map(a => a.date?.slice(0, 10)));
  let streak = 0, d = new Date();
  while (true) {
    const ds = d.toISOString().slice(0, 10);
    if (days.has(ds)) { streak++; d.setDate(d.getDate() - 1); }
    else break;
  }
  return streak;
}

function renderTimelineChart(filtered) {
  const ctx = document.getElementById('chart-timeline');
  if (charts['timeline']) charts['timeline'].destroy();

  const byDay = {};
  filtered.forEach(a => {
    const day = a.date?.slice(0, 10) || 'unknown';
    if (!byDay[day]) byDay[day] = { c: 0, w: 0 };
    a.correct ? byDay[day].c++ : byDay[day].w++;
  });
  const labels = Object.keys(byDay).sort();
  charts['timeline'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels.map(l => l.slice(5)),
      datasets: [
        { label: 'Correct', data: labels.map(l => byDay[l].c), backgroundColor: 'rgba(52,211,153,0.8)', borderRadius: 4 },
        { label: 'Wrong', data: labels.map(l => byDay[l].w), backgroundColor: 'rgba(248,113,113,0.8)', borderRadius: 4 }
      ]
    },
    options: { ...chartOptions({}), plugins: { ...chartOptions({}).plugins }, scales: { x: { stacked: true, ...chartScaleX() }, y: { stacked: true, ...chartScaleY() } } }
  });
}

function renderByPartChart(filtered) {
  const ctx = document.getElementById('chart-by-part');
  if (charts['by-part']) charts['by-part'].destroy();

  const compMap = {};
  filtered.forEach(a => {
    const prob = problems.find(p => p.ID === a.id);
    if (!prob) return;
    const key = prob.Comp || 'Unknown';
    if (!compMap[key]) compMap[key] = { c: 0, t: 0 };
    compMap[key].t++;
    if (a.correct) compMap[key].c++;
  });

  const labels = Object.keys(compMap).sort();
  const colors = ['rgba(108,142,245,0.8)', 'rgba(167,139,250,0.8)', 'rgba(94,207,128,0.8)',
    'rgba(251,191,36,0.8)', 'rgba(248,113,113,0.8)', 'rgba(56,189,248,0.8)',
    'rgba(232,121,249,0.8)', 'rgba(251,146,60,0.8)', 'rgba(129,140,248,0.8)', 'rgba(52,211,153,0.8)'];

  charts['by-part'] = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data: labels.map(k => compMap[k].t ? Math.round(compMap[k].c / compMap[k].t * 100) : 0),
        backgroundColor: labels.map((_, i) => colors[i % colors.length]),
        borderWidth: 0, borderRadius: 4
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { labels: { color: '#9198b5', font: { family: 'Outfit', size: 11 } } } }
    }
  });
}

function renderByProbNumChart(filtered) {
  const ctx = document.getElementById('chart-by-probnum');
  if (charts['by-probnum']) charts['by-probnum'].destroy();

  const byNum = {};
  filtered.forEach(a => {
    const prob = problems.find(p => p.ID === a.id);
    if (!prob) return;
    const n = prob.PN;
    if (!byNum[n]) byNum[n] = { c: 0, t: 0 };
    byNum[n].t++;
    if (a.correct) byNum[n].c++;
  });

  const labels = Object.keys(byNum).map(Number).sort((a,b)=>a-b).map(String);
  charts['by-probnum'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label: 'Accuracy %', data: labels.map(n => byNum[n].t ? Math.round(byNum[n].c / byNum[n].t * 100) : 0), backgroundColor: getAccentColor(0.75), borderRadius: 4 }]
    },
    options: chartOptions({ max: 100, unit: '%' })
  });
}

function renderByYearChart(filtered) {
  const ctx = document.getElementById('chart-by-year');
  if (charts['by-year']) charts['by-year'].destroy();

  const byYear = {};
  filtered.forEach(a => {
    const prob = problems.find(p => p.ID === a.id);
    if (!prob) return;
    const y = prob.Year;
    if (!byYear[y]) byYear[y] = { c: 0, t: 0 };
    byYear[y].t++;
    if (a.correct) byYear[y].c++;
  });

  const labels = Object.keys(byYear).sort();
  charts['by-year'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ label: 'Accuracy %', data: labels.map(y => byYear[y].t ? Math.round(byYear[y].c / byYear[y].t * 100) : 0), backgroundColor: labels.map((_, i) => `hsl(${220 + i * 3}, 70%, 60%)`), borderRadius: 4 }]
    },
    options: chartOptions({ max: 100, unit: '%' })
  });
}

function renderTestStatsSection() {
  const tests = loadTests();
  const summaryEl = document.getElementById('test-stats-summary');
  const listEl = document.getElementById('test-history-list');

  if (!tests.length) {
    summaryEl.innerHTML = `<div class="stat-card" style="grid-column:1/-1;"><div class="stat-value" style="font-size:1.2rem;color:var(--text3);">No tests taken yet</div><div class="stat-label">Complete a mock test to see your stats here</div></div>`;
    listEl.innerHTML = '';
    // Destroy test charts if they exist
    ['test-scores','test-by-pos'].forEach(k => { if (charts[k]) { charts[k].destroy(); delete charts[k]; } });
    return;
  }

  const avgScore = (tests.reduce((s, t) => s + t.score, 0) / tests.length).toFixed(1);
  const bestScore = Math.max(...tests.map(t => t.score));
  const recentScore = tests[tests.length - 1].score;
  const avgTotal = tests.reduce((s,t)=>s+(t.total||15),0)/tests.length;
  const avgPct = Math.round(parseFloat(avgScore) / avgTotal * 100);

  summaryEl.innerHTML = `
    <div class="stat-card"><div class="stat-value">${tests.length}</div><div class="stat-label">Tests Taken</div></div>
    <div class="stat-card stat-correct"><div class="stat-value">${bestScore}/${tests.find(t=>t.score===bestScore)?.total||'?'}</div><div class="stat-label">Best Score</div></div>
    <div class="stat-card stat-accent2"><div class="stat-value">${avgScore}</div><div class="stat-label">Avg Score</div><div class="stat-sub">${avgPct}%</div></div>
    <div class="stat-card"><div class="stat-value">${recentScore}/${tests[tests.length-1]?.total||'?'}</div><div class="stat-label">Last Test</div></div>
  `;

  // Score distribution chart
  const scoreDistCtx = document.getElementById('chart-test-scores');
  if (charts['test-scores']) charts['test-scores'].destroy();
  const distBuckets = Array(16).fill(0);
  tests.forEach(t => distBuckets[t.score]++);
  charts['test-scores'] = new Chart(scoreDistCtx, {
    type: 'bar',
    data: {
      labels: Array.from({length: 16}, (_, i) => i),
      datasets: [{ label: 'Tests', data: distBuckets, backgroundColor: getAccentColor(0.75), borderRadius: 4 }]
    },
    options: { ...chartOptions({}), plugins: { legend: { display: false } } }
  });

  // Accuracy by position chart (across all tests)
  const byPosCtx = document.getElementById('chart-test-by-pos');
  if (charts['test-by-pos']) charts['test-by-pos'].destroy();
  const posTotals = Array(15).fill(0);
  const posCorrect = Array(15).fill(0);
  tests.forEach(t => {
    (t.results || []).forEach((r, i) => {
      posTotals[i]++;
      if (r.correct) posCorrect[i]++;
    });
  });
  charts['test-by-pos'] = new Chart(byPosCtx, {
    type: 'bar',
    data: {
      labels: Array.from({length: 15}, (_, i) => i + 1),
      datasets: [{ label: 'Accuracy %', data: posTotals.map((t, i) => t ? Math.round(posCorrect[i] / t * 100) : 0), backgroundColor: Array.from({length:15}, (_, i) => `hsl(${140 - i * 8}, 65%, 55%)`), borderRadius: 4 }]
    },
    options: chartOptions({ max: 100, unit: '%' })
  });

  // Recent tests list (last 10)
  const recent = [...tests].reverse().slice(0, 10);
  listEl.innerHTML = recent.map(t => {
    const pct = Math.round(t.score / t.total * 100);
    const d = new Date(t.date).toLocaleDateString();
    return `<div class="test-history-row">
      <div class="test-score-badge">${t.score}/${t.total}</div>
      <div class="test-history-bar"><div class="test-history-bar-fill" style="width:${pct}%"></div></div>
      <div class="test-history-meta">${pct}% · ${d}</div>
    </div>`;
  }).join('');
}

document.getElementById('stats-time-filter').addEventListener('click', e => {
  const btn = e.target.closest('.toggle-btn');
  if (!btn) return;
  document.querySelectorAll('#stats-time-filter .toggle-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  statsRange = btn.dataset.range;
  renderStats();
});
async function renderTestStatsSection() {
  const tests = await loadTests();

  if (!tests.length) {
    summaryEl.innerHTML = `<div class="stat-card" style="grid-column:1/-1;"><div class="stat-value" style="font-size:1.2rem;color:var(--text3);">No tests taken yet</div><div class="stat-label">Complete a mock test to see your stats here</div></div>`;
    listEl.innerHTML = '';
    // Destroy test charts if they exist
    ['test-scores','test-by-pos'].forEach(k => { if (charts[k]) { charts[k].destroy(); delete charts[k]; } });
    return;
  }

  const avgScore = (tests.reduce((s, t) => s + t.score, 0) / tests.length).toFixed(1);
  const bestScore = Math.max(...tests.map(t => t.score));
  const recentScore = tests[tests.length - 1].score;
  const avgTotal = tests.reduce((s,t)=>s+(t.total||15),0)/tests.length;
  const avgPct = Math.round(parseFloat(avgScore) / avgTotal * 100);

  summaryEl.innerHTML = `
    <div class="stat-card"><div class="stat-value">${tests.length}</div><div class="stat-label">Tests Taken</div></div>
    <div class="stat-card stat-correct"><div class="stat-value">${bestScore}/${tests.find(t=>t.score===bestScore)?.total||'?'}</div><div class="stat-label">Best Score</div></div>
    <div class="stat-card stat-accent2"><div class="stat-value">${avgScore}</div><div class="stat-label">Avg Score</div><div class="stat-sub">${avgPct}%</div></div>
    <div class="stat-card"><div class="stat-value">${recentScore}/${tests[tests.length-1]?.total||'?'}</div><div class="stat-label">Last Test</div></div>
  `;

  // Score distribution chart
  const scoreDistCtx = document.getElementById('chart-test-scores');
  if (charts['test-scores']) charts['test-scores'].destroy();
  const distBuckets = Array(16).fill(0);
  tests.forEach(t => distBuckets[t.score]++);
  charts['test-scores'] = new Chart(scoreDistCtx, {
    type: 'bar',
    data: {
      labels: Array.from({length: 16}, (_, i) => i),
      datasets: [{ label: 'Tests', data: distBuckets, backgroundColor: getAccentColor(0.75), borderRadius: 4 }]
    },
    options: { ...chartOptions({}), plugins: { legend: { display: false } } }
  });

  // Accuracy by position chart (across all tests)
  const byPosCtx = document.getElementById('chart-test-by-pos');
  if (charts['test-by-pos']) charts['test-by-pos'].destroy();
  const posTotals = Array(15).fill(0);
  const posCorrect = Array(15).fill(0);
  tests.forEach(t => {
    (t.results || []).forEach((r, i) => {
      posTotals[i]++;
      if (r.correct) posCorrect[i]++;
    });
  });
  charts['test-by-pos'] = new Chart(byPosCtx, {
    type: 'bar',
    data: {
      labels: Array.from({length: 15}, (_, i) => i + 1),
      datasets: [{ label: 'Accuracy %', data: posTotals.map((t, i) => t ? Math.round(posCorrect[i] / t * 100) : 0), backgroundColor: Array.from({length:15}, (_, i) => `hsl(${140 - i * 8}, 65%, 55%)`), borderRadius: 4 }]
    },
    options: chartOptions({ max: 100, unit: '%' })
  });

  // Recent tests list (last 10)
  const recent = [...tests].reverse().slice(0, 10);
  listEl.innerHTML = recent.map(t => {
    const pct = Math.round(t.score / t.total * 100);
    const d = new Date(t.date).toLocaleDateString();
    return `<div class="test-history-row">
      <div class="test-score-badge">${t.score}/${t.total}</div>
      <div class="test-history-bar"><div class="test-history-bar-fill" style="width:${pct}%"></div></div>
      <div class="test-history-meta">${pct}% · ${d}</div>
    </div>`;
  }).join('');
}

document.getElementById('stats-time-filter').addEventListener('click', e => {
  const btn = e.target.closest('.toggle-btn');
  if (!btn) return;
  document.querySelectorAll('#stats-time-filter .toggle-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  statsRange = btn.dataset.range;
  renderStats();
});

// ============================================================
// HISTORY PAGE (async)
// ============================================================
async function renderHistory() {
  const attempts = await loadAttempts();
  const histComp = document.getElementById('hist-comp').value;
  const histYear = document.getElementById('hist-year').value;
  const histResult = document.getElementById('hist-result').value;
  const histSort = document.getElementById('hist-sort').value;

  const rows = [];
  Object.entries(attempts).forEach(([id, arr]) => {
    arr.forEach(a => rows.push({ id, ...a }));
  });

  let filtered = rows;
  if (histComp !== 'all') filtered = filtered.filter(r => {
    const p = problems.find(x => x.ID === r.id);
    return p && p.Comp === histComp;
  });
  if (histYear !== 'all') filtered = filtered.filter(r => {
    const p = problems.find(x => x.ID === r.id);
    return p && p.Year === histYear;
  });
  if (histResult !== 'all') filtered = filtered.filter(r => histResult === 'correct' ? r.correct : !r.correct);

  if (histSort === 'date') {
    filtered.sort((a, b) => new Date(b.date) - new Date(a.date));
  } else if (histSort === 'comp') {
    filtered.sort((a, b) => {
      const pa = problems.find(x => x.ID === a.id), pb = problems.find(x => x.ID === b.id);
      return (pa?.Comp || '').localeCompare(pb?.Comp || '') || (pa?.Year || '').localeCompare(pb?.Year || '');
    });
  } else if (histSort === 'year') {
    filtered.sort((a, b) => {
      const pa = problems.find(x => x.ID === a.id), pb = problems.find(x => x.ID === b.id);
      return (pa?.Year || '').localeCompare(pb?.Year || '') || (pa?.Comp || '').localeCompare(pb?.Comp || '');
    });
  }

  const tbody = document.getElementById('history-tbody');
  if (!filtered.length) {
    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text3);">No attempts yet. Start practicing!</td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.slice(0, 200).map((r, i) => {
    const prob = problems.find(p => p.ID === r.id);
    return `<tr>
      <td class="col-id">${r.id}</td>
      <td><span class="badge" style="font-size:10px;padding:2px 6px;">${prob?.Comp || '—'}</span></td>
      <td>${prob?.Year || '—'}</td>
      <td>${prob?.PN || '—'}</td>
      <td style="font-family:var(--font-mono);">${r.userAnswer ?? '—'}</td>
      <td class="col-correct-ans" style="font-family:var(--font-mono); color:var(--accent);">${r.correctAnswer ?? prob?.A ?? '—'}</td>
      <td><span class="pill ${r.correct ? 'pill-correct' : 'pill-wrong'}">${r.correct ? '✓' : '✗'}</span></td>
      <td style="font-size:11px;">${r.date ? new Date(r.date).toLocaleDateString() : '—'}</td>
      <td style="text-align:center; padding:4px;">
        ${prob ? `<button class="open-problem-btn" data-id="${r.id}" title="Open problem">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        </button>` : ''}
      </td>
      <td style="text-align:center; padding:4px;">
        <button class="delete-attempt-btn" data-id="${r.id}" data-date="${r.date}" title="Delete this attempt">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
        </button>
      </td>
    </tr>`;
  }).join('');
}

async function deleteAttempt(problemId, date) {
  await api('delete_attempt', { problem_id: problemId, date });
  if (_attemptsCache && _attemptsCache[problemId]) {
    _attemptsCache[problemId] = _attemptsCache[problemId].filter(a => a.date !== date);
    if (!_attemptsCache[problemId].length) delete _attemptsCache[problemId];
  }
  renderHistory();
}
// Delete attempt button handler (delegated)
document.getElementById('history-tbody').addEventListener('click', e => {
  const openBtn = e.target.closest('.open-problem-btn');
  if (openBtn) {
    const prob = problems.find(p => p.ID === openBtn.dataset.id);
    if (!prob) return;
    navigate('practice');
    sessionQueue = [prob];
    sessionIndex = 0;
    document.getElementById('practice-config').style.display = 'none';
    document.getElementById('question-card').classList.add('active');
    showQuestion(prob);
    return;
  }
  const btn = e.target.closest('.delete-attempt-btn');
  if (!btn) return;
  const id = btn.dataset.id;
  const date = btn.dataset.date;
  deleteAttempt(id, date);
});

document.getElementById('hist-comp').addEventListener('change', renderHistory);
document.getElementById('hist-year').addEventListener('change', renderHistory);
document.getElementById('hist-result').addEventListener('change', renderHistory);
document.getElementById('hist-sort').addEventListener('change', renderHistory);

document.getElementById('export-btn').addEventListener('click', () => {
  const attempts = loadAttempts();
  let csv = 'ID,Competition,Year,Problem Number,Your Answer,Correct Answer,Result,Date\n';
  Object.entries(attempts).forEach(([id, arr]) => {
    const prob = problems.find(p => p.ID === id);
    arr.forEach(a => {
      csv += `${id},${prob?.Comp||''},${prob?.Year||''},${prob?.PN||''},${a.userAnswer??''},${a.correctAnswer??''},${a.correct?'correct':'wrong'},${a.date||''}\n`;
    });
  });
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href = url; a.download = 'aimelab_history.csv'; a.click();
});
document.getElementById('export-btn').addEventListener('click', async () => {
  const attempts = await loadAttempts();
  let csv = 'ID,Competition,Year,Problem Number,Your Answer,Correct Answer,Result,Date\n';
  Object.entries(attempts).forEach(([id, arr]) => {
    const prob = problems.find(p => p.ID === id);
    arr.forEach(a => {
      csv += `${id},${prob?.Comp||''},${prob?.Year||''},${prob?.PN||''},${a.userAnswer??''},${a.correctAnswer??''},${a.correct?'correct':'wrong'},${a.date||''}\n`;
    });
  });
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href = url; a.download = 'aimelab_history.csv'; a.click();
});

// ============================================================
// SETTINGS (DB-backed)
// ============================================================
function applySettings(s) {
  // Map snake_case from DB to camelCase expected by applySettings
  const mapped = {
    theme: s.theme || 'dark',
    fontSize: s.font_size || s.fontSize || 'medium',
    autoadvance: !!(s.autoadvance),
    showid: s.showid !== undefined ? !!(s.showid) : true,
    confirmSkip: !!(s.confirm_skip || s.confirmSkip),
    hcontrast: !!(s.hcontrast),
    reducemotion: !!(s.reducemotion),
    largetargets: !!(s.largetargets),
  };
  document.documentElement.dataset.theme = mapped.theme;
  document.documentElement.dataset.fontSize = mapped.fontSize;
  if (mapped.hcontrast) {
    const hc = {
      dark:     { text: '#ffffff', text2: '#d0d4f0', text3: '#a0a8cc', border: '#6070c0' },
      light:    { text: '#000000', text2: '#111630', text3: '#2a3060', border: '#4a5080' },
      sepia:    { text: '#fff8e8', text2: '#f0ddb0', text3: '#c8a860', border: '#c09040' },
      forest:   { text: '#f0fff4', text2: '#c0f0cc', text3: '#70d888', border: '#40b860' },
      midnight: { text: '#ffffff', text2: '#c8d0ff', text3: '#9090e0', border: '#6060c0' },
      rose:     { text: '#ffffff', text2: '#f8c0d8', text3: '#e080a8', border: '#c05080' },
      ocean:    { text: '#ffffff', text2: '#b0e0f8', text3: '#60c0f0', border: '#2090c0' },
      dracula:  { text: '#ffffff', text2: '#e0d0ff', text3: '#b090e0', border: '#8060c0' },
      sunset:   { text: '#ffffff', text2: '#ffd8b0', text3: '#ffb060', border: '#e08030' },
      arctic:   { text: '#000000', text2: '#0a2040', text3: '#204060', border: '#0080c0' },
      candy:    { text: '#ffffff', text2: '#f0c0ff', text3: '#c070e0', border: '#9030c0' },
      mocha:    { text: '#ffffff', text2: '#f0d8b0', text3: '#d0a860', border: '#a07030' },
    }[mapped.theme] || { text: '#ffffff', text2: '#d0d4f0', text3: '#a0a8cc', border: '#6070c0' };
    document.documentElement.style.setProperty('--text', hc.text);
    document.documentElement.style.setProperty('--text2', hc.text2);
    document.documentElement.style.setProperty('--text3', hc.text3);
    document.documentElement.style.setProperty('--border', hc.border);
  } else {
    document.documentElement.style.removeProperty('--text');
    document.documentElement.style.removeProperty('--text2');
    document.documentElement.style.removeProperty('--text3');
    document.documentElement.style.removeProperty('--border');
  }
  if (mapped.reducemotion) {
    document.documentElement.style.setProperty('--transition', '0s');
  } else {
    document.documentElement.style.removeProperty('--transition');
  }
  if (mapped.largetargets) {
    document.documentElement.style.setProperty('--btn-padding', '14px 24px');
    document.documentElement.style.setProperty('font-size', '17px');
  } else {
    document.documentElement.style.removeProperty('--btn-padding');
    document.documentElement.style.removeProperty('font-size');
  }

  document.querySelectorAll('.theme-swatch').forEach(s2 => s2.classList.toggle('active', s2.dataset.theme === mapped.theme));
  document.querySelectorAll('.font-size-btn').forEach(b => b.classList.toggle('active', b.dataset.size === mapped.fontSize));
  document.getElementById('setting-autoadvance').checked = mapped.autoadvance;
  document.getElementById('setting-showid').checked = mapped.showid;
  document.getElementById('setting-confirm-skip').checked = mapped.confirmSkip;
  document.getElementById('setting-hcontrast').checked = mapped.hcontrast;
  document.getElementById('setting-reducemotion').checked = mapped.reducemotion;
  document.getElementById('setting-largetargets').checked = mapped.largetargets;
}

document.querySelectorAll('.theme-swatch').forEach(sw => {
  sw.addEventListener('click', () => {
    const s = loadSettings(); s.theme = sw.dataset.theme;
    applySettings(s); saveSettings(s);
    Object.assign(INITIAL_SETTINGS, { theme: s.theme });
  });
});

document.querySelectorAll('.font-size-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const s = loadSettings(); s.font_size = btn.dataset.size; s.fontSize = btn.dataset.size;
    applySettings(s); saveSettings(s);
    Object.assign(INITIAL_SETTINGS, { font_size: s.font_size });
  });
});

['setting-autoadvance', 'setting-showid', 'setting-confirm-skip', 'setting-hcontrast', 'setting-reducemotion', 'setting-largetargets'].forEach(id => {
  document.getElementById(id).addEventListener('change', function() {
    const s = loadSettings();
    const keyMap = {
      'setting-autoadvance': 'autoadvance', 'setting-showid': 'showid',
      'setting-confirm-skip': ['confirm_skip','confirmSkip'],
      'setting-hcontrast': 'hcontrast', 'setting-reducemotion': 'reducemotion',
      'setting-largetargets': 'largetargets'
    };
    const k = keyMap[id];
    if (Array.isArray(k)) { s[k[0]] = this.checked; s[k[1]] = this.checked; }
    else s[k] = this.checked;
    applySettings(s); saveSettings(s);
  });
});

document.getElementById('export-data-btn').addEventListener('click', async () => {
  const data = { attempts: await loadAttempts(), settings: loadSettings(), tests: await loadTests() };
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob); const a = document.createElement('a');
  a.href = url; a.download = 'aimelab_data.json'; a.click();
});

document.getElementById('clear-data-btn').addEventListener('click', async () => {
  if (confirm('This will permanently delete all your attempt history and test records. Are you sure?')) {
    await api('clear_attempts');
    _attemptsCache = null; _testsCache = null;
    alert('Data cleared.');
  }
});

document.getElementById('reset-progress-btn').addEventListener('click', async () => {
  if (confirm('Reset ALL data? This cannot be undone.')) {
    await api('clear_attempts');
    _attemptsCache = null; _testsCache = null;
    alert('Progress reset.'); navigate('home');
  }
});

// ============================================================
// TESTS PAGE
// ============================================================
let testQuestions = [];
let testAnswers = [];
let testCurrentIdx = 0;
let testActive = false;

function renderTestsPage() {
  document.getElementById('test-config').style.display = '';
  document.getElementById('test-active').style.display = 'none';
  document.getElementById('test-results').style.display = 'none';
}

async function buildTestQuestions() {
  const noRepeat = document.getElementById('test-no-repeat').checked;
  const testCompVal = document.getElementById('test-comp').value;
  const testYearVal = document.getElementById('test-year').value;
  const attempts = await loadAttempts();

  let basePool = [...problems];
  if (testCompVal === 'AIME_ALL') basePool = basePool.filter(p => p.Comp.startsWith('AIME'));
  else if (testCompVal === 'AMC_ALL') basePool = basePool.filter(p => p.Comp.startsWith('AMC'));
  else basePool = basePool.filter(p => p.Comp === testCompVal);
  if (testYearVal !== 'all') basePool = basePool.filter(p => p.Year === testYearVal);

  const maxPN = Math.max(...basePool.map(p => parseInt(p.PN) || 0).filter(n => n > 0));
  if (!maxPN) return null;

  const questions = [];
  for (let pn = 1; pn <= maxPN; pn++) {
    let pool = basePool.filter(p => parseInt(p.PN, 10) === pn);
    if (!pool.length) continue;
    if (noRepeat) {
      const noRepeatPool = pool.filter(p => !attempts[p.ID] || !attempts[p.ID].length);
      pool = noRepeatPool.length ? noRepeatPool : pool;
    }
    questions.push(pool[Math.floor(Math.random() * pool.length)]);
  }
  return questions.length ? questions : null;
}

async function startTest() {
  const qs = await buildTestQuestions();
  if (!qs) { alert('Not enough problems to build a test. Try unchecking "Skip already-attempted".'); return; }
  testQuestions = qs;
  testAnswers = Array(qs.length).fill(null);
  testCurrentIdx = 0;
  testActive = true;
  document.getElementById('test-config').style.display = 'none';
  document.getElementById('test-results').style.display = 'none';
  document.getElementById('test-active').style.display = '';
  renderTestNav();
  showTestQuestion(0);
}
function renderTestNav() {
  const totalEl = document.getElementById('test-q-total');
  if (totalEl) totalEl.textContent = testQuestions.length;
  // Progress dots
  const dotsEl = document.getElementById('test-progress-dots');
  dotsEl.innerHTML = '';
  testAnswers.forEach((ans, i) => {
    const dot = document.createElement('div');
    dot.className = 'test-prog-dot';
    if (i === testCurrentIdx) dot.classList.add('current');
    else if (ans !== null) dot.classList.add(ans.correct ? 'answered-correct' : 'answered-wrong');
    dotsEl.appendChild(dot);
  });

  // Sidebar nav
  const nav = document.getElementById('test-question-nav');
  nav.innerHTML = '';
  testAnswers.forEach((ans, i) => {
    const row = document.createElement('div');
    row.className = 'test-q-row' + (i === testCurrentIdx ? ' current-q' : '');
    row.innerHTML = `
      <div class="test-q-num ${ans === null ? '' : ans.correct ? 'status-correct' : 'status-wrong'}">${i + 1}</div>
      <span class="test-q-year">${testQuestions[i].Comp} ${testQuestions[i].Year}</span>
      <span class="test-q-status-icon">
        ${ans === null
          ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>'
          : ans.correct
            ? '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--correct)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>'
            : '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--wrong)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
        }
      </span>`;
    row.addEventListener('click', () => navigateTestQuestion(i));
    nav.appendChild(row);
  });

  // Answered count
  const answeredCount = testAnswers.filter(a => a !== null).length;
  document.getElementById('test-answered-count').textContent = answeredCount;
  document.getElementById('test-q-label').textContent = testCurrentIdx + 1;
}

async function showTestQuestion(idx) {
  testCurrentIdx = idx;
  const problem = testQuestions[idx];
  const ans = testAnswers[idx];
  const settings = loadSettings();

  const meta = document.getElementById('test-question-meta');
  let metaHTML = `<span class="badge badge-accent">${problem.Comp}</span>`;
  metaHTML += `<span class="badge">${problem.Year}</span>`;
  metaHTML += `<span class="badge">Problem ${problem.PN}</span>`;
  if (settings.showid)
    metaHTML += `<span class="badge" style="opacity:0.6;font-family:var(--font-mono);font-size:11px;cursor:default;" data-tooltip="${problem.ID}">${problem.ID.slice(0,8)}…</span>`;
  meta.innerHTML = metaHTML;

  const html = await renderProblemText(problem.Q);
  document.getElementById('test-question-body').innerHTML = html;
  requestAnimationFrame(() => typeset('#test-question-body'));

  // Update label and placeholder based on competition type
  const testLabel = document.getElementById('test-answer-input-label');
  if (testLabel) testLabel.textContent = problem.IsAIME ? 'Your Answer (numeric)' : 'Your Answer (A–E)';

  // Restore answer state if already answered
  const input = document.getElementById('test-answer-input');
  input.placeholder = problem.IsAIME ? 'e.g. 42' : 'e.g. B';
  const banner = document.getElementById('test-result-banner');
  const displayCorrect = problem.Letter ? `${problem.Letter} (${ans?.correctAnswer||problem.A})` : (ans?.correctAnswer||problem.A);

  if (ans !== null) {
    input.value = ans.userAnswer ?? '';
    input.disabled = true;
    document.getElementById('test-submit-btn').disabled = true;
    document.getElementById('test-submit-btn2').disabled = true;
    banner.className = 'result-banner show ' + (ans.correct ? 'correct' : 'wrong');
    banner.innerHTML = ans.correct
      ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg> Correct! The answer is <strong>${displayCorrect}</strong>.`
      : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg><span>Incorrect.</span><span>Your answer: <strong>${ans.userAnswer}</strong></span><span>Correct: <strong>${displayCorrect}</strong></span>`;
  } else {
    input.value = '';
    input.disabled = false;
    document.getElementById('test-submit-btn').disabled = false;
    document.getElementById('test-submit-btn2').disabled = false;
    banner.className = 'result-banner';
    banner.innerHTML = '';
  }

  renderTestNav();
}

function navigateTestQuestion(idx) {
  showTestQuestion(idx);
}

function submitTestAnswer() {
  const idx = testCurrentIdx;
  if (testAnswers[idx] !== null) return; // already answered

  const val = document.getElementById('test-answer-input').value.trim();
  if (val === '') { alert('Please enter an answer.'); return; }
  if (!problem.IsAIME && !/^[A-Ea-e]$/.test(val)) {
    alert('Please enter a letter answer (A–E).'); return;
  }
  const correctAnswer = problem.A;
  const correct = checkAnswer(val, problem);
  const displayCorrect = problem.Letter ? `${problem.Letter} (${correctAnswer})` : correctAnswer;

  testAnswers[idx] = { userAnswer: val, correctAnswer, correct };

  // Record as a regular attempt too
  recordAttempt(problem.ID, correct, val, correctAnswer);

  const banner = document.getElementById('test-result-banner');
  banner.className = 'result-banner show ' + (correct ? 'correct' : 'wrong');
  banner.innerHTML = correct
    ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg> Correct! The answer is <strong>${displayCorrect}</strong>.`
    : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg><span>Incorrect.</span><span>Your answer: <strong>${val}</strong></span><span>Correct: <strong>${displayCorrect}</strong></span>`;

  document.getElementById('test-answer-input').disabled = true;
  document.getElementById('test-submit-btn').disabled = true;
  document.getElementById('test-submit-btn2').disabled = true;

  renderTestNav();

  // Auto-advance to next unanswered
  const nextUnanswered = testAnswers.findIndex((a, i) => i > idx && a === null);
  if (nextUnanswered !== -1) {
    setTimeout(() => showTestQuestion(nextUnanswered), 800);
  }
}

function finishTest() {
  const unanswered = testAnswers.filter(a => a === null).length;
  if (unanswered > 0) {
    if (!confirm(`You have ${unanswered} unanswered question${unanswered > 1 ? 's' : ''}. Finish anyway? Unanswered questions will count as wrong.`)) return;
    // Fill unanswered as wrong with null answer
    testAnswers = testAnswers.map((a, i) => a !== null ? a : {
      userAnswer: null, correctAnswer: parseInt(testQuestions[i].A, 10), correct: false
    });
  }

  const score = testAnswers.filter(a => a.correct).length;
  const results = testAnswers.map((a, i) => ({
    pn: i + 1,
    id: testQuestions[i].ID,
    year: testQuestions[i].Year,
    comp: testQuestions[i].Comp,
    correct: a.correct,
    userAnswer: a.userAnswer,
    correctAnswer: a.correctAnswer
  }));

  recordTest(score, testQuestions.length, results);
  testActive = false;

  showTestResults(score, results);
}

function showTestResults(score, results) {
  document.getElementById('test-active').style.display = 'none';
  document.getElementById('test-results').style.display = '';

  const total = testQuestions.length;
  const pct = Math.round(score / total * 100);
  document.getElementById('test-score-title').textContent = `${score} / ${total}`;
  document.getElementById('test-score-subtitle').textContent =
    pct >= 80 ? '🏆 Excellent work!' :
    pct >= 60 ? '👍 Good effort — keep practicing!' :
    pct >= 40 ? '📚 Keep at it — review the mistakes below.' :
    '💪 Every attempt counts. Review and retry!';

  document.getElementById('test-result-stats').innerHTML = `
    <div class="stat-card stat-correct"><div class="stat-value">${score}</div><div class="stat-label">Correct</div></div>
    <div class="stat-card stat-wrong"><div class="stat-value">${15 - score}</div><div class="stat-label">Wrong</div></div>
    <div class="stat-card stat-accent2"><div class="stat-value">${pct}%</div><div class="stat-label">Score</div></div>
  `;

  // Result grid cells
  const grid = document.getElementById('test-results-grid');
  grid.innerHTML = results.map(r =>
    `<div class="test-res-cell ${r.correct ? 'correct' : r.userAnswer === null ? 'skipped' : 'wrong'}" title="Q${r.pn}: ${r.correct ? 'Correct' : r.userAnswer === null ? 'Skipped' : 'Wrong (got ' + r.userAnswer + ', ans ' + r.correctAnswer + ')'}">
      ${r.pn}
    </div>`
  ).join('');

  // Detailed breakdown
  const detail = document.getElementById('test-results-detail');
  const wrongOnes = results.filter(r => !r.correct);
  if (!wrongOnes.length) {
    detail.innerHTML = `<p class="text-muted" style="margin-top:12px;">Perfect score! 🎉</p>`;
  } else {
    detail.innerHTML = `<div class="card-title" style="margin-top:16px; margin-bottom:8px;">Missed Questions</div>` +
      wrongOnes.map(r => `
        <div style="display:flex; align-items:center; gap:12px; padding:8px 0; border-bottom:1px solid var(--border);">
          <span class="badge" style="flex-shrink:0;">Q${r.pn}</span>
          <span style="font-size:12px; color:var(--text2); flex:1;">${r.comp || ''} ${r.year}</span>
          <span style="font-size:12px; color:var(--text3);">Your: <strong style="color:var(--wrong);">${r.userAnswer ?? '—'}</strong></span>
          <span style="font-size:12px; color:var(--text3);">Ans: <strong style="color:var(--correct);">${r.correctAnswer}</strong></span>
          <a href="${getAoPSUrl(testQuestions[r.pn - 1])}" target="_blank" rel="noopener" class="badge" style="background:var(--accent-glow);color:var(--accent);text-decoration:none;">
            Solution ↗
          </a>
        </div>`
      ).join('');
  }
}

function abandonTest() {
  if (!confirm('Abandon this test? Progress will be lost.')) return;
  testActive = false;
  testQuestions = [];
  testAnswers = [];
  renderTestsPage();
}

document.getElementById('start-test-btn').addEventListener('click', startTest);
document.getElementById('test-submit-btn').addEventListener('click', submitTestAnswer);
document.getElementById('test-submit-btn2').addEventListener('click', submitTestAnswer);
document.getElementById('test-next-btn').addEventListener('click', () => {
  const nextIdx = testAnswers.findIndex((a, i) => i > testCurrentIdx && a === null);
  const idx = nextIdx !== -1 ? nextIdx : (testCurrentIdx + 1 < testQuestions.length ? testCurrentIdx + 1 : testCurrentIdx);
  showTestQuestion(idx);
});
document.getElementById('test-finish-btn').addEventListener('click', finishTest);
document.getElementById('test-abandon-btn').addEventListener('click', abandonTest);
document.getElementById('test-again-btn').addEventListener('click', () => { renderTestsPage(); });
document.getElementById('test-review-btn').addEventListener('click', () => {
  // Load wrong answers into practice session queue
  const wrongProblems = testAnswers.map((a, i) => (!a.correct ? testQuestions[i] : null)).filter(Boolean);
  if (!wrongProblems.length) { alert('No mistakes to review!'); return; }
  navigate('practice');
  sessionQueue = wrongProblems;
  sessionIndex = 0;
  document.getElementById('practice-config').style.display = 'none';
  document.getElementById('question-card').classList.add('active');
  showQuestion(wrongProblems[0]);
});
document.getElementById('test-answer-input').addEventListener('keydown', e => {
  if (e.key === 'Enter') submitTestAnswer();
});
// Part filter mutual exclusion
document.getElementById('test-again-btn').addEventListener('click', () => { renderTestsPage(); });
document.getElementById('test-review-btn').addEventListener('click', async () => {
  const wrongProblems = testAnswers.map((a, i) => (!a.correct ? testQuestions[i] : null)).filter(Boolean);
  if (!wrongProblems.length) { alert('No mistakes to review!'); return; }
  navigate('practice');
  sessionQueue = wrongProblems;
  sessionIndex = 0;
  document.getElementById('practice-config').style.display = 'none';
  document.getElementById('question-card').classList.add('active');
  showQuestion(wrongProblems[0]);
});
document.getElementById('test-answer-input').addEventListener('keydown', e => {
  if (e.key === 'Enter') submitTestAnswer();
});
document.getElementById('test-comp').addEventListener('change', function() {
  const years = getYearsForComp(this.value);
  const testYearSel = document.getElementById('test-year');
  testYearSel.innerHTML = '<option value="all">All Years</option>';
  years.forEach(y => { const o = document.createElement('option'); o.value = y; o.textContent = y; testYearSel.appendChild(o); });
});
// ============================================================
// CHART HELPERS
// ============================================================
function getAccentColor(alpha) {
  const theme = document.documentElement.dataset.theme;
  const colors = {
    sepia:    `rgba(212,168,90,${alpha})`,
    forest:   `rgba(94,207,128,${alpha})`,
    midnight: `rgba(129,140,248,${alpha})`,
    rose:     `rgba(244,114,182,${alpha})`,
    ocean:    `rgba(56,189,248,${alpha})`,
    dracula:  `rgba(189,147,249,${alpha})`,
    sunset:   `rgba(251,146,60,${alpha})`,
    arctic:   `rgba(14,165,233,${alpha})`,
    candy:    `rgba(232,121,249,${alpha})`,
    mocha:    `rgba(203,168,122,${alpha})`,
  };
  return colors[theme] || `rgba(108,142,245,${alpha})`;
}

function chartScaleX() {
  return { ticks: { color: '#9198b5', font: { family: 'Outfit', size: 11 } }, grid: { color: 'rgba(255,255,255,0.05)' } };
}
function chartScaleY(max, unit) {
  return {
    ticks: { color: '#9198b5', font: { family: 'Outfit', size: 11 }, callback: v => unit ? v + unit : v },
    grid: { color: 'rgba(255,255,255,0.05)' },
    ...(max ? { max } : {})
  };
}
function chartOptions(opts = {}) {
  return {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#9198b5', font: { family: 'Outfit', size: 12 } } } },
    scales: { x: chartScaleX(), y: chartScaleY(opts.max, opts.unit) }
  };
}

// ============================================================
// MATHJAX HELPER
// ============================================================
function typeset(selector) {
  if (window.MathJax && MathJax.typesetPromise) {
    const el = selector
      ? document.querySelectorAll(selector)
      : [document.body];

    MathJax.typesetPromise([...el]).catch(console.error);
  }
}

// ============================================================
// PROFILE & AUTH JS
// ============================================================

// ── Banner CSS generation ──
function bannerCSS(style, c1, c2, angle) {
  switch(style) {
    case 'solid':
      return `background:${c1};`;
    case 'mesh':
      return `background:radial-gradient(ellipse at 20% 50%, ${c1} 0%, transparent 60%), radial-gradient(ellipse at 80% 20%, ${c2} 0%, transparent 55%), radial-gradient(ellipse at 60% 80%, ${c1}88 0%, transparent 50%);background-color:${c2}22;`;
    case 'wave':
      return `background:linear-gradient(${angle}deg, ${c1} 0%, ${c2} 50%, ${c1} 100%);`;
    case 'dots':
      return `background-color:${c1}22;background-image:radial-gradient(${c2} 1.5px, transparent 1.5px);background-size:18px 18px;`;
    case 'none':
      return `background:var(--bg3);`;
    default: // gradient
      return `background:linear-gradient(${angle}deg, ${c1}, ${c2});`;
  }
}

function applyBannerToEl(el, style, c1, c2, angle) {
  const css = bannerCSS(style, c1, c2, angle);
  el.setAttribute('style', css);
}

// State for edit form
let editBannerStyle  = CURRENT_USER.banner_style  || 'gradient';
let editBannerColor1 = CURRENT_USER.banner_color1 || '#6c8ef5';
let editBannerColor2 = CURRENT_USER.banner_color2 || '#a78bfa';
let editBannerAngle  = CURRENT_USER.banner_angle  || 135;

function refreshBannerPreview() {
  const prev = document.getElementById('banner-preview-box');
  if (prev) applyBannerToEl(prev, editBannerStyle, editBannerColor1, editBannerColor2, editBannerAngle);
  // Also update the live card banner
  const liveBanner = document.querySelector('#profile-card-view .profile-banner');
  if (liveBanner) applyBannerToEl(liveBanner, editBannerStyle, editBannerColor1, editBannerColor2, editBannerAngle);
  // Show/hide angle slider
  const angleRow = document.getElementById('banner-angle-row');
  if (angleRow) angleRow.style.display = ['gradient','wave'].includes(editBannerStyle) ? '' : 'none';
}

// Banner style picker
document.getElementById('banner-style-picker')?.addEventListener('click', e => {
  const btn = e.target.closest('.banner-style-btn');
  if (!btn) return;
  document.querySelectorAll('.banner-style-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  editBannerStyle = btn.dataset.style;
  refreshBannerPreview();
});

// Color pickers
document.getElementById('banner-color1')?.addEventListener('input', e => {
  editBannerColor1 = e.target.value;
  document.getElementById('bc1-swatch').style.background = editBannerColor1;
  refreshBannerPreview();
});
document.getElementById('bc1-swatch')?.addEventListener('click', () => {
  document.getElementById('banner-color1').click();
});
document.getElementById('banner-color2')?.addEventListener('input', e => {
  editBannerColor2 = e.target.value;
  document.getElementById('bc2-swatch').style.background = editBannerColor2;
  refreshBannerPreview();
});
document.getElementById('bc2-swatch')?.addEventListener('click', () => {
  document.getElementById('banner-color2').click();
});

// Angle slider
document.getElementById('banner-angle')?.addEventListener('input', e => {
  editBannerAngle = parseInt(e.target.value);
  document.getElementById('banner-angle-val').textContent = editBannerAngle + '°';
  refreshBannerPreview();
});

document.getElementById('auz')
  ?.addEventListener('click', () => {
    const input = document.getElementById('avatar-upload-input');
    
    if (input) {
      input.value = ''; // allows re-selecting same file
      input.click();
    }
});

// ── Avatar upload ──
document.getElementById('avatar-upload-input')?.addEventListener('change', async (e) => {
  const file = e.target.files[0];
  if (!file) return;

  // Client-side preview immediately
  const reader = new FileReader();
  reader.onload = ev => {
    const editAvatar = document.getElementById('edit-avatar-preview');
    if (editAvatar) editAvatar.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" id="avatar-img-preview"/>`;
    // Also update live card
    document.getElementById('profile-view-avatar-img')?.setAttribute('src', ev.target.result);
  };
  reader.readAsDataURL(file);

  // Upload
  const prog = document.getElementById('avatar-upload-progress');
  const bar  = document.getElementById('avatar-upload-bar');
  const stat = document.getElementById('avatar-upload-status');
  prog.style.display = '';
  bar.style.width = '30%';
  stat.textContent = 'Uploading…';

  const form = new FormData();
  form.append('avatar', file);
  try {
    const r = await fetch('api/api.php?action=upload_avatar', { method: 'POST', body: form });
    const res = await r.json();
    bar.style.width = '100%';
    if (res.ok) {
      stat.textContent = 'Uploaded!';
      stat.style.color = 'var(--correct)';
      CURRENT_USER.avatar_url = res.url;
      document.getElementById('delete-avatar-btn').style.display = '';
      // Update sidebar avatar
      const sideAvatar = document.getElementById('sidebar-avatar');
      sideAvatar.innerHTML = `<img src="${res.url}?v=${Date.now()}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;"/>`;
      sideAvatar.style.background = 'transparent';
      setTimeout(() => { prog.style.display = 'none'; stat.style.color = ''; }, 2000);
    } else {
      stat.textContent = res.error || 'Upload failed';
      stat.style.color = 'var(--wrong)';
    }
  } catch {
    stat.textContent = 'Network error';
    stat.style.color = 'var(--wrong)';
  }
});

document.getElementById('delete-avatar-btn')?.addEventListener('click', async () => {
  if (!confirm('Remove your profile photo?')) return;
  await api('delete_avatar');
  CURRENT_USER.avatar_url = null;
  document.getElementById('delete-avatar-btn').style.display = 'none';
  // Reset avatar preview to emoji
  const prev = document.getElementById('edit-avatar-preview');
  if (prev) {
    prev.style.background = profileAvatarColor;
    prev.innerHTML = `<span id="avatar-emoji-display">${profileAvatarEmoji}</span>`;
  }
  // Update sidebar
  const sideAvatar = document.getElementById('sidebar-avatar');
  sideAvatar.style.background = profileAvatarColor;
  sideAvatar.textContent = profileAvatarEmoji;
  // Update live card
  renderProfile();
});

// ── renderProfile ──
async function renderProfile() {
  // Apply current banner to card
  const liveBanner = document.querySelector('#profile-card-view .profile-banner');
  if (liveBanner) applyBannerToEl(liveBanner, CURRENT_USER.banner_style, CURRENT_USER.banner_color1, CURRENT_USER.banner_color2, CURRENT_USER.banner_angle);

  // Update avatar display
  const avatarEl = document.getElementById('profile-preview-avatar');
  if (avatarEl) {
    if (CURRENT_USER.avatar_url) {
      avatarEl.style.background = 'transparent';
      avatarEl.innerHTML = `<img src="${CURRENT_USER.avatar_url}?v=${Date.now()}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" id="profile-view-avatar-img"/>`;
    } else {
      avatarEl.style.background = CURRENT_USER.avatar_color;
      avatarEl.innerHTML = `<span id="avatar-emoji-display">${CURRENT_USER.avatar_emoji}</span>`;
    }
  }

  const attempts = await loadAttempts();
  const tests = await loadTests();
  const allAttempts = [];
  Object.entries(attempts).forEach(([id, arr]) => arr.forEach(a => allAttempts.push({ id, ...a })));

  const total = allAttempts.length;
  const correct = allAttempts.filter(a => a.correct).length;
  const unique = new Set(allAttempts.map(a => a.id)).size;
  const acc = total ? Math.round(correct / total * 100) : 0;
  const streak = calcStreak(allAttempts);

  document.getElementById('pstat-attempts').textContent = total;
  document.getElementById('pstat-correct').textContent = correct;
  document.getElementById('pstat-acc').textContent = acc + '%';
  document.getElementById('pstat-unique').textContent = unique;
  document.getElementById('pstat-tests').textContent = tests.length;
  document.getElementById('profile-streak-val').textContent = streak;

  // ── Heatmap ──
  const heatmap = document.getElementById('profile-heatmap');
  heatmap.innerHTML = '';
  const dayCounts = {};
  allAttempts.forEach(a => { const d = a.date?.slice(0,10); if (d) dayCounts[d] = (dayCounts[d]||0)+1; });
  const today = new Date();
  const startDate = new Date(today); startDate.setDate(startDate.getDate() - 181);
  startDate.setDate(startDate.getDate() - startDate.getDay());
  for (let d = new Date(startDate); d <= today; d.setDate(d.getDate()+1)) {
    const ds = d.toISOString().slice(0,10);
    const count = dayCounts[ds] || 0;
    const level = count===0?0:count<=2?1:count<=5?2:count<=9?3:4;
    const cell = document.createElement('div');
    cell.className = 'heatmap-cell';
    if (level > 0) cell.setAttribute('data-count', level);
    cell.title = ds + (count ? ': '+count+' attempt'+(count>1?'s':'') : ': no activity');
    heatmap.appendChild(cell);
  }

  // ── Comp bars ──
  const compMap = {};
  allAttempts.forEach(a => {
    const prob = problems.find(p => p.ID === a.id); if (!prob) return;
    const k = prob.Comp; if (!compMap[k]) compMap[k] = { c:0, t:0 };
    compMap[k].t++; if (a.correct) compMap[k].c++;
  });
  const sortedComps = Object.entries(compMap).filter(([,v])=>v.t>=3).sort((a,b)=>b[1].t-a[1].t).slice(0,8);
  const barsEl = document.getElementById('profile-comp-bars');
  if (!sortedComps.length) { barsEl.innerHTML = '<p style="color:var(--text3);font-size:13px;">No attempts yet.</p>'; }
  else {
    barsEl.innerHTML = sortedComps.map(([comp,v]) => {
      const pct = Math.round(v.c/v.t*100);
      return `<div class="profile-comp-bar-row"><div class="profile-comp-label">${comp}</div><div class="profile-comp-bar-bg"><div class="profile-comp-bar-fill" style="width:${pct}%"></div></div><div class="profile-comp-pct">${pct}%</div></div>`;
    }).join('');
  }

  // ── Achievements ──
  const achievements = [
    { icon:'🎯', name:'First Blood',    desc:'Solved your first problem',      earned: total>=1 },
    { icon:'🔟', name:'Getting Started',desc:'10 problems attempted',          earned: total>=10 },
    { icon:'💯', name:'Century',        desc:'100 problems attempted',         earned: total>=100 },
    { icon:'⚡', name:'Grind Mode',     desc:'500 problems attempted',         earned: total>=500 },
    { icon:'🏹', name:'Sharpshooter',   desc:'80%+ accuracy (min 20)',         earned: total>=20&&acc>=80 },
    { icon:'✨', name:'Perfectionist',  desc:'90%+ accuracy (min 50)',         earned: total>=50&&acc>=90 },
    { icon:'🔥', name:'On Fire',        desc:'3-day streak',                   earned: streak>=3 },
    { icon:'🌟', name:'Week Warrior',   desc:'7-day streak',                   earned: streak>=7 },
    { icon:'🏆', name:'Unstoppable',    desc:'30-day streak',                  earned: streak>=30 },
    { icon:'📝', name:'Test Taker',     desc:'Completed a mock test',          earned: tests.length>=1 },
    { icon:'📊', name:'Data Driven',    desc:'5 mock tests completed',         earned: tests.length>=5 },
    { icon:'🗺️', name:'Explorer',       desc:'50 unique problems seen',        earned: unique>=50 },
    { icon:'🎖️', name:'Veteran',        desc:'250 unique problems seen',       earned: unique>=250 },
  ];
  document.getElementById('profile-achievements').innerHTML = achievements.map(a =>
    `<div class="profile-achievement ${a.earned?'earned':''}" title="${a.desc}">
      <span class="ach-icon">${a.icon}</span>
      <div class="ach-info"><span class="ach-name">${a.name}</span><span class="ach-desc">${a.desc}</span></div>
    </div>`
  ).join('');
}

// ── Toggle edit/view ──
document.getElementById('profile-edit-toggle').addEventListener('click', () => {
  document.getElementById('profile-edit-section').style.display = '';
  document.getElementById('profile-card-view').style.display = 'none';
  document.getElementById('profile-edit-toggle').parentElement.style.display = 'none';
  document.querySelectorAll('#page-profile > .card:not(#profile-edit-section), #page-profile > div[style*="grid"]').forEach(el => el.style.display = 'none');
  // Init banner state from CURRENT_USER
  editBannerStyle  = CURRENT_USER.banner_style  || 'gradient';
  editBannerColor1 = CURRENT_USER.banner_color1 || '#6c8ef5';
  editBannerColor2 = CURRENT_USER.banner_color2 || '#a78bfa';
  editBannerAngle  = CURRENT_USER.banner_angle  || 135;
  refreshBannerPreview();
});

document.getElementById('profile-cancel-btn').addEventListener('click', () => {
  document.getElementById('profile-edit-section').style.display = 'none';
  document.getElementById('profile-card-view').style.display = '';
  document.getElementById('profile-edit-toggle').parentElement.style.display = '';
  document.querySelectorAll('#page-profile > .card, #page-profile > div[style*="grid"]').forEach(el => el.style.display = '');
  renderProfile();
});

document.getElementById('user-chip-btn').addEventListener('click', (e) => {
  e.stopPropagation();
  document.getElementById('user-menu').classList.toggle('open');
});
document.addEventListener('click', () => {
  document.getElementById('user-menu')?.classList.remove('open');
});
document.getElementById('logout-btn').addEventListener('click', async () => {
  await api('logout');
  location.href = 'index.php?auth=login';
});

// Profile avatar live preview
let profileAvatarColor = <?= json_encode($user['avatar_color'] ?? '#6c8ef5') ?>;
let profileAvatarEmoji = <?= json_encode($user['avatar_emoji'] ?? '\u2211', JSON_UNESCAPED_UNICODE) ?>;

document.getElementById('avatar-color-picker').addEventListener('click', e => {
  const btn = e.target.closest('.avatar-color-btn');
  if (!btn) return;
  document.querySelectorAll('.avatar-color-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  profileAvatarColor = btn.dataset.color;
  // Update the edit-form avatar preview (only color bg, keep emoji)
  if (!CURRENT_USER.avatar_url) {
    const editAvatar = document.getElementById('edit-avatar-preview');
    if (editAvatar) editAvatar.style.background = profileAvatarColor;
  }
});

document.getElementById('avatar-emoji-picker').addEventListener('click', e => {
  const btn = e.target.closest('.avatar-emoji-btn');
  if (!btn) return;
  document.querySelectorAll('.avatar-emoji-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  profileAvatarEmoji = btn.dataset.emoji;
  // Update the edit-form avatar preview emoji
  if (!CURRENT_USER.avatar_url) {
    const emojiSpan = document.getElementById('avatar-emoji-display');
    if (emojiSpan) emojiSpan.textContent = profileAvatarEmoji;
  }
});

document.getElementById('save-profile-btn').addEventListener('click', async () => {
  const newName = document.getElementById('profile-display-name').value.trim();
  const newBio  = document.getElementById('profile-bio').value.trim();
  const res = await api('save_profile', {
    display_name:  newName,
    bio:           newBio,
    avatar_color:  profileAvatarColor,
    avatar_emoji:  profileAvatarEmoji,
    banner_style:  editBannerStyle,
    banner_color1: editBannerColor1,
    banner_color2: editBannerColor2,
    banner_angle:  editBannerAngle,
  });
  if (res?.ok) {
    // Update CURRENT_USER cache
    CURRENT_USER.display_name  = newName;
    CURRENT_USER.bio           = newBio;
    CURRENT_USER.avatar_color  = profileAvatarColor;
    CURRENT_USER.avatar_emoji  = profileAvatarEmoji;
    CURRENT_USER.banner_style  = editBannerStyle;
    CURRENT_USER.banner_color1 = editBannerColor1;
    CURRENT_USER.banner_color2 = editBannerColor2;
    CURRENT_USER.banner_angle  = editBannerAngle;

    // Update sidebar chip
    if (!CURRENT_USER.avatar_url) {
      document.getElementById('sidebar-avatar').style.background = profileAvatarColor;
      document.getElementById('sidebar-avatar').textContent = profileAvatarEmoji;
    }
    // Update live card text
    document.getElementById('profile-view-name').textContent = newName || CURRENT_USER.username;
    document.getElementById('profile-view-bio').innerHTML = newBio
  ? newBio.replace(/\n/g, '<br>')
  : '<span style="color:var(--text3);font-style:italic;">No bio yet.</span>';
  
    const msg = document.getElementById('profile-save-msg');
    msg.style.display = 'inline';
    setTimeout(() => {
      msg.style.display = 'none';
      document.getElementById('profile-cancel-btn').click();
    }, 1200);
  }
});

document.getElementById('share-profile-btn').addEventListener('click', () => {
  const base = <?= json_encode(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')) ?>;
  const url = window.location.origin + (base ? base + '/' : '/') + 'u/' + CURRENT_USER.username;
  navigator.clipboard.writeText(url).then(() => {
    const btn = document.getElementById('share-profile-btn-text');
    btn.textContent = 'Copied!';
    setTimeout(() => { btn.textContent = 'Share Profile'; }, 2000);
  }).catch(() => {
    prompt('Your profile URL:', url);
  });
});

document.getElementById('change-pw-btn').addEventListener('click', async () => {
  const err = document.getElementById('pw-error');
  err.classList.remove('show');
  const res = await api('change_password', {
    current_password: document.getElementById('pw-current').value,
    new_password: document.getElementById('pw-new').value,
  });
  if (res?.ok) {
    document.getElementById('pw-current').value = '';
    document.getElementById('pw-new').value = '';
    const msg = document.getElementById('pw-save-msg');
    msg.style.display = 'inline';
    setTimeout(() => { msg.style.display = 'none'; }, 2000);
  } else {
    err.textContent = (res?.errors || ['Error updating password.']).join(' ');
    err.classList.add('show');
  }
});

// ============================================================
// ADMIN JS
// ============================================================
<?php if ($user['role'] === 'admin'): ?>
async function renderAdmin() {
  // Stats
  const stats = await api('admin_stats');
  if (stats) {
    document.getElementById('admin-stats-grid').innerHTML = `
      <div class="stat-card"><div class="stat-value">${stats.total_users}</div><div class="stat-label">Total Users</div></div>
      <div class="stat-card stat-correct"><div class="stat-value">${stats.users_today}</div><div class="stat-label">New Today</div></div>
      <div class="stat-card stat-accent2"><div class="stat-value">${stats.total_attempts}</div><div class="stat-label">Total Attempts</div></div>
      <div class="stat-card"><div class="stat-value">${stats.total_tests}</div><div class="stat-label">Tests Taken</div></div>
      <div class="stat-card"><div class="stat-value">${stats.active_today}</div><div class="stat-label">Active Today</div></div>
    `;
  }

  // Users table
  const users = await api('admin_users');
  if (!users) return;
  const tbody = document.getElementById('admin-users-tbody');
  const searchVal = (document.getElementById('admin-search')?.value || '').toLowerCase();
  const filtered = searchVal ? users.filter(u =>
    u.username.toLowerCase().includes(searchVal) ||
    u.email.toLowerCase().includes(searchVal) ||
    (u.display_name || '').toLowerCase().includes(searchVal)
  ) : users;

  tbody.innerHTML = filtered.map(u => `
    <tr>
      <td style="color:var(--text3);">${u.id}</td>
      <td><strong>${u.username}</strong></td>
      <td style="color:var(--text2);font-size:12px;">${u.email}</td>
      <td>${u.display_name || '—'}</td>
      <td><span class="role-badge ${u.role}">${u.role}</span></td>
      <td style="text-align:right;">${u.attempt_count}</td>
      <td style="font-size:11px;color:var(--text3);">${u.created_at ? new Date(u.created_at).toLocaleDateString() : '—'}</td>
      <td style="font-size:11px;color:var(--text3);">${u.last_login ? new Date(u.last_login).toLocaleDateString() : 'Never'}</td>
      <td>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          ${u.role !== 'admin' ? `<button class="btn btn-secondary btn-sm" onclick="adminToggleRole(${u.id},'admin')">Make Admin</button>` : `<button class="btn btn-secondary btn-sm" onclick="adminToggleRole(${u.id},'user')">Demote</button>`}
          ${u.role !== 'admin' ? `<button class="btn btn-sm" style="background:var(--wrong);color:#fff;" onclick="adminDeleteUser(${u.id},'${u.username.replace("'","\\'")}')">Delete</button>` : ''}
        </div>
      </td>
    </tr>
  `).join('');
}

async function adminToggleRole(id, role) {
  if (!confirm(`Set user #${id} role to "${role}"?`)) return;
  await api('admin_set_role', { user_id: id, role });
  renderAdmin();
}

async function adminDeleteUser(id, username) {
  if (!confirm(`Delete user "${username}" and all their data? This cannot be undone.`)) return;
  await api('admin_delete_user', { user_id: id });
  renderAdmin();
}

document.getElementById('admin-search')?.addEventListener('input', renderAdmin);
<?php endif; ?>

// ============================================================
// NAVIGATE — single definition
// ============================================================
function navigate(page) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  const pageEl = document.getElementById('page-' + page);
  if (!pageEl) return;
  pageEl.classList.add('active');
  document.querySelector(`.nav-item[data-page="${page}"]`)?.classList.add('active');
  document.querySelectorAll('.mobile-nav-item[data-page]').forEach(n => n.classList.remove('active'));
  document.querySelector(`.mobile-nav-item[data-page="${page}"]`)?.classList.add('active');
  document.querySelectorAll('.mobile-menu-item[data-page]').forEach(n => n.classList.remove('active'));
  document.querySelector(`.mobile-menu-item[data-page="${page}"]`)?.classList.add('active');
  if (page === 'home') renderHome();
  if (page === 'stats') renderStats();
  if (page === 'history') renderHistory();
  if (page === 'browse') renderBrowse();
  if (page === 'tests') renderTestsPage();
  if (page === 'profile') renderProfile();
  <?php if ($user['role'] === 'admin'): ?>
  if (page === 'admin') renderAdmin();
  <?php endif; ?>
  window.scrollTo(0, 0);
  document.getElementById('user-menu')?.classList.remove('open');
}

// Wire up all nav clicks
document.querySelectorAll('.nav-item[data-page]').forEach(item => {
  item.addEventListener('click', () => navigate(item.dataset.page));
});
document.querySelectorAll('.mobile-nav-item[data-page]').forEach(item => {
  item.addEventListener('click', () => navigate(item.dataset.page));
});
document.querySelectorAll('.mobile-menu-item[data-page]').forEach(item => {
  item.addEventListener('click', () => { navigate(item.dataset.page); closeMoreMenu(); });
});
// ============================================================
// TABLE CELL TOOLTIP — show full value on click/tap
// ============================================================
(function() {
  const tip = document.getElementById('cell-tooltip');
  let hideTimer;

  function showTip(el, e) {
    const full = el.getAttribute('title') || el.textContent.trim();
    // Only show if content is actually truncated or has a title
    const isTruncated = el.scrollWidth > el.clientWidth || el.getAttribute('title');
    if (!full || !isTruncated) return;

    tip.textContent = full;
    tip.classList.add('show');

    // Position near the tap/click point but keep within viewport
    const x = e.clientX ?? (el.getBoundingClientRect().left + el.offsetWidth / 2);
    const y = e.clientY ?? (el.getBoundingClientRect().bottom);
    const tipW = 240, tipH = 60;
    const vw = window.innerWidth, vh = window.innerHeight;
    tip.style.left = Math.min(x, vw - tipW - 8) + 'px';
    tip.style.top = Math.min(y + 10, vh - tipH - 8) + 'px';

    clearTimeout(hideTimer);
    hideTimer = setTimeout(hideTip, 2500);
  }

  function hideTip() {
    tip.classList.remove('show');
  }

  function showTipDataAttr(el, e) {
    const full = el.getAttribute('data-tooltip');
    if (!full) return;
    tip.textContent = full;
    tip.classList.add('show');
    const x = e.clientX ?? (el.getBoundingClientRect().left + el.offsetWidth / 2);
    const y = e.clientY ?? (el.getBoundingClientRect().bottom);
    const tipW = 320, tipH = 40;
    const vw = window.innerWidth, vh = window.innerHeight;
    tip.style.left = Math.min(x, vw - tipW - 8) + 'px';
    tip.style.top = Math.min(y + 10, vh - tipH - 8) + 'px';
    clearTimeout(hideTimer);
    hideTimer = setTimeout(hideTip, 3000);
  }

  // Delegate from document — catches dynamically rendered rows too
  document.addEventListener('click', e => {
    const cell = e.target.closest('.history-table td, .history-table th');
    if (cell) { showTip(cell, e); return; }
    const badge = e.target.closest('[data-tooltip]');
    if (badge) { showTipDataAttr(badge, e); return; }
    hideTip();
  });
  document.addEventListener('touchend', e => {
    const cell = e.target.closest('.history-table td, .history-table th');
    if (cell) {
      const t = e.changedTouches[0];
      showTip(cell, { clientX: t.clientX, clientY: t.clientY });
    }
    const badge = e.target.closest('[data-tooltip]');
    if (badge) {
      const t = e.changedTouches[0];
      showTipDataAttr(badge, { clientX: t.clientX, clientY: t.clientY });
    }
  });
  // Also show on mouseenter for desktop
  document.addEventListener('mouseover', e => {
    const badge = e.target.closest('[data-tooltip]');
    if (badge) showTipDataAttr(badge, e);
  });
  document.addEventListener('mouseout', e => {
    if (e.target.closest('[data-tooltip]')) hideTip();
  });
})();

(function keepAlive() {
  const PING_URL = 'https://asymptote-renderer-2.onrender.com/render';
  const INTERVAL = 4 * 60 * 1000; // 4 minutes

  async function ping() {
    try {
      await fetch(PING_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code: 'size(1);' }),
        signal: AbortSignal.timeout(10000),
      });
    } catch (_) {
      // silently ignore — this is just a keepalive
    }
  }

  // First ping after 30s so it doesn't delay startup, then every 4 min
  setTimeout(() => { ping(); setInterval(ping, INTERVAL); }, 30000);
})();


// ============================================================
// INIT
// ============================================================
async function init() {
  // Apply settings from server
  applySettings(INITIAL_SETTINGS);

  // Load attempts early (caching)
  await loadAttempts();

  try {
    const DATASET_URL = './dataset/dataset.csv';
    const resp = await fetch(DATASET_URL);
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    const text = await resp.text();

    const raw = Papa.parse(text, { header: true, skipEmptyLines: true }).data;
    const seen = new Set();
    const deduped = [];
    raw.forEach(r => {
      if (!r.problem_id || !r.problem) return;
      if (!seen.has(r.problem_id)) { seen.add(r.problem_id); deduped.push(r); }
    });

    problems = deduped.map(r => {
      const linkMatch = r.link && r.link.match(
        /(\d{4})_(Fall_AMC_12[AB]|Fall_AMC_10[AB]|AMC_12[ABP]?|AMC_10[AB]?|AMC_8|AIME_II|AIME_I|AIME|AJHSME|AHSME|USAJMO|USAMO|USOJMO|USOMO)_Problems/
      );
      const year = linkMatch ? linkMatch[1] : '';
      const comp = linkMatch ? linkMatch[2].replace(/_/g, ' ') : '';
      const isAIME = comp.startsWith('AIME')
        || ['USAMO', 'USAJMO', 'USOJMO', 'USOMO'].includes(comp)
        || (comp === 'AHSME' && !r.letter);
      const pnMatch = r.link && r.link.match(/Problem_(\d+)/);
      const pn = pnMatch ? pnMatch[1] : '';
      return {
        ID: r.problem_id, Year: year, Comp: comp, PN: pn, Q: r.problem,
        A: (() => {
          if (r.answer == null) return '';
          const s = String(r.answer).trim();
          const n = Number(s);
          return (s !== '' && !isNaN(n) && isFinite(n)) ? String(n) : s;
        })(),
        Letter: r.letter || '', IsAIME: isAIME, Link: r.link || ''
      };
    });

    console.log('Loaded dataset:', problems.length, 'unique problems');
  } catch (err) {
    console.error('Dataset loading failed:', err);
    document.getElementById('loading-overlay').innerHTML = `
      <p style="color:var(--wrong);font-weight:600;">Failed to load dataset. Make sure dataset.csv is at ./dataset/dataset.csv</p>
    `;
    return;
  }

  // Populate dropdowns
  const comps = [...new Set(problems.map(p => p.Comp))].filter(Boolean).sort();
  const compOrder = [
    'AIME', 'AIME I', 'AIME II',
    'AMC 8', 'AMC 10', 'AMC 10A', 'AMC 10B', 'AMC 12', 'AMC 12A', 'AMC 12B', 'AMC 12P',
    'AJHSME', 'AHSME',
    'Fall AMC 10A', 'Fall AMC 10B', 'Fall AMC 12A', 'Fall AMC 12B',
    'USAJMO', 'USAMO', 'USOJMO', 'USOMO'
  ];
  const sortedComps = [...compOrder.filter(c => comps.includes(c)), ...comps.filter(c => !compOrder.includes(c))];

  const filterCompSel = document.getElementById('filter-comp');
  filterCompSel.innerHTML = '<option value="all">All Competitions</option>';
  sortedComps.forEach(c => { const o = document.createElement('option'); o.value = c; o.textContent = c; filterCompSel.appendChild(o); });

  const browseCompSel = document.getElementById('browse-comp');
  browseCompSel.innerHTML = '';
  sortedComps.forEach(c => { const o = document.createElement('option'); o.value = c; o.textContent = c; browseCompSel.appendChild(o); });

  const histCompSel = document.getElementById('hist-comp');
  histCompSel.innerHTML = '<option value="all">All Competitions</option>';
  sortedComps.forEach(c => { const o = document.createElement('option'); o.value = c; o.textContent = c; histCompSel.appendChild(o); });

  const testCompSel = document.getElementById('test-comp');
  testCompSel.innerHTML = '';
  const aimeComps = sortedComps.filter(c => c.startsWith('AIME'));
  const amcComps = sortedComps.filter(c => c.startsWith('AMC') || c.startsWith('Fall AMC'));
  const otherComps = sortedComps.filter(c => !c.startsWith('AIME') && !c.startsWith('AMC') && !c.startsWith('Fall AMC'));
  [
    { v: 'AIME_ALL', t: 'AIME (all)' }, ...aimeComps.map(c => ({ v: c, t: c })),
    { v: 'AMC_ALL',  t: 'AMC (all)'  }, ...amcComps.map(c => ({ v: c, t: c })),
    ...otherComps.map(c => ({ v: c, t: c }))
  ].forEach(({ v, t }) => { const o = document.createElement('option'); o.value = v; o.textContent = t; testCompSel.appendChild(o); });

  populateYearSelects();

  if (browseCompSel.options.length) browseCompSel.value = browseCompSel.options[0].value;
  updateBrowseYears();
  updateProbFilter();

  document.getElementById('loading-overlay').style.display = 'none';
  renderHome();
  renderBrowse();
}

function getYearsForComp(compVal) {
  let pool = [...problems];
  if (compVal === 'AIME_ALL') pool = pool.filter(p => p.Comp.startsWith('AIME'));
  else if (compVal === 'AMC_ALL') pool = pool.filter(p => p.Comp.startsWith('AMC'));
  else if (compVal && compVal !== 'all') pool = pool.filter(p => p.Comp === compVal);
  return [...new Set(pool.map(p => p.Year))].filter(Boolean).sort();
}

function populateYearSelects() {
  // Filter-year and hist-year use their own comp selector to filter years
  ['filter-year', 'hist-year'].forEach(id => {
    const sel = document.getElementById(id);
    const years = getYearsForComp('all');
    sel.innerHTML = '<option value="all">All Years</option>';
    years.forEach(y => { const o = document.createElement('option'); o.value = y; o.textContent = y; sel.appendChild(o); });
  });
  // Test year
  const testCompVal = document.getElementById('test-comp').value;
  const testYears = getYearsForComp(testCompVal);
  const testYearSel = document.getElementById('test-year');
  testYearSel.innerHTML = '<option value="all">All Years</option>';
  testYears.forEach(y => { const o = document.createElement('option'); o.value = y; o.textContent = y; testYearSel.appendChild(o); });
}

function updateBrowseYears() {
  const comp = document.getElementById('browse-comp').value;
  const years = getYearsForComp(comp);
  const sel = document.getElementById('browse-year');
  sel.innerHTML = '<option value="all">All Years</option>';
  years.forEach(y => { const o = document.createElement('option'); o.value = y; o.textContent = y; sel.appendChild(o); });
  if (years.includes('2024')) sel.value = '2024';
  else if (years.length) sel.value = years[years.length - 1];
}

function updateProbFilter() {
  const comp = document.getElementById('filter-comp').value;
  let pool = [...problems];
  if (comp && comp !== 'all') pool = pool.filter(p => p.Comp === comp);
  const maxPN = Math.max(...pool.map(p => parseInt(p.PN) || 0).filter(n => n > 0));
  const probSel = document.getElementById('filter-prob');
  probSel.innerHTML = '<option value="all">Any</option>';
  for (let i = 1; i <= (maxPN || 30); i++) {
    const o = document.createElement('option'); o.value = String(i); o.textContent = `#${i}`; probSel.appendChild(o);
  }
}

init();
</script>
</body>
</html>