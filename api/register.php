<?php

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/util.php";


// Validate the incoming request is a POST Method
if($_SERVER['REQUEST_METHOD'] != 'POST'){
    http_response_code(405);
    response(405, "Method Not Allowed!", null);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    http_response_code(400);
    response(400, "Request body must be valid JSON.", null);
}


$firstName = trim($data["firstName"]);
$lastName  = trim($data["lastName"]);
$login     = trim($data["login"]);
$password  = $data["password"];


if ($firstName === "" || $lastName === "" || $login === "" || !is_string($password)){
    http_response_code(400);
    response(400, "Must fill in all required registration information", null);
}

if (!validatePassword($password)){
    http_response_code(400);
    response(400, "Password must contain at least 8 characters, a number and a special symbol!", null);
}

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
    if (
        isset($e->errorInfo[1]) &&
        (int) $e->errorInfo[1] === 1062
    ) {
        http_response_code(409);
        response(409, "That login already exists.", null);
    }
    http_response_code(500);
    response(500, "Unable to create user.", null);

}

