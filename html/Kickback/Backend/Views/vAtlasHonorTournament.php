<?php
declare(strict_types=1);

namespace Kickback\Backend\Views;

/**
 * Atlas Archive view model for tournament honors.
 */
class vAtlasHonorTournament
{
    public ?vAccount $account = null;
    public int $tournamentsWon = 0;
    public int $gamesCount = 0;
    /**
     * @var array<vGame>
     */
    public array $games = [];

    /**
     * Convert to a front-end friendly array.
     *
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        return [
            'profile' => $this->account ? [
                'id' => $this->account->crand,
                'username' => $this->account->username,
                'avatar' => $this->account->profilePictureURL(),
                'level' => $this->account->level ?? null,
                'prestige' => $this->account->prestige ?? null,
                'badges' => $this->account->badges ?? null,
            ] : null,
            'tournamentsWon' => $this->tournamentsWon,
            'gamesCount' => $this->gamesCount,
            'games' => array_map(function (vGame $game) {
                return [
                    'id' => $game->crand,
                    'name' => $game->name,
                    'icon' => isset($game->icon) ? $game->icon->getFullPath() : null,
                    'locator' => $game->locator,
                ];
            }, $this->games),
        ];
    }
}

?>
