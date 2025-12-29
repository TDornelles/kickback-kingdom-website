<?php
// Kickback Kingdom - Atlas Archive (POC with immersive award-show styling)
$requestedAtlasYear = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 3000]]);
$atlasYear = $requestedAtlasYear === false || $requestedAtlasYear === null ? 2026 : $requestedAtlasYear;
$pageTitle = "Atlas Archive {$atlasYear} - Yearly Review (POC)";
$pageImage = "https://kickback-kingdom.com/assets/media/context/loading.gif";
$pageDesc = "Yearly recap for Kickback Kingdom adventurers in {$atlasYear}.";
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Services\Database;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Views\vRecordId;

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require(\Kickback\SCRIPT_ROOT . "/php-components/base-page-pull-active-account-info.php");

$atlasYearStart = sprintf('%04d-01-01', $atlasYear);
$atlasYearEnd = sprintf('%04d-01-01', $atlasYear + 1);
$previousYearStart = sprintf('%04d-01-01', $atlasYear - 1);
$twoYearsBackStart = sprintf('%04d-01-01', $atlasYear - 2);
$accountCountEndOfDataYear = atlas_fetch_scalar('SELECT COUNT(*) FROM account WHERE DateCreated < ?', [$atlasYearStart]);
$accountCountEndOfPriorYear = atlas_fetch_scalar('SELECT COUNT(*) FROM account WHERE DateCreated < ?', [$previousYearStart]);
$questsHostedPreviousYear = atlas_fetch_scalar(
    'SELECT COUNT(*) FROM quest WHERE published = 1 and finished = 1 and end_date >= ? AND end_date < ?',
    [$previousYearStart, $atlasYearStart]
);
$questsHostedBeforeYear = atlas_fetch_scalar(
    'SELECT COUNT(*) FROM quest WHERE published = 1 and finished = 1 and end_date < ?',
    [$atlasYearStart]
);
$rankedMatchesPreviousYear = atlas_fetch_scalar(
    'SELECT COUNT(*) FROM game_match WHERE `set` IN (0,1) AND Date >= ? AND Date < ?',
    [$previousYearStart, $atlasYearStart]
);
$rankedMatchesBeforeYear = atlas_fetch_scalar(
    'SELECT COUNT(*) FROM game_match WHERE `set` IN (0,1) AND Date < ?',
    [$atlasYearStart]
);
$newAccountsPreviousYear = null;
if ($accountCountEndOfDataYear !== null && $accountCountEndOfPriorYear !== null) {
    $newAccountsPreviousYear = $accountCountEndOfDataYear - $accountCountEndOfPriorYear;
}

/**
 * Safely fetch a single scalar from the database.
 *
 * @param string $query
 * @param array<int|string, mixed> $params
 * @param string|null $cast
 */
function atlas_fetch_scalar(string $query, array $params = [], ?string $cast = 'int') : mixed
{
    try {
        $result = Database::executeSqlQuery($query, $params);

        if ($result instanceof \mysqli_result) {
            $row = $result->fetch_row();
            if ($row !== null && array_key_exists(0, $row)) {
                $value = $row[0];
                if ($value === null) {
                    return null;
                }

                return match ($cast) {
                    'float' => (float)$value,
                    'string' => (string)$value,
                    default => (int)$value,
                };
            }
        }
    } catch (\Throwable $e) {
        // If something fails (ex: local dev without DB), we degrade gracefully to null.
    }

    return null;
}

/**
 * Fetch a single associative row.
 *
 * @param string $query
 * @param array<int|string, mixed> $params
 */
function atlas_fetch_one(string $query, array $params = []) : ?array
{
    try {
        $result = Database::executeSqlQuery($query, $params);
        if ($result instanceof \mysqli_result) {
            $row = $result->fetch_assoc();
            return $row !== null ? $row : null;
        }
    } catch (\Throwable $e) {
        // Gracefully degrade to null.
    }

    return null;
}

/**
 * Fetch all rows as associative arrays.
 *
 * @param string $query
 * @param array<int|string, mixed> $params
 * @return array<int, array<string, mixed>>
 */
function atlas_fetch_all(string $query, array $params = []) : array
{
    try {
        $result = Database::executeSqlQuery($query, $params);
        if ($result instanceof \mysqli_result) {
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            return $rows;
        }
    } catch (\Throwable $e) {
        // Gracefully degrade to an empty set.
    }

    return [];
}

