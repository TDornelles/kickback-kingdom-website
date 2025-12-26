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
      gap: 18px;
      padding: 28px 28px 64px;
    }
    header.hero {
      grid-column: 1;
      background: linear-gradient(145deg, rgba(255, 209, 102, 0.18), rgba(108, 240, 194, 0.14), rgba(124, 183, 255, 0.16));
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 26px 26px 30px;
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
      gap: 16px;
    }
    .control-bar {
      background: linear-gradient(90deg, rgba(19,29,44,0.95), rgba(12,18,28,0.98));
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: var(--shadow);
      position: sticky;
      top: 16px;
      z-index: 4;
    }
    .control-bar select, .control-bar button {
      background: rgba(255,255,255,0.02);
      color: var(--text);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 8px 10px;
      font-weight: 600;
      cursor: pointer;
    }
    .control-bar button:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }
    .immersive {
      margin-left: auto;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .slides-stage {
      position: relative;
      overflow: hidden;
      border-radius: 18px;
      border: 1px solid var(--border);
      background: linear-gradient(160deg, rgba(16,23,32,0.85), rgba(12,18,28,0.9));
      box-shadow: var(--shadow);
      min-height: 320px;
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
      inset: 12px;
      opacity: 0;
      transform: translateX(60px) scale(0.98);
      pointer-events: none;
      transition: opacity 260ms ease, transform 320ms ease;
      background: linear-gradient(180deg, rgba(16,23,32,0.94), rgba(19,29,44,0.9));
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 18px;
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    .slides-stage .slide.active {
      position: relative;
      opacity: 1;
      transform: translateX(0) scale(1);
      pointer-events: auto;
      z-index: 2;
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
      background: var(--card-strong);
      color: var(--text);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 8px 10px;
      cursor: pointer;
      font-weight: 600;
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
      font-size: 28px;
      font-weight: 700;
      color: var(--accent);
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
        <div class="control-bar">
          <button id="prev-btn">◀ Prev</button>
          <button id="next-btn">Next ▶</button>
          <span id="counter" class="muted" aria-live="polite">Slide 1/1</span>
          <select id="jump-select" aria-label="Jump to slide"></select>
          <button id="immersive-toggle" class="immersive">⛶ Immersive</button>
        </div>
        <div id="slides" class="slides-stage"></div>
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
            <div class="card">
              <div class="ribbon">Thank You, Kickback Kingdom</div>
              <p class="muted">A year of quests, guild triumphs, and countless nights in the Realm. This archive is our love letter to everyone who journeyed with us.</p>
              <p><strong>Press Next</strong> or use arrow keys to advance. Copy any slide link to share highlights.</p>
            </div>
            <div class="card">
              <div class="ribbon">Award Show Vibes</div>
              <p class="muted">Curtains up. The spotlight is yours. Expect applause, gold confetti, and the loudest horns in the Kingdom.</p>
            </div>
          </div>
        `
      },
      {
        id: "world-status",
        title: "World Status",
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
          `;
        }
      },
      {
        id: "victory-lap",
        title: "Community Victory Lap",
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
          `;
        }
      },
      {
        id: "population",
        title: "Population & Activity",
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
        `
      },
      {
        id: "guild-atlas",
        title: "Guild Atlas",
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
        `
      },
      {
        id: "economy",
        title: "Economy Ledger",
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
        `
      },
      {
        id: "systems",
        title: "Systems Utilization",
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
        `
      },
      {
        id: "events",
        title: "Notable Events Timeline",
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
        `
      },
      {
        id: "account-spotlight",
        title: "Account Spotlight",
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
          `;
        }
      },
      {
        id: "best-friend",
        title: "Best Friend / Frequent Ally",
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
          `;
        }
      },
      {
        id: "games",
        title: "Favorite, Best, and Worst Games",
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
          `;
        }
      },
      {
        id: "outlook",
        title: "Forward Outlook",
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
        `
      }
    ];

    const slidesContainer = document.getElementById("slides");
    const jumpSelect = document.getElementById("jump-select");
    const prevBtn = document.getElementById("prev-btn");
    const nextBtn = document.getElementById("next-btn");
    const counter = document.getElementById("counter");
    const immersiveToggle = document.getElementById("immersive-toggle");

    let activeIndex = 0;
    const sectionRefs = [];

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

      const option = document.createElement("option");
      option.value = index;
      option.textContent = `${index + 1}. ${slide.title}`;
      jumpSelect.appendChild(option);
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

      jumpSelect.value = String(activeIndex);
      counter.textContent = `Slide ${activeIndex + 1} / ${slides.length}`;
      prevBtn.disabled = activeIndex === 0;
      nextBtn.disabled = activeIndex === slides.length - 1;

      sectionRefs.forEach((section, idx) => {
        if (idx === activeIndex) {
          section.classList.add("active");
          section.setAttribute("aria-hidden", "false");
        } else {
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
    jumpSelect.addEventListener("change", (e) => setActiveSlide(Number(e.target.value), { scroll: true }));

    document.addEventListener("keydown", (e) => {
      if (e.key === "ArrowLeft") {
        setActiveSlide(activeIndex - 1, { scroll: true });
      } else if (e.key === "ArrowRight") {
        setActiveSlide(activeIndex + 1, { scroll: true });
      } else if (e.key === " ") {
        e.preventDefault();
        setActiveSlide(activeIndex + 1, { scroll: true });
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

    window.addEventListener("resize", () => {
      const target = sectionRefs[activeIndex];
      updateStageHeight(target);
    });
    updateStageHeight(sectionRefs[0]);
  </script>
</body>
</html>
