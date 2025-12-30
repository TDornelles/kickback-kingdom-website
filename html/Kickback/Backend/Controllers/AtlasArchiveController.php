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
        $accountPayload = $this->buildAccountPayload($requestedAccountId, $requestedUsername, $activeAccount, $previousYearStart, $atlasYearStart);

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
            'treasureHunter' => $this->buildTreasureCollectorHonor($periodStart, $periodEnd),
            'raffleLuck' => $this->buildRaffleLuckHonor($periodStart, $periodEnd),
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
            'WITH hostings AS (
    SELECT q.Id AS quest_id,
           q.host_id AS host_account_id,
           COUNT(DISTINCT qa.account_id) AS participants,
           SUM(CASE WHEN qa.host_rating IS NOT NULL THEN 1 ELSE 0 END) AS rating_count,
           SUM(COALESCE(qa.host_rating, 0)) AS rating_sum
    FROM quest q
    LEFT JOIN quest_applicants qa ON qa.quest_id = q.Id
    WHERE q.end_date >= ? AND q.end_date < ?
      AND q.published = 1 AND q.finished = 1
      AND q.host_id IS NOT NULL AND q.host_id > 0  and q.host_id <> 46 and qa.rewards_collected = 1 and qa.participated = 1
    GROUP BY q.Id, q.host_id
    HAVING COUNT(DISTINCT qa.account_id) > 0

    UNION ALL

    SELECT q.Id AS quest_id,
           q.host_id_2 AS host_account_id,
           COUNT(DISTINCT qa.account_id) AS participants,
           SUM(CASE WHEN qa.host_rating IS NOT NULL THEN 1 ELSE 0 END) AS rating_count,
           SUM(COALESCE(qa.host_rating, 0)) AS rating_sum
    FROM quest q
    LEFT JOIN quest_applicants qa ON qa.quest_id = q.Id
    WHERE q.end_date >= ? AND q.end_date < ?
      AND q.published = 1 AND q.finished = 1
      AND q.host_id_2 IS NOT NULL AND q.host_id_2 > 0  and q.host_id_2 <> 46 and qa.rewards_collected = 1 and qa.participated = 1
    GROUP BY q.Id, q.host_id_2
    HAVING COUNT(DISTINCT qa.account_id) > 0
),
agg AS (
    SELECT host_account_id AS account_id,
           COUNT(DISTINCT quest_id) AS quests_hosted,
           SUM(participants) AS participants_total,
           SUM(rating_sum) AS rating_sum,
           SUM(rating_count) AS rating_count
    FROM hostings
    WHERE host_account_id > 0
    GROUP BY host_account_id
),
scored AS (
    SELECT account_id,
           quests_hosted,
           participants_total,
           ((rating_sum + 20 * 4.5) / NULLIF(rating_count + 20, 0)) AS bayes_avg,
           ((rating_sum + 20 * 4.5) / NULLIF(rating_count + 20, 0))
             * LOG(1 + quests_hosted)
             * SQRT(participants_total) AS score
    FROM agg
    WHERE participants_total > 0
)
SELECT account_id,
       quests_hosted,
       participants_total,
       bayes_avg AS hosting_score,
       score as score