/**
 * Fetch a lightweight account profile with avatar and level.
 */
function atlas_fetch_account_profile(int $accountId) : ?array
{
    try {
        $resp = AccountController::getAccountById(new vRecordId('', $accountId));
        if ($resp->success && $resp->data) {
            $account = $resp->data;
            return [
                'id' => (int)$account->crand,
                'username' => (string)$account->username,
                'avatar' => $account->profilePictureURL(),
                'level' => isset($account->level) ? (int)$account->level : null,
                'prestige' => isset($account->prestige) ? (int)$account->prestige : null,
                'badges' => isset($account->badges) ? (int)$account->badges : null,
            ];
        }
    } catch (\Throwable $e) {
        // Gracefully degrade to a lightweight fallback query.
    }

    $row = atlas_fetch_one(
        'SELECT Id, Username, avatar_media, level, prestige, badges FROM v_account_info WHERE Id = ? LIMIT 1',
        [$accountId]
    );

    if (!$row) {
        return null;
    }

    return [
        'id' => isset($row['Id']) ? (int)$row['Id'] : $accountId,
        'username' => (string)$row['Username'],
        'avatar' => $row['avatar_media'] ?? null,
        'level' => isset($row['level']) ? (int)$row['level'] : null,
        'prestige' => isset($row['prestige']) ? (int)$row['prestige'] : null,
        'badges' => isset($row['badges']) ? (int)$row['badges'] : null,
    ];
}

$worldStats = [
    'accounts' => $accountCountEndOfDataYear,
    'guildsmen' => $accountCountEndOfDataYear,
    'games' => atlas_fetch_scalar('SELECT COUNT(*) FROM game'),
    'matches' => atlas_fetch_scalar('SELECT COUNT(*) FROM game_match'),
    'rankedMatches' => atlas_fetch_scalar('SELECT COUNT(*) FROM game_match WHERE `set` IN (0,1)'),
    'records' => atlas_fetch_scalar('SELECT COUNT(*) FROM game_record'),
    'questsPublished' => atlas_fetch_scalar('SELECT COUNT(*) FROM quest WHERE published = 1'),
    'questsRanPriorYear' => atlas_fetch_scalar(
        'SELECT COUNT(*) FROM quest WHERE finished = 1 AND end_date >= ? AND end_date < ?',
        [$previousYearStart, $atlasYearStart]
    ),
    'questsHostedPreviousYear' => $questsHostedPreviousYear,
    'questsHostedBeforeYear' => $questsHostedBeforeYear,
    'rankedMatchesPreviousYear' => $rankedMatchesPreviousYear,
    'rankedMatchesBeforeYear' => $rankedMatchesBeforeYear,
    'accountsCreatedPreviousYear' => $newAccountsPreviousYear,
    'questLines' => atlas_fetch_scalar('SELECT COUNT(*) FROM quest_line'),
    'questApplicants' => atlas_fetch_scalar('SELECT COUNT(*) FROM quest_applicants'),
    'questParticipants' => atlas_fetch_scalar('SELECT COUNT(*) FROM quest_applicants WHERE participated = 1'),
    'lootMinted' => atlas_fetch_scalar('SELECT COUNT(*) FROM loot'),
    'items' => atlas_fetch_scalar('SELECT COUNT(*) FROM item'),
    'trades' => atlas_fetch_scalar('SELECT COUNT(*) FROM trade'),
    'transactions' => atlas_fetch_scalar('SELECT COUNT(*) FROM `transaction`'),
    'shareholders' => atlas_fetch_scalar('SELECT COUNT(DISTINCT AccountId) FROM share_purchase'),
    'sharesSold' => atlas_fetch_scalar('SELECT COALESCE(SUM(SharesPurchased),0) FROM share_purchase', [], 'float'),
    'ticketsOpened' => atlas_fetch_scalar('SELECT COUNT(*) FROM ticket'),
    'ticketsClosed' => atlas_fetch_scalar('SELECT COUNT(*) FROM ticket WHERE resolved_at IS NOT NULL OR closed_at IS NOT NULL'),
    'analyticsEvents' => atlas_fetch_scalar('SELECT COUNT(*) FROM analytic')
];

