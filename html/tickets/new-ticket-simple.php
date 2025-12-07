<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__ . '/..') . "/Kickback/init.php");

use Kickback\Common\Version;

$target = Version::urlBetaPrefix() . '/tickets/new-ticket.php';
header('Location: ' . $target, true, 302);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url=<?= htmlspecialchars($target); ?>">
    <title>Redirecting…</title>
</head>
<body>
    <p>If you are not redirected, <a href="<?= htmlspecialchars($target); ?>">submit a ticket here</a>.</p>
</body>
</html>