FROM scored
ORDER BY score DESC, bayes_avg DESC, participants_total DESC
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
            'SELECT SmallImgPath, name
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
                $icon = $this->mediaUrl($badgeRow['SmallImgPath'] ?? null);
                $name = isset($badgeRow['name']) ? (string)$badgeRow['name'] : null;
                if ($icon === null && $name === null) {
                    return null;
                }
                return [
                    'icon' => $icon,
                    'name' => $name,
                ];
            }, $badgeRows))),
            'previousYear' => $previousYear,
        ];
    }

    private function buildTreasureCollectorHonor(string $periodStart, string $periodEnd) : ?array
    {
        $row = $this->fetchOne(
            'SELECT thc.account_id,
                    COUNT(*) AS treasures_collected,
                    COUNT(DISTINCT CONCAT(tho.ref_event_ctime, "-", tho.ref_event_crand)) AS events_count
             FROM treasure_hunt_collections thc
             INNER JOIN treasure_hunt_objects tho ON thc.ref_object_ctime = tho.ctime AND thc.ref_object_crand = tho.crand
             INNER JOIN treasure_hunt_event the ON tho.ref_event_ctime = the.ctime AND tho.ref_event_crand = the.crand
             WHERE the.start_date >= ? AND the.start_date < ?
             GROUP BY thc.account_id
             ORDER BY treasures_collected DESC, events_count DESC
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
            'treasuresCollected' => (int)$row['treasures_collected'],
            'eventsCount' => isset($row['events_count']) ? (int)$row['events_count'] : null,
        ];
    }

    private function buildRaffleLuckHonor(string $periodStart, string $periodEnd) : ?array
    {
        $row = $this->fetchOne(
            'WITH winning_raffles AS (
                 SELECT r.Id AS raffle_id,
                        q.end_date AS raffle_end_date,
                        l.account_id AS account_id
                 FROM raffle r
                 INNER JOIN raffle_submissions rs ON rs.Id = r.winner_submission_id
                 INNER JOIN loot l ON l.Id = rs.loot_id
                 LEFT JOIN quest q ON q.raffle_id = r.Id
                 WHERE r.winner_submission_id IS NOT NULL
                   AND q.end_date IS NOT NULL
                   AND q.end_date >= ?
                   AND q.end_date < ?
             ),
             tickets AS (
                 SELECT rs.raffle_id,
                        l.account_id,
                        COUNT(*) AS tickets_used
                 FROM raffle_submissions rs
                 INNER JOIN loot l ON l.Id = rs.loot_id
                 GROUP BY rs.raffle_id, l.account_id
             )
             SELECT w.account_id,
                    COUNT(*) AS raffles_won,
                    SUM(COALESCE(t.tickets_used, 0)) AS tickets_used
             FROM winning_raffles w
             LEFT JOIN tickets t ON t.raffle_id = w.raffle_id AND t.account_id = w.account_id
             GROUP BY w.account_id
             ORDER BY raffles_won DESC, tickets_used ASC
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

        $raffleRewardRows = $this->fetchAll(
            'SELECT r.Id AS raffle_id,
                    q.Id AS quest_id,
                    q.name AS quest_name,
                    q.end_date AS raffle_end_date,
                    vqr.Id AS reward_item_id,
                    vqr.name AS reward_item_name,
                    vqr.category AS reward_category,
                    COALESCE(vqr.SmallImgPath, vqr.BigImgPath) AS reward_icon_path
             FROM raffle r
             INNER JOIN raffle_submissions rs ON rs.Id = r.winner_submission_id
             INNER JOIN loot l ON l.Id = rs.loot_id
             LEFT JOIN quest q ON q.raffle_id = r.Id
             LEFT JOIN v_quest_reward_info vqr ON vqr.quest_id = q.Id
             WHERE r.winner_submission_id IS NOT NULL
               AND q.end_date IS NOT NULL
               AND q.end_date >= ?
               AND q.end_date < ?
               AND l.account_id = ?
             ORDER BY q.end_date DESC, r.Id DESC, vqr.name ASC',
            [$periodStart, $periodEnd, $row['account_id']]
        );

        $raffleRewards = [];
        foreach ($raffleRewardRows as $rewardRow) {
            if (!isset($rewardRow['raffle_id'])) {
                continue;
            }

            $raffleId = (int)$rewardRow['raffle_id'];
            if (!isset($raffleRewards[$raffleId])) {
                $raffleRewards[$raffleId] = [
                    'raffleId' => $raffleId,
                    'questId' => isset($rewardRow['quest_id']) ? (int)$rewardRow['quest_id'] : null,
                    'questName' => $rewardRow['quest_name'] ?? null,
                    'endDate' => $rewardRow['raffle_end_date'] ?? null,
                    'rewards' => [],
                ];
            }

            if (!empty($rewardRow['reward_item_id'])) {
                $raffleRewards[$raffleId]['rewards'][] = [
                    'itemId' => (int)$rewardRow['reward_item_id'],
                    'name' => $rewardRow['reward_item_name'] ?? null,
                    'category' => $rewardRow['reward_category'] ?? null,
                    'icon' => $this->mediaUrl($rewardRow['reward_icon_path'] ?? null),
                ];
            }
        }

        return [
            'profile' => $this->formatAccountProfile($profile),
            'rafflesWon' => (int)$row['raffles_won'],
            'ticketsUsed' => isset($row['tickets_used']) ? (int)$row['tickets_used'] : null,
            'raffleRewards' => array_values($raffleRewards),
        ];
    }

    private function buildKingOfGamesHonor(string $periodStart, string $periodEnd) : ?array
    {
        $row = $this->fetchOne(
            'WITH active_accounts AS (
                 SELECT DISTINCT gr.account_id
                 FROM game_record gr
                 INNER JOIN game_match gm ON gm.Id = gr.game_match_id
                 WHERE gm.Date < ? AND gm.`set` IN (0,1)
             ),
             ranked_gold_cards AS (
                 SELECT DISTINCT v.account_id, v.game_id, v.elo_rating
                 FROM v_game_elo_rank_info v
                 INNER JOIN game_record gr ON gr.account_id = v.account_id
                 INNER JOIN game_match gm ON gm.Id = gr.game_match_id
                 WHERE v.rank = 1
                   AND v.is_ranked = 1
                   AND gm.Date < ?
                   AND gm.`set` IN (0,1)
             )
             SELECT rgc.account_id, COUNT(DISTINCT rgc.game_id) AS gold_cards, SUM(rgc.elo_rating) AS elo_sum
             FROM ranked_gold_cards rgc
             INNER JOIN active_accounts aa ON aa.account_id = rgc.account_id
             GROUP BY rgc.account_id
             ORDER BY gold_cards DESC, elo_sum DESC
             LIMIT 1',
            [$periodEnd, $periodEnd]
        );

        if (empty($row['account_id'])) {
            return null;
        }

        $profile = $this->fetchAccount((int)$row['account_id']);
        if (!$profile instanceof vAccount) {
            return null;
        }

        $gameRows = $this->fetchAll(
            'SELECT vg.Id AS game_id,
                    vg.Name AS game_name,
                    vg.ShortName AS game_short_name,
                    vg.media_icon_id AS game_media_icon_id,
                    vg.icon_path AS game_icon_path,
                    vg.locator AS game_locator
             FROM v_game_elo_rank_info v
             LEFT JOIN v_game_info vg ON v.game_id = vg.Id
             WHERE v.rank = 1
               AND v.is_ranked = 1
               AND v.account_id = ?
               AND EXISTS (
                 SELECT 1
                 FROM game_record gr
                 INNER JOIN game_match gm ON gm.Id = gr.game_match_id
                 WHERE gr.account_id = v.account_id
                   AND gm.game_id = v.game_id
                   AND gm.Date < ?
                   AND gm.`set` IN (0,1)
               )
             GROUP BY vg.Id, vg.Name, vg.ShortName, vg.media_icon_id, vg.icon_path, vg.locator
             ORDER BY vg.Name',
            [$row['account_id'], $periodEnd]
        );

        $games = array_values(array_filter(array_map(function (array $gameRow) {
            if (empty($gameRow['game_id'])) {
                return null;
            }

            $iconPath = null;
            if (!empty($gameRow['game_media_icon_id'])) {
                $icon = new vMedia('', (int)$gameRow['game_media_icon_id']);
                if (!empty($gameRow['game_icon_path'])) {
                    $icon->setMediaPath($gameRow['game_icon_path']);
                }
                $iconPath = $icon->getFullPath();
            } elseif (!empty($gameRow['game_icon_path'])) {
                $iconPath = (string)$gameRow['game_icon_path'];
            }

            return [
                'id' => (int)$gameRow['game_id'],
                'name' => (string)($gameRow['game_name'] ?? ''),
                'shortName' => (string)($gameRow['game_short_name'] ?? ''),
                'icon' => $iconPath,
                'locator' => (string)($gameRow['game_locator'] ?? ''),
            ];
        }, $gameRows)));

        return [
            'profile' => $this->formatAccountProfile($profile),
            'goldCards' => (int)$row['gold_cards'],
            'eloSum' => isset($row['elo_sum']) ? (float)$row['elo_sum'] : null,
            'games' => $games,
        ];
    }

    private function buildAccountPayload(?int $requestedAccountId, ?string $requestedUsername, ?vAccount $activeAccount, string $yearStart, string $yearEnd) : ?array
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

        $bestFriends = $this->buildQuestBestFriends($accountId, $yearStart, $yearEnd);
        $favoriteRankedGame = $this->buildFavoriteRankedGame($accountId, $yearStart, $yearEnd);
        $matchmaker = $this->buildMatchmakerStreaks($accountId, $yearStart, $yearEnd);
        $momentumShifts = $this->buildMomentumShifts($accountId, $yearStart, $yearEnd);
        $duoOfDestiny = $this->buildDuoOfDestiny($accountId, $yearStart, $yearEnd);
        $nemeses = $this->buildNemeses($accountId, $yearStart, $yearEnd);

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
            'yearRange' => [
                'start' => $yearStart,
                'end' => $yearEnd,
            ],
            'bestFriends' => $bestFriends,
            'favoriteRankedGame' => $favoriteRankedGame,
            'matchmaker' => $matchmaker,
            'momentumShifts' => $momentumShifts,
            'duoOfDestiny' => $duoOfDestiny,
            'nemeses' => $nemeses,
        ];
    }

    private function buildQuestBestFriends(int $accountId, string $yearStart, string $yearEnd) : array
    {
        $rows = $this->fetchAll(
            'SELECT teammate.account_id AS teammate_id,
                    COUNT(*) AS quests_together
             FROM quest_applicants me
             INNER JOIN quest_applicants teammate
                ON me.quest_id = teammate.quest_id
               AND me.account_id <> teammate.account_id
             INNER JOIN quest q ON q.Id = me.quest_id
             WHERE me.account_id = ?
               AND me.participated = 1
               AND teammate.participated = 1
               AND q.end_date >= ?
               AND q.end_date < ?
             GROUP BY teammate.account_id
             ORDER BY quests_together DESC, teammate.account_id
             LIMIT 3',
            [$accountId, $yearStart, $yearEnd]
        );

        $profiles = $this->loadAccountProfiles(array_map(fn ($row) => (int)($row['teammate_id'] ?? 0), $rows));

        return array_values(array_map(function ($row) use ($profiles) {
            $questsTogether = (int)($row['quests_together'] ?? 0);
            return [
                'profile' => $profiles[(int)($row['teammate_id'] ?? 0)] ?? null,
                'quests' => $questsTogether,
            ];
        }, $rows));
    }

    private function buildFavoriteRankedGame(int $accountId, string $yearStart, string $yearEnd) : ?array
    {
        $row = $this->fetchOne(
            'select game_id, count(*) as matches, sum(win) as wins from v_game_record_match 
            where account_id = ? 
            and Date >= ? 
            and Date < ? 
            group by game_id 
            order by matches desc, wins desc 
            LIMIT 1',
            [$accountId, $yearStart, $yearEnd]
        );

        if (empty($row['game_id'])) {
            return null;
        }

        $gameId = (int)$row['game_id'];
        $game = $this->loadGamesByIds([$gameId])[$gameId] ?? ['id' => $gameId];
        $matches = (int)($row['matches'] ?? 0);
        $wins = (int)($row['wins'] ?? 0);

        return [
            'game' => $game,
            'matches' => $matches,
            'wins' => $wins,
            'winRate' => $matches > 0 ? $wins / max($matches, 1) : null,
        ];
    }

    private function buildMatchmakerStreaks(int $accountId, string $yearStart, string $yearEnd) : array
    {
        $summary = $this->fetchOne(
            'SELECT COUNT(*) AS matches, SUM(CASE WHEN gr.win = 1 THEN 1 ELSE 0 END) AS wins
             FROM game_record gr
             INNER JOIN game_match gm ON gm.Id = gr.game_match_id
             WHERE gr.account_id = ?
               AND gm.Date >= ?
               AND gm.Date < ?
             LIMIT 1',
            [$accountId, $yearStart, $yearEnd]
        ) ?? [];

        $matches = (int)($summary['matches'] ?? 0);
        $wins = (int)($summary['wins'] ?? 0);

        $monthlyRows = $this->fetchAll(
            'SELECT DATE_FORMAT(gm.Date, "%Y-%m-01") AS month,
                    COUNT(*) AS matches
             FROM game_record gr
             INNER JOIN game_match gm ON gm.Id = gr.game_match_id
             WHERE gr.account_id = ?
               AND gm.Date >= ?
               AND gm.Date < ?
             GROUP BY DATE_FORMAT(gm.Date, "%Y-%m-01")
             ORDER BY month ASC',
            [$accountId, $yearStart, $yearEnd]
        );

        $monthly = array_map(function ($row) {
            return [
                'month' => (string)($row['month'] ?? ''),
                'matches' => (int)($row['matches'] ?? 0),
            ];
        }, $monthlyRows);

        return [
            'matches' => $matches,
            'wins' => $wins,
            'winRate' => $matches > 0 ? $wins / max($matches, 1) : null,
            'monthly' => $monthly,
        ];
    }

    private function buildMomentumShifts(int $accountId, string $yearStart, string $yearEnd) : array
    {
        $rows = $this->fetchAll(
            'SELECT gr.game_id, gr.elo_change, gr.win, gm.Date AS match_date
             FROM game_record gr
             INNER JOIN game_match gm ON gm.Id = gr.game_match_id
             WHERE gr.account_id = ?
               AND gm.Date >= ?
               AND gm.Date < ?
             ORDER BY ABS(gr.elo_change) DESC, gm.Date DESC
             LIMIT 5',
            [$accountId, $yearStart, $yearEnd]
        );

        $games = $this->loadGamesByIds(array_map(fn ($row) => (int)($row['game_id'] ?? 0), $rows));

        return array_values(array_map(function ($row) use ($games) {
            $gameId = (int)($row['game_id'] ?? 0);
            return [
                'game' => $games[$gameId] ?? ['id' => $gameId],
                'eloChange' => isset($row['elo_change']) ? (int)$row['elo_change'] : 0,
                'win' => isset($row['win']) ? ((int)$row['win'] === 1) : false,
                'date' => (string)($row['match_date'] ?? ''),
            ];
        }, $rows));
    }

    private function buildDuoOfDestiny(int $accountId, string $yearStart, string $yearEnd) : ?array
    {
        $duo = $this->fetchOne(
            'SELECT teammate.account_id AS teammate_id,
                    COUNT(*) AS matches,
                    SUM(CASE WHEN me.win = 1 THEN 1 ELSE 0 END) AS wins
             FROM game_record me
             INNER JOIN game_record teammate
               ON me.game_match_id = teammate.game_match_id
              AND IFNULL(me.team_name, "") = IFNULL(teammate.team_name, "")
              AND me.account_id <> teammate.account_id
             INNER JOIN game_match gm ON gm.Id = me.game_match_id
             WHERE me.account_id = ?
               AND gm.`set` IN (0,1)
               AND gm.Date >= ?
               AND gm.Date < ?
             GROUP BY teammate.account_id
             ORDER BY matches DESC, teammate.account_id
             LIMIT 1',
            [$accountId, $yearStart, $yearEnd]
        );

        if (empty($duo['teammate_id'])) {
            return null;
        }

        $teammateId = (int)$duo['teammate_id'];
        $games = $this->fetchAll(
            'SELECT me.game_id, COUNT(*) AS matches
             FROM game_record me
             INNER JOIN game_record teammate
               ON me.game_match_id = teammate.game_match_id
              AND IFNULL(me.team_name, "") = IFNULL(teammate.team_name, "")
              AND me.account_id = ?
              AND teammate.account_id = ?
             INNER JOIN game_match gm ON gm.Id = me.game_match_id
             WHERE gm.`set` IN (0,1)
               AND gm.Date >= ?
               AND gm.Date < ?
             GROUP BY me.game_id
             ORDER BY matches DESC',
            [$accountId, $teammateId, $yearStart, $yearEnd]
        );

        $gameDetails = $this->loadGamesByIds(array_map(fn ($row) => (int)($row['game_id'] ?? 0), $games));
        $formattedGames = array_values(array_map(function ($row) use ($gameDetails) {
            $gameId = (int)($row['game_id'] ?? 0);
            return [
                'game' => $gameDetails[$gameId] ?? ['id' => $gameId],
                'matches' => (int)($row['matches'] ?? 0),
            ];
        }, $games));

        $matches = (int)($duo['matches'] ?? 0);
        $wins = (int)($duo['wins'] ?? 0);
        $profiles = $this->loadAccountProfiles([$teammateId]);

        return [
            'profile' => $profiles[$teammateId] ?? null,
            'matches' => $matches,
            'wins' => $wins,
            'winRate' => $matches > 0 ? $wins / max($matches, 1) : null,
            'games' => $formattedGames,
        ];
    }

    private function buildNemeses(int $accountId, string $yearStart, string $yearEnd) : array
    {
        $rows = $this->fetchAll(
            'SELECT opp.account_id AS opponent_id,
                    opp.game_id,
                    COUNT(*) AS defeats
             FROM game_record me
             INNER JOIN game_record opp
               ON me.game_match_id = opp.game_match_id
              AND me.account_id <> opp.account_id
              AND IFNULL(opp.team_name, "") <> IFNULL(me.team_name, "")
             INNER JOIN game_match gm ON gm.Id = me.game_match_id
             WHERE me.account_id = ?
               AND gm.`set` IN (0,1)
               AND gm.Date >= ?
               AND gm.Date < ?
               AND opp.win = 1
               AND me.win = 0
             GROUP BY opp.account_id, opp.game_id
             ORDER BY defeats DESC, opp.account_id
             LIMIT 12',
            [$accountId, $yearStart, $yearEnd]
        );

        if (empty($rows)) {
            return [];
        }

        $opponentTotals = [];
        foreach ($rows as $row) {
            $opponentId = (int)($row['opponent_id'] ?? 0);
            $defeats = (int)($row['defeats'] ?? 0);
            $gameId = (int)($row['game_id'] ?? 0);

            if (!isset($opponentTotals[$opponentId])) {
                $opponentTotals[$opponentId] = [
                    'defeats' => 0,
                    'topGame' => ['game_id' => $gameId, 'defeats' => $defeats],
                ];
            }

            $opponentTotals[$opponentId]['defeats'] += $defeats;
            if ($defeats >= ($opponentTotals[$opponentId]['topGame']['defeats'] ?? 0)) {
                $opponentTotals[$opponentId]['topGame'] = ['game_id' => $gameId, 'defeats' => $defeats];
            }
        }

        uasort($opponentTotals, function ($a, $b) {
            return ($b['defeats'] ?? 0) <=> ($a['defeats'] ?? 0);
        });

        $topOpponents = array_slice($opponentTotals, 0, 3, true);
        $opponentIds = array_keys($topOpponents);
        $profiles = $this->loadAccountProfiles($opponentIds);
        $gameIds = array_map(fn ($entry) => (int)($entry['topGame']['game_id'] ?? 0), $topOpponents);
        $games = $this->loadGamesByIds($gameIds);

        $result = [];
        foreach ($topOpponents as $opponentId => $data) {
            $gameId = (int)($data['topGame']['game_id'] ?? 0);
            $result[] = [
                'profile' => $profiles[$opponentId] ?? null,
                'defeats' => (int)($data['defeats'] ?? 0),
                'game' => $games[$gameId] ?? ['id' => $gameId],
            ];
        }

        return $result;
    }

    /**
     * @param array<int> $accountIds
     * @return array<int, array<string, mixed>>
     */
    private function loadAccountProfiles(array $accountIds) : array
    {
        $profiles = [];
        foreach (array_unique(array_filter(array_map('intval', $accountIds))) as $accountId) {
            $profile = $this->fetchAccount($accountId);
            if ($profile instanceof vAccount) {
                $profiles[$accountId] = $this->formatAccountProfile($profile);
            }
        }

        return $profiles;
    }

    /**
     * @param array<int> $gameIds
     * @return array<int, array<string, mixed>>
     */
    private function loadGamesByIds(array $gameIds) : array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $gameIds))));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->fetchAll(
            "SELECT vg.Id AS game_id, vg.Name AS game_name, vg.ShortName AS game_short_name, vg.icon_path AS game_icon_path, vg.locator AS game_locator
             FROM v_game_info vg
             WHERE vg.Id IN ($placeholders)",
            $ids
        );

        $games = [];
        foreach ($rows as $row) {
            $gameId = (int)($row['game_id'] ?? 0);
            $iconPath = $this->mediaUrl(isset($row['game_icon_path']) ? (string)$row['game_icon_path'] : null);
            $games[$gameId] = [
                'id' => $gameId,
                'name' => (string)($row['game_name'] ?? ''),
                'shortName' => (string)($row['game_short_name'] ?? ''),
                'icon' => $iconPath,
                'locator' => (string)($row['game_locator'] ?? ''),
            ];
        }

        return $games;
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