$yearProgress = [
    'accounts' => [
        'label' => 'Adventurers Registered',
        'start' => $accountCountEndOfPriorYear,
        'end' => $accountCountEndOfDataYear,
    ],
    'quests' => [
        'label' => 'Published Quests',
        'start' => atlas_fetch_scalar(
            'SELECT COUNT(*) FROM quest WHERE published = 1 AND end_date >= ? AND end_date < ?',
            [$twoYearsBackStart, $previousYearStart]
        ),
        'end' => atlas_fetch_scalar(
            'SELECT COUNT(*) FROM quest WHERE published = 1 AND end_date >= ? AND end_date < ?',
            [$previousYearStart, $atlasYearStart]
        ),
    ],
    'matches' => [
        'label' => 'Matches Logged',
        'start' => atlas_fetch_scalar(
            'SELECT COUNT(*) FROM game_match WHERE Date >= ? AND Date < ?',
            [$twoYearsBackStart, $previousYearStart]
        ),
        'end' => atlas_fetch_scalar(
            'SELECT COUNT(*) FROM game_match WHERE Date >= ? AND Date < ?',
            [$previousYearStart, $atlasYearStart]
        ),
    ],
    'transactions' => [
        'label' => 'Store Transactions',
        'start' => atlas_fetch_scalar(
            'SELECT COUNT(*) FROM `transaction` WHERE ctime >= ? AND ctime < ?',
            [$twoYearsBackStart, $previousYearStart]
        ),
        'end' => atlas_fetch_scalar(
            'SELECT COUNT(*) FROM `transaction` WHERE ctime >= ? AND ctime < ?',
            [$previousYearStart, $atlasYearStart]
        ),
    ],
];

$honors = [
    'tournaments' => null,
    'prestige' => null,
    'quester' => null,
    'host' => null,
    'renown' => null,
    'kingOfGames' => null,
];

$honorsPeriodStart = $previousYearStart;
$honorsPeriodEnd = $atlasYearStart;

$tournamentRow = atlas_fetch_one(
    'SELECT tr.team_captain AS account_id, COUNT(*) AS tournaments_won, COUNT(DISTINCT t.game_id) AS games_played
     FROM tournament_record tr
     INNER JOIN tournament t ON tr.tournament_id = t.Id
     WHERE tr.win = 1 AND tr.team_captain IS NOT NULL AND t.Date >= ? AND t.Date < ?
     GROUP BY tr.team_captain
     ORDER BY tournaments_won DESC, games_played DESC
     LIMIT 1',
    [$honorsPeriodStart, $honorsPeriodEnd]
);
if (!empty($tournamentRow['account_id'])) {
    $profile = atlas_fetch_account_profile((int)$tournamentRow['account_id']);
    if ($profile) {
        $honors['tournaments'] = [
            'profile' => $profile,
            'tournamentsWon' => (int)$tournamentRow['tournaments_won'],
            'gamesCount' => (int)$tournamentRow['games_played'],
        ];
    }
}

$prestigeRow = atlas_fetch_one(
    'SELECT account_id_to AS account_id,
            SUM(CASE WHEN commend = 1 THEN 1 ELSE -1 END) AS net_prestige,
            COUNT(DISTINCT account_id_from) AS unique_givers
     FROM prestige
     WHERE date >= ? AND date < ?
     GROUP BY account_id_to
     ORDER BY net_prestige DESC, unique_givers DESC
     LIMIT 1',
    [$honorsPeriodStart, $honorsPeriodEnd]
);
if (!empty($prestigeRow['account_id'])) {
    $profile = atlas_fetch_account_profile((int)$prestigeRow['account_id']);
    if ($profile) {
        $honors['prestige'] = [
            'profile' => $profile,
            'netPrestige' => (int)$prestigeRow['net_prestige'],
            'uniqueGivers' => (int)$prestigeRow['unique_givers'],
        ];
    }
}

$questerRow = atlas_fetch_one(
    'SELECT qa.account_id AS account_id, COUNT(*) AS quests_participated
     FROM quest_applicants qa
     INNER JOIN quest q ON qa.quest_id = q.Id
     WHERE qa.participated = 1 AND q.end_date >= ? AND q.end_date < ?
     GROUP BY qa.account_id
     ORDER BY quests_participated DESC
     LIMIT 1',
    [$honorsPeriodStart, $honorsPeriodEnd]
);
if (!empty($questerRow['account_id'])) {
    $profile = atlas_fetch_account_profile((int)$questerRow['account_id']);
    if ($profile) {
        $honors['quester'] = [
            'profile' => $profile,
            'questsParticipated' => (int)$questerRow['quests_participated'],
        ];
    }
}

