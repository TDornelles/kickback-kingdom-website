<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

class SeasonController
{
    public const SEASON_NONE         = 'none';
    public const SEASON_CHRISTMAS    = 'christmas';
    public const SEASON_NEW_YEAR     = 'new_year';
    public const SEASON_VALENTINES   = 'valentines';
    public const SEASON_EASTER       = 'easter';
    public const SEASON_HALLOWEEN    = 'halloween';
    public const SEASON_THANKSGIVING = 'thanksgiving';

    /**
     * Get the current active holiday season key.
     *
     * Example:
     *  - 'christmas'
     *  - 'halloween'
     *  - 'none'
     */
    public function getCurrentSeasonKey(?\DateTimeInterface $now = null): string
    {
        if ($now === null) {
            $now = new \DateTimeImmutable('now');
        }

        // Priority order if overlaps ever happen
        if ($this->isChristmasSeason($now)) {
            return self::SEASON_CHRISTMAS;
        }

        if ($this->isNewYearSeason($now)) {
            return self::SEASON_NEW_YEAR;
        }

        if ($this->isValentinesSeason($now)) {
            return self::SEASON_VALENTINES;
        }

        if ($this->isEasterSeason($now)) {
            return self::SEASON_EASTER;
        }

        if ($this->isHalloweenSeason($now)) {
            return self::SEASON_HALLOWEEN;
        }

        if ($this->isThanksgivingSeason($now)) {
            return self::SEASON_THANKSGIVING;
        }

        return self::SEASON_NONE;
    }

    /**
     * Check if the current season matches a specific key.
     *
     * @param string $seasonKey One of the SEASON_* constants.
     */
    public function isSeason(string $seasonKey, ?\DateTimeInterface $now = null): bool
    {
        return $this->getCurrentSeasonKey($now) === $seasonKey;
    }

    // ─────────────────────────────────────────────────────────────
    // Convenience season checks
    // ─────────────────────────────────────────────────────────────

    public function isChristmasSeason(?\DateTimeInterface $now = null): bool
    {
        if ($now === null) {
            $now = new \DateTimeImmutable('now');
        }

        $year  = (int)$now->format('Y');
        $start = new \DateTimeImmutable("$year-12-01 00:00:00");
        $end   = new \DateTimeImmutable("$year-12-26 23:59:59");

        return $this->isBetween($now, $start, $end);
    }

    public function isNewYearSeason(?\DateTimeInterface $now = null): bool
    {
        if ($now === null) {
            $now = new \DateTimeImmutable('now');
        }

        $year = (int)$now->format('Y');

        // Season from Dec 27 (previous year) to Jan 3 (current year)
        $start = new \DateTimeImmutable(($year - 1) . "-12-27 00:00:00");
        $end   = new \DateTimeImmutable("$year-01-03 23:59:59");

        return $this->isBetween($now, $start, $end);
    }

    public function isValentinesSeason(?\DateTimeInterface $now = null): bool
    {
        if ($now === null) {
            $now = new \DateTimeImmutable('now');
        }

        $year  = (int)$now->format('Y');
        $start = new \DateTimeImmutable("$year-02-07 00:00:00");
        $end   = new \DateTimeImmutable("$year-02-21 23:59:59");

        return $this->isBetween($now, $start, $end);
    }

    public function isEasterSeason(?\DateTimeInterface $now = null): bool
    {
        if ($now === null) {
            $now = new \DateTimeImmutable('now');
        }

        $year   = (int)$now->format('Y');
        $easter = $this->getWesternEasterSunday($year);

        // Example window: 10 days before Easter to 3 days after
        $start = $easter->modify('-10 days')->setTime(0, 0, 0);
        $end   = $easter->modify('+3 days')->setTime(23, 59, 59);

        return $this->isBetween($now, $start, $end);
    }

    public function isHalloweenSeason(?\DateTimeInterface $now = null): bool
    {
        if ($now === null) {
            $now = new \DateTimeImmutable('now');
        }

        $year  = (int)$now->format('Y');
        $start = new \DateTimeImmutable("$year-10-01 00:00:00");
        $end   = new \DateTimeImmutable("$year-10-31 23:59:59");

        return $this->isBetween($now, $start, $end);
    }

    public function isThanksgivingSeason(?\DateTimeInterface $now = null): bool
    {
        if ($now === null) {
            $now = new \DateTimeImmutable('now');
        }

        $year = (int)$now->format('Y');

        // Fourth Thursday in November (US Thanksgiving)
        $novemberFirst = new \DateTimeImmutable("$year-11-01 00:00:00");
        $dayOfWeek     = (int)$novemberFirst->format('w'); // 0=Sun, 4=Thu
        $thursdayOffset = (4 - $dayOfWeek + 7) % 7;
        $firstThursday  = $novemberFirst->modify("+$thursdayOffset days");
        $fourthThursday = $firstThursday->modify('+21 days'); // 3 more weeks

        // Whole Thanksgiving week: Monday–Sunday
        $start = $fourthThursday->modify('-3 days')->setTime(0, 0, 0);
        $end   = $fourthThursday->modify('+3 days')->setTime(23, 59, 59);

        return $this->isBetween($now, $start, $end);
    }

    // ─────────────────────────────────────────────────────────────
    // Season config for UI (carousel, hero, etc.)
    // ─────────────────────────────────────────────────────────────

