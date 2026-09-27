<?php

require_once __DIR__ . "/../services/db.php";
require_once __DIR__ . "/../services/util.php";


// Validate the incoming request is a POST Method
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    response(405,"Method not Allowed",null);
}

// Check User
$userId = checkAuth();
if ($userId === null) {
    http_response_code(401);
    response(401, "Unauthenticated", null);
}

// Check admin
try{
    $db = getDB();
    $query = $db->prepare("SELECT Role FROM Users WHERE ID = :userId");
    $query->execute([':userId' => $userId]);

    $user = $query->fetch();
    if (!$user || $user['Role'] !== 'Admin') {
        http_response_code(403);
        response(403, "Forbidden", null);
    }

}
catch(PDOException $e){
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

$requiredStringFields = ["firstName", "lastName", "login", "role", "password"];
foreach ($requiredStringFields as $field) {
    if (!isset($data[$field]) || !is_string($data[$field])) {
        http_response_code(400);
        response(400, "Must fill in all required registration information", null);
    }
}

if (!array_key_exists("active", $data) || !in_array($data["active"], [0, 1, "0", "1"], true)) {
    http_response_code(400);
    response(400, "The active field must be 0 or 1.", null);
}

if(!array_key_exists("role", $data) || !in_array($data["role"], ["User", "Admin"], true)){
    http_response_code(400);
    response(400, "The role field must be 'User' or 'Admin'.", null);
}

$firstName = trim($data["firstName"]);
$lastName  = trim($data["lastName"]);
$login     = trim($data["login"]);
$role      = trim($data["role"]);
$active    = (int) $data["active"];
$password  = $data["password"];


// Validate data is non empty
if ($firstName === "" || $lastName === "" || $login === "" || $role === "" || trim($password) === "") {
    http_response_code(400);
    response(400, "Must fill in all required registration information", null);
}

// Attempt to create a user in the database with credentials
try{
    $db = getDB();
    if($db){
        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        $query = $db->prepare(
        "INSERT INTO Users
            (FirstName, LastName, Login, Password, Role, Active)
        VALUES
            (:firstName, :lastName, :login, :password, :role, :active)"
        );
        $query->execute([":firstName" => $firstName, ":lastName"  => $lastName, ":login"     => $login, ":password"  => $hashed_pass, ":role" => $role, ":active" => $active]);
        $user = [
            "id" => $db->lastInsertId(),
            "firstName" => $firstName,
            "lastName" => $lastName,
            "login" => $login,
            "role" => $role,
            "active" => $active
        ];
        http_response_code(201);
        response(201, "Successfully created registered user!", $user);
    }

}
catch(PDOException $e){
    // Check if user exists error
    if (
        isset($e->errorInfo[1]) &&
        (int) $e->errorInfo[1] === 1062
    ) {
        http_response_code(409);
        response(409, "That login already exists.", null);
    }
    error_log($e->getMessage());
    http_response_code(500);
    response(500, "Unable to create user.", null);

}
