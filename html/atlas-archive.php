<?php
// Kickback Kingdom - Atlas Archive (POC)
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Atlas Archive - Yearly Review (POC)</title>
  <style>
    :root {
      --bg: #0b0f14;
      --card: #101720;
      --card-strong: #162231;
      --text: #e8f0ff;
      --muted: #9bb0c6;
      --accent: #6cf0c2;
      --accent-2: #7cb7ff;
      --border: #1f2c3c;
      --shadow: 0 20px 50px rgba(0,0,0,0.45);
      --mono: "IBM Plex Mono", Menlo, Monaco, Consolas, monospace;
      --sans: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: var(--sans);
      background: radial-gradient(circle at 20% 20%, rgba(108, 240, 194, 0.05), transparent 30%), radial-gradient(circle at 80% 10%, rgba(124, 183, 255, 0.05), transparent 25%), var(--bg);
      color: var(--text);
    }
    a { color: var(--accent-2); }
    .page {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 18px;
      padding: 28px;
    }
    header {
      grid-column: 1 / span 2;
      background: linear-gradient(135deg, rgba(108, 240, 194, 0.08), rgba(124, 183, 255, 0.08));
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 18px 20px;
      box-shadow: var(--shadow);
    }
    header h1 {
      margin: 0 0 6px;
      font-weight: 700;
      letter-spacing: -0.02em;
    }
    header p {
      margin: 0;
      color: var(--muted);
      max-width: 960px;
    }
    .nav {
      position: sticky;
      top: 16px;
      align-self: start;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 12px;
      box-shadow: var(--shadow);
      max-height: calc(100vh - 48px);
      overflow: auto;
    }
    .nav h3 {
      margin: 6px 0 12px;
      font-size: 14px;
      letter-spacing: 0.08em;
      color: var(--muted);
      text-transform: uppercase;
    }
    .nav ul {
      list-style: none;
      padding: 0;
      margin: 0;
      display: grid;
      gap: 6px;
    }
    .nav button {
      width: 100%;
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--text);
      padding: 10px 12px;
      text-align: left;
      cursor: pointer;
      font-weight: 600;
      transition: border-color 120ms ease, background 120ms ease, transform 120ms ease;
    }
    .nav button:hover {
      border-color: var(--accent-2);
      transform: translateX(2px);
    }
    .nav button.active {
      background: rgba(108, 240, 194, 0.08);
      border-color: var(--accent);
      box-shadow: 0 0 0 1px rgba(108,240,194,0.25) inset;
    }
    .content {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .control-bar {
      background: var(--card);
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
      background: var(--card-strong);
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
    .slide {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 18px;
      box-shadow: var(--shadow);
      scroll-margin-top: 90px;
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
      color: var(--muted);
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
      background: var(--card-strong);
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
    .list, .timeline {
      display: grid;
      gap: 8px;
      margin: 12px 0 0;
    }
    .card {
      background: var(--card-strong);
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
    @media (max-width: 960px) {
      .page { grid-template-columns: 1fr; }
      header { grid-column: 1; }
      .nav { position: relative; top: 0; max-height: none; }
      .control-bar { position: sticky; top: 0; }
    }
  </style>
</head>
<body>
  <div class="page">
    <header>
      <h1>Atlas Archive · Yearly Review (Prototype)</h1>
      <p>An in-world archival slideshow with deep links, shareable slides, and reusable slide definitions. This proof of concept uses inline mock data shaped like the live classes and database objects.</p>
    </header>

    <aside class="nav">
      <h3>Atlas Nodes</h3>
      <ul id="slide-nav"></ul>
    </aside>

    <main class="content">
      <div class="control-bar">
        <button id="prev-btn">◀ Prev</button>
        <button id="next-btn">Next ▶</button>
        <span id="counter" class="muted" aria-live="polite">Slide 1/1</span>
        <select id="jump-select" aria-label="Jump to slide"></select>
      </div>
      <div id="slides"></div>
    </main>
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
    const navList = document.getElementById("slide-nav");
    const jumpSelect = document.getElementById("jump-select");
    const prevBtn = document.getElementById("prev-btn");
    const nextBtn = document.getElementById("next-btn");
    const counter = document.getElementById("counter");

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

      const navItem = document.createElement("li");
      const navBtn = document.createElement("button");
      navBtn.textContent = slide.title;
      navBtn.addEventListener("click", () => setActiveSlide(index, { scroll: true }));
      navItem.appendChild(navBtn);
      navList.appendChild(navItem);

      const option = document.createElement("option");
      option.value = index;
      option.textContent = `${index + 1}. ${slide.title}`;
      jumpSelect.appendChild(option);
    }

    function setActiveSlide(index, opts = { scroll: false, updateHash: true }) {
      activeIndex = Math.max(0, Math.min(index, slides.length - 1));
      const target = sectionRefs[activeIndex];
      if (!target) return;

      navList.querySelectorAll("button").forEach(btn => btn.classList.remove("active"));
      const navButton = navList.querySelectorAll("button")[activeIndex];
      if (navButton) navButton.classList.add("active");

      jumpSelect.value = String(activeIndex);
      counter.textContent = `Slide ${activeIndex + 1} / ${slides.length}`;
      prevBtn.disabled = activeIndex === 0;
      nextBtn.disabled = activeIndex === slides.length - 1;

      if (opts.scroll) {
        target.focus({ preventScroll: true });
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      }

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

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const idx = Number(entry.target.dataset.index);
          if (idx !== activeIndex) setActiveSlide(idx, { scroll: false });
        }
      });
    }, { threshold: 0.55 });

    sectionRefs.forEach(section => observer.observe(section));

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
  </script>
</body>
</html>
