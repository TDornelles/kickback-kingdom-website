<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Ability;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vAbility;
use Kickback\Backend\Views\vRecordId;
use Kickback\Services\Database;

class AbilityController
{
    private static function rowToVAbility(array $row, vRecordId $abilityId): vAbility
    {
        $ability = new vAbility($abilityId->ctime, $abilityId->crand);

        $ability->name = $row['name'] ?? '';
        $ability->description = $row['desc'] ?? '';
        $ability->prestigeGain = (int)($row['prestige_gain'] ?? 0);
        $ability->prestigeMultiplier = (float)($row['prestige_multiplier'] ?? 0.0);
        $ability->expGain = (int)($row['exp_gain'] ?? 0);
        $ability->expMultiplier = (float)($row['exp_multiplier'] ?? 0.0);
        $ability->levelGain = (int)($row['level_gain'] ?? 0);
        $ability->levelMultiplier = (float)($row['level_multiplier'] ?? 0.0);
        $ability->titleChange = trim((string)($row['title_change'] ?? ''));

        return $ability;
    }

    private static function validateAbility(Ability $ability): ?string
    {
        if (trim($ability->name) === '') {
            return 'Ability name is required.';
        }

        if (trim($ability->desc) === '') {
            return 'Ability description is required.';
        }

        $numericFields = [
            'prestige gain' => $ability->prestigeGain,
            'prestige multiplier' => $ability->prestigeMultiplier,
            'experience gain' => $ability->expGain,
            'experience multiplier' => $ability->expMultiplier,
            'level gain' => $ability->levelGain,
            'level multiplier' => $ability->levelMultiplier,
        ];

        foreach ($numericFields as $label => $value) {
            if (!is_numeric($value)) {
                return "Invalid value for {$label}.";
            }

            if ($value < 0) {
                return ucfirst($label) . ' cannot be negative.';
            }
        }

        if (strlen($ability->titleChange) > 45) {
            return 'Title change cannot exceed 45 characters.';
        }

        return null;
    }

    public static function getAbilityTable(): Response
    {
        $conn = Database::getConnection();

        $sql = 'SELECT Id, name, `desc`, prestige_gain, prestige_multiplier, exp_gain, exp_multiplier, level_gain, level_multiplier, title_change FROM ability ORDER BY name';
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return new Response(false, 'Failed to load abilities: ' . $conn->error);
        }

        if (!$stmt->execute()) {
            return new Response(false, 'Failed to load abilities: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $abilities = [];

        while ($row = $result->fetch_assoc()) {
            $abilityId = new vRecordId('', (int)$row['Id']);
            $abilities[] = self::rowToVAbility($row, $abilityId);
        }

        $stmt->close();

        return new Response(true, 'Abilities retrieved successfully', $abilities);
    }

    public static function getAbilityById(vRecordId $abilityId): Response
    {
        if ($abilityId->crand <= 0) {
            return new Response(false, 'A valid ability id must be provided.');
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare('SELECT Id, name, `desc`, prestige_gain, prestige_multiplier, exp_gain, exp_multiplier, level_gain, level_multiplier, title_change FROM ability WHERE Id = ?');

        if (!$stmt) {
            return new Response(false, 'Failed to load ability: ' . $conn->error);
        }

        $stmt->bind_param('i', $abilityId->crand);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            return new Response(false, 'Failed to load ability: ' . $error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return new Response(false, 'Ability not found.');
        }

        $ability = self::rowToVAbility($row, $abilityId);
        return new Response(true, 'Ability retrieved successfully', $ability);
    }

    public static function insertAbility(Ability $ability): Response
    {
        $validationError = self::validateAbility($ability);
        if ($validationError !== null) {
            return new Response(false, $validationError);
        }

        $conn = Database::getConnection();

        $sql = 'INSERT INTO ability (name, `desc`, prestige_gain, prestige_multiplier, exp_gain, exp_multiplier, level_gain, level_multiplier, title_change) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return new Response(false, 'Failed to prepare ability insert: ' . $conn->error);
        }

        $stmt->bind_param(
            'ssididids',
            $ability->name,
            $ability->desc,
            $ability->prestigeGain,
            $ability->prestigeMultiplier,
            $ability->expGain,
            $ability->expMultiplier,
            $ability->levelGain,
            $ability->levelMultiplier,
            $ability->titleChange
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            return new Response(false, 'Failed to create ability: ' . $error);
        }

        $newId = (int)$conn->insert_id;
        $stmt->close();

        $abilityView = new vAbility('', $newId);
        $abilityView->name = $ability->name;
        $abilityView->description = $ability->desc;
        $abilityView->prestigeGain = $ability->prestigeGain;
        $abilityView->prestigeMultiplier = $ability->prestigeMultiplier;
        $abilityView->expGain = $ability->expGain;
        $abilityView->expMultiplier = $ability->expMultiplier;
        $abilityView->levelGain = $ability->levelGain;
        $abilityView->levelMultiplier = $ability->levelMultiplier;
        $abilityView->titleChange = $ability->titleChange;

        return new Response(true, 'Ability created successfully.', $abilityView);
    }

    public static function updateAbility(Ability $ability): Response
    {
        if ($ability->crand <= 0) {
            return new Response(false, 'A valid ability id must be provided.');
        }

        $validationError = self::validateAbility($ability);
        if ($validationError !== null) {
            return new Response(false, $validationError);
        }

        $conn = Database::getConnection();

        $sql = 'UPDATE ability SET name = ?, `desc` = ?, prestige_gain = ?, prestige_multiplier = ?, exp_gain = ?, exp_multiplier = ?, level_gain = ?, level_multiplier = ?, title_change = ? WHERE Id = ?';
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return new Response(false, 'Failed to prepare ability update: ' . $conn->error);
        }

        $stmt->bind_param(
            'ssidididsi',
            $ability->name,
            $ability->desc,
            $ability->prestigeGain,
            $ability->prestigeMultiplier,
            $ability->expGain,
            $ability->expMultiplier,
            $ability->levelGain,
            $ability->levelMultiplier,
            $ability->titleChange,
            $ability->crand
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            return new Response(false, 'Failed to update ability: ' . $error);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        return new Response(true, 'Ability updated successfully.', ['affectedRows' => $affected]);
    }

    public static function deleteAbility(vRecordId $abilityId): Response
    {
        if ($abilityId->crand <= 0) {
            return new Response(false, 'A valid ability id must be provided.');
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare('DELETE FROM ability WHERE Id = ?');

        if (!$stmt) {
            return new Response(false, 'Failed to prepare ability delete: ' . $conn->error);
        }

        $stmt->bind_param('i', $abilityId->crand);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            return new Response(false, 'Failed to delete ability: ' . $error);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        return new Response(true, 'Ability deleted successfully.', ['affectedRows' => $affected]);
    }
}

?>
