<?php
// Kickback Kingdom - Atlas Archive (POC with immersive award-show styling)
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Backend\Controllers\SeasonController;

$seasonController = new SeasonController();
$seasonBackgroundUrl = $seasonController->getBackgroundImageUrl();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Atlas Archive - Yearly Review (POC)</title>
  <link rel="stylesheet" href="/assets/vendors/bootstrap/bootstrap.min.css" />
  <link rel="stylesheet" href="/assets/css/kickback-kingdom.css" />
  <link rel="stylesheet" href="/assets/vendors/animate/animate.min.css" />
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
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: var(--sans);
      background: <?php echo $seasonBackgroundUrl ? "url('{$seasonBackgroundUrl}')" : "var(--gradient)"; ?>;
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
    .slide-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }
    .slide-body {
      flex: 1;
      overflow: visible;
      padding-right: 0;
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
    .opening-grid {
      display: grid;
      grid-template-columns: 1fr;
      justify-items: center;
      gap: 18px;
    }
    .opening-panel {
      position: relative;
      overflow: hidden;
      padding: 26px 28px;
      max-width: 820px;
      width: 100%;
      text-align: center;
      background: linear-gradient(140deg, rgba(255, 209, 102, 0.12), rgba(124, 183, 255, 0.08));
      border: 1px solid rgba(255, 209, 102, 0.35);
      border-radius: 18px;
      box-shadow: var(--shadow);
    }
    .opening-panel::after {
      content: "";
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 18% 24%, rgba(255, 209, 102, 0.18), transparent 45%), radial-gradient(circle at 82% 76%, rgba(124, 183, 255, 0.16), transparent 36%);
      opacity: 0.6;
      pointer-events: none;
    }
    .opening-panel > * { position: relative; z-index: 1; }
    .opening-title {
      font-size: clamp(28px, 5vw, 44px);
      font-weight: 900;
      letter-spacing: -0.02em;
      margin-bottom: 10px;
    }
    .opening-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 12px;
      margin: 16px auto;
      max-width: 640px;
    }
    .opening-stat {
      padding: 12px;
      border: 1px solid var(--border);
      border-radius: 12px;
      background: rgba(12, 18, 28, 0.35);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }
    .opening-stat strong {
      display: block;
      font-size: 26px;
      letter-spacing: 0.04em;
    }
    .opening-program {
      display: grid;
      gap: 10px;
      margin: 10px auto 0;
      max-width: 680px;
    }
    .opening-program-item {
      display: grid;
      grid-template-columns: 120px 1fr;
      align-items: center;
      gap: 12px;
      padding: 12px 14px;
      border-radius: 12px;
      background: rgba(255,255,255,0.03);
      border: 1px solid var(--border);
      font-weight: 700;
      letter-spacing: 0.02em;
      text-align: left;
    }
    .opening-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 999px;
      background: rgba(255,255,255,0.08);
      border: 1px solid var(--border);
      font-size: 12px;
      letter-spacing: 0.04em;
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
      animation: none;
    }
    .sub {
      font-size: 14px;
      color: var(--muted);
    }
    .ribbon {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid rgba(255, 209, 102, 0.4);
      background: linear-gradient(90deg, rgba(255, 209, 102, 0.12), rgba(124, 183, 255, 0.1));
      font-weight: 700;
      color: #ffd166;
      text-transform: uppercase;
      letter-spacing: 0.06em;
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
    .sequence-item { opacity: 1; }
    .control-bar {
      background: rgba(12,18,28,0.6);
      border: 1px solid var(--border);
      border-radius: 12px;
      backdrop-filter: blur(8px);
      box-shadow: var(--shadow);
      padding: 10px 14px;
    }
    .control-bar button {
      border-radius: 10px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.05);
      color: var(--text);
      font-weight: 700;
      padding: 8px 14px;
    }
    .control-bar button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    @media (max-width: 960px) {
      .page { grid-template-columns: 1fr; }
      .control-bar { position: sticky; top: 0; }
    }
    @media (max-width: 768px) {
      .slides-stage { padding: 78px 14px 18px; height: 76vh; }
      .slides-stage .slide { inset: 12px; padding: 18px; }
      .hud.floating { inset: 12px 12px auto 12px; flex-wrap: wrap; gap: 8px; }
      .slide-header { flex-direction: column; align-items: flex-start; }
      .copy-link { width: 100%; justify-content: center; text-align: center; }
      .control-bar { width: 100%; }
      .control-bar button { flex: 1 1 120px; }
      .opening-grid { grid-template-columns: 1fr; }
      .opening-panel { padding: 22px 18px; }
      .opening-program-item { grid-template-columns: 1fr; text-align: center; }
    }
  </style>
