<?php
// Kickback Kingdom - Atlas Archive (POC with immersive award-show styling)
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Atlas Archive - Yearly Review (POC)</title>
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
      background: var(--gradient);
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
      gap: 12px;
      padding: 18px 18px 42px;
    }
    header.hero {
      grid-column: 1;
      background: radial-gradient(circle at 30% 20%, rgba(255, 209, 102, 0.18), transparent 45%), radial-gradient(circle at 80% 10%, rgba(124, 183, 255, 0.12), transparent 50%), linear-gradient(145deg, rgba(34, 48, 64, 0.75), rgba(18, 26, 38, 0.94));
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 32px 28px 34px;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
      isolation: isolate;
    }
    .hero::before {
      content: "";
      position: absolute;
      inset: -20% 40% auto auto;
      width: 320px;
      height: 320px;
      border-radius: 999px;
      background: radial-gradient(circle, rgba(255, 209, 102, 0.45), transparent 60%);
      filter: blur(30px);
      opacity: 0.7;
      z-index: -1;
      animation: pulse 6s ease-in-out infinite alternate;
    }
    .hero::after {
      content: "";
      position: absolute;
      inset: auto auto -40% -10%;
      width: 420px;
      height: 420px;
      border-radius: 999px;
      background: radial-gradient(circle, rgba(124, 183, 255, 0.35), transparent 60%);
      filter: blur(40px);
      opacity: 0.6;
      z-index: -1;
      animation: pulse 8s ease-in-out infinite alternate-reverse;
    }
    @keyframes pulse {
      from { transform: scale(0.96); opacity: 0.7; }
      to { transform: scale(1.05); opacity: 1; }
    }
    header.hero h1 {
      margin: 0 0 8px;
      font-weight: 800;
      letter-spacing: -0.03em;
      font-size: clamp(26px, 4vw, 38px);
      text-transform: uppercase;
    }
    header.hero p {
      margin: 0 0 8px;
      color: var(--muted);
      max-width: 960px;
      font-size: 15px;
      line-height: 1.5;
    }
    .hero .subline {
      font-family: var(--mono);
      text-transform: uppercase;
      letter-spacing: 0.28em;
      font-size: 11px;
      color: var(--accent);
    }
    .hero .kudos {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      margin-top: 10px;
      padding: 10px 14px;
      border-radius: 12px;
      background: rgba(255, 209, 102, 0.08);
      border: 1px solid rgba(255, 209, 102, 0.26);
      box-shadow: 0 10px 30px rgba(0,0,0,0.25);
      font-weight: 700;
      color: #ffd166;
    }
    .hero .kudos .spark {
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: #ffd166;
      box-shadow: 0 0 12px 3px rgba(255, 209, 102, 0.6);
      animation: glow 1.8s ease-in-out infinite alternate;
    }
    @keyframes glow {
      from { opacity: 0.75; }
      to { opacity: 1; transform: scale(1.08); }
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
      background: linear-gradient(160deg, rgba(16,23,32,0.85), rgba(12,18,28,0.9));
      box-shadow: var(--shadow);
      min-height: 60vh;
      padding: 12px;
      isolation: isolate;
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
      inset: 20px;
      opacity: 0;
      transform: translateY(40px) scale(0.98);
      pointer-events: none;
      transition: opacity 260ms ease, transform 320ms ease, filter 320ms ease;
      background: radial-gradient(circle at 12% 10%, rgba(255, 209, 102, 0.12), transparent 38%), radial-gradient(circle at 90% 15%, rgba(124, 183, 255, 0.16), transparent 42%), linear-gradient(180deg, rgba(16,23,32,0.94), rgba(19,29,44,0.9));
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 22px;
      box-shadow: var(--shadow);
      overflow: hidden;
      filter: drop-shadow(0 20px 40px rgba(0,0,0,0.35));
    }
    .slides-stage .slide.active {
      position: relative;
      opacity: 1;
      transform: translateY(0) scale(1);
      pointer-events: auto;
      z-index: 2;
      animation: slideIn 520ms ease;
    }
    .slides-stage .slide.leaving {
      opacity: 0;
      transform: translateY(-20px) scale(0.96);
      pointer-events: none;
      filter: blur(2px);
      transition: opacity 240ms ease, transform 240ms ease, filter 240ms ease;
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
    .slide:focus {
      outline: 2px solid var(--accent);
      outline-offset: 4px;
    }
    .slide-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 12px;
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
      gap: 8px;
      margin: 12px 0 0;
    }
    .card {
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px;
    }
    .muted { color: var(--muted); }
    .two-col {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 12px;
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
      background: linear-gradient(90deg, rgba(255, 209, 102, 0.25), rgba(124, 183, 255, 0.18));
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
      animation: sparkFly 900ms ease-out forwards;
    }
    @keyframes sparkFly {
      0% { transform: translate(0,0) scale(1); opacity: 1; }
      70% { opacity: 1; }
      100% { transform: translate(var(--dx), var(--dy)) scale(0.2); opacity: 0; }
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
    @media (max-width: 960px) {
      .page { grid-template-columns: 1fr; }
      header.hero { grid-column: 1; }
      .control-bar { position: sticky; top: 0; }
    }
  </style>
</head>
<body>
  <div class="page-shell scanlines">
    <div class="film-grain"></div>
    <div class="page">
      <header class="hero">
        <div class="subline">Kickback Kingdom · Annual Awards</div>
        <h1>Atlas Archive · Year in Review</h1>
        <p>Thank you, Realmwalkers, for an incredible year. Tonight we open the vault, spotlight our heroes, and celebrate the stories that shaped the Kingdom.</p>
        <div class="kudos"><span class="spark"></span> <span>Presented with gratitude to our community</span></div>
      </header>

      <main class="content">
        <div class="hud">
          <div class="hud-left">
            <span>Atlas Checkpoint</span>
            <span class="pill">2025</span>
          </div>
          <div class="hud-dots" id="dot-nav" aria-label="Slide navigation"></div>
          <div class="hud-right">
            <button id="immersive-toggle" class="hud-icon" title="Fullscreen">⛶</button>
            <button id="mute-toggle" class="hud-icon" title="Mute">🔈</button>
          </div>
        </div>
        <div id="slides" class="slides-stage"></div>
        <div class="control-bar" aria-label="Slide controls">
          <span id="counter" class="muted" aria-live="polite">Slide 1/1</span>
          <div style="margin-left:auto; display:flex; gap:8px;">
            <button id="prev-btn">◀</button>
            <button id="next-btn">▶</button>
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
        id: "opening",
        title: "Opening Ceremony",
        render: () => `
          <div class="two-col">
            <div class="card accent-bg-gold">
              <div class="ribbon">Grand Opening</div>
              <div class="kpi">ATLAS ARCHIVE</div>
              <p class="muted">Thank you for an epic year of quests, guild triumphs, and countless nights in the Realm.</p>
              <div class="actions">
                <button class="cta-primary" data-action="celebrate">Celebrate</button>
                <button class="cta-primary" data-action="next">Begin</button>
              </div>
            </div>
            <div class="card accent-bg-cyan">
              <div class="ribbon">Award Show Vibes</div>
              <p class="muted">Curtains up. The spotlight is yours. Expect applause, gold confetti, and the loudest horns in the Kingdom.</p>
              <div class="actions">
                <button class="cta-primary" data-action="fullscreen">Go Fullscreen</button>
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
            <div class="stat-grid">
              <div class="stat"><label>Active Accounts</label><strong>${activeAccounts}</strong></div>
              <div class="stat"><label>Realm Uptime</label><strong>${uptime}</strong></div>
              <div class="stat"><label>Guilds Tracked</label><strong>${data.guilds.length}</strong></div>
              <div class="stat"><label>Ledger Streams</label><strong>${data.ledgers.length}</strong></div>
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
            <div class="spotlight">
              <div class="card">
                <div class="ribbon">Total Play Hours</div>
                <p class="kpi">${hours.toLocaleString()}</p>
                <p class="sub">Time spent defending, crafting, and celebrating together.</p>
              </div>
              <div class="card">
                <div class="ribbon">Quests Completed</div>
                <p class="kpi">${quests.toLocaleString()}</p>
                <p class="sub">From Emberwood to the Frontier, every quest logged in the ledger.</p>
              </div>
              <div class="card">
                <div class="ribbon">Allies Formed</div>
                <p class="kpi">8,420</p>
                <p class="sub">Party invitations accepted, friendships forged.</p>
              </div>
              <div class="card">
                <div class="ribbon">Events Hosted</div>
                <p class="kpi">${data.events.length * 4}</p>
                <p class="sub">From festivals to sieges — every gathering a memory.</p>
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
          <div class="stat-grid">
            ${data.activity.map(item => `
              <div class="stat">
                <label>${item.month}</label>
                <strong>${item.active} active</strong>
                <span class="muted">${item.returning} returning</span>
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
          <div class="list">
            ${data.guilds.map(g => `
              <div class="card">
                <div class="slide-title">
                  <span class="pill">Guild</span>
                  <strong>${g.name}</strong>
                </div>
                <p class="muted">Members: ${g.members} · Completions: ${g.completions} · Growth: ${g.growth}</p>
                <div>${g.tags.map(t => `<span class="tag">${t}</span>`).join("")}</div>
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
          <div class="two-col">
            <div class="card">
              <div class="pill">Ledgers</div>
              ${data.ledgers.map(l => `
                <p><strong>${l.description}</strong><br><span class="muted">${l.total.toLocaleString()} total · ${l.delta}</span></p>
              `).join("")}
            </div>
            <div class="card">
              <div class="pill">Recent Transactions</div>
              ${data.transactions.map(tx => `
                <p><strong>${tx.type}</strong> with ${tx.counterparty}<br><span class="muted">${tx.amount.toLocaleString()} value</span></p>
              `).join("")}
            </div>
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
          <div class="list">
            ${data.servers.map(s => `
              <div class="card">
                <div class="slide-title">
                  <span class="pill">Server</span>
                  <strong>${s.name}</strong>
                </div>
                <p class="muted">Uptime: ${s.uptime}</p>
                <p>Hotspots: ${s.hotspots.join(", ")}</p>
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
          <div class="timeline">
            ${data.events.map(ev => `
              <div class="card">
                <div class="pill">${ev.date}</div>
                <strong>${ev.title}</strong>
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
            <div class="two-col">
              <div class="stat">
                <label>${acct.name}</label>
                <strong>${acct.class}</strong>
                <p class="muted">Joined ${acct.joinDate} · ${acct.level} • ${acct.reputation}</p>
                <p class="muted">Last login: ${new Date(acct.lastLogin).toLocaleDateString()} · Streak: ${acct.streakDays} days</p>
              </div>
              <div class="stat">
                <label>Completions</label>
                <strong>${acct.questsCompleted} quests</strong>
                <p class="muted">${acct.tasksCompleted} tasks logged</p>
                <p class="muted">Guilds: ${acct.guilds.join(", ")}</p>
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
            <div class="two-col">
              <div class="card">
                <div class="pill">Best Ally</div>
                <strong>${best.name}</strong>
                <p class="muted">${best.shared} shared quests · ${Math.round(best.successRate * 100)}% success</p>
                <p class="muted">Last quested: ${best.last}</p>
              </div>
              <div class="card">
                <div class="pill">Party Chemistry</div>
                <p><strong>${reliableTrio}</strong></p>
                <p class="muted">Highest synergy trio by shared completions.</p>
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
            <div class="two-col">
              <div class="card">
                <div class="pill">Favorite Mode</div>
                <strong>${favoriteMode?.mode ?? "—"}</strong>
                <p class="muted">${favoriteMode?.attempts ?? 0} runs · ${favoriteMode ? Math.round((favoriteMode.wins / favoriteMode.attempts) * 100) : 0}% success</p>
              </div>
              <div class="card">
                <div class="pill">Best Game</div>
                <strong>${bestMatch?.label ?? "—"}</strong>
                <p class="muted">Peak ELO: ${bestMatch?.elo ?? "—"} · ${bestMatch?.result ?? ""} · ${bestMatch?.date ?? ""}</p>
              </div>
              <div class="card">
                <div class="pill">Worst Game</div>
                <strong>${worstMatch?.label ?? "—"}</strong>
                <p class="muted">Low ELO: ${worstMatch?.elo ?? "—"} · ${worstMatch?.result ?? ""} · ${worstMatch?.date ?? ""}</p>
              </div>
              <div class="card">
                <div class="pill">ELO Trend</div>
                <p class="kpi">${acct.eloHistory.slice(-1)[0]}</p>
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
          <div class="stat-grid">
            <div class="stat">
              <label>Focus</label>
              <strong>Expand Guild Raids</strong>
              <p class="muted">Continue momentum with high success duo/trio runs.</p>
            </div>
            <div class="stat">
              <label>Skill Track</label>
              <strong>Refine Arena Play</strong>
              <p class="muted">Targeted practice to lift lower-ELO arenas.</p>
            </div>
            <div class="stat">
              <label>Community</label>
              <strong>Mentor New Archivists</strong>
              <p class="muted">Share builds and ledgers with newer accounts.</p>
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
    const immersiveToggle = document.getElementById("immersive-toggle");
    const muteToggle = document.getElementById("mute-toggle");

    let activeIndex = 0;
    let isMuted = false;
    const sectionRefs = [];
    const dotRefs = [];

    function createSlideSection(slide, index) {
      const section = document.createElement("section");
      section.className = "slide";
      section.id = slide.id;
      section.tabIndex = -1;
      section.dataset.index = index;
      section.innerHTML = `
        <div class="slide-header">
          <div class="slide-title">
            <span class="pill">Archive Node</span>
            <h2>${slide.title}</h2>
          </div>
          <button class="copy-link" data-slide="${slide.id}">Copy link</button>
        </div>
        <div class="slide-body">${slide.render(fakeData)}</div>
      `;
      slidesContainer.appendChild(section);
      sectionRefs.push(section);

      const dot = document.createElement("button");
      dot.className = "hud-dot";
      dot.setAttribute("aria-label", `Go to ${slide.title}`);
      dot.addEventListener("click", () => setActiveSlide(index, { scroll: true }));
      dotNav.appendChild(dot);
      dotRefs.push(dot);
    }

    function updateStageHeight(target) {
      if (!target) return;
      const stage = slidesContainer;
      stage.style.height = `${target.offsetHeight + 40}px`;
    }

    function setActiveSlide(index, opts = { scroll: false, updateHash: true }) {
      activeIndex = Math.max(0, Math.min(index, slides.length - 1));
      const target = sectionRefs[activeIndex];
      if (!target) return;

      counter.textContent = `Slide ${activeIndex + 1} / ${slides.length}`;
      prevBtn.disabled = activeIndex === 0;
      nextBtn.disabled = activeIndex === slides.length - 1;

      dotRefs.forEach((dot, idx) => {
        dot.classList.toggle("active", idx === activeIndex);
      });

      sectionRefs.forEach((section, idx) => {
        if (idx === activeIndex) {
          section.classList.add("active");
          section.classList.remove("leaving");
          section.setAttribute("aria-hidden", "false");
          const accent = slides[activeIndex].accent;
          if (accent) section.classList.add(accent);
        } else {
          const accent = slides[idx].accent;
          if (accent) section.classList.remove(accent);
          if (section.classList.contains("active")) {
            section.classList.add("leaving");
            setTimeout(() => section.classList.remove("leaving"), 260);
          }
          section.classList.remove("active");
          section.setAttribute("aria-hidden", "true");
        }
      });

      updateStageHeight(target);

      if (opts.updateHash) {
        const newUrl = `${window.location.pathname}#${slides[activeIndex].id}`;
        history.replaceState(null, "", newUrl);
      }
    }

    slides.forEach(createSlideSection);
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

    immersiveToggle.addEventListener("click", () => {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
      } else {
        document.exitFullscreen().catch(() => {});
      }
    });

    muteToggle.addEventListener("click", () => {
      isMuted = !isMuted;
      muteToggle.textContent = isMuted ? "🔇" : "🔈";
    });

    function triggerCelebration() {
      const bounds = slidesContainer.getBoundingClientRect();
      const count = 18;
      for (let i = 0; i < count; i++) {
        const spark = document.createElement("div");
        spark.className = "spark";
        const dx = (Math.random() * 200 - 100) + "px";
        const dy = (Math.random() * 260 - 80) + "px";
        spark.style.setProperty("--dx", dx);
        spark.style.setProperty("--dy", dy);
        spark.style.left = (bounds.width / 2) + "px";
        spark.style.top = (bounds.height / 2) + "px";
        spark.style.background = ["#ffd166", "#7cb7ff", "#ff7edb", "#6cf0c2"][i % 4];
        slidesContainer.appendChild(spark);
        setTimeout(() => spark.remove(), 900);
      }
    }

    slidesContainer.addEventListener("click", (e) => {
      const target = e.target;
      if (target.matches(".cta-primary[data-action='celebrate']")) {
        triggerCelebration();
      }
      if (target.matches(".cta-primary[data-action='next']")) {
        setActiveSlide(activeIndex + 1, { scroll: true });
      }
      if (target.matches(".cta-primary[data-action='fullscreen']")) {
        immersiveToggle.click();
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

    window.addEventListener("resize", () => {
      const target = sectionRefs[activeIndex];
      updateStageHeight(target);
    });
    updateStageHeight(sectionRefs[0]);
  </script>
</body>
</html>
