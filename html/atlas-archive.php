<?php
// Kickback Kingdom - Atlas Archive (POC with immersive award-show styling)
$requestedAtlasYear = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 3000]]);
$atlasYear = $requestedAtlasYear === false || $requestedAtlasYear === null ? 2026 : $requestedAtlasYear;
$pageTitle = "Atlas Archive {$atlasYear} - Yearly Review (POC)";
$pageImage = "https://kickback-kingdom.com/assets/media/context/loading.gif";
$pageDesc = "Yearly recap for Kickback Kingdom adventurers in {$atlasYear}.";
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Backend\Controllers\AtlasArchiveController;

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-pull-active-account-info.php");

$requestedAccountId = filter_input(INPUT_GET, 'accountId', FILTER_VALIDATE_INT);
$requestedUsername = filter_input(INPUT_GET, 'accountUsername', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$initialTab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

$atlasPayload = AtlasArchiveController::buildPayload(
    $atlasYear,
    $activeAccountInfo->account ?? null,
    $requestedAccountId !== false ? $requestedAccountId : null,
    $requestedUsername ?: null
);

$atlasYear = $atlasPayload['year'] ?? $atlasYear;
?>
<!doctype html>
<html lang="en">
<?php require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-head.php"); ?>
<body>
  <style>
    :root {
      --bg: #060910;
      --card: rgba(12, 18, 28, 0.9);
      --card-strong: rgba(19, 29, 44, 0.9);
      --text: #eaf2ff;
      --muted: #9bb0c6;
      --accent: #ffd166;
      --accent-2: #7cb7ff;
      --border: #1f2c3c;
      --shadow: 0 20px 60px rgba(0,0,0,0.55);
      --mono: "IBM Plex Mono", Menlo, Monaco, Consolas, monospace;
      --sans: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      --gradient: radial-gradient(circle at 18% 20%, rgba(255, 209, 102, 0.14), transparent 36%), radial-gradient(circle at 82% 18%, rgba(124, 183, 255, 0.18), transparent 34%), radial-gradient(circle at 40% 82%, rgba(108, 240, 194, 0.16), transparent 40%), #060910;
    }
    .nemesis-defeats {
      font-size: 20px;
      font-weight: 800;
      color: var(--accent);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: var(--sans);
      background-color: #060910;
      background-image: var(--gradient);
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      color: var(--text);
      min-height: 100vh;
      overflow-x: hidden;
    }
    a { color: var(--accent-2); }
    .page-shell {
      position: relative;
      min-height: 100vh;
      overflow: hidden;
    }
    .scanlines::after {
      content: "";
      position: fixed;
      inset: 0;
      pointer-events: none;
      background: repeating-linear-gradient(to bottom, rgba(255,255,255,0.02), rgba(255,255,255,0.02) 1px, transparent 1px, transparent 3px);
      mix-blend-mode: screen;
      opacity: 0.5;
      z-index: 0;
    }
    .film-grain {
      position: fixed;
      inset: 0;
      pointer-events: none;
      background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><filter id="n"><feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="3" stitchTiles="stitch"/></filter><rect width="120" height="120" filter="url(%23n)" opacity="0.18"/></svg>');
      mix-blend-mode: soft-light;
      opacity: 0.25;
      z-index: 1;
    }
    .page {
      position: relative;
      z-index: 2;
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr;
      gap: 0;
      padding: 0 18px 32px;
      align-content: center;
    }
    .content {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .hud {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 6px 10px;
      color: var(--muted);
      font-weight: 700;
      letter-spacing: 0.02em;
    }
    .hud.floating {
      position: absolute;
      inset: 16px 16px auto 16px;
      z-index: 3;
      background: rgba(12,18,28,0.6);
      border: 1px solid var(--border);
      border-radius: 12px;
      backdrop-filter: blur(8px);
      box-shadow: var(--shadow);
    }
    .hud-left {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 13px;
      text-transform: uppercase;
    }
    .hud-dots {
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .hud-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.04);
      cursor: pointer;
      transition: transform 140ms ease, background 140ms ease, border-color 140ms ease;
    }
    .hud-dot.active {
      background: var(--accent);
      border-color: var(--accent);
      transform: scale(1.1);
    }
    .hud-right {
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }
    .hud-icon {
      width: 34px;
      height: 34px;
      border: 1px solid var(--border);
      border-radius: 10px;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,0.02);
      cursor: pointer;
    }
    .slides-stage {
      position: relative;
      overflow: hidden;
      border-radius: 18px;
      border: 1px solid var(--border);
      background: linear-gradient(160deg, rgba(16,23,32,0.92), rgba(12,18,28,0.96));
      box-shadow: var(--shadow);
      height: min(85vh, 980px);
      padding: 88px 18px 18px;
      isolation: isolate;
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      align-items: stretch;
    }
    .slides-stage::before {
      content: "";
      position: absolute;
      inset: 8px;
      border-radius: 14px;
      background: radial-gradient(circle at 20% 30%, rgba(255, 209, 102, 0.06), transparent 40%), radial-gradient(circle at 80% 20%, rgba(124, 183, 255, 0.08), transparent 36%);
      z-index: 0;
      pointer-events: none;
    }
    .slides-stage .slide {
      position: absolute;
      inset: 8px 10px 10px 10px;
      opacity: 0;
      transform: none;
      pointer-events: none;
      transition: none;
      z-index: 0;
      background: none;
      border-radius: 14px;
      padding: 22px;
      min-height: 100%;
      overflow: hidden;
      box-shadow: none;
      filter: none;
      backdrop-filter: blur(4px);
      max-width: none;
      outline: none;
      display: none;
      flex-direction: column;
      overflow: hidden;
    }
    .slides-stage .slide.active {
      position: relative;
      opacity: 1;
      transform: none;
      pointer-events: auto;
      z-index: 2;
      display: flex;
    }
    .slides-stage .slide.leaving {
      opacity: 0;
      transform: none;
      pointer-events: none;
      filter: none;
      z-index: 1;
      transition: none;
    }
    #slides {
      position: relative;
      width: 100%;
      height: 100%;
      overflow-y: auto;
      overflow-x: hidden;
      padding-right: 14px;
      scrollbar-gutter: stable;
      scrollbar-width: thin;
      scrollbar-color: var(--accent) rgba(255,255,255,0.06);
    }
    #slides::-webkit-scrollbar {
      width: 12px;
    }
    #slides::-webkit-scrollbar-track {
      background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
      border-radius: 999px;
    }
    #slides::-webkit-scrollbar-thumb {
      background: linear-gradient(180deg, rgba(255,209,102,0.55), rgba(124,183,255,0.7));
      border-radius: 999px;
      border: 2px solid rgba(12,18,28,0.9);
      box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    }
    #slides::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(180deg, rgba(255,209,102,0.7), rgba(124,183,255,0.85));
    }
    .slide::before {
      content: "";
      position: absolute;
      inset: -20% auto auto -10%;
      width: 200px;
      height: 200px;
      background: radial-gradient(circle, rgba(255, 209, 102, 0.12), transparent 60%);
      filter: blur(20px);
      opacity: 0.7;
      pointer-events: none;
    }
    .slide::after {
      content: "";
      position: absolute;
      inset: auto -10% -20% auto;
      width: 220px;
      height: 220px;
      background: radial-gradient(circle, rgba(124, 183, 255, 0.12), transparent 60%);
      filter: blur(20px);
      opacity: 0.6;
      pointer-events: none;
    }
    .slides-stage .slide:focus,
    .slides-stage .slide:focus-visible {
      outline: none;
    }
    .slide-body {
      flex: 1;
      overflow: visible;
      padding-right: 0;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .slide-title {
      display: flex;
      align-items: center;
      gap: 10px;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      font-weight: 800;
    }
    .pill {
      font-family: var(--mono);
      font-size: 11px;
      padding: 4px 8px;
      border-radius: 999px;
      border: 1px solid var(--border);
      color: #ffd166;
      background: rgba(255, 255, 255, 0.02);
    }
    .slide h2 {
      margin: 0;
      letter-spacing: -0.01em;
    }
    .copy-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,255,255,0.04);
      color: var(--text);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px 12px;
      cursor: pointer;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .slide-share {
      position: absolute;
      top: 12px;
      right: 12px;
      z-index: 4;
    }
    .stat-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 10px;
    }
    .stat {
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px;
    }
    .stat label {
      display: block;
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 4px;
      letter-spacing: 0.04em;
    }
    .stat strong {
      font-size: 20px;
      color: var(--text);
      display: block;
    }
    .stat .mini {
      display: flex;
      align-items: center;
      gap: 6px;
      color: var(--muted);
      font-size: 13px;
    }
    .list, .timeline {
      display: grid;
      gap: 12px;
      margin: 12px 0 0;
    }
    .card {
      background: rgba(255,255,255,0.03);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 14px;
    }
    .muted { color: var(--muted); }
    .two-col {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 12px;
    }
    .community-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 12px;
      margin-top: 12px;
    }
    .tag {
      display: inline-block;
      margin-right: 6px;
      margin-bottom: 4px;
      padding: 4px 8px;
      border-radius: 999px;
      border: 1px solid var(--border);
      color: var(--muted);
      font-size: 12px;
    }
    .kpi {
      font-size: clamp(30px, 6vw, 64px);
      font-weight: 800;
      color: var(--accent);
      letter-spacing: 0.08em;
    }
    .cta-primary {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 14px 18px;
      border-radius: 12px;
      border: 1px solid var(--border);
      color: #0d121b;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      box-shadow: 0 15px 40px rgba(0,0,0,0.35);
      cursor: pointer;
      transition: transform 140ms ease, box-shadow 140ms ease;
    }
    .cta-primary:hover { transform: translateY(-2px) scale(1.01); box-shadow: 0 20px 46px rgba(0,0,0,0.4); }
    .cta-primary:active { transform: translateY(0) scale(0.99); }
    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 14px;
    }
    .accent-bg-gold { --accent: #ffd166; }
    .accent-bg-cyan { --accent: #6cf0c2; }
    .accent-bg-pink { --accent: #ff7edb; }
    .accent-bg-violet { --accent: #c08bff; }
    .spark {
      position: absolute;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--accent);
      opacity: 0.9;
      pointer-events: none;
      animation: sparkFly 800ms ease-out forwards;
    }
    .sub {
      font-size: 14px;
      color: #e7edf8;
    }
    .ribbon {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 14px;
      border-radius: 999px;
      border: 1px solid rgba(255, 209, 102, 0.4);
      background: linear-gradient(90deg, rgba(255, 209, 102, 0.12), rgba(124, 183, 255, 0.1));
      font-weight: 700;
      color: #ffd166;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      font-size: 12px;
      white-space: nowrap;
    }
    .spotlight {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 12px;
    }
    .spotlight .card {
      border: 1px solid rgba(255, 209, 102, 0.24);
    }
    .slide-hero {
      display: grid;
      place-items: center;
      text-align: center;
      min-height: 360px;
      gap: 10px;
    }
    .slide-hero .mega {
      font-size: clamp(40px, 10vw, 90px);
      letter-spacing: -0.04em;
      font-weight: 900;
    }
    .slide-hero .lead {
      font-size: clamp(16px, 3vw, 24px);
      color: var(--muted);
      max-width: 760px;
      margin: 0 auto;
      line-height: 1.4;
    }
    .title-logo {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.04);
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }
    .title-logo img {
      max-width: min(320px, 60vw);
      height: auto;
      display: block;
    }
    .title-present {
      font-family: var(--mono);
      letter-spacing: 0.3em;
      text-transform: uppercase;
      color: var(--muted);
      margin-top: 8px;
    }
    .stat-hero {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 12px;
      margin-top: 12px;
    }
    .stat-hero .card {
      text-align: center;
      padding: 16px;
    }
    .stat-hero .kpi {
      font-size: clamp(34px, 6vw, 72px);
    }
    .honor-card {
      border: 1px solid rgba(255, 209, 102, 0.35);
      border-radius: 14px;
      padding: 18px;
      background: linear-gradient(160deg, rgba(255, 209, 102, 0.08), rgba(12,18,28,0.85));
      box-shadow: var(--shadow);
      display: grid;
      gap: 14px;
    }
    .honor-profile {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
    }
    .honor-avatar {
      width: 80px;
      height: 80px;
      border-radius: 14px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.04);
      display: grid;
      place-items: center;
      overflow: hidden;
    }
    .honor-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .honor-meta {
      display: grid;
      gap: 2px;
    }
    .honor-name-row {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .profile-open-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 34px;
      height: 34px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.05);
      color: var(--text);
      transition: transform 140ms ease, background 140ms ease, border-color 140ms ease;
    }
    .profile-open-link:hover {
      transform: translateY(-2px);
      background: rgba(255,255,255,0.08);
      border-color: rgba(255,255,255,0.18);
      color: var(--accent-2);
    }
    .profile-open-link:focus-visible {
      outline: 2px solid var(--accent-2);
      outline-offset: 2px;
    }
    .honor-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 10px;
    }
    .stat-pill {
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px 12px;
      background: rgba(255,255,255,0.02);
    }
    .stat-pill .label { color: var(--muted); font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; }
    .stat-pill .value { font-size: 26px; font-weight: 800; color: var(--accent); }
    .badge-row {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      align-items: center;
      padding-top: 4px;
    }
    .badge-row img {
      width: 46px;
      height: 46px;
      border-radius: 10px;
      border: 1px solid var(--border);
      object-fit: cover;
      background: rgba(255,255,255,0.05);
    }
    .badge-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 46px;
      height: 46px;
      padding: 0 12px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.05);
      font-weight: 700;
      letter-spacing: 0.01em;
      text-align: center;
    }
    .game-icon-row {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
      margin-top: 8px;
    }
    .game-icon {
      width: 54px;
      height: 54px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.05);
      display: grid;
      place-items: center;
      overflow: hidden;
    }
    .game-icon img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .game-badge {
      display: inline-flex !important;
      align-items: center;
      gap: 10px;
      padding: 8px 12px;
      font-size: 15px;
      letter-spacing: 0.01em;
    }
    .game-badge-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.05);
      object-fit: cover;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
    }
    .game-badge-name {
      font-weight: 800;
    }
    .king-games-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 12px;
      margin-top: 14px;
    }
    .king-game-card {
      position: relative;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      border-radius: 14px;
      border: 1px solid rgba(255, 209, 102, 0.6);
      box-shadow: 0 10px 26px rgba(0,0,0,0.28), 0 0 0 1px rgba(255,255,255,0.04);
      background-blend-mode: screen;
      color: #3b2a00 !important;
    }
    .king-game-card .king-game-icon {
      width: 54px;
      height: 54px;
      border-radius: 12px;
      border: 2px solid rgba(255, 255, 255, 0.35);
      background: rgba(0,0,0,0.14);
      display: grid;
      place-items: center;
      overflow: hidden;
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12), 0 8px 18px rgba(0,0,0,0.32);
    }
    .king-game-card .game-icon-fallback {
      color: #3b2a00;
      font-weight: 800;
    }
    .king-game-card .king-game-icon img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .king-game-name {
      font-weight: 800;
      letter-spacing: 0.01em;
      color: #3b2a00 !important;
      text-shadow: 0 1px 0 rgba(255,255,255,0.3);
    }
    .raffle-rewards { margin-top: 14px; }
    .raffle-reward-strip {
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 12px;
      background: rgba(255,255,255,0.02);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.02), 0 10px 28px rgba(0,0,0,0.25);
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .raffle-reward-strip-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    .raffle-reward-title {
      font-weight: 800;
      letter-spacing: 0.01em;
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .raffle-reward-meta {
      color: var(--muted);
      font-size: 13px;
    }
    .raffle-item-grid {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      align-items: center;
      padding-top: 4px;
    }
    .raffle-item {
      width: 46px;
      height: 46px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.05);
      display: grid;
      place-items: center;
      overflow: hidden;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.03);
    }
    .raffle-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .raffle-item-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 46px;
      height: 46px;
      padding: 0 12px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.05);
      font-weight: 700;
      letter-spacing: 0.01em;
      text-align: center;
    }
    .slide-icon {
      transition: transform 140ms ease, box-shadow 140ms ease, border-color 140ms ease, background 140ms ease;
      cursor: pointer;
    }
    .slide-icon:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 18px rgba(0,0,0,0.28);
      border-color: rgba(255,255,255,0.18);
    }
    .slide-icon-group {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      align-items: center;
    }
    .profile-chip {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.03);
      font-weight: 700;
    }
    .profile-chip .avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      overflow: hidden;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.04);
      display: grid;
      place-items: center;
    }
    .profile-chip .avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 12px;
      margin-top: 12px;
    }
    .momentum-grid {
      grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
    }
    .list-card {
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 14px;
      background: rgba(255,255,255,0.03);
      box-shadow: var(--shadow);
    }
    .list-card .title-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 6px;
    }
    .list-card .game-badge-name {
      font-size: 18px;
    }
    .momentum-card {
      display: grid;
      gap: 8px;
    }
    .momentum-rows {
      display: grid;
      gap: 10px;
    }
    .momentum-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }
    .momentum-meta {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .nemesis-list {
      display: grid;
      grid-template-columns: 1fr;
      gap: 12px;
    }
    .nemesis-card {
      display: grid;
      gap: 10px;
    }
    .nemesis-games {
      display: grid;
      gap: 8px;
    }
    .nemesis-game-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
    }
    .pill-muted {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid var(--border);
      color: var(--muted);
      font-size: 12px;
      letter-spacing: 0.02em;
    }
    .bar-chart {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 12px;
      margin-top: 12px;
    }
    .bar-chart .bar {
      display: grid;
      gap: 6px;
    }
    .bar-track {
      height: 12px;
      border-radius: 999px;
      background: rgba(255,255,255,0.08);
      overflow: hidden;
    }
    .bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #ffd166, #7cb7ff);
      border-radius: 999px;
    }
    .bar-track.segmented {
      display: flex;
      background: rgba(255,255,255,0.04);
      gap: 4px;
      padding: 2px;
    }
    .bar-track.segmented .bar-fill {
      flex: 0 0 auto;
      border-radius: 8px;
    }
    .bar-fill.win {
      background: linear-gradient(90deg, #6cf0c2, #7cb7ff);
    }
    .bar-fill.loss {
      background: linear-gradient(90deg, #ff9b7d, #ffd166);
    }
    .muted-note {
      color: var(--muted);
      font-size: 14px;
      margin-top: 8px;
    }
    .game-icon-fallback {
      color: var(--muted);
      font-weight: 700;
    }
    .sequence-item { opacity: 1; }
    .slide-progress {
      display: flex;
      justify-content: flex-end;
      margin-top: 18px;
      gap: 10px;
      flex-wrap: wrap;
    }
    @keyframes sparkFly {
      0% { transform: translate(0, 0) scale(1); opacity: 1; }
      100% { transform: translate(var(--dx), var(--dy)) scale(0.35); opacity: 0; }
    }
    @media (max-width: 960px) {
      .page { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
      .slides-stage { padding: 78px 14px 18px; height: 76vh; }
      .slides-stage .slide { inset: 12px; padding: 18px; }
      .hud.floating { inset: 12px 12px auto 12px; flex-wrap: wrap; gap: 8px; }
      .copy-link { width: auto; }
    }
  </style>
  <div class="page-shell scanlines">
    <div class="film-grain"></div>
    <div class="page container-fluid py-4">
      <main class="content row justify-content-center g-3">
        <div class="col-12">
          <div class="slides-stage w-100">
            <div class="hud floating d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="hud-left d-inline-flex align-items-center gap-2 flex-wrap">
                <span class="pill">Atlas</span>
                <span class="pill"><?php echo htmlspecialchars((string)$atlasYear, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            <div class="hud-dots d-inline-flex gap-2" id="dot-nav" aria-label="Slide navigation"></div>
            </div>
            <div id="slides" class="w-100"></div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    const atlasData = <?php echo json_encode($atlasPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const requestedInitialTab = <?php echo json_encode($initialTab); ?>;
    const year = atlasData?.year ?? <?php echo json_encode($atlasYear); ?>;
    const previousYear = (year ?? new Date().getFullYear()) - 1;
    const account = atlasData.account;
    const honors = atlasData?.honors || {};

    const escapeHtml = (value) => {
      if (value === null || value === undefined) return "";
      return value
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#39;");
    };

    const slugifySegment = (value, fallback) => {
      const cleaned = (value ?? "")
        .toString()
        .trim()
        .replace(/[^a-zA-Z0-9\s-_]/g, "")
        .replace(/\s+/g, "-")
        .replace(/-+/g, "-")
        .replace(/^-+|-+$/g, "");
      return encodeURIComponent(cleaned || fallback);
    };

    const buildShareUrl = (slideId) => {
      const slideSegment = slugifySegment(slideId, "opening");
      const audienceSegment = slugifySegment(account?.profile?.username, "kingdom");
      const yearSegment = encodeURIComponent(year ?? new Date().getFullYear());
      return `${window.location.origin}/atlas-archive/${yearSegment}/${audienceSegment}/${slideSegment}/`;
    };

    const fmt = (value, fallback = "—") => {
      if (value === null || value === undefined || Number.isNaN(value)) return fallback;
      const num = Number(value);
      return Number.isFinite(num) ? num.toLocaleString() : `${value}`;
    };
    const numericOrNull = (value) => {
      const num = Number(value);
      return Number.isFinite(num) ? num : null;
    };
    const renderKpi = (value, fallback = "—") => {
      const num = numericOrNull(value);
      const attr = num !== null ? `data-target="${num}"` : "";
      const cls = num !== null ? "kpi stat-number" : "kpi";
      const display = num !== null ? fmt(num) : fallback;
      return `<div class="${cls}" ${attr}>${display}</div>`;
    };
    const pct = (numerator, denominator) => {
      if (!denominator || denominator === 0 || numerator === null || numerator === undefined) return "—";
      return `${Math.round((numerator / denominator) * 100)}%`;
    };
    const safeAvatar = (url) => {
      if (!url) return null;
      if (url.startsWith("http")) return url;
      return url.startsWith("/") ? url : `/${url}`;
    };
    const renderGameIconRow = (games) => {
      if (!games || games.length === 0) return "";
      const items = games.map((game) => {
        const icon = safeAvatar(game.icon);
        const name = escapeHtml(game.name ?? "Unknown game");
        const fallback = escapeHtml((game.name ?? "?").slice(0, 1) || "?");
        const visual = icon
          ? `<img src="${icon}" alt="${name} icon">`
          : `<span class="game-icon-fallback">${fallback}</span>`;
        return `<div class="game-icon slide-icon" title="${name}" data-bs-toggle="tooltip" data-bs-placement="top">${visual}</div>`;
      }).join("");
      return `<div class="game-icon-row slide-icon-group" aria-label="Games spanned">${items}</div>`;
    };
    const renderGameIcons = (games) => renderGameIconRow(games);
    const renderKingGameCard = (game) => {
      const icon = safeAvatar(game?.icon);
      const name = escapeHtml(game?.name ?? "Unknown game");
      const fallback = escapeHtml((game?.name ?? "?").slice(0, 1) || "?");
      const visual = icon
        ? `<img src="${icon}" alt="${name} icon">`
        : `<span class="game-icon-fallback">${fallback}</span>`;
      return `
        <div class="king-game-card bg-ranked-1 slide-icon" title="${name}" data-bs-toggle="tooltip" data-bs-placement="top">
          <div class="king-game-icon">${visual}</div>
          <div class="king-game-name">${name}</div>
        </div>
      `;
    };
    const fmtPct = (value) => {
      if (value === null || value === undefined || Number.isNaN(value)) return "—";
      return `${Math.round(value * 100)}%`;
    };
    const renderProfileChip = (profile, fallback = "Unknown adventurer") => {
      const label = profile?.username ? escapeHtml(profile.username) : escapeHtml(fallback);
      const avatar = profile?.avatar ? safeAvatar(profile.avatar) : null;
      return `
        <div class="profile-chip">
          <div class="avatar">
            ${avatar ? `<img src="${avatar}" alt="${label} avatar">` : `<span class="muted">${label.slice(0, 1).toUpperCase()}</span>`}
          </div>
          <span>${label}</span>
        </div>
      `;
    };
    const renderGameBadge = (game, suffix = "") => {
      if (!game) return `<span class="pill game-badge"><span class="game-badge-name">${escapeHtml(suffix || "Game")}</span></span>`;
      const icon = safeAvatar(game.icon);
      const name = escapeHtml(game.name || game.shortName || `Game ${game.id || ""}`);
      const visual = icon
        ? `<img src="${icon}" class="game-badge-icon" alt="${name} icon">`
        : `<span class="game-icon-fallback game-badge-icon">${escapeHtml((name || "?").slice(0,1))}</span>`;
      return `<span class="pill game-badge">${visual}<span class="game-badge-name">${name}${suffix ? ` ${escapeHtml(suffix)}` : ""}</span></span>`;
    };
    const formatMonthLabel = (month) => {
      if (!month) return "";
      const date = new Date(month);
      if (Number.isNaN(date.getTime())) return month;
      return date.toLocaleDateString(undefined, { month: "short" });
    };
    const formatDateLabel = (dateStr) => {
      if (!dateStr) return "";
      const date = new Date(dateStr);
      if (Number.isNaN(date.getTime())) return escapeHtml(dateStr);
      return date.toLocaleDateString(undefined, { month: "short", day: "numeric" });
    };
    const renderKingGames = (games, yearLabel) => {
      if (!games || games.length === 0) {
        const label = escapeHtml(yearLabel ?? "this year");
        return `<div class="muted">No ranked ladders with gold card holders recorded for ${label}.</div>`;
      }
      return `<div class="king-games-grid">${games.map(renderKingGameCard).join("")}</div>`;
    };
    const renderHonorProfile = (entry, subtitle) => {
      const profile = entry?.profile;
      if (!profile) {
        return `
          <div class="honor-profile">
            <div class="honor-avatar"></div>
            <div class="honor-meta">
              <div class="mega" style="font-size:32px;">No data</div>
              <div class="muted">Not enough records in ${year}</div>
            </div>
          </div>
        `;
      }
      const avatar = safeAvatar(profile.avatar);
      const profileUrl = `/profile.php?u=${encodeURIComponent(profile.username)}`;
      return `
        <div class="honor-profile">
          <div class="honor-avatar">
            ${avatar ? `<img src="${avatar}" alt="${profile.username} avatar">` : `<span class="muted">No avatar</span>`}
          </div>
          <div class="honor-meta">
            <div class="honor-name-row">
              <div class="mega" style="font-size:32px;">${profile.username}</div>
              <a class="profile-open-link" href="${profileUrl}" target="_blank" rel="noopener" aria-label="Open ${profile.username}'s profile" title="Open profile in new tab">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
              </a>
            </div>
            <div class="muted">${subtitle || ""}</div>
          </div>
        </div>
      `;
    };
    const renderStatPill = (label, value, detail, extra, options = {}) => {
      const { hideValue = false, hideDetail = false } = options ?? {};
      const valueMarkup = hideValue ? "" : `<div class="value">${fmt(value)}</div>`;
      const detailMarkup = !hideDetail && detail ? `<div class="muted">${detail}</div>` : "";
      return `
        <div class="stat-pill">
          <div class="label">${label}</div>
          ${valueMarkup}
          ${detailMarkup}
          ${extra ?? ""}
        </div>
      `;
    };
    const renderBadgeRow = (badges) => {
      const normalized = (badges ?? [])
        .map((badge) => {
          if (typeof badge === "string") {
            return { icon: badge, name: null };
          }
          if (badge && typeof badge === "object") {
            return {
              icon: badge.icon ?? badge.url ?? badge.src ?? null,
              name: badge.name ?? badge.title ?? badge.label ?? null,
            };
          }
          return null;
        })
        .filter((badge) => badge && (badge.icon || badge.name));

      if (!normalized || normalized.length === 0) {
        return `<div class="muted">No badge art recorded for this year.</div>`;
      }

      const items = normalized.map((badge, idx) => {
        const icon = safeAvatar(badge.icon);
        const label = escapeHtml(badge.name ?? `Badge ${idx + 1}`);
        const tooltipAttrs = `class="slide-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="${label}"`;
        if (icon) {
          return `<img src="${icon}" alt="${label}" ${tooltipAttrs}>`;
        }
        return `<span ${tooltipAttrs} aria-label="${label}"><span class="badge-pill">${label}</span></span>`;
      }).join("");

      return `<div class="badge-row">${items}</div>`;
    };

    const renderRewardItems = (rewards) => {
      const normalized = (rewards ?? [])
        .map((reward) => {
          if (typeof reward === "string") {
            return { icon: reward, name: null, category: null };
          }
          if (reward && typeof reward === "object") {
            return {
              icon: reward.icon ?? reward.url ?? reward.src ?? null,
              name: reward.name ?? reward.title ?? reward.label ?? null,
              category: reward.category ?? null,
            };
          }
          return null;
        })
        .filter((reward) => reward && (reward.icon || reward.name));

      if (!normalized || normalized.length === 0) {
        return `<div class="muted">No reward items recorded.</div>`;
      }

      const items = normalized.map((reward, idx) => {
        const icon = safeAvatar(reward.icon);
        const name = escapeHtml(reward.name ?? `Reward ${idx + 1}`);
        const category = reward.category ? escapeHtml(reward.category) : null;
        const tooltipTitle = category ? `${name}` : name;
        const tooltipAttrs = `class="raffle-item slide-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="${tooltipTitle}" aria-label="${name}"`;
        if (icon) {
          return `<img src="${icon}" alt="${name}" ${tooltipAttrs}>`;
        }
        return `<span ${tooltipAttrs}><span class="raffle-item-pill">${name}</span></span>`;
      }).join("");

      return `<div class="raffle-item-grid" aria-label="Raffle rewards">${items}</div>`;
    };

    const renderRaffleRewards = (raffleRewards) => {
      const aggregatedRewards = (raffleRewards ?? [])
        .flatMap((raffle) => {
          if (!raffle || !Array.isArray(raffle.rewards)) {
            return [];
          }
          return raffle.rewards;
        });

      return `
        <div class="stat-pill">
          <div class="label">
            Prizes
            <div class="value">
              ${renderRewardItems(aggregatedRewards)}
            </div>
          </div>
        </div>
      `;
    };
    const slides = [
      {
        id: "title",
        title: "Atlas Archive",
        accent: "accent-bg-cyan",
        hideHeaderTitle: true,
        showNextCta: false,
        nextCtaLabel: "Begin",
        render: () => `
          <div class="slide-hero">
            <div class="title-logo shadow-sm">
              <img src="https://kickback-kingdom.com/assets/images/logo-kk.png" alt="Kickback Kingdom" class="img-fluid">
            </div>
            <div class="title-present">Presents</div>
            <div class="mega">Atlas Archive ${year}</div>
            <div class="lead">A reflection of what we have achieved together in the past year.</div>
            <div class="actions" style="justify-content:center;">
              <button class="cta-primary" data-action="next">Start</button>
              <button class="cta-primary" data-action="fullscreen">Fullscreen</button>
            </div>
          </div>
        `,
      },
      {
        id: "community-thanks",
        title: "Community Spotlight",
        accent: "accent-bg-gold",
        hideHeaderTitle: true,
        showNextCta: true,
        nextCtaLabel: "Continue",
        render: () => {
          const w = atlasData.world || {};
          const prog = atlasData.yearProgress || {};
          const metricCards = [
            {
              key: "accounts",
              label: "Guildsmen",
              sub: `New guildsmen in ${previousYear}`,
              fallbackEnd: w.guildsmen,
              primary: "direct",
              mainValue: w.accountsCreatedPreviousYear,
              secondaryValue: w.guildsmen,
              secondaryLabel: "Total",
            },
            {
              key: "questsHosted",
              label: "Quests Hosted",
              sub: `Quests hosted during ${previousYear}`,
              fallbackEnd: w.questsHostedPreviousYear ?? w.questsHostedBeforeYear,
              primary: "direct",
              mainValue: w.questsHostedPreviousYear,
              secondaryValue: w.questsHostedBeforeYear,
              secondaryLabel: `Total`,
            },
            {
              key: "matches",
              label: "Ranked Matches",
              sub: `Matches held in ${previousYear}`,
              fallbackEnd: w.rankedMatchesPreviousYear ?? w.rankedMatchesBeforeYear,
              primary: "direct",
              mainValue: w.rankedMatchesPreviousYear,
              secondaryValue: w.rankedMatchesBeforeYear,
              secondaryLabel: `Total`,
            },
          ];

          const renderDeltaLine = (entry, fallbackEnd) => {
            if (!entry) {
              return `<p class="sub">Started — · Change —</p>`;
            }

            const startNum = numericOrNull(entry.start);
            const endNum = numericOrNull(entry.end ?? fallbackEnd);
            const startLabel = fmt(startNum, "—");
            let deltaLabel = "—";

            if (startNum !== null && endNum !== null) {
              const delta = endNum - startNum;
              deltaLabel = delta > 0 ? `+${fmt(delta)}` : fmt(delta);
            }

            return `<p class="sub">Started ${startLabel} · Change ${deltaLabel}</p>`;
          };

          const renderPrimaryKpi = (metric, entry, fallbackEnd, endValue) => {
            if (metric.primary === "yearCount") {
              const startNum = numericOrNull(entry?.start);
              const endNum = numericOrNull(entry?.end ?? fallbackEnd);
              const yearCount = startNum !== null && endNum !== null ? endNum - startNum : null;
              return renderKpi(yearCount, fmt(endNum, "—"));
            }

            if (metric.primary === "direct") {
              const directValue = metric.mainValue ?? endValue ?? fallbackEnd;
              return renderKpi(directValue);
            }

            return renderKpi(endValue);
          };

          const renderSecondaryLine = (metric, entry, fallbackEnd, endValue) => {
            if (metric.secondaryValue !== undefined) {
              return `<p class="sub">${metric.secondaryLabel ?? "Total"}: ${fmt(metric.secondaryValue, "—")}</p>`;
            }

            if (metric.primary === "yearCount") {
              const startNum = numericOrNull(entry?.start);
              const startLabel = fmt(startNum, "—");
              return `<p class="sub">Total on Jan 1: ${startLabel}</p>`;
            }

            return renderDeltaLine(entry, fallbackEnd);
          };

          return `
            <div class="slide-hero">
              <div class="mega">Cheers to the Kingdom!</div>
              <div class="lead">${previousYear} brought new friendships, lasting memories, and unforgettable moments of fierce competition.</div>
            </div>
            <div class="stat-hero">
              ${metricCards.map((metric) => {
                const entry = metric.entryKey ? prog[metric.entryKey] : prog[metric.key];
                const endValue = metric.mainValue ?? entry?.end ?? metric.fallbackEnd;
                return `
                  <div class="card">
                    <div class="pill">${metric.label}</div>
                    ${renderPrimaryKpi(metric, entry, metric.fallbackEnd, endValue)}
                    ${renderSecondaryLine(metric, entry, metric.fallbackEnd, endValue)}
                    <p class="sub">${metric.sub}</p>
                  </div>
                `;
              }).join("")}
            </div>
          `;
        },
      },
      {
        id: "honors-title",
        title: "Kingdom Highlights",
        accent: "accent-bg-violet",
        hideHeaderTitle: true,
        showNextCta: false,
        nextCtaLabel: "Begin Highlights",
        render: () => `
          <div class="slide-hero">
            <div class="mega">Honorable Stats & Achievements</div>
            <div class="lead">Remembering our most epic moments and legendary heroes!</div>
            <div class="actions" style="justify-content:center;">
              <button class="cta-primary" data-action="next">Begin Highlights</button>
            </div>
          </div>
        `,
      },
      {
        id: "honor-tournaments",
        title: "Most Tournaments Won",
        accent: "accent-bg-gold",
        nextCtaLabel: "Next Highlight",
        render: () => {
          const entry = honors?.tournaments;
          return `
            <div class="slide-hero">
              <div class="mega">Most Tournaments Won</div>
              <div class="lead">This goes to our adventurer with the most championships in ${previousYear}! Nothing can stop them!</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Led the ranked quests in ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Tournaments Won", entry.tournamentsWon, null)}
                  ${renderStatPill("Games Spanned", entry.gamesCount, null, renderGameIconRow(entry.games), { hideValue: true })}
                </div>
              ` : `<div class="muted">No tournament victories recorded for ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "honor-prestige",
        title: "Most Prestigious",
        accent: "accent-bg-pink",
        nextCtaLabel: "Next Highlight",
        render: () => {
          const entry = honors?.prestige;
          return `
            <div class="slide-hero">
              <div class="mega">Most Prestigious</div>
              <div class="lead">This guildsman is beloved by the community and was most recognized in ${previousYear} for their valiant efforts.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Commended the most in ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Total Commendations", entry.netPrestige)}
                  ${renderStatPill("Unique Commenders", entry.uniqueGivers)}
                </div>
              ` : `<div class="muted">No prestige activity recorded for ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "honor-quester",
        title: "Most Adventurous",
        accent: "accent-bg-cyan",
        nextCtaLabel: "Next Highlight",
        render: () => {
          const entry = honors?.quester;
          return `
            <div class="slide-hero">
              <div class="mega">Most Adventurous</div>
              <div class="lead">This hero answered the call and completed more quests than any other in ${previousYear}.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Completed the most quests in ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Quests Completed", entry.questsParticipated)}
                </div>
              ` : `<div class="muted">No quest participation detected for ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "honor-host",
        title: "Greatest Quest Giver",
        accent: "accent-bg-gold",
        nextCtaLabel: "Next Highlight",
        render: () => {
          const entry = honors?.host;
          return `
            <div class="slide-hero">
              <div class="mega">Greatest Quest Giver</div>
              <div class="lead">In ${previousYear}, they united the Adventurers' Guild and safeguarded the realm by crafting the quests that heroes rose to complete.
</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Greatest Quest Giver in ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Quest Giver Score", entry.hostingScore?.toFixed ? Number(entry.hostingScore).toFixed(2) : entry.hostingScore)}
                  ${renderStatPill("Quests Hosted", entry.questsHosted)}
                </div>
              ` : `<div class="muted">No hosted quests with feedback in ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "honor-renown",
        title: "Most Renown",
        accent: "accent-bg-violet",
        nextCtaLabel: "Next Highlight",
        render: () => {
          const entry = honors?.renown;
          return `
            <div class="slide-hero">
              <div class="mega">Most Renown</div>
              <div class="lead">In ${previousYear}, no name carried greater renown, as they earned more badges than any other in the realm.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Badge haul in ${previousYear}`)}
              ${entry ? `
                ${renderBadgeRow(entry.badgeIcons)}
              ` : `<div class="muted">No badge awards recorded for ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "honor-treasure",
        title: "Most Treasure Collected",
        accent: "accent-bg-gold",
        nextCtaLabel: "Next Highlight",
        render: () => {
          const entry = honors?.treasureHunter;
          return `
            <div class="slide-hero">
              <div class="mega">Most Treasure Collected</div>
              <div class="lead">In ${previousYear}, no secret stayed buried — this adventurer uncovered more hidden treasure than any other across the realm.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Greatest treasure hunter of ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Hidden Treasures Unearthed", entry.treasuresCollected)}
                  ${renderStatPill("Expeditions Undertaken", entry.eventsCount)}
                </div>
              ` : `<div class="muted">No treasure hunts recorded for ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "honor-raffle",
        title: "Luckiest Person",
        accent: "accent-bg-pink",
        nextCtaLabel: "Next Highlight",
        render: () => {
          const entry = honors?.raffleLuck;
          return `
            <div class="slide-hero">
              <div class="mega">Luckiest Person</div>
              <div class="lead">In ${previousYear}, fortune smiled brightest on this adventurer, granting them more raffle victories than any other.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Fortune's favorite in ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Raffles Won", entry.rafflesWon)}
                  ${renderStatPill("Tickets Wagered", entry.ticketsUsed)}
                  ${renderRaffleRewards(entry.raffleRewards)}
                </div>
              ` : `<div class="muted">No raffle winners recorded for ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "honor-king-games",
        title: "King of Games",
        accent: "accent-bg-cyan",
        nextCtaLabel: "Next Highlight",
        render: () => {
          const entry = honors?.kingOfGames;
          return `
            <div class="slide-hero">
              <div class="mega">King of Games</div>
              <div class="lead">In ${previousYear}, songs were sung of one who mastered every contest, crowned by fate as the King of Games — a legend that will echo through the ages.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `The King of Games — Holder of the most Gold Cards in ${previousYear}`)}
              ${entry ? renderKingGames(entry.games, previousYear) : `<div class="muted">No ranked ladders with gold card holders recorded for ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "account-title",
        title: "Your Atlas Archive",
        accent: "accent-bg-violet",
        hideHeaderTitle: true,
        showNextCta: false,
        nextCtaLabel: "View Your Story",
        render: () => {
          const username = account?.profile?.username;
          const heroTitle = username ? `${escapeHtml(username)}'s Atlas Archive` : "Your Atlas Archive";
          const leadCopy = username
            ? `From realm-wide legends to ${escapeHtml(username)}'s personal saga. Ready to see this guildsmen's tale?`
            : "From realm-wide legends to your personal saga. Ready to see your account's tale?";
          const ctaLabel = username ? `View ${escapeHtml(username)}'s Story` : "View Your Story";

          return `
            <div class="slide-hero">
              <div class="mega">${heroTitle}</div>
              <div class="lead">${leadCopy}</div>
              <div class="actions" style="justify-content:center;">
                <button class="cta-primary" data-action="next">${ctaLabel}</button>
              </div>
            </div>
          `;
        },
      },
      {
        id: "best-friends",
        title: "Best Friends",
        accent: "accent-bg-gold",
        nextCtaLabel: "Favorite Ranked Game",
        render: () => {
          if (!account) {
            return `
              <div class="slide-hero">
                <div class="mega">Log in to see your allies</div>
                <div class="lead">Connect to reveal your most quested-with teammates.</div>
                <div class="actions" style="justify-content:center;">
                  <a class="cta-primary" href="/login.php">Login</a>
                </div>
              </div>
            `;
          }
          const friends = account.bestFriends || [];
          return `
            <div class="slide-hero">
              <div class="mega">Best Friends</div>
              <div class="lead">Top 3 adventurers you quested or teamed with the most.</div>
            </div>
            ${friends.length === 0 ? `<div class="muted">No shared matches found for this year.</div>` : `
              <div class="card-grid">
                ${friends.map((friend, idx) => `
                  <div class="list-card sequence-item" data-delay="${idx * 120}">
                    <div class="title-row">
                      <span class="pill">#${idx + 1}</span>
                      <span class="pill-muted">Best Friend</span>
                    </div>
                    ${renderProfileChip(friend.profile, "Teammate")}
                    <div class="stat-hero" style="margin-top:10px;">
                      <div class="card">
                        <div class="pill">Shared Runs</div>
                        ${renderKpi(friend.totalShared ?? ((friend.quests ?? 0) + (friend.teamMatches ?? 0)))}
                        <p class="sub">Combined quests and team-ups</p>
                      </div>
                      <div class="card">
                        <div class="pill">Quests Together</div>
                        ${renderKpi(friend.quests)}
                        <p class="sub">Co-adventured</p>
                      </div>
                      <div class="card">
                        <div class="pill">Team Matches</div>
                        ${renderKpi(friend.teamMatches)}
                        <p class="sub">Same-side matches</p>
                      </div>
                    </div>
                  </div>
                `).join("")}
              </div>
            `}
          `;
        },
      },
      {
        id: "favorite-ranked-game",
        title: "Favorite Ranked Game",
        accent: "accent-bg-cyan",
        nextCtaLabel: "Matchmaker Streaks",
        render: () => {
          if (!account) {
            return `
              <div class="slide-hero">
                <div class="mega">Ranked legend pending</div>
                <div class="lead">Play ranked matches to reveal your favorite battleground.</div>
              </div>
            `;
          }
          const entry = account.favoriteRankedGame;
          if (!entry) {
            return `
              <div class="slide-hero">
                <div class="mega">No ranked matches yet</div>
                <div class="lead">Queue up in ranked to discover your signature game.</div>
              </div>
            `;
          }
          return `
            <div class="slide-hero">
              <div class="mega">Favorite Ranked Game</div>
              <div class="lead">The game you played the most ranked matches in.</div>
            </div>
            <div class="list-card">
              <div class="title-row">
                ${renderGameBadge(entry.game)}
                <span class="pill-muted">${fmtPct(entry.winRate)} win rate</span>
              </div>
              <div class="stat-hero">
                <div class="card">
                  <div class="pill">Ranked Matches</div>
                  ${renderKpi(entry.matches)}
                  <p class="sub">game_record × ranked set</p>
                </div>
                <div class="card">
                  <div class="pill">Wins</div>
                  ${renderKpi(entry.wins)}
                  <p class="sub">Hard-fought victories</p>
                </div>
              </div>
            </div>
          `;
        },
      },
      {
        id: "matchmaker-streaks",
        title: "Matchmaker Streaks",
        accent: "accent-bg-violet",
        nextCtaLabel: "Momentum Shifts",
        render: () => {
          if (!account) {
            return `
              <div class="slide-hero">
                <div class="mega">Your streaks await</div>
                <div class="lead">Log in and play matches to see your yearly volume.</div>
              </div>
            `;
          }
          const summary = account.matchmaker || {};
          const rankedGames = summary.games || [];
          return `
            <div class="slide-hero">
              <div class="mega">Matchmaker Streaks</div>
              <div class="lead">Total ranked matches, wins, win rate, plus every ladder you queued in.</div>
            </div>
            <div class="stat-hero">
              <div class="card">
                <div class="pill">Matches</div>
                ${renderKpi(summary.matches)}
                <p class="sub">Across ${previousYear}</p>
              </div>
              <div class="card">
                <div class="pill">Wins</div>
                ${renderKpi(summary.wins)}
                <p class="sub">Clutch closers</p>
              </div>
              <div class="card">
                <div class="pill">Win Rate</div>
                <div class="kpi">${fmtPct(summary.winRate)}</div>
                <p class="sub">Victory ratio</p>
              </div>
            </div>
            ${rankedGames.length === 0 ? `<div class="muted-note">No ranked matches recorded in this window.</div>` : `
              <div class="list-card">
                <div class="title-row">
                  <span class="pill">Ranked Games</span>
                  <span class="pill-muted">${rankedGames.length === 1 ? '1 game' : `${fmt(rankedGames.length)} games`}</span>
                </div>
                ${renderGameIcons(rankedGames)}
              </div>
            `}
          `;
        },
      },
      {
        id: "momentum-shifts",
        title: "Momentum Shifts",
        accent: "accent-bg-pink",
        nextCtaLabel: "Duo of Destiny",
        render: () => {
          if (!account) {
            return `
              <div class="slide-hero">
                <div class="mega">Play to track swings</div>
              <div class="lead">Elo swings light up once you log matches.</div>
              </div>
            `;
          }
          const swings = account.momentumShifts || [];
          const gains = Array.isArray(swings) ? swings.filter((entry) => (entry.eloChange ?? 0) > 0) : (swings.gains || []);
          const losses = Array.isArray(swings) ? swings.filter((entry) => (entry.eloChange ?? 0) < 0) : (swings.losses || []);
          const renderMomentumItems = (items, positive) => {
            if (!items || items.length === 0) {
              return `<div class="muted-note">No Elo ${positive ? "gains" : "drops"} recorded.</div>`;
            }
            return `
              <div class="momentum-rows">
                ${items.map((swing, idx) => `
                  <div class="momentum-row sequence-item" data-delay="${idx * 120}">
                    ${renderGameBadge(swing.game)}
                    <div class="momentum-meta">
                      <span class="pill" style="background:${swing.eloChange >= 0 ? 'rgba(108,240,194,0.16)' : 'rgba(255,155,125,0.16)'};border-color:${swing.eloChange >=0 ? 'rgba(108,240,194,0.4)' : 'rgba(255,155,125,0.35)'};">${swing.eloChange >=0 ? '+' : ''}${fmt(swing.eloChange)}</span>
                      <span class="pill-muted">${swing.win ? "Win" : "Loss"} • ${formatDateLabel(swing.date)}</span>
                    </div>
                  </div>
                `).join("")}
              </div>
            `;
          };
          const hasAny = (gains?.length || 0) + (losses?.length || 0) > 0;
          return `
            <div class="slide-hero">
              <div class="mega">Momentum Shifts</div>
              <div class="lead">Biggest Elo gains and drops from single matches, spotlighting comebacks and lessons.</div>
            </div>
            ${!hasAny ? `<div class="muted">No Elo swings found for this year.</div>` : `
              <div class="card-grid momentum-grid">
                <div class="list-card momentum-card">
                  <div class="title-row">
                    <span class="pill">Top Gainers</span>
                    <span class="pill-muted">Biggest Elo jumps</span>
                  </div>
                  ${renderMomentumItems(gains, true)}
                </div>
                <div class="list-card momentum-card">
                  <div class="title-row">
                    <span class="pill">Top Losers</span>
                    <span class="pill-muted">Toughest Elo drops</span>
                  </div>
                  ${renderMomentumItems(losses, false)}
                </div>
              </div>
            `}
          `;
        },
      },
      {
        id: "duo-of-destiny",
        title: "Duo of Destiny",
        accent: "accent-bg-gold",
        nextCtaLabel: "Nemesis",
        render: () => {
          if (!account) {
            return `
              <div class="slide-hero">
                <div class="mega">Find your duo</div>
                <div class="lead">Squad up and log matches to reveal your most reliable teammate.</div>
              </div>
            `;
          }
          const duo = account.duoOfDestiny;
          if (!duo) {
            return `
              <div class="slide-hero">
                <div class="mega">No duo yet</div>
                <div class="lead">Play ranked as a team to forge your Duo of Destiny.</div>
              </div>
            `;
          }
          return `
            <div class="slide-hero">
              <div class="mega">Duo of Destiny</div>
              <div class="lead">Your most frequent teammate across every ladder, showing your combined win rate and the games you conquered together.</div>
            </div>
            <div class="list-card">
              <div class="title-row">
                ${renderProfileChip(duo.profile, "Teammate")}
                <span class="pill-muted">Win Rate ${fmtPct(duo.winRate)}</span>
              </div>
              <div class="stat-hero">
                <div class="card">
                  <div class="pill">Matches Together</div>
                  ${renderKpi(duo.matches)}
                  <p class="sub">Shared rosters</p>
                </div>
                <div class="card">
                  <div class="pill">Wins</div>
                  ${renderKpi(duo.wins)}
                  <p class="sub">Victories side-by-side</p>
                </div>
              </div>
              ${duo.games && duo.games.length ? `
                <div class="card-grid">
                  ${duo.games.map((entry, idx) => `
                    <div class="list-card sequence-item" data-delay="${idx * 120}">
                      <div class="title-row">
                        ${renderGameBadge(entry.game)}
                        <span class="pill-muted">${fmt(entry.matches)} ranked matches together</span>
                      </div>
                    </div>
                  `).join("")}
                </div>
                <div class="muted-note">Games climbed together</div>
                ${renderGameIconRow((duo.games || []).map((entry) => entry.game))}
              ` : ``}
            </div>
          `;
        },
      },
      {
        id: "nemeses",
        title: "Nemesis",
        accent: "accent-bg-cyan",
        nextCtaLabel: "Finish",
        render: () => {
          if (!account) {
            return `
              <div class="slide-hero">
                <div class="mega">No rivals logged</div>
                <div class="lead">Play ranked to uncover who toppled you most.</div>
              </div>
            `;
          }
          const nemeses = account.nemeses || [];
          const renderNemesisGames = (games) => {
            if (!games || games.length === 0) {
              return `<div class="muted-note">No defeats recorded by game.</div>`;
            }
            return `
              <div class="nemesis-games">
                ${games.map((entry) => `
                  <div class="nemesis-game-row">
                    ${renderGameBadge(entry.game)}
                    <span class="pill-muted">${fmt(entry.defeats)} defeats</span>
                  </div>
                `).join("")}
              </div>
            `;
          };
          return `
            <div class="slide-hero">
              <div class="mega">Nemesis</div>
              <div class="lead">Opponents who beat you the most across all games — including where they claimed those wins.</div>
            </div>
            ${nemeses.length === 0 ? `<div class="muted">No rivalries detected this year.</div>` : `
              <div class="nemesis-list">
                ${nemeses.map((entry, idx) => `
                  <div class="list-card nemesis-card sequence-item" data-delay="${idx * 120}">
                    <div class="title-row">
                      ${renderProfileChip(entry.profile, "Opponent")}
                      <span class="nemesis-defeats">${fmt(entry.defeats)} defeats</span>
                    </div>
                    <div class="pill-muted">Defeats by game</div>
                    ${renderNemesisGames(entry.games || (entry.game ? [{ game: entry.game, defeats: entry.defeats }] : []))}
                  </div>
                `).join("")}
              </div>
            `}
          `;
        },
      },
      {
        id: "farewell",
        title: "Farewell",
        accent: "accent-bg-pink",
        nextCtaLabel: "Finish",
        render: () => `
          <div class="slide-hero">
            <div class="mega">Thank you for building ${year}</div>
            <div class="lead">From controllers to quests, every row tells a story. See you in the next chapter.</div>
            <div class="actions" style="justify-content:center;">
              <button class="cta-primary" data-action="celebrate">Raise Banner</button>
              <button class="cta-primary" data-action="share">Share Archive</button>
            </div>
          </div>
        `,
      },
    ];

    const slideIds = slides.map((slide) => slide.id);
    const preferredInitialTab = slideIds.includes(requestedInitialTab) ? requestedInitialTab : null;

    const slidesContainer = document.getElementById("slides");
    const dotNav = document.getElementById("dot-nav");
    const slideStage = document.querySelector(".slides-stage");
    const animationConfig = {
      duration: 900,
      exitDuration: 650,
      stagger: 140,
      defaultStage: {
        enter: "animate__fadeInUp",
        exit: "animate__fadeOutDown",
      },
      defaultElement: {
        enter: "animate__fadeInUp",
        exit: "animate__fadeOutDown",
        duration: 750,
      },
      defaultElements: [
        { selector: ".slide-body > *", enter: "animate__fadeInUp", exit: "animate__fadeOutDown", stagger: true },
        { selector: ".slide-share", enter: "animate__fadeInDown", exit: "animate__fadeOutUp", delay: 0 },
      ],
      slides: {
        title: {
          stage: { enter: "animate__fadeIn", exit: "animate__fadeOut", duration: 900 },
          elements: [
            { selector: ".title-logo", enter: "animate__fadeInDown", delay: 0 },
            { selector: ".title-present", enter: "animate__fadeIn", delay: 120 },
            { selector: ".mega", enter: "animate__fadeInUp", delay: 240 },
            { selector: ".lead", enter: "animate__fadeInUp", delay: 360 },
            { selector: ".actions .cta-primary", enter: "animate__zoomIn", stagger: true, delay: 500 },
          ],
        },
        "community-thanks": {
          elements: [
            { selector: ".slide-hero", enter: "animate__fadeInUp", exit: "animate__fadeOutDown" },
            { selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true },
            { selector: ".community-grid .card", enter: "animate__fadeInUp", stagger: true },
            { selector: ".actions .cta-primary", enter: "animate__zoomIn", stagger: true },
          ],
        },
        "world-status": {
          stage: { enter: "animate__fadeIn", exit: "animate__fadeOut" },
          elements: [{ selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true }],
        },
        "victory-lap": {
          elements: [{ selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true }],
        },
        population: {
          elements: [{ selector: ".stat-hero .card", enter: "animate__lightSpeedInRight", stagger: true }],
        },
        "guild-atlas": {
          elements: [{ selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true }],
        },
        economy: {
          elements: [{ selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true }],
        },
        systems: {
          elements: [{ selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true }],
        },
        events: {
          elements: [{ selector: ".timeline .card", enter: "animate__fadeInLeft", stagger: true }],
        },
        "best-friends": {
          elements: [{ selector: ".list-card", enter: "animate__fadeInUp", stagger: true }],
        },
        "favorite-ranked-game": {
          elements: [{ selector: ".list-card", enter: "animate__fadeInUp", stagger: true }],
        },
        "matchmaker-streaks": {
          elements: [
            { selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true },
            { selector: ".list-card", enter: "animate__fadeInUp", stagger: true },
            { selector: ".list-card .slide-icon", enter: "animate__zoomIn", stagger: true },
          ],
        },
        "momentum-shifts": {
          elements: [
            { selector: ".list-card", enter: "animate__fadeInUp", stagger: true },
            { selector: ".momentum-rows .momentum-row", enter: "animate__fadeInUp", stagger: true },
          ],
        },
        "duo-of-destiny": {
          elements: [
            { selector: ".list-card", enter: "animate__fadeInUp", stagger: true },
            { selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true },
          ],
        },
        nemeses: {
          elements: [{ selector: ".list-card", enter: "animate__fadeInUp", stagger: true }],
        },
        games: {
          elements: [{ selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true }],
        },
        outlook: {
          elements: [{ selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true }],
        },
      },
    };

    let activeIndex = -1;
    let isTransitioning = false;
    const sectionRefs = [];
    const dotRefs = [];

    function shouldShowNextCta(slide) {
      return slide.showNextCta !== false;
    }

    function createSlideSection(slide, index) {
      const section = document.createElement("section");
      section.className = "slide";
      section.id = slide.id;
      section.tabIndex = -1;
      section.dataset.index = index;
      section.innerHTML = `
        <button class="copy-link slide-share" data-slide="${slide.id}" aria-label="Share slide ${slide.title}">
          <i class="fa-solid fa-link"></i>
        </button>
        <div class="slide-body">
          ${slide.render(atlasData)}
        </div>
      `;
      const slideBody = section.querySelector(".slide-body");
      if (shouldShowNextCta(slide)) {
        const navRow = document.createElement("div");
        navRow.className = "slide-progress actions";
        const nextButton = document.createElement("button");
        nextButton.className = "cta-primary";
        nextButton.dataset.action = "slide-next";
        nextButton.dataset.defaultLabel = slide.nextCtaLabel || "Continue";
        nextButton.textContent = slide.nextCtaLabel || "Continue";
        navRow.appendChild(nextButton);
        slideBody.appendChild(navRow);
      }
      slidesContainer.appendChild(section);
      sectionRefs.push(section);
      tagSequenceItems(section);
      primeNumberElements(section);

      const dot = document.createElement("button");
      dot.className = "hud-dot";
      dot.setAttribute("aria-label", `Go to ${slide.title}`);
      dot.addEventListener("click", () => setActiveSlide(index, { scroll: true }));
      dotNav.appendChild(dot);
      dotRefs.push(dot);
    }

    function applyCtaBranding() {
      document.querySelectorAll(".cta-primary").forEach((btn) => {
        btn.classList.add("bg-ranked-1");
      });
    }
    function resetAnimationClasses(el) {
      if (!el) return;
      const animateClasses = Array.from(el.classList).filter(cls => cls.startsWith("animate__"));
      animateClasses.forEach(cls => el.classList.remove(cls));
    }
    function cleanupAnimation(el) {
      if (!el) return;
      resetAnimationClasses(el);
      el.style.removeProperty("animationDelay");
      el.style.removeProperty("--animate-delay");
      el.style.removeProperty("--animate-duration");
      el.style.removeProperty("animationIterationCount");
      el.style.removeProperty("--animate-repeat");
      el.style.removeProperty("animationFillMode");
    }
    function applyAnimation(el, animationName, { delay = 0, duration = animationConfig.duration, repeat = 1, holdFinalState = false } = {}) {
      if (!el || !animationName) return Promise.resolve();
      resetAnimationClasses(el);
      el.style.animationDelay = `${delay}ms`;
      el.style.setProperty("--animate-delay", `${delay}ms`);
      el.style.setProperty("--animate-duration", `${duration}ms`);
      el.style.animationIterationCount = `${repeat}`;
      el.style.setProperty("--animate-repeat", `${repeat}`);
      el.style.animationFillMode = "both";
      return new Promise((resolve) => {
        const handle = () => {
          if (!holdFinalState) {
            cleanupAnimation(el);
          }
          el.removeEventListener("animationend", handle);
          resolve();
        };
        el.addEventListener("animationend", handle, { once: true });
        el.classList.add("animate__animated", animationName);
      });
    }
    function tagSequenceItems(section) {
      const items = section.querySelectorAll(".sequence-item");
      items.forEach((item, idx) => {
        if (!item.dataset.animate) {
          item.dataset.animate = animationConfig.defaultElement.enter;
        }
        if (!item.dataset.delay) {
          item.dataset.delay = idx * animationConfig.stagger;
        }
      });
      const customNodes = section.querySelectorAll("[data-animate]");
      customNodes.forEach((node, idx) => {
        if (!node.dataset.delay) {
          node.dataset.delay = idx * animationConfig.stagger;
        }
      });
    }
    function primeNumberElements(section) {
      section.querySelectorAll(".stat-number").forEach((node) => {
        const raw = node.dataset.target ?? node.textContent;
        const num = numericOrNull(raw);
        if (num !== null) {
          node.dataset.target = `${num}`;
          node.textContent = "0";
        }
      });
    }
    function getSlideAnimationConfig(sectionId) {
      const overrides = animationConfig.slides[sectionId] || {};
      return {
        stage: { ...animationConfig.defaultStage, ...(overrides.stage || {}) },
        elements: [...animationConfig.defaultElements, ...(overrides.elements || [])],
      };
    }
    function collectElementAnimations(section, config) {
      const collected = [];
      config.elements.forEach((def, defIdx) => {
        const nodes = section.querySelectorAll(def.selector);
        nodes.forEach((node, nodeIdx) => {
          if (node.classList.contains("bg-ranked-1")) return;
          collected.push({
            el: node,
            enter: def.enter || animationConfig.defaultElement.enter,
            exit: def.exit || animationConfig.defaultElement.exit,
            duration: def.duration || animationConfig.defaultElement.duration,
            delay: def.delay ?? (def.stagger ? nodeIdx * animationConfig.stagger : defIdx * animationConfig.stagger),
          });
        });
      });
      section.querySelectorAll("[data-animate]").forEach((node) => {
        if (node.classList.contains("bg-ranked-1")) return;
        collected.push({
          el: node,
          enter: node.dataset.animate,
          exit: node.dataset.exitAnimate || animationConfig.defaultElement.exit,
          duration: Number(node.dataset.duration) || animationConfig.defaultElement.duration,
          delay: Number(node.dataset.delay) || 0,
        });
      });
      const seen = new Set();
      return collected.filter(({ el }) => {
        const key = el;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
      });
    }
    function enableTooltips() {
      if (!window.bootstrap || !bootstrap.Tooltip) return;
      document.querySelectorAll('[data-bs-toggle=\"tooltip\"]').forEach((triggerEl) => {
        const existing = bootstrap.Tooltip.getInstance(triggerEl);
        if (existing) existing.dispose();
        new bootstrap.Tooltip(triggerEl);
      });
    }
    function animateNumbers(section) {
      const nodes = section.querySelectorAll(".stat-number[data-target]");
      const formatter = new Intl.NumberFormat();
      nodes.forEach((node) => {
        const target = Number(node.dataset.target);
        if (!Number.isFinite(target)) return;
        const duration = 1800;
        const start = 0;
        const startTime = performance.now();
        const step = (now) => {
          const progress = Math.min((now - startTime) / duration, 1);
          const value = Math.round(start + (target - start) * progress);
          node.textContent = formatter.format(value);
          if (progress < 1) {
            requestAnimationFrame(step);
          }
        };
        requestAnimationFrame(step);
      });
    }
    function startEnterAnimation(section) {
      const config = getSlideAnimationConfig(section.id);
      const elementAnimations = collectElementAnimations(section, config);
      applyAnimation(section, config.stage.enter, { duration: config.stage.duration || animationConfig.duration });
      elementAnimations.forEach((item) => {
        applyAnimation(item.el, item.enter, { delay: item.delay, duration: item.duration });
      });
      animateNumbers(section);
    }
    function startExitAnimation(section) {
      const config = getSlideAnimationConfig(section.id);
      const elementAnimations = collectElementAnimations(section, config);
      const animations = [
        applyAnimation(section, config.stage.exit, { duration: config.stage.exitDuration || animationConfig.exitDuration, holdFinalState: true }),
      ];
      elementAnimations.forEach((item) => {
        animations.push(applyAnimation(item.el, item.exit, { delay: 0, duration: item.duration, holdFinalState: true }));
      });
      return Promise.all(animations);
    }

    function waitMs(ms) {
      return new Promise((resolve) => setTimeout(resolve, ms));
    }
    function updateProgressButtons(activeIdx) {
      sectionRefs.forEach((section, idx) => {
        const btn = section.querySelector("[data-action='slide-next']");
        if (!btn) return;
        const isActive = idx === activeIdx;
        btn.toggleAttribute("disabled", isActive && activeIdx === slides.length - 1);
        btn.textContent = btn.dataset.defaultLabel || "Continue";
      });
    }
    function setNavState(idx) {
      updateProgressButtons(idx);
      dotRefs.forEach((dot, dotIdx) => {
        dot.classList.toggle("active", dotIdx === idx);
      });
    }
    async function setActiveSlide(index, opts = { scroll: false, updateHash: true }) {
      if (isTransitioning) return;
      const clamped = Math.max(0, Math.min(index, slides.length - 1));
      if (clamped === activeIndex) return;
      isTransitioning = true;

      const previousIndex = activeIndex;
      const previousSection = sectionRefs[previousIndex];
      const previousAccent = previousIndex >= 0 ? slides[previousIndex].accent : null;

      const target = sectionRefs[clamped];
      if (!target) {
        isTransitioning = false;
        return;
      }

      const accent = slides[clamped].accent;

      activeIndex = clamped;
      setNavState(activeIndex);

      if (previousSection) {
        previousSection.classList.add("leaving");
        previousSection.style.display = "block";
        await startExitAnimation(previousSection);
        previousSection.style.visibility = "hidden";
        previousSection.classList.remove("active", "leaving");
        previousSection.style.removeProperty("opacity");
        if (previousAccent) previousSection.classList.remove(previousAccent);
        previousSection.setAttribute("aria-hidden", "true");
        previousSection.style.display = "none";
        previousSection.style.removeProperty("visibility");
        cleanupAnimation(previousSection);
        previousSection.querySelectorAll(".animate__animated").forEach(cleanupAnimation);
      }

      target.classList.add("active");
      target.classList.remove("leaving");
      target.setAttribute("aria-hidden", "false");
      target.style.display = "block";
      if (accent) target.classList.add(accent);
      startEnterAnimation(target);

      if (opts.updateHash) {
        const newUrl = `${window.location.pathname}${window.location.search}#${slides[activeIndex].id}`;
        history.replaceState(null, "", newUrl);
      }

      isTransitioning = false;
    }

    function toggleFullscreen() {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
      } else {
        document.exitFullscreen().catch(() => {});
      }
    }

    slides.forEach(createSlideSection);
    applyCtaBranding();
    setActiveSlide(0, { scroll: false, updateHash: false });

    document.addEventListener("keydown", (e) => {
      if (e.key === "ArrowLeft") {
        setActiveSlide(activeIndex - 1, { scroll: true });
      } else if (e.key === "ArrowRight") {
        setActiveSlide(activeIndex + 1, { scroll: true });
      } else if (e.key === " ") {
        e.preventDefault();
        setActiveSlide(activeIndex + 1, { scroll: true });
      } else if (e.key === "c") {
        triggerCelebration();
      }
    });

    slidesContainer.addEventListener("click", (e) => {
      const btn = e.target.closest(".copy-link");
      if (!btn) return;
      const slideId = btn.dataset.slide;
      const url = buildShareUrl(slideId);
      navigator.clipboard.writeText(url).then(() => {
        btn.textContent = "Copied!";
        setTimeout(() => (btn.textContent = "Share Slide"), 1200);
      });
    });

    function scrollToHash(preferredTab = preferredInitialTab) {
      const rawHash = (window.location.hash || (preferredTab ? `#${preferredTab}` : "")).replace("#", "");
      const hash = rawHash === "opening" ? "community-thanks" : rawHash;
      if (!hash) return;
      const idx = slides.findIndex(s => s.id === hash);
      if (idx >= 0) {
        setActiveSlide(idx, { scroll: true, updateHash: false });
      }
    }

    window.addEventListener("hashchange", () => scrollToHash(null));
    scrollToHash();

    let celebrationLock = false;
    function triggerCelebration() {
      if (celebrationLock) return;
      celebrationLock = true;
      const targetStage = slideStage || document.body;
      const sparks = 18;
      for (let i = 0; i < sparks; i++) {
        const spark = document.createElement("div");
        spark.className = "spark";
        spark.style.setProperty("--dx", `${(Math.random() - 0.5) * 180}px`);
        spark.style.setProperty("--dy", `${(Math.random() - 0.4) * 240}px`);
        spark.style.left = `${50 + (Math.random() - 0.5) * 40}%`;
        spark.style.top = `${50 + (Math.random() - 0.5) * 30}%`;
        spark.style.animationDuration = `${700 + Math.random() * 400}ms`;
        targetStage.appendChild(spark);
        spark.addEventListener("animationend", () => spark.remove());
      }
      setTimeout(() => { celebrationLock = false; }, 800);
    }

    slidesContainer.addEventListener("click", (e) => {
      const target = e.target;
      if (target.matches(".cta-primary[data-action='celebrate']")) {
        triggerCelebration();
      }
      if (target.matches(".cta-primary[data-action='next']")) {
        setActiveSlide(activeIndex + 1, { scroll: true });
      }
      if (target.matches("[data-action='slide-next']")) {
        setActiveSlide(activeIndex + 1, { scroll: true });
      }
      if (target.matches(".cta-primary[data-action='fullscreen']")) {
        toggleFullscreen();
      }
      if (target.matches(".cta-primary[data-action='share']")) {
        const slideId = slides[activeIndex].id;
        const url = buildShareUrl(slideId);
        navigator.clipboard.writeText(url).then(() => {
          target.textContent = "Copied!";
          setTimeout(() => target.textContent = "Share Slide", 1200);
        });
      }
    });

    window.addEventListener("load", () => {
      enableTooltips();
      if (typeof StartFireworks === "function") {
        StartFireworks();
      }
    });

  </script>
  <script src="/assets/vendors/bootstrap/bootstrap.bundle.min.js"></script>
  <!-- FX Overlays -->
  <div class="confetti-box" style="z-index:10000; pointer-events:none;">
    <div class="js-container-confetti" style="width:100vw; height:100vh;"></div>
  </div>
  <div class="fireworks-box" style="z-index:10000; pointer-events:none;">
    <div class="js-container-fireworks" style="width:100vw; height:100vh;"></div>
  </div>
  <?php require("php-components/base-page-version-popup.php"); ?>
  <?php require("php-components/base-page-javascript.php"); ?>
</body>
</html>
