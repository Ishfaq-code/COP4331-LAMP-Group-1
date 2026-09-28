<?php

require_once __DIR__ . "/../services/db.php";
require_once __DIR__ . "/../services/util.php";

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    response(405, "Method not Allowed", null);
}

$userId = checkAuth();
if ($userId === null) {
    http_response_code(401);
    response(401, "Unauthenticated", null);
}

try {
    $db = getDB();

    $authQuery = $db->prepare("SELECT Role FROM Users WHERE ID = :userId");
    $authQuery->execute([":userId" => $userId]);
    $user = $authQuery->fetch();

    if (!$user || $user["Role"] !== "Admin") {
        http_response_code(403);
        response(403, "Forbidden", null);
    }

    $userStatsQuery = $db->query(
        "SELECT
            COUNT(*) AS totalUsers,
            COALESCE(SUM(CASE WHEN Active = 1 THEN 1 ELSE 0 END), 0) AS activeUsers,
            COALESCE(SUM(CASE WHEN Active = 0 THEN 1 ELSE 0 END), 0) AS suspendedUsers,
            COALESCE(SUM(CASE WHEN Role = 'Admin' THEN 1 ELSE 0 END), 0) AS admins
         FROM Users"
    );
    $userStats = $userStatsQuery->fetch();
    $contactStatsQuery = $db->query("SELECT COUNT(*) AS totalContacts FROM Contacts");
    $contactStats = $contactStatsQuery->fetch();

    $stats = [
        "totalUsers" => (int) $userStats["totalUsers"],
        "totalContacts" => (int) $contactStats["totalContacts"],
        "activeUsers" => (int) $userStats["activeUsers"],
        "suspendedUsers" => (int) $userStats["suspendedUsers"],
        "admins" => (int) $userStats["admins"]
    ];

    http_response_code(200);
    response(200, "Dashboard statistics retrieved successfully.", $stats);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    response(500, "Unable to retrieve dashboard statistics.", null);
}
