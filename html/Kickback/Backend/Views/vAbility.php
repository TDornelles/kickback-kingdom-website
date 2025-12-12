<?php

declare(strict_types=1);

namespace Kickback\Backend\Views;

class vAbility extends vRecordId
{
    public string $name;
    public string $description;
    public string $icon;
    public int $prestigeGain;
    public float $prestigeMultiplier;
    public int $expGain;
    public float $expMultiplier;
    public int $levelGain;
    public float $levelMultiplier;
    public string $titleChange;

    public function __construct(string $ctime = '', int $crand = -1)
    {
        parent::__construct($ctime, $crand);

        $this->name = '';
        $this->description = '';
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