$hostRow = atlas_fetch_one(
    'SELECT host_account_id AS account_id,
            COUNT(DISTINCT quest_id) AS quests_hosted,
            AVG(host_rating) AS hosting_score
     FROM (
         SELECT q.Id AS quest_id, q.host_id AS host_account_id, qa.host_rating
         FROM quest q
         LEFT JOIN quest_applicants qa ON qa.quest_id = q.Id
         WHERE q.end_date >= ? AND q.end_date < ? AND q.published = 1 AND q.finished = 1
         UNION ALL
         SELECT q.Id AS quest_id, q.host_id_2 AS host_account_id, qa.host_rating
         FROM quest q
         LEFT JOIN quest_applicants qa ON qa.quest_id = q.Id
         WHERE q.host_id_2 IS NOT NULL AND q.end_date >= ? AND q.end_date < ? AND q.published = 1 AND q.finished = 1
     ) hosted
     GROUP BY host_account_id
     ORDER BY (hosting_score IS NULL), hosting_score DESC, quests_hosted DESC
     LIMIT 1',
    [$honorsPeriodStart, $honorsPeriodEnd, $honorsPeriodStart, $honorsPeriodEnd]
);
if (!empty($hostRow['account_id'])) {
    $profile = atlas_fetch_account_profile((int)$hostRow['account_id']);
    if ($profile) {
        $honors['host'] = [
            'profile' => $profile,
            'questsHosted' => (int)$hostRow['quests_hosted'],
            'hostingScore' => isset($hostRow['hosting_score']) ? (float)$hostRow['hosting_score'] : null,
        ];
    }
}

$renownRow = atlas_fetch_one(
    'SELECT account_id, COUNT(*) AS badge_count
     FROM v_account_badge_info
     WHERE dateObtained >= ? AND dateObtained < ?
     GROUP BY account_id
     ORDER BY badge_count DESC
     LIMIT 1',
    [$honorsPeriodStart, $honorsPeriodEnd]
);
if (!empty($renownRow['account_id'])) {
    $profile = atlas_fetch_account_profile((int)$renownRow['account_id']);
    if ($profile) {
        $badgeRows = atlas_fetch_all(
            'SELECT SmallImgPath
             FROM v_account_badge_info
             WHERE account_id = ? AND dateObtained >= ? AND dateObtained < ?
             ORDER BY dateObtained DESC
             LIMIT 12',
            [$renownRow['account_id'], $honorsPeriodStart, $honorsPeriodEnd]
        );
        $honors['renown'] = [
            'profile' => $profile,
            'badgesEarned' => (int)$renownRow['badge_count'],
            'badgeIcons' => array_values(array_filter(array_map(fn($row) => $row['SmallImgPath'] ?? null, $badgeRows))),
        ];
    }
}

$kingRow = atlas_fetch_one(
    'WITH yearly_players AS (
         SELECT DISTINCT gr.account_id, gr.game_id
         FROM game_record gr
         INNER JOIN game_match gm ON gm.Id = gr.game_match_id
         WHERE gm.Date >= ? AND gm.Date < ?
     ),
     top_ranks AS (
         SELECT v.account_id, v.game_id, v.elo_rating, v.rank
         FROM v_game_elo_rank_info v
         INNER JOIN yearly_players yp ON yp.account_id = v.account_id AND yp.game_id = v.game_id
         WHERE v.rank = 1
     )
     SELECT account_id, COUNT(*) AS gold_cards, SUM(elo_rating) AS elo_sum
     FROM top_ranks
     GROUP BY account_id
     ORDER BY gold_cards DESC, elo_sum DESC
     LIMIT 1',
    [$honorsPeriodStart, $honorsPeriodEnd]
);
if (!empty($kingRow['account_id'])) {
    $profile = atlas_fetch_account_profile((int)$kingRow['account_id']);
    if ($profile) {
        $honors['kingOfGames'] = [
            'profile' => $profile,
            'goldCards' => (int)$kingRow['gold_cards'],
            'eloSum' => isset($kingRow['elo_sum']) ? (float)$kingRow['elo_sum'] : null,
        ];
    }
}