    /**
     * Get a season-aware config block.
     *
     * Example:
     * [
     *     'key'      => 'christmas',
     *     'title'    => 'Happy Holidays from Kickback Kingdom',
     *     'subtitle' => 'Join special winter events...',
     *     'images'   => ['/assets/media/seasonal/484.png', '/assets/media/seasonal/485.png'],
     * ]
     */
    public function getSeasonConfig(?\DateTimeInterface $now = null): array
    {
        $season = $this->getCurrentSeasonKey($now);

        switch ($season) {
            case self::SEASON_CHRISTMAS:
                return [
                    'key'      => $season,
                    'title'    => 'Happy Holidays from Kickback Kingdom',
                    'subtitle' => 'Join special winter events, unlock festive loot, and celebrate with your guild.',
                    'images'   => [
                        '/assets/media/seasonal/484.png',
                        '/assets/media/seasonal/485.png',
                    ],
                ];

            case self::SEASON_NEW_YEAR:
                return [
                    'key'      => $season,
                    'title'    => 'New Year, New Adventures',
                    'subtitle' => 'Kick off the year with fresh quests, seasons, and challenges in Kickback Kingdom.',
                    'images'   => [
                        '/assets/images/kk-1.jpg',
                        '/assets/images/kk-2.jpg',
                    ],
                ];

            case self::SEASON_HALLOWEEN:
                return [
                    'key'      => $season,
                    'title'    => 'Spooky Season in Kickback Kingdom',
                    'subtitle' => 'Face haunted dungeons, ghostly quests, and limited-time eerie rewards.',
                    'images'   => [
                        '/assets/images/kk-1.jpg',
                        '/assets/images/kk-2.jpg',
                    ],
                ];

            case self::SEASON_VALENTINES:
                return [
                    'key'      => $season,
                    'title'    => 'Valentine’s in the Kingdom',
                    'subtitle' => 'Team up with your favorite guildmates and earn special duo rewards.',
                    'images'   => [
                        '/assets/images/kk-1.jpg',
                        '/assets/images/kk-2.jpg',
                    ],
                ];

            case self::SEASON_EASTER:
                return [
                    'key'      => $season,
                    'title'    => 'Springtime in Kickback Kingdom',
                    'subtitle' => 'Hunt for hidden treasures, eggs, and limited-time spring cosmetics.',
                    'images'   => [
                        '/assets/images/kk-1.jpg',
                        '/assets/images/kk-2.jpg',
                    ],
                ];

            case self::SEASON_THANKSGIVING:
                return [
                    'key'      => $season,
                    'title'    => 'Feast of Guilds',
                    'subtitle' => 'Celebrate with your guild, share bounties, and earn bonus rewards together.',
                    'images'   => [
                        '/assets/images/kk-1.jpg',
                        '/assets/images/kk-2.jpg',
                    ],
                ];

            default:
                return [
                    'key'      => self::SEASON_NONE,
                    'title'    => 'Welcome to Kickback Kingdom',
                    'subtitle' => 'The gaming realm where friendships are formed and scores are settled.',
                    'images'   => [
                        '/assets/images/kk-1.jpg',
                        '/assets/images/kk-2.jpg',
                    ],
                ];
        }
    }

    /**
     * Get a seasonal background image URL for the <body>.
     *
     * Returns null if no seasonal override should be applied,
     * so your normal CSS background can take over.
     */
    public function getBackgroundImageUrl(?\DateTimeInterface $now = null): ?string
    {
        $season = $this->getCurrentSeasonKey($now);

        switch ($season) {
            case self::SEASON_CHRISTMAS:
                // Your current Christmas / winter background
                return '/assets/media/seasonal/486.png';

            case self::SEASON_HALLOWEEN:
                // Example – swap in whatever path you want
                return '/assets/media/seasonal/halloween-bg.png';

            case self::SEASON_EASTER:
                return '/assets/media/seasonal/easter-bg.png';

            case self::SEASON_VALENTINES:
                return '/assets/media/seasonal/valentines-bg.png';

            case self::SEASON_NEW_YEAR:
                return '/assets/media/seasonal/newyear-bg.png';

            case self::SEASON_THANKSGIVING:
                return '/assets/media/seasonal/thanksgiving-bg.png';

            default:
                // null = no seasonal override, use default CSS
                return null;
        }
    }

    public static function getSeasonalParticipationRewardItemIds(?\DateTimeInterface $now = null): array
    {
        $controller = new self();
        $season = $controller->getCurrentSeasonKey($now);

        switch ($season) {
            case self::SEASON_CHRISTMAS:
                // TODO: replace 123 with the actual Candy Cane item_id
                return [124];

            case self::SEASON_HALLOWEEN:
                // Example: spooky loot
                // return [456];
                return [115];

            case self::SEASON_EASTER:
                // Example: Easter egg item id
                // return [789];
                return [5];

            // Add more seasons as needed

            default:
                // No seasonal extras
                return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Check if a date is between two others (inclusive).
     */
    private function isBetween(
        \DateTimeInterface $date,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): bool {
        return $date >= $start && $date <= $end;
    }

    /**
     * Compute Western (Gregorian) Easter Sunday for a given year.
     * Meeus/Jones/Butcher algorithm.
     */
    private function getWesternEasterSunday(int $year): \DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31); // 3 = March, 4 = April
        $day   = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day));
    }
}
