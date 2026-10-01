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
    $page = filter_var($_GET["page"] ?? 1, FILTER_VALIDATE_INT);
    if ($page === false || $page < 1) {
        http_response_code(400);
        response(400, "The page must be a positive integer.", null);
    }
    $pageSize = 9;
    $offset = ($page - 1) * $pageSize;

    $search = trim((string) ($_GET["search"] ?? ""));
    if ($search !== "") {
        $conditions[] = "(FirstName LIKE :firstNameSearch OR LastName LIKE :lastNameSearch OR Login LIKE :loginSearch)";
        $searchPattern = "%" . $search . "%";
        $params[":firstNameSearch"] = $searchPattern;
        $params[":lastNameSearch"] = $searchPattern;
        $params[":loginSearch"] = $searchPattern;
    }

    foreach (["isAdmin" => "role", "isActive" => "active"] as $filter => $field) {
        if (!array_key_exists($filter, $_GET)) {
            continue;
        }

        $value = filter_var($_GET[$filter], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($value === null) {
            http_response_code(400);
            response(400, "The {$filter} filter must be true or false.", null);
        }

        if ($field === "role") {
            $conditions[] = "Role = :role";
            $params[":role"] = $value ? "Admin" : "User";
        } else {
            $conditions[] = "Active = :active";
            $params[":active"] = $value ? 1 : 0;
        }
    }

    $where = $conditions ? " WHERE " . implode(" AND ", $conditions) : "";
    $countQuery = $db->prepare("SELECT COUNT(*) FROM Users" . $where);
    $countQuery->execute($params);
    $totalItems = (int) $countQuery->fetchColumn();

    $sql = "SELECT ID, FirstName, LastName, Login, Role, Active FROM Users" . $where . " ORDER BY FirstName LIMIT :limit OFFSET :offset";

    $query = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $query->bindValue($key, $value);
    }
    $query->bindValue(":limit", $pageSize, PDO::PARAM_INT);
    $query->bindValue(":offset", $offset, PDO::PARAM_INT);
    $query->execute();

    $users = array_map(static function ($row) {
        return [
            "id" => (int) $row["ID"],
            "firstName" => $row["FirstName"],
            "lastName" => $row["LastName"],
            "login" => $row["Login"],
            "role" => $row["Role"],
            "active" => (int) $row["Active"]
        ];
    }, $query->fetchAll());

    http_response_code(200);
    response(200, "Users retrieved successfully.", $users, [
        "page" => $page,
        "pageSize" => $pageSize,
        "totalItems" => $totalItems,
        "totalPages" => max(1, (int) ceil($totalItems / $pageSize))
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    response(500, "Unable to retrieve users.", null);
}
