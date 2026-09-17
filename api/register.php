<?php

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/util.php";


// Validate the incoming request is a POST Method
if($_SERVER['REQUEST_METHOD'] != 'POST'){
    http_response_code(405);
    response(405, "Method Not Allowed!", null);
}

// Get data from request
$data = json_decode(file_get_contents("php://input"), true);

// Validate data exists
if (!is_array($data)) {
    http_response_code(400);
    response(400, "Request body must be valid JSON.", null);
}


$firstName = trim($data["firstName"]);
$lastName  = trim($data["lastName"]);
$login     = trim($data["login"]);
$password  = $data["password"];

// Validate data is non empty
if ($firstName === "" || $lastName === "" || $login === "" || !is_string($password)){
    http_response_code(400);
    response(400, "Must fill in all required registration information", null);
}

// Validate password is strong
if (!validatePassword($password)){
    http_response_code(400);
    response(400, "Password must contain at least 8 characters, a number and a special symbol!", null);
}

// Attempt to create a user in the database with credentials
try{
    $db = getDB();
    if($db){
        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        $query = $db->prepare(
        "INSERT INTO Users
            (FirstName, LastName, Login, Password)
        VALUES
            (:firstName, :lastName, :login, :password)"
        );
        $query->execute([":firstName" => $firstName, ":lastName"  => $lastName, ":login"     => $login, ":password"  => $hashed_pass]);
        $user = [
            "id" => $db->lastInsertId(),
            "firstName" => $firstName,
            "lastName" => $lastName,
            "login" => $login
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
        response(409, "That login already exists.", $e);
    }
    http_response_code(500);
    response(500, "Unable to create user.", $e);

}

