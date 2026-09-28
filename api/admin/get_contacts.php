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

    $conditions = [];
    $params = [];

    $search = trim((string) ($_GET["search"] ?? ""));
    if ($search !== "") {
        $conditions[] = "(FirstName LIKE :firstNameSearch OR LastName LIKE :lastNameSearch OR EmailAddress LIKE :emailSearch OR PhoneNumber LIKE :phoneSearch)";
        $searchPattern = "%" . $search . "%";
        $params[":firstNameSearch"] = $searchPattern;
        $params[":lastNameSearch"] = $searchPattern;
        $params[":emailSearch"] = $searchPattern;
        $params[":phoneSearch"] = $searchPattern;
    }

    if (array_key_exists("userId", $_GET)) {
        $contactOwnerId = filter_var($_GET["userId"], FILTER_VALIDATE_INT);
        if ($contactOwnerId === false || $contactOwnerId < 1) {
            http_response_code(400);
            response(400, "The userId filter must be a positive integer.", null);
        }
        $conditions[] = "UserID = :contactOwnerId";
        $params[":contactOwnerId"] = $contactOwnerId;
    }

    $sql = "SELECT ID, FirstName, LastName, EmailAddress, PhoneNumber, UserID FROM Contacts";
    if ($conditions) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY ID";

    $query = $db->prepare($sql);
    $query->execute($params);

    http_response_code(200);
    response(200, "Contacts retrieved successfully.", $query->fetchAll());
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    response(500, "Unable to retrieve contacts.", null);
}
