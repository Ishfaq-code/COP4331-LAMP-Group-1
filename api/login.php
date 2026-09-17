<?php

require_once __DIR__ . "/services/db.php";
require_once __DIR__ . "/services/util.php";

// Validate the incoming request is a POST Method
if($_SERVER['REQUEST_METHOD'] != 'POST'){
    http_response_code(405);
    response(405, "Method Not Allowed!", null);
}

$data = json_decode(file_get_contents("php://input"), true);
$login     = trim($data["login"]);
$password  = $data["password"];


// Validate data exists
if (!is_array($data)) {
    http_response_code(400);
    response(400, "Request body must be valid JSON.", null);
}

if ($login === "" || !is_string($password) || $password === ''){
    http_response_code(400);
    response(400, "Login and Password are required ", null);
}

try{
    $db = getDB();
    if ($db){
        $query = $db->prepare("SELECT ID, FirstName, LastName, Login, Password FROM Users WHERE Login = :login");
        $query->execute([":login" => $login]);
        $user = $query->fetch();

        if($user) {
            if (password_verify($password, $user["Password"])) {
                http_response_code(200);
                response(200, "Successfully logged in!", ["id" => $user["ID"], "firstName" => $user["FirstName"], "lastName" => $user["LastName"], "login" => $user["Login"]]);
            }
        }
        http_response_code(401);
        response(401, "Failed to fetch user. The username or password you have entered are incorrect.", null);

    }

}
catch(PDOException $e){
    http_response_code(500);
    response(500, "Unable to create user.", $e);
}
