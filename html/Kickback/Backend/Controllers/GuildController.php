<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Services\Database;

class GuildController
{
    public static function listGuilds(): Response
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare('SELECT Id, Name FROM guild ORDER BY Name ASC');

        if ($stmt === false) {
            return new Response(false, 'Unable to prepare guild list query.', null);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to load guild list.', null);
        }

        $result = $stmt->get_result();
        if ($result === false) {
            $stmt->close();
            return new Response(false, 'Unable to fetch guild list.', null);
        }

        $guilds = [];
        while ($row = $result->fetch_assoc()) {
            $guilds[] = [
                'id' => isset($row['Id']) ? (int) $row['Id'] : null,
                'name' => (string) ($row['Name'] ?? ''),
            ];
        }

        $stmt->close();

        return new Response(true, 'Guild list loaded.', $guilds);
    }
}
?>
