<?php

require_once __DIR__ . "/../services/db.php";
require_once __DIR__ . "/../services/util.php";

// Validate the incoming request is a PUT method.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'PUT') {
    http_response_code(405);
    response(405, "Method not Allowed", null);
}

// Check user authentication.
$userId = checkAuth();
if ($userId === null) {
    http_response_code(401);
    response(401, "Unauthenticated", null);
}

// Check administrator access.
try {
    $db = getDB();
    $query = $db->prepare("SELECT Role FROM Users WHERE ID = :userId");
    $query->execute([':userId' => $userId]);
    $user = $query->fetch();

    if (!$user || $user['Role'] !== 'Admin') {
        http_response_code(403);
        response(403, "Forbidden", null);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage());
    response(500, "Unable to verify administrator access.", null);
}

// Get and validate the JSON request body.
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    http_response_code(400);
    response(400, "Request body must be valid JSON.", null);
}

$requiredStringFields = ["firstName", "lastName", "login", "role"];
foreach ($requiredStringFields as $field) {
    if (!isset($data[$field]) || !is_string($data[$field])) {
        http_response_code(400);
        response(400, "Must fill in all required user information.", null);
    }
}

if (!array_key_exists("id", $data) || filter_var($data["id"], FILTER_VALIDATE_INT) === false || (int) $data["id"] < 1) {
    http_response_code(400);
    response(400, "The id field must be a positive integer.", null);
}

if (!array_key_exists("active", $data) || !in_array($data["active"], [0, 1, "0", "1"], true)) {
    http_response_code(400);
    response(400, "The active field must be 0 or 1.", null);
}

if (!in_array($data["role"], ["User", "Admin"], true)) {
    http_response_code(400);
    response(400, "The role field must be 'User' or 'Admin'.", null);
}

$targetUserId = (int) $data["id"];
$firstName = trim($data["firstName"]);
$lastName = trim($data["lastName"]);
$login = trim($data["login"]);
$role = $data["role"];
$active = (int) $data["active"];

if ($firstName === "" || $lastName === "" || $login === "") {
    http_response_code(400);
    response(400, "Must fill in all required user information.", null);
}

// Prevent an administrator from locking themselves out of administrator access.
if ($targetUserId === $userId && ($role !== "Admin" || $active !== 1)) {
    http_response_code(400);
    response(400, "You cannot deactivate or demote your own account.", null);
}

try {
    $query = $db->prepare(
        "UPDATE Users
         SET FirstName = :firstName,
             LastName = :lastName,
             Login = :login,
             Role = :role,
             Active = :active
         WHERE ID = :id"
    );
    $query->execute([
        ":firstName" => $firstName,
        ":lastName" => $lastName,
        ":login" => $login,
        ":role" => $role,
        ":active" => $active,
        ":id" => $targetUserId
    ]);

    if ($query->rowCount() === 0) {
        $existsQuery = $db->prepare("SELECT ID FROM Users WHERE ID = :id");
        $existsQuery->execute([":id" => $targetUserId]);
        if (!$existsQuery->fetch()) {
            http_response_code(404);
            response(404, "User not found.", null);
        }
    }

    $updatedUser = [
        "id" => $targetUserId,
        "firstName" => $firstName,
        "lastName" => $lastName,
        "login" => $login,
        "role" => $role,
        "active" => $active
    ];
    http_response_code(200);
    response(200, "User updated successfully.", $updatedUser);
} catch (PDOException $e) {
    if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
        http_response_code(409);
        response(409, "That login already exists.", null);
    }
    error_log($e->getMessage());
    http_response_code(500);
    response(500, "Unable to update user.", null);
}