$accountPayload = null;
$atlasAccount = null;

$requestedAccountId = filter_input(INPUT_GET, 'accountId', FILTER_VALIDATE_INT);
$requestedUsername = filter_input(INPUT_GET, 'accountUsername', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$initialTab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if ($requestedAccountId !== null && $requestedAccountId !== false) {
    $resp = AccountController::getAccountById(new vRecordId('', (int)$requestedAccountId));
    if ($resp->success) {
        $atlasAccount = $resp->data;
    }
}

if (is_null($atlasAccount) && !empty($requestedUsername)) {
    $resp = AccountController::getAccountByUsername($requestedUsername);
    if ($resp->success) {
        $atlasAccount = $resp->data;
    }
}

if (is_null($atlasAccount) && !empty($activeAccountInfo->account)) {
    $atlasAccount = $activeAccountInfo->account;
}

if (!is_null($atlasAccount)) {
    $accountId = $atlasAccount->crand;

    $accountStats = [
        'matches' => atlas_fetch_scalar('SELECT COUNT(*) FROM game_record WHERE account_id = ?', [$accountId]),
        'wins' => atlas_fetch_scalar('SELECT COUNT(*) FROM game_record WHERE account_id = ? AND win = 1', [$accountId]),
        'questsHosted' => atlas_fetch_scalar('SELECT COUNT(*) FROM quest WHERE host_id = ? OR host_id_2 = ?', [$accountId, $accountId]),
        'questsJoined' => atlas_fetch_scalar('SELECT COUNT(*) FROM quest_applicants WHERE account_id = ? AND participated = 1', [$accountId]),
        'applications' => atlas_fetch_scalar('SELECT COUNT(*) FROM quest_applicants WHERE account_id = ?', [$accountId]),
        'badges' => atlas_fetch_scalar('SELECT COUNT(*) FROM v_account_badge_info WHERE account_id = ?', [$accountId]),
        'loot' => atlas_fetch_scalar('SELECT COUNT(*) FROM loot WHERE account_id = ?', [$accountId]),
        'containers' => atlas_fetch_scalar('SELECT COUNT(*) FROM loot l INNER JOIN item i ON l.item_id = i.Id WHERE l.account_id = ? AND i.is_container = 1', [$accountId]),
        'trades' => atlas_fetch_scalar('SELECT COUNT(*) FROM trade WHERE from_account_id = ? OR to_account_id = ?', [$accountId, $accountId]),
        'sharePurchases' => atlas_fetch_scalar('SELECT COALESCE(SUM(SharesPurchased),0) FROM share_purchase WHERE AccountId = ?', [$accountId], 'float'),
        'ticketsFiled' => atlas_fetch_scalar('SELECT COUNT(*) FROM ticket WHERE created_by_crand = ?', [$accountId]),
        'ticketsResolved' => atlas_fetch_scalar('SELECT COUNT(*) FROM ticket WHERE created_by_crand = ? AND resolved_at IS NOT NULL', [$accountId]),
    ];

    $winRate = null;
    if (!empty($accountStats['matches'])) {
        $winRate = ($accountStats['wins'] ?? 0) / max($accountStats['matches'], 1);
    }

    $accountPayload = [
        'profile' => [
            'username' => $atlasAccount->username,
            'title' => $atlasAccount->getAccountTitle(),
            'level' => $atlasAccount->level,
            'prestige' => $atlasAccount->prestige,
            'exp' => $atlasAccount->exp,
            'roles' => [
                'admin' => $atlasAccount->isAdmin,
                'merchant' => $atlasAccount->isMerchant,
                'adventurer' => $atlasAccount->isAdventurer,
                'questGiver' => $atlasAccount->isQuestGiver,
                'steward' => $atlasAccount->isSteward,
                'craftsmen' => $atlasAccount->isCraftsmen,
                'artist' => $atlasAccount->isArtist,
            ],
            'links' => [
                'discord' => $atlasAccount->isDiscordLinked(),
                'steam' => $atlasAccount->isSteamLinked(),
            ],
        ],
        'stats' => $accountStats,
        'winRate' => $winRate,
    ];
}

$atlasPayload = [
    'year' => $atlasYear,
    'world' => $worldStats,
    'yearProgress' => $yearProgress,
    'honors' => $honors,
    'account' => $accountPayload,
];
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
    @keyframes sparkFly {
      0% { transform: translate(0, 0) scale(1); opacity: 1; }
      100% { transform: translate(var(--dx), var(--dy)) scale(0.35); opacity: 0; }
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
    const atlasData = <?php echo json_encode($atlasPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const requestedInitialTab = <?php echo json_encode($initialTab); ?>;
    const year = atlasData?.year ?? <?php echo json_encode($atlasYear); ?>;
    const previousYear = (year ?? new Date().getFullYear()) - 1;
    const account = atlasData.account;
    const honors = atlasData?.honors || {};

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
            <div class="mega" style="font-size:32px;">${profile.username}</div>
            <div class="muted">${subtitle || ""}</div>
            <div class="actions">
              <a class="cta-primary" href="${profileUrl}" target="_blank" rel="noopener">Open Profile</a>
            </div>
          </div>
        </div>
      `;
    };
    const renderStatPill = (label, value, detail) => {
      return `
        <div class="stat-pill">
          <div class="label">${label}</div>
          <div class="value">${fmt(value)}</div>
          ${detail ? `<div class="muted">${detail}</div>` : ""}
        </div>
      `;
    };
    const renderBadgeRow = (icons) => {
      if (!icons || icons.length === 0) {
        return `<div class="muted">No badge art recorded for this year.</div>`;
      }
      return `<div class="badge-row">${icons.map((src) => `<img src="${safeAvatar(src) ?? ""}" alt="Badge icon">`).join("")}</div>`;
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
            <div class="actions" style="justify-content:flex-start; margin-top:18px;">
              <button class="cta-primary" data-action="next">Continue</button>
            </div>
          `;
        },
      },
      {
        id: "honors-title",
        title: "Kingdom Highlights",
        accent: "accent-bg-violet",
        hideHeaderTitle: true,
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
        render: () => {
          const entry = honors?.tournaments;
          return `
            <div class="slide-hero">
              <div class="mega">Most Tournaments Won</div>
              <div class="lead">Champion with the most trophies in ${previousYear}. Distinct games played are counted for cross-discipline glory.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Led the bracket in ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Tournaments Won", entry.tournamentsWon, `Finished first in ${previousYear}`)}
                  ${renderStatPill("Games Spanned", entry.gamesCount, "Different titles conquered")}
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
        render: () => {
          const entry = honors?.prestige;
          return `
            <div class="slide-hero">
              <div class="mega">Most Prestigious</div>
              <div class="lead">Highest net prestige earned from unique commendations during ${previousYear}.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Commended the most in ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Net Prestige", entry.netPrestige, "Commends minus denouncements")}
                  ${renderStatPill("Unique Givers", entry.uniqueGivers, "Different accounts who granted prestige")}
                </div>
              ` : `<div class="muted">No prestige activity recorded for ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "honor-quester",
        title: "Biggest Quester",
        accent: "accent-bg-cyan",
        render: () => {
          const entry = honors?.quester;
          return `
            <div class="slide-hero">
              <div class="mega">Biggest Quester</div>
              <div class="lead">Most quest participations for ${previousYear}, using finished quests within the calendar window.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Participated the most in ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Quests Participated", entry.questsParticipated, "Finished quests joined")}
                </div>
              ` : `<div class="muted">No quest participation detected for ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "honor-host",
        title: "Best Host",
        accent: "accent-bg-gold",
        render: () => {
          const entry = honors?.host;
          return `
            <div class="slide-hero">
              <div class="mega">Best Host</div>
              <div class="lead">Highest hosting score from participant feedback on published, finished quests in ${previousYear}.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Host excellence in ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Hosting Score", entry.hostingScore?.toFixed ? Number(entry.hostingScore).toFixed(2) : entry.hostingScore, "Average host rating")}
                  ${renderStatPill("Quests Hosted", entry.questsHosted, "Published & finished in-year")}
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
        render: () => {
          const entry = honors?.renown;
          return `
            <div class="slide-hero">
              <div class="mega">Most Renown</div>
              <div class="lead">Most badges earned during ${previousYear}, showcasing their iconic art.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Badge haul in ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Badges Earned", entry.badgesEarned, `${previousYear} only`)}
                </div>
                ${renderBadgeRow(entry.badgeIcons)}
              ` : `<div class="muted">No badge awards recorded for ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "honor-king-games",
        title: "King of Games",
        accent: "accent-bg-cyan",
        render: () => {
          const entry = honors?.kingOfGames;
          return `
            <div class="slide-hero">
              <div class="mega">King of Games</div>
              <div class="lead">Most gold cards (#1 Elo per game) among accounts active in ${previousYear}; tie-breaks by Elo sum and profile level.</div>
            </div>
            <div class="honor-card">
              ${renderHonorProfile(entry, `Top of the ladders in ${previousYear}`)}
              ${entry ? `
                <div class="honor-stats">
                  ${renderStatPill("Gold Cards Held", entry.goldCards, "Games where they are rank #1")}
                  ${renderStatPill("Elo Sum", entry.eloSum, "Tie-break metric")}
                  ${entry.profile?.level !== undefined ? renderStatPill("Profile Level", entry.profile.level, "Secondary tie-breaker") : ""}
                </div>
              ` : `<div class="muted">No ranked ladders with gold card holders recorded for ${previousYear}.</div>`}
            </div>
          `;
        },
      },
      {
        id: "account-hero",
        title: "Your Atlas Spotlight",
        accent: "accent-bg-gold",
        render: () => {
          if (!account) {
            return `
              <div class="slide-hero">
                <div class="mega">Log in to see your story</div>
                <div class="lead">We&apos;ll pull your AccountController profile, loot, quests, trades, and tickets automatically.</div>
                <div class="actions" style="justify-content:center;">
                  <a class="cta-primary" href="/login.php">Login</a>
                </div>
              </div>
            `;
          }
          const profile = account.profile;
          return `
            <div class="slide-hero">
              <div class="mega">${profile.username}</div>
              <div class="lead">${profile.title} · Level ${fmt(profile.level)} · Prestige ${fmt(profile.prestige)}</div>
            </div>
            <div class="stat-hero">
              <div class="card">
                <div class="pill">EXP</div>
                ${renderKpi(profile.exp)}
                <p class="sub">Progress this year</p>
              </div>
              <div class="card">
                <div class="pill">Roles</div>
                <div class="kpi" style="font-size:22px;">${Object.entries(profile.roles || {}).filter(([,v]) => v).map(([k]) => k).join(" • ") || "—"}</div>
                <p class="sub">Flags from Session & AccountController</p>
              </div>
              <div class="card">
                <div class="pill">Links</div>
                <div class="kpi">${profile.links.discord ? "Discord linked" : "Discord pending"}</div>
                <p class="sub">${profile.links.steam ? "Steam linked" : "Steam pending"}</p>
              </div>
            </div>
          `;
        },
      },
      {
        id: "account-quests",
        title: "Your Quest Journey",
        accent: "accent-bg-cyan",
        render: () => {
          if (!account) {
            return `
              <div class="slide-hero">
                <div class="mega">Quest stats await</div>
                <div class="lead">Host, apply, and participate to populate this page.</div>
              </div>
            `;
          }
          const stats = account.stats || {};
          return `
            <div class="slide-hero">
              <div class="mega">Questing</div>
              <div class="lead">Pulled from quest, quest_applicants, and QuestLineController helpers.</div>
            </div>
            <div class="stat-hero">
              <div class="card">
                <div class="pill">Hosted</div>
                ${renderKpi(stats.questsHosted)}
                <p class="sub">Quest hosts</p>
              </div>
              <div class="card">
                <div class="pill">Applications</div>
                ${renderKpi(stats.applications)}
                <p class="sub">Total submitted</p>
              </div>
              <div class="card">
                <div class="pill">Participated</div>
                ${renderKpi(stats.questsJoined)}
                <p class="sub">Accepted and played</p>
              </div>
              <div class="card">
                <div class="pill">Badges</div>
                ${renderKpi(stats.badges)}
                <p class="sub">Earned via LootController</p>
              </div>
            </div>
          `;
        },
      },
      {
        id: "account-play",
        title: "Your Match History",
        accent: "accent-bg-violet",
        render: () => {
          if (!account) {
            return `
              <div class="slide-hero">
                <div class="mega">Play a match</div>
                <div class="lead">Once you log battles, Elo & win rate will auto-populate.</div>
              </div>
            `;
          }
          const stats = account.stats || {};
          const winRate = account.winRate !== null && account.winRate !== undefined ? pct(stats.wins, stats.matches) : "—";
          return `
            <div class="slide-hero">
              <div class="mega">Battle Ledger</div>
              <div class="lead">game_record rows for your account.</div>
            </div>
            <div class="stat-hero">
              <div class="card">
                <div class="pill">Matches</div>
                ${renderKpi(stats.matches)}
                <p class="sub">game_record rows</p>
              </div>
              <div class="card">
                <div class="pill">Wins</div>
                ${renderKpi(stats.wins)}
                <p class="sub">Victory count</p>
              </div>
              <div class="card">
                <div class="pill">Win Rate</div>
                <div class="kpi">${winRate}</div>
                <p class="sub">Calculated on the fly</p>
              </div>
              <div class="card">
                <div class="pill">Hosted Quests</div>
                ${renderKpi(stats.questsHosted)}
                <p class="sub">Leadership on the board</p>
              </div>
            </div>
          `;
        },
      },
      {
        id: "account-inventory",
        title: "Your Collection & Trade",
        accent: "accent-bg-gold",
        render: () => {
          if (!account) {
            return `
              <div class="slide-hero">
                <div class="mega">Claim loot to unlock</div>
                <div class="lead">Containers, loot, trades, and merchant shares will render after login.</div>
              </div>
            `;
          }
          const stats = account.stats || {};
          return `
            <div class="slide-hero">
              <div class="mega">Loot & Commerce</div>
              <div class="lead">LootController, Trade records, and MerchantGuild purchases tied to your account.</div>
            </div>
            <div class="stat-hero">
              <div class="card">
                <div class="pill">Loot</div>
                ${renderKpi(stats.loot)}
                <p class="sub">Items owned</p>
              </div>
              <div class="card">
                <div class="pill">Containers</div>
                ${renderKpi(stats.containers)}
                <p class="sub">Ready for storage</p>
              </div>
              <div class="card">
                <div class="pill">Trades</div>
                ${renderKpi(stats.trades)}
                <p class="sub">From trade table</p>
              </div>
              <div class="card">
                <div class="pill">Shares Purchased</div>
                ${renderKpi(stats.sharePurchases)}
                <p class="sub">Merchant Guild stake</p>
              </div>
            </div>
          `;
        },
      },
      {
        id: "account-support",
        title: "Your Support & Safety",
        accent: "accent-bg-cyan",
        render: () => {
          if (!account) {
            return `
              <div class="slide-hero">
                <div class="mega">Support footprint</div>
                <div class="lead">Open tickets, resolve them, and your record will appear.</div>
              </div>
            `;
          }
          const stats = account.stats || {};
          return `
            <div class="slide-hero">
              <div class="mega">Signals & Care</div>
              <div class="lead">Pulled from ticket table and linked profiles.</div>
            </div>
            <div class="stat-hero">
              <div class="card">
                <div class="pill">Tickets Filed</div>
                ${renderKpi(stats.ticketsFiled)}
                <p class="sub">Support requests</p>
              </div>
              <div class="card">
                <div class="pill">Tickets Resolved</div>
                ${renderKpi(stats.ticketsResolved)}
                <p class="sub">Completed for you</p>
              </div>
              <div class="card">
                <div class="pill">Discord</div>
                <div class="kpi">${account.profile.links.discord ? "Linked" : "Not linked"}</div>
                <p class="sub">Session + AccountController</p>
              </div>
              <div class="card">
                <div class="pill">Steam</div>
                <div class="kpi">${account.profile.links.steam ? "Linked" : "Not linked"}</div>
                <p class="sub">Third-party presence</p>
              </div>
            </div>
          `;
        },
      },
      {
        id: "farewell",
        title: "Farewell",
        accent: "accent-bg-pink",
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
    const prevBtn = document.getElementById("prev-btn");
    const nextBtn = document.getElementById("next-btn");
    const counter = document.getElementById("counter");
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
            ${heading}
          </div>
          <button class="copy-link" data-slide="${slide.id}">Share Slide</button>
        </div>
        <div class="slide-body">${slide.render(atlasData)}</div>
      `;
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
