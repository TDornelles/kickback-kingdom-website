<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vAtlasHonorTournament;
use Kickback\Backend\Views\vGame;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vRecordId;
use Kickback\Services\Database;

class AtlasArchiveController
{
    /**
     * Build the Atlas Archive payload for the requested year.
     *
     * @param int $atlasYear
     * @param vAccount|null $activeAccount
     * @param int|null $requestedAccountId
     * @param string|null $requestedUsername
     * @return array<string, mixed>
     */
    public static function buildPayload(int $atlasYear, ?vAccount $activeAccount = null, ?int $requestedAccountId = null, ?string $requestedUsername = null) : array
    {
        $instance = new self();
        return $instance->generatePayload($atlasYear, $activeAccount, $requestedAccountId, $requestedUsername);
    }

    /**
     * @param int $atlasYear
     * @param vAccount|null $activeAccount
     * @param int|null $requestedAccountId
     * @param string|null $requestedUsername
     * @return array<string, mixed>
     */
    private function generatePayload(int $atlasYear, ?vAccount $activeAccount, ?int $requestedAccountId, ?string $requestedUsername) : array
    {
        $atlasYearStart = sprintf('%04d-01-01', $atlasYear);
        $atlasYearEnd = sprintf('%04d-01-01', $atlasYear + 1);
        $previousYearStart = sprintf('%04d-01-01', $atlasYear - 1);
        $twoYearsBackStart = sprintf('%04d-01-01', $atlasYear - 2);

        $worldStats = $this->buildWorldStats($atlasYearStart, $atlasYearEnd, $previousYearStart, $twoYearsBackStart);
        $yearProgress = $this->buildYearProgress($previousYearStart, $atlasYearStart, $twoYearsBackStart);
        $honors = $this->buildHonors($previousYearStart, $atlasYearStart, $atlasYear - 1);
        $accountPayload = $this->buildAccountPayload($requestedAccountId, $requestedUsername, $activeAccount);

        return [
            'year' => $atlasYear,
            'yearProgress' => $yearProgress,
            'world' => $worldStats,
            'honors' => $honors,
            'account' => $accountPayload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWorldStats(string $atlasYearStart, string $atlasYearEnd, string $previousYearStart, string $twoYearsBackStart) : array
    {
        $accountCountEndOfDataYear = $this->fetchScalar('SELECT COUNT(*) FROM account WHERE DateCreated < ?', [$atlasYearStart]);
        $accountCountEndOfPriorYear = $this->fetchScalar('SELECT COUNT(*) FROM account WHERE DateCreated < ?', [$previousYearStart]);
        $newAccountsPreviousYear = null;
        if ($accountCountEndOfDataYear !== null && $accountCountEndOfPriorYear !== null) {
            $newAccountsPreviousYear = $accountCountEndOfDataYear - $accountCountEndOfPriorYear;
        }

        return [
            'accounts' => $accountCountEndOfDataYear,
            'guildsmen' => $accountCountEndOfDataYear,
            'accountsCreatedPreviousYear' => $newAccountsPreviousYear,
            'quests' => $this->fetchScalar('SELECT COUNT(*) FROM quest'),
            'games' => $this->fetchScalar('SELECT COUNT(*) FROM game'),
            'matches' => $this->fetchScalar('SELECT COUNT(*) FROM game_match'),
            'rankedMatches' => $this->fetchScalar('SELECT COUNT(*) FROM game_match WHERE `set` IN (0,1)'),
            'records' => $this->fetchScalar('SELECT COUNT(*) FROM game_record'),
            'rankedMatchesPreviousYear' => $this->fetchScalar(
                'SELECT COUNT(*) FROM game_match WHERE `set` IN (0,1) AND Date >= ? AND Date < ?',
                [$previousYearStart, $atlasYearStart]
            ),
            'rankedMatchesBeforeYear' => $this->fetchScalar(
                'SELECT COUNT(*) FROM game_match WHERE `set` IN (0,1) AND Date < ?',
                [$atlasYearStart]
            ),
            'questsHostedPreviousYear' => $this->fetchScalar(
                'SELECT COUNT(*) FROM quest WHERE published = 1 and finished = 1 and end_date >= ? AND end_date < ?',
                [$previousYearStart, $atlasYearStart]
            ),
            'questsHostedBeforeYear' => $this->fetchScalar(
                'SELECT COUNT(*) FROM quest WHERE published = 1 and finished = 1 and end_date < ?',
                [$atlasYearStart]
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildYearProgress(string $previousYearStart, string $atlasYearStart, string $twoYearsBackStart) : array
    {
        $accountCountEndOfPriorYear = $this->fetchScalar('SELECT COUNT(*) FROM account WHERE DateCreated < ?', [$previousYearStart]);
        $accountCountEndOfDataYear = $this->fetchScalar('SELECT COUNT(*) FROM account WHERE DateCreated < ?', [$atlasYearStart]);

        return [
            'accounts' => [
                'label' => 'Adventurers Registered',
                'start' => $accountCountEndOfPriorYear,
                'end' => $accountCountEndOfDataYear,
            ],
            'quests' => [
                'label' => 'Published Quests',
                'start' => $this->fetchScalar(
                    'SELECT COUNT(*) FROM quest WHERE published = 1 AND end_date >= ? AND end_date < ?',
                    [$twoYearsBackStart, $previousYearStart]
                ),
                'end' => $this->fetchScalar(
                    'SELECT COUNT(*) FROM quest WHERE published = 1 AND end_date >= ? AND end_date < ?',
                    [$previousYearStart, $atlasYearStart]
                ),
            ],
            'matches' => [
                'label' => 'Matches Logged',
                'start' => $this->fetchScalar(
                    'SELECT COUNT(*) FROM game_match WHERE Date >= ? AND Date < ?',
                    [$twoYearsBackStart, $previousYearStart]
                ),
                'end' => $this->fetchScalar(
                    'SELECT COUNT(*) FROM game_match WHERE Date >= ? AND Date < ?',
                    [$previousYearStart, $atlasYearStart]
                ),
            ],
            'questsHosted' => [
                'label' => 'Quests Hosted',
                'start' => $this->fetchScalar(
                    'SELECT COUNT(*) FROM quest WHERE published = 1 and finished = 1 and end_date >= ? AND end_date < ?',
                    [$twoYearsBackStart, $previousYearStart]
                ),
                'end' => $this->fetchScalar(
                    'SELECT COUNT(*) FROM quest WHERE published = 1 and finished = 1 and end_date >= ? AND end_date < ?',
                    [$previousYearStart, $atlasYearStart]
                ),
            ],
            'transactions' => [
                'label' => 'Store Transactions',
                'start' => $this->fetchScalar(
                    'SELECT COUNT(*) FROM `transaction` WHERE ctime >= ? AND ctime < ?',
                    [$twoYearsBackStart, $previousYearStart]
                ),
                'end' => $this->fetchScalar(
                    'SELECT COUNT(*) FROM `transaction` WHERE ctime >= ? AND ctime < ?',
                    [$previousYearStart, $atlasYearStart]
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHonors(string $periodStart, string $periodEnd, int $previousYear) : array
    {
        return [
            'tournaments' => $this->buildTournamentHonor($periodStart, $periodEnd),
            'prestige' => $this->buildPrestigeHonor($periodStart, $periodEnd),
            'quester' => $this->buildQuesterHonor($periodStart, $periodEnd),
            'host' => $this->buildHostHonor($periodStart, $periodEnd),
            'renown' => $this->buildRenownHonor($periodStart, $periodEnd, $previousYear),
            'kingOfGames' => $this->buildKingOfGamesHonor($periodStart, $periodEnd),
        ];
    }

    private function buildTournamentHonor(string $periodStart, string $periodEnd) : ?array
    {
        $tournamentRow = $this->fetchOne(
            'SELECT vtr.account_id AS account_id,
                    COUNT(DISTINCT vtr.tournament_id) AS tournaments_won,
                    COUNT(DISTINCT t.game_id) AS games_played
             FROM v_tournament_results vtr
             INNER JOIN tournament t ON vtr.tournament_id = t.Id
             WHERE vtr.win = 1 AND vtr.account_id IS NOT NULL AND t.Date >= ? AND t.Date < ?
             GROUP BY vtr.account_id
             ORDER BY tournaments_won DESC, games_played DESC
             LIMIT 1',
            [$periodStart, $periodEnd]
        );

        if (empty($tournamentRow['account_id'])) {
            return null;
        }

        $profile = $this->fetchAccount((int)$tournamentRow['account_id']);
        if (!$profile instanceof vAccount) {
            return null;
        }

        $gameRows = $this->fetchAll(
            'SELECT vg.Id AS game_id, vg.Name AS game_name, vg.Desc AS game_desc, vg.ShortName AS game_short_name,
                    vg.CanRank AS game_can_rank, vg.MinRankedMatches AS game_min_ranked_matches,
                    vg.media_icon_id AS game_media_icon_id, vg.icon_path AS game_icon_path, vg.locator AS game_locator
             FROM v_tournament_results vtr
             INNER JOIN tournament t ON vtr.tournament_id = t.Id
             LEFT JOIN v_game_info vg ON t.game_id = vg.Id
             WHERE vtr.win = 1
               AND vtr.account_id = ?
               AND t.Date >= ?
               AND t.Date < ?
             GROUP BY vg.Id, vg.Name, vg.Desc, vg.ShortName, vg.CanRank, vg.MinRankedMatches, vg.media_icon_id, vg.icon_path, vg.locator
             ORDER BY vg.Name',
            [$tournamentRow['account_id'], $periodStart, $periodEnd]
        );

        $honor = new vAtlasHonorTournament();
        $honor->account = $profile;
        $honor->tournamentsWon = (int)$tournamentRow['tournaments_won'];
        $honor->gamesCount = (int)$tournamentRow['games_played'];
        $honor->games = array_values(array_filter(array_map(function ($row) {
            if (empty($row['game_id'])) {
                return null;
            }
            $game = new vGame('', (int)$row['game_id']);
            $game->name = (string)($row['game_name'] ?? '');
            $game->description = (string)($row['game_desc'] ?? '');
            $game->shortName = (string)($row['game_short_name'] ?? '');
            $game->canRank = isset($row['game_can_rank']) ? ((int)$row['game_can_rank'] === 1) : false;
            $game->minRankedMatches = isset($row['game_min_ranked_matches']) ? (int)$row['game_min_ranked_matches'] : 0;
            $game->locator = (string)($row['game_locator'] ?? '');

            if (!empty($row['game_media_icon_id'])) {
                $icon = new vMedia('', (int)$row['game_media_icon_id']);
                if (!empty($row['game_icon_path'])) {
                    $icon->setMediaPath($row['game_icon_path']);
                }
                $game->icon = $icon;
            }

            return $game;
        }, $gameRows)));

        return $honor->toArray();
    }

    private function buildPrestigeHonor(string $periodStart, string $periodEnd) : ?array
    {
        $row = $this->fetchOne(
            "WITH agg AS (
                SELECT
                    account_id_to AS account_id,
                    SUM(CASE WHEN commend = 1 THEN 1 ELSE -1 END) AS net_prestige,
                    COUNT(DISTINCT account_id_from) AS unique_givers,
                    SUM(CASE WHEN commend = 1 THEN 1 ELSE 0 END) AS pos,
                    SUM(CASE WHEN commend = 1 THEN 0 ELSE 1 END) AS neg
                FROM prestige
                WHERE date >= ? AND date < ?
                GROUP BY account_id_to
            )
            SELECT
                account_id,
                net_prestige,
                unique_givers,
                pos,
                neg,
                ((pos + 1.0) / (pos + neg + 2.0)) * SQRT(unique_givers) * net_prestige AS score
            FROM agg
            WHERE net_prestige > 0
            ORDER BY score DESC, net_prestige DESC, unique_givers DESC
            LIMIT 1",
            [$periodStart, $periodEnd]
        );

        if (empty($row['account_id'])) {
            return null;
        }

        $profile = $this->fetchAccount((int)$row['account_id']);
        if (!$profile instanceof vAccount) {
            return null;
        }

        return [
            'profile' => $this->formatAccountProfile($profile),
            'netPrestige' => (int)$row['net_prestige'],
            'uniqueGivers' => (int)$row['unique_givers'],
        ];
    }

    private function buildQuesterHonor(string $periodStart, string $periodEnd) : ?array
    {
        $row = $this->fetchOne(
            'SELECT qa.account_id AS account_id, COUNT(*) AS quests_participated
             FROM quest_applicants qa
             INNER JOIN quest q ON qa.quest_id = q.Id
             WHERE qa.participated = 1 AND q.end_date >= ? AND q.end_date < ?
             GROUP BY qa.account_id
             ORDER BY quests_participated DESC
             LIMIT 1',
            [$periodStart, $periodEnd]
        );

        if (empty($row['account_id'])) {
            return null;
        }

        $profile = $this->fetchAccount((int)$row['account_id']);
        if (!$profile instanceof vAccount) {
            return null;
        }

        return [
            'profile' => $this->formatAccountProfile($profile),
            'questsParticipated' => (int)$row['quests_participated'],
        ];
    }

    private function buildHostHonor(string $periodStart, string $periodEnd) : ?array
    {
        $row = $this->fetchOne(
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
                 WHERE q.end_date >= ? AND q.end_date < ? AND q.published = 1 AND q.finished = 1 AND q.host_id_2 IS NOT NULL
             ) AS host_data
             WHERE host_account_id IS NOT NULL
             GROUP BY host_account_id
             HAVING quests_hosted > 0
             ORDER BY hosting_score DESC
             LIMIT 1',
            [$periodStart, $periodEnd, $periodStart, $periodEnd]
        );

        if (empty($row['account_id'])) {
            return null;
        }

        $profile = $this->fetchAccount((int)$row['account_id']);
        if (!$profile instanceof vAccount) {
            return null;
        }

        return [
            'profile' => $this->formatAccountProfile($profile),
            'questsHosted' => (int)$row['quests_hosted'],
            'hostingScore' => isset($row['hosting_score']) ? (float)$row['hosting_score'] : null,
        ];
    }

    private function buildRenownHonor(string $periodStart, string $periodEnd, int $previousYear) : ?array
    {
        $row = $this->fetchOne(
            'SELECT account_id, COUNT(*) AS badge_count
             FROM v_account_badge_info
             WHERE dateObtained >= ? AND dateObtained < ?
             GROUP BY account_id
             ORDER BY badge_count DESC
             LIMIT 1',
            [$periodStart, $periodEnd]
        );

        if (empty($row['account_id'])) {
            return null;
        }

        $profile = $this->fetchAccount((int)$row['account_id']);
        if (!$profile instanceof vAccount) {
            return null;
        }

        $badgeRows = $this->fetchAll(
            'SELECT SmallImgPath
             FROM v_account_badge_info
             WHERE account_id = ? AND dateObtained >= ? AND dateObtained < ?
             ORDER BY dateObtained DESC
             LIMIT 12',
            [$row['account_id'], $periodStart, $periodEnd]
        );

        return [
            'profile' => $this->formatAccountProfile($profile),
            'badgesEarned' => (int)$row['badge_count'],
            'badgeIcons' => array_values(array_filter(array_map(function ($badgeRow) {
                return $this->mediaUrl($badgeRow['SmallImgPath'] ?? null);
            }, $badgeRows))),
            'previousYear' => $previousYear,
        ];
    }

    private function buildKingOfGamesHonor(string $periodStart, string $periodEnd) : ?array
    {
        $row = $this->fetchOne(
            'WITH active_accounts AS (
                 SELECT DISTINCT gr.account_id
                 FROM game_record gr
                 INNER JOIN game_match gm ON gm.Id = gr.game_match_id
                 WHERE gm.Date >= ? AND gm.Date < ?
             ),
             top_ranks AS (
                 SELECT v.account_id, v.game_id, v.elo_rating
                 FROM v_game_elo_rank_info v
                 WHERE v.rank = 1 AND v.is_ranked = 1
             )
             SELECT tr.account_id, COUNT(DISTINCT tr.game_id) AS gold_cards, SUM(tr.elo_rating) AS elo_sum
             FROM top_ranks tr
             INNER JOIN active_accounts aa ON aa.account_id = tr.account_id
             GROUP BY tr.account_id
             ORDER BY gold_cards DESC, elo_sum DESC
             LIMIT 1',
            [$periodStart, $periodEnd]
        );

        if (empty($row['account_id'])) {
            return null;
        }

        $profile = $this->fetchAccount((int)$row['account_id']);
        if (!$profile instanceof vAccount) {
            return null;
        }

        return [
            'profile' => $this->formatAccountProfile($profile),
            'goldCards' => (int)$row['gold_cards'],
            'eloSum' => isset($row['elo_sum']) ? (float)$row['elo_sum'] : null,
        ];
    }

    private function buildAccountPayload(?int $requestedAccountId, ?string $requestedUsername, ?vAccount $activeAccount) : ?array
    {
        $atlasAccount = $this->resolveAccount($requestedAccountId, $requestedUsername, $activeAccount);

        if (!$atlasAccount instanceof vAccount) {
            return null;
        }

        $accountId = $atlasAccount->crand;

        $accountStats = [
            'matches' => $this->fetchScalar('SELECT COUNT(*) FROM game_record WHERE account_id = ?', [$accountId]),
            'wins' => $this->fetchScalar('SELECT COUNT(*) FROM game_record WHERE account_id = ? AND win = 1', [$accountId]),
            'questsHosted' => $this->countHostedQuests($accountId),
            'questsJoined' => $this->fetchScalar('SELECT COUNT(*) FROM quest_applicants WHERE account_id = ? AND participated = 1', [$accountId]),
            'applications' => $this->fetchScalar('SELECT COUNT(*) FROM quest_applicants WHERE account_id = ?', [$accountId]),
            'badges' => $this->fetchScalar('SELECT COUNT(*) FROM v_account_badge_info WHERE account_id = ?', [$accountId]),
            'loot' => $this->fetchScalar('SELECT COUNT(*) FROM loot WHERE account_id = ?', [$accountId]),
            'containers' => $this->fetchScalar('SELECT COUNT(*) FROM loot l INNER JOIN item i ON l.item_id = i.Id WHERE l.account_id = ? AND i.is_container = 1', [$accountId]),
            'trades' => $this->fetchScalar('SELECT COUNT(*) FROM trade WHERE from_account_id = ? OR to_account_id = ?', [$accountId, $accountId]),
            'sharePurchases' => $this->fetchScalar('SELECT COALESCE(SUM(SharesPurchased),0) FROM share_purchase WHERE AccountId = ?', [$accountId], 'float'),
            'ticketsFiled' => $this->fetchScalar('SELECT COUNT(*) FROM ticket WHERE created_by_crand = ?', [$accountId]),
            'ticketsResolved' => $this->fetchScalar('SELECT COUNT(*) FROM ticket WHERE created_by_crand = ? AND resolved_at IS NOT NULL', [$accountId]),
        ];

        $winRate = null;
        if (!empty($accountStats['matches'])) {
            $winRate = ($accountStats['wins'] ?? 0) / max($accountStats['matches'], 1);
        }

        return [
            'profile' => [
                'username' => $atlasAccount->username,
                'title' => $atlasAccount->getAccountTitle(),
                'level' => $atlasAccount->level,
                'prestige' => $atlasAccount->prestige,
                'exp' => $atlasAccount->exp,
                'avatar' => $atlasAccount->profilePictureURL(),
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

    private function resolveAccount(?int $requestedAccountId, ?string $requestedUsername, ?vAccount $activeAccount) : ?vAccount
    {
        if (!is_null($requestedAccountId)) {
            $resp = AccountController::getAccountById(new vRecordId('', (int)$requestedAccountId));
            if ($resp->success && $resp->data) {
                return $resp->data;
            }
        }

        if (!is_null($requestedUsername) && $requestedUsername !== '') {
            $resp = AccountController::getAccountByUsername($requestedUsername);
            if ($resp->success && $resp->data) {
                return $resp->data;
            }
        }

        return $activeAccount;
    }

    private function countHostedQuests(int $accountId) : ?int
    {
        return $this->fetchScalar(
            'SELECT COUNT(*) FROM quest WHERE host_id = ? OR host_id_2 = ?',
            [$accountId, $accountId]
        );
    }

    private function fetchAccount(int $accountId) : ?vAccount
    {
        try {
            $resp = AccountController::getAccountById(new vRecordId('', $accountId));
            if ($resp instanceof Response && $resp->success && $resp->data instanceof vAccount) {
                return $resp->data;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    /**
     * @param string $query
     * @param array<int|string, mixed> $params
     * @param string|null $cast
     */
    private function fetchScalar(string $query, array $params = [], ?string $cast = 'int') : mixed
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
        }

        return null;
    }

    /**
     * @param string $query
     * @param array<int|string, mixed> $params
     */
    private function fetchOne(string $query, array $params = []) : ?array
    {
        try {
            $result = Database::executeSqlQuery($query, $params);
            if ($result instanceof \mysqli_result) {
                $row = $result->fetch_assoc();
                return $row !== null ? $row : null;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    /**
     * @param string $query
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(string $query, array $params = []) : array
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
        }

        return [];
    }

    private function mediaUrl(?string $path) : ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('/^https?:\\/\\//i', $path)) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (strncmp($normalized, 'assets/media/', strlen('assets/media/')) === 0) {
            return '/' . $normalized;
        }

        return '/assets/media/' . $normalized;
    }

    private function formatAccountProfile(vAccount $account) : array
    {
        return [
            'id' => $account->crand,
            'username' => $account->username,
            'avatar' => $account->profilePictureURL(),
            'level' => $account->level ?? null,
            'prestige' => $account->prestige ?? null,
            'badges' => $account->badges ?? null,
        ];
    }
}

?>
