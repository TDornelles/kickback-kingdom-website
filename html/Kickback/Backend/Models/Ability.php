<?php

declare(strict_types=1);

namespace Kickback\Backend\Models;

class Ability extends RecordId
{
    public string $name;
    public string $desc;
    public string $icon;
    public int $prestigeGain;
    public float $prestigeMultiplier;
    public int $expGain;
    public float $expMultiplier;
    public int $levelGain;
    public float $levelMultiplier;
    public string $titleChange;

    public function __construct()
    {
        parent::__construct();

        $this->name = '';
        $this->desc = '';
        $this->icon = '';
        $this->prestigeGain = 0;
        $this->prestigeMultiplier = 0.0;
        $this->expGain = 0;
        $this->expMultiplier = 0.0;
        $this->levelGain = 0;
        $this->levelMultiplier = 0.0;
        $this->titleChange = '';
    }
}

?>