</head>
<body>
  <div class="page-shell scanlines">
    <div class="film-grain"></div>
    <div class="page container-fluid py-4">
      <main class="content row justify-content-center g-3">
        <div class="col-12">
          <div class="slides-stage w-100">
            <div class="hud floating d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="hud-left d-inline-flex align-items-center gap-2 flex-wrap">
                <span class="pill">Atlas</span>
                <span class="pill">2025</span>
              </div>
              <div class="hud-dots d-inline-flex gap-2" id="dot-nav" aria-label="Slide navigation"></div>
            </div>
            <div id="slides" class="w-100"></div>
          </div>
        </div>
        <div class="col-12">
          <div class="control-bar d-flex align-items-center justify-content-center gap-2 flex-wrap mt-2" aria-label="Slide controls">
            <button id="prev-btn" class="btn btn-outline-light btn-sm">◀</button>
            <span id="counter" class="muted" aria-live="polite">Slide 1/1</span>
            <button id="next-btn" class="btn btn-outline-light btn-sm">▶</button>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    const year = 2025;
    const fakeData = {
      accounts: [
        {
          id: "a-001",
          name: "Aria Cloudbinder",
          class: "Archivist",
          joinDate: "2022-05-12",
          level: 48,
          reputation: "Esteemed",
          lastLogin: "2025-01-12T10:00:00Z",
          streakDays: 24,
          questsCompleted: 138,
          tasksCompleted: 54,
          guilds: ["g-ember", "g-sapphire"],
          eloHistory: [1210, 1280, 1325, 1290, 1375, 1402, 1386],
          coQuestPartners: [
            { id: "a-002", name: "Brannor of the Vale", shared: 24, successRate: 0.82, last: "2024-12-20" },
            { id: "a-003", name: "Lyra Duskwhisper", shared: 17, successRate: 0.76, last: "2024-12-11" }
          ],
          modes: [
            { mode: "Guild Raids", attempts: 32, wins: 24, bestElo: 1402, worstElo: 1210 },
            { mode: "Arena Skirmish", attempts: 18, wins: 9, bestElo: 1350, worstElo: 1224 },
            { mode: "Expedition Quests", attempts: 22, wins: 18, bestElo: 1380, worstElo: 1272 }
          ],
          matches: [
            { id: "match-4472", label: "Guild Raid vs Obsidian Maw", elo: 1402, result: "Win", date: "2024-11-04" },
            { id: "match-3361", label: "Arena Skirmish vs Silver Pike", elo: 1210, result: "Loss", date: "2024-02-10" }
          ]
        }
      ],
      guilds: [
        { id: "g-ember", name: "Ember Syndicate", members: 48, completions: 204, growth: "+12 this year", tags: ["Trade", "Logistics"] },
        { id: "g-sapphire", name: "Sapphire Accord", members: 31, completions: 144, growth: "+8 this year", tags: ["Arcana", "Support"] }
      ],
      tasks: [
        { id: "t-001", title: "Stabilize the Leyline", status: "Completed", impact: "High", guild: "g-ember" },
        { id: "t-002", title: "Escort the Sky Caravans", status: "Completed", impact: "Medium", guild: "g-sapphire" },
        { id: "t-003", title: "Archive Lost Tomes", status: "Active", impact: "High", guild: "g-sapphire" }
      ],
      servers: [
        { id: "s-1", name: "Citadel Core", uptime: "99.95%", hotspots: ["Guild Raids", "Markets"] },
        { id: "s-2", name: "Frontier Relay", uptime: "99.80%", hotspots: ["Expeditions", "Arena"] }
      ],
      activity: [
        { month: "Sep", active: 1420, returning: 280 },
        { month: "Oct", active: 1510, returning: 320 },
        { month: "Nov", active: 1610, returning: 340 },
        { month: "Dec", active: 1690, returning: 360 }
      ],
      transactions: [
        { id: "tx-101", type: "Trade", amount: 3200, counterparty: "Ember Syndicate" },
        { id: "tx-102", type: "Commission", amount: 2100, counterparty: "Sapphire Accord" }
      ],
      ledgers: [
        { id: "l-01", description: "Trade Volume", total: 184000, delta: "+12%" },
        { id: "l-02", description: "Quest Payouts", total: 98000, delta: "+8%" }
      ],
      events: [
        { date: "2024-03-10", title: "Skyforge Patch", note: "New raid tier unlocked" },
        { date: "2024-07-22", title: "Summer Convergence", note: "Double rewards festival" },
        { date: "2024-10-05", title: "Obsidian Maw Siege", note: "Realm-wide defense event" }
      ]
    };

    const slides = [
      {
        id: "title",
        title: "Atlas Archive",
        accent: "accent-bg-cyan",
        hideHeaderTitle: true,
        render: () => `
          <div class="slide-hero">
            <div class="title-logo shadow-sm">
              <img src="https://kickback-kingdom.com/assets/images/logo-kk.png" alt="Kickback Kingdom" class="img-fluid">
            </div>
            <div class="title-present">Presents</div>
            <div class="mega">Atlas Archive 2026</div>
            <div class="lead">Your annual reflection through Kickback Kingdom — stories, stats, and highlights from the realm.</div>
            <div class="actions" style="justify-content:center;">
              <button class="cta-primary" data-action="next">Start</button>
              <button class="cta-primary" data-action="fullscreen">Fullscreen</button>
            </div>
          </div>
        `
      },
      {
        id: "opening",
        title: "Opening Ceremony",
        render: () => `
          <div class="opening-grid">
            <div class="opening-panel card accent-bg-gold">
              <div class="ribbon">Grand Opening</div>
              <div class="opening-title">Atlas Archive is live</div>
              <p class="muted">Curtains up. This year&apos;s legends, quests, and guild moments are queued for the spotlight.</p>
              <div class="opening-stats">
                <div class="opening-stat">
                  <span class="pill">Archive Year</span>
                  <strong>2026</strong>
                  <span class="sub">Season showcase</span>
                </div>
                <div class="opening-stat">
                  <span class="pill">Guilds RSVP&apos;d</span>
                  <strong>12</strong>
                  <span class="sub">Featured banners</span>
                </div>
              </div>
              <div class="opening-program">
                <div class="opening-program-item">
                  <span class="opening-chip">Highlights</span>
                  State of the Realm, ledger sparks, and guild showstoppers.
                </div>
                <div class="opening-program-item">
                  <span class="opening-chip">Allies</span>
                  Best partners, clutch duos, and chemistry rankings.
                </div>
                <div class="opening-program-item">
                  <span class="opening-chip">Future</span>
                  Outlooks, upgrades, and where the banners fly next.
                </div>
              </div>
              <div class="actions flex-wrap" style="justify-content:center; margin-top:18px;">
                <button class="cta-primary" data-action="celebrate">Start the fanfare</button>
                <button class="cta-primary" data-action="next">Begin the slides</button>
                <button class="cta-primary" data-action="fullscreen">Fullscreen</button>
                <button class="cta-primary" data-action="share">Share link</button>
              </div>
            </div>
          </div>
        `
      },
      {
        id: "world-status",
        title: "World Status",
        accent: "accent-bg-cyan",
        render: (data) => {
          const activeAccounts = 2480;
          const uptime = data.servers.map(s => s.uptime).join(" / ");
          return `
            <div class="slide-hero">
              <div class="mega">State of the Realm</div>
              <div class="lead">A snapshot of the Kingdom at a glance.</div>
            </div>
            <div class="stat-hero">
              <div class="card">
                <div class="pill">Active Accounts</div>
                <div class="kpi">${activeAccounts}</div>
                <p class="sub">Adventurers in the Kingdom</p>
              </div>
              <div class="card">
                <div class="pill">Realm Uptime</div>
                <div class="kpi">${uptime}</div>
                <p class="sub">Citadel stability</p>
              </div>
              <div class="card">
                <div class="pill">Guilds</div>
                <div class="kpi">${data.guilds.length}</div>
                <p class="sub">Active banners</p>
              </div>
              <div class="card">
                <div class="pill">Ledgers</div>
                <div class="kpi">${data.ledgers.length}</div>
                <p class="sub">Economic streams</p>
              </div>
            </div>
            <div class="actions">
              <button class="cta-primary" data-action="celebrate">Spark</button>
              <button class="cta-primary" data-action="share">Share Slide</button>
            </div>
          `;
        }
      },
      {
        id: "victory-lap",
        title: "Community Victory Lap",
        accent: "accent-bg-gold",
        render: (data) => {
          const quests = data.accounts[0].questsCompleted + 420; // mock uplift
          const hours = 38000;
          return `
            <div class="slide-hero">
              <div class="mega">Thank You, Kingdom</div>
              <div class="lead">Your hours, victories, and friendships lit up the realm.</div>
            </div>
            <div class="stat-hero">
              <div class="card">
                <div class="pill">Play Hours</div>
                <div class="kpi">${hours.toLocaleString()}</div>
                <p class="sub">Moments shared in-world</p>
              </div>
              <div class="card">
                <div class="pill">Quests</div>
                <div class="kpi">${quests.toLocaleString()}</div>
                <p class="sub">Ledgered adventures</p>
              </div>
              <div class="card">
                <div class="pill">Allies</div>
                <div class="kpi">8,420</div>
                <p class="sub">Parties and friendships</p>
              </div>
              <div class="card">
                <div class="pill">Events</div>
                <div class="kpi">${data.events.length * 4}</div>
                <p class="sub">Realm gatherings</p>
              </div>
            </div>
            <div class="actions">
              <button class="cta-primary" data-action="celebrate">Celebrate</button>
              <button class="cta-primary" data-action="next">Next Highlight</button>
            </div>
          `;
        }
      },
      {
        id: "population",
        title: "Population & Activity",
        accent: "accent-bg-violet",
        render: (data) => `
          <div class="slide-hero">
            <div class="mega">Pulse of the Realm</div>
            <div class="lead">How the Kingdom moved month over month.</div>
          </div>
          <div class="stat-hero">
            ${data.activity.map(item => `
              <div class="card">
                <div class="pill">${item.month}</div>
                <div class="kpi">${item.active}</div>
                <p class="sub">${item.returning} returning</p>
              </div>
            `).join("")}
          </div>
          <div class="actions">
            <button class="cta-primary" data-action="celebrate">Pulse</button>
            <button class="cta-primary" data-action="share">Share Slide</button>
          </div>
        `
      },
      {
        id: "guild-atlas",
        title: "Guild Atlas",
        accent: "accent-bg-cyan",
        render: (data) => `
          <div class="slide-hero">
            <div class="mega">Guild Highlights</div>
            <div class="lead">Banners that defined the year.</div>
          </div>
          <div class="stat-hero">
            ${data.guilds.map(g => `
              <div class="card">
                <div class="pill">${g.name}</div>
                <div class="kpi">${g.members}</div>
                <p class="sub">${g.completions} completions · ${g.growth}</p>
              </div>
            `).join("")}
          </div>
          <div class="actions">
            <button class="cta-primary" data-action="celebrate">Guild Cheer</button>
            <button class="cta-primary" data-action="share">Share Slide</button>
          </div>
        `
      },
      {
        id: "economy",
        title: "Economy Ledger",
        accent: "accent-bg-gold",
        render: (data) => `
          <div class="slide-hero">
            <div class="mega">Economy Pulse</div>
            <div class="lead">Volume, payouts, and trades that fueled the year.</div>
          </div>
          <div class="stat-hero">
            ${data.ledgers.map(l => `
              <div class="card">
                <div class="pill">${l.description}</div>
                <div class="kpi">${l.total.toLocaleString()}</div>
                <p class="sub">${l.delta}</p>
              </div>
            `).join("")}
            ${data.transactions.map(tx => `
              <div class="card">
                <div class="pill">${tx.type}</div>
                <div class="kpi">${tx.amount.toLocaleString()}</div>
                <p class="sub">With ${tx.counterparty}</p>
              </div>
            `).join("")}
          </div>
          <div class="actions">
            <button class="cta-primary" data-action="celebrate">Showers</button>
            <button class="cta-primary" data-action="share">Share Slide</button>
          </div>
        `
      },
      {
        id: "systems",
        title: "Systems Utilization",
        accent: "accent-bg-cyan",
        render: (data) => `
          <div class="slide-hero">
            <div class="mega">Systems</div>
            <div class="lead">Where the realm stayed strong.</div>
          </div>
          <div class="stat-hero">
            ${data.servers.map(s => `
              <div class="card">
                <div class="pill">${s.name}</div>
                <div class="kpi">${s.uptime}</div>
                <p class="sub">Hotspots: ${s.hotspots.join(", ")}</p>
              </div>
            `).join("")}
          </div>
          <div class="actions">
            <button class="cta-primary" data-action="celebrate">Light Up</button>
            <button class="cta-primary" data-action="share">Share Slide</button>
          </div>
        `
      },
      {
        id: "events",
        title: "Notable Events Timeline",
        accent: "accent-bg-pink",
        render: (data) => `
          <div class="slide-hero">
            <div class="mega">Events</div>
            <div class="lead">Moments that shook the Kingdom.</div>
          </div>
          <div class="timeline">
            ${data.events.map(ev => `
              <div class="card">
                <div class="pill">${ev.date}</div>
                <div class="kpi" style="font-size:32px; letter-spacing:0.04em;">${ev.title}</div>
                <p class="muted">${ev.note}</p>
              </div>
            `).join("")}
          </div>
          <div class="actions">
            <button class="cta-primary" data-action="celebrate">Fireworks</button>
            <button class="cta-primary" data-action="share">Share Slide</button>
          </div>
        `
      },
      {
        id: "account-spotlight",
        title: "Account Spotlight",
        accent: "accent-bg-gold",
        render: (data) => {
          const acct = data.accounts[0];
          return `
            <div class="slide-hero">
              <div class="mega">${acct.name}</div>
              <div class="lead">${acct.class} · ${acct.reputation}</div>
            </div>
            <div class="stat-hero">
              <div class="card">
                <div class="pill">Joined</div>
                <div class="kpi">${acct.joinDate}</div>
                <p class="sub">Level ${acct.level}</p>
              </div>
              <div class="card">
                <div class="pill">Quests</div>
                <div class="kpi">${acct.questsCompleted}</div>
                <p class="sub">${acct.tasksCompleted} tasks</p>
              </div>
              <div class="card">
                <div class="pill">Streak</div>
                <div class="kpi">${acct.streakDays}</div>
                <p class="sub">Days active</p>
              </div>
              <div class="card">
                <div class="pill">Guilds</div>
                <div class="kpi" style="font-size:22px;">${acct.guilds.join(" • ")}</div>
                <p class="sub">Current banners</p>
              </div>
            </div>
            <div class="actions">
              <button class="cta-primary" data-action="celebrate">Applause</button>
              <button class="cta-primary" data-action="share">Share Spotlight</button>
            </div>
          `;
        }
      },
      {
        id: "best-friend",
        title: "Best Friend / Frequent Ally",
        accent: "accent-bg-cyan",
        render: (data) => {
          const acct = data.accounts[0];
          const best = acct.coQuestPartners[0];
          const reliableTrio = acct.coQuestPartners.slice(0, 2).map(p => p.name).join(" + ");
          return `
            <div class="slide-hero">
              <div class="mega">Allies</div>
              <div class="lead">Those who stood beside you.</div>
            </div>
            <div class="stat-hero">
              <div class="card">
                <div class="pill">Best Ally</div>
                <div class="kpi" style="font-size:32px;">${best.name}</div>
                <p class="sub">${best.shared} shared quests · ${Math.round(best.successRate * 100)}% win</p>
              </div>
              <div class="card">
                <div class="pill">Party Chemistry</div>
                <div class="kpi" style="font-size:24px;">${reliableTrio}</div>
                <p class="sub">Highest synergy trio</p>
              </div>
            </div>
            <div class="actions">
              <button class="cta-primary" data-action="celebrate">Cheer Duo</button>
              <button class="cta-primary" data-action="share">Share Link</button>
            </div>
          `;
        }
      },
      {
        id: "games",
        title: "Favorite, Best, and Worst Games",
        accent: "accent-bg-violet",
        render: (data) => {
          const acct = data.accounts[0];
          const bestMatch = acct.matches.find(m => m.elo === Math.max(...acct.eloHistory)) || acct.matches[0];
          const worstMatch = acct.matches.find(m => m.elo === Math.min(...acct.eloHistory)) || acct.matches[acct.matches.length - 1];
          const favoriteMode = acct.modes.reduce((top, mode) => (mode.attempts > (top?.attempts ?? 0) ? mode : top), null);
          return `
            <div class="slide-hero">
              <div class="mega">Games & Glory</div>
              <div class="lead">Peaks, recoveries, and favorites.</div>
            </div>
            <div class="stat-hero">
              <div class="card">
                <div class="pill">Favorite Mode</div>
                <div class="kpi" style="font-size:32px;">${favoriteMode?.mode ?? "—"}</div>
                <p class="sub">${favoriteMode?.attempts ?? 0} runs · ${favoriteMode ? Math.round((favoriteMode.wins / favoriteMode.attempts) * 100) : 0}% success</p>
              </div>
              <div class="card">
                <div class="pill">Best Game</div>
                <div class="kpi" style="font-size:26px;">${bestMatch?.label ?? "—"}</div>
                <p class="sub">Peak ELO: ${bestMatch?.elo ?? "—"} · ${bestMatch?.result ?? ""}</p>
              </div>
              <div class="card">
                <div class="pill">Toughest Game</div>
                <div class="kpi" style="font-size:26px;">${worstMatch?.label ?? "—"}</div>
                <p class="sub">Low ELO: ${worstMatch?.elo ?? "—"} · ${worstMatch?.result ?? ""}</p>
              </div>
              <div class="card">
                <div class="pill">ELO Trend</div>
                <div class="kpi">${acct.eloHistory.slice(-1)[0]}</div>
                <p class="sub">Peak ${Math.max(...acct.eloHistory)} · Floor ${Math.min(...acct.eloHistory)}</p>
              </div>
            </div>
            <div class="actions">
              <button class="cta-primary" data-action="celebrate">Confetti</button>
              <button class="cta-primary" data-action="share">Share Game</button>
            </div>
          `;
        }
      },
      {
        id: "outlook",
        title: "Forward Outlook",
        accent: "accent-bg-cyan",
        render: () => `
          <div class="slide-hero">
            <div class="mega">Next Year</div>
            <div class="lead">Where the archive points us.</div>
          </div>
          <div class="stat-hero">
            <div class="card">
              <div class="pill">Focus</div>
              <div class="kpi" style="font-size:26px;">Expand Guild Raids</div>
              <p class="sub">Carry momentum from duo/trio wins</p>
            </div>
            <div class="card">
              <div class="pill">Skill</div>
              <div class="kpi" style="font-size:26px;">Refine Arena Play</div>
              <p class="sub">Lift the arena floor</p>
            </div>
            <div class="card">
              <div class="pill">Community</div>
              <div class="kpi" style="font-size:26px;">Mentor Archivists</div>
              <p class="sub">Share ledgers and builds</p>
            </div>
          </div>
          <div class="actions">
            <button class="cta-primary" data-action="celebrate">Raise Banner</button>
            <button class="cta-primary" data-action="share">Share Outlook</button>
          </div>
        `
      }
    ];

    const slidesContainer = document.getElementById("slides");
    const dotNav = document.getElementById("dot-nav");
    const prevBtn = document.getElementById("prev-btn");
    const nextBtn = document.getElementById("next-btn");
    const counter = document.getElementById("counter");
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
        { selector: ".slide-header", enter: "animate__fadeInDown", exit: "animate__fadeOutUp", delay: 0 },
        { selector: ".slide-body > *", enter: "animate__fadeInUp", exit: "animate__fadeOutDown", stagger: true },
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
        opening: {
          elements: [
            { selector: ".opening-panel", enter: "animate__fadeInUp", exit: "animate__fadeOutDown" },
            { selector: ".opening-stat", enter: "animate__fadeInUp", stagger: true },
            { selector: ".opening-program-item", enter: "animate__fadeInLeft", stagger: true },
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
        "account-spotlight": {
          elements: [{ selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true }],
        },
        "best-friend": {
          elements: [{ selector: ".stat-hero .card", enter: "animate__fadeInUp", stagger: true }],
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

    function createSlideSection(slide, index) {
      const section = document.createElement("section");
      section.className = "slide";
      section.id = slide.id;
      section.tabIndex = -1;
      section.dataset.index = index;
      const heading = slide.hideHeaderTitle ? "" : `<h2>${slide.title}</h2>`;
      section.innerHTML = `
        <div class="slide-header">
          <div class="slide-title">
            <span class="pill">Archive Node</span>
            ${heading}
          </div>
          <button class="copy-link" data-slide="${slide.id}">Copy link</button>
        </div>
        <div class="slide-body">${slide.render(fakeData)}</div>
      `;
      slidesContainer.appendChild(section);
      sectionRefs.push(section);
      tagSequenceItems(section);

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
    function startEnterAnimation(section) {
      const config = getSlideAnimationConfig(section.id);
      const elementAnimations = collectElementAnimations(section, config);
      applyAnimation(section, config.stage.enter, { duration: config.stage.duration || animationConfig.duration });
      elementAnimations.forEach((item) => {
        applyAnimation(item.el, item.enter, { delay: item.delay, duration: item.duration });
      });
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
    function setNavState(idx) {
      counter.textContent = `Slide ${idx + 1} / ${slides.length}`;
      prevBtn.disabled = idx === 0;
      nextBtn.disabled = idx === slides.length - 1;
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
        const newUrl = `${window.location.pathname}#${slides[activeIndex].id}`;
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
    setActiveSlide(0);

    prevBtn.addEventListener("click", () => setActiveSlide(activeIndex - 1, { scroll: true }));
    nextBtn.addEventListener("click", () => setActiveSlide(activeIndex + 1, { scroll: true }));

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
      const url = `${window.location.origin}${window.location.pathname}#${slideId}`;
      navigator.clipboard.writeText(url).then(() => {
        btn.textContent = "Copied!";
        setTimeout(() => (btn.textContent = "Copy link"), 1200);
      });
    });

    function scrollToHash() {
      const hash = window.location.hash.replace("#", "");
      if (!hash) return;
      const idx = slides.findIndex(s => s.id === hash);
      if (idx >= 0) {
        setActiveSlide(idx, { scroll: true, updateHash: false });
      }
    }

    window.addEventListener("hashchange", scrollToHash);
    scrollToHash();

    function triggerCelebration() { return; }

    slidesContainer.addEventListener("click", (e) => {
      const target = e.target;
      if (target.matches(".cta-primary[data-action='celebrate']")) {
        triggerCelebration();
      }
      if (target.matches(".cta-primary[data-action='next']")) {
        setActiveSlide(activeIndex + 1, { scroll: true });
      }
      if (target.matches(".cta-primary[data-action='fullscreen']")) {
        toggleFullscreen();
      }
      if (target.matches(".cta-primary[data-action='share']")) {
        const slideId = slides[activeIndex].id;
        const url = `${window.location.origin}${window.location.pathname}#${slideId}`;
        navigator.clipboard.writeText(url).then(() => {
          target.textContent = "Copied!";
          setTimeout(() => target.textContent = "Share Slide", 1200);
        });
      }
    });

  </script>
  <script src="/assets/vendors/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
