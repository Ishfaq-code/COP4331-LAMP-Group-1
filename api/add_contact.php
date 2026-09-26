<?php

require_once __DIR__ . "/services/db.php";
require_once __DIR__ . "/services/util.php";

// Validate the incoming request is a POST Method
if($_SERVER['REQUEST_METHOD'] != 'POST'){
    http_response_code(405);
    response(405,"Method not Allowed",null);
}

$userId = checkAuth();
if ($userId === null) {
    http_response_code(401);
    response(401, "Unauthenticated", null);
}

// Get data from the request
$data = json_decode(file_get_contents("php://input"), true);

// Validate data exists
if (!is_array($data)) {
    http_response_code(400);
    response(400, "Request body must be valid JSON.", null);
}

//Load data
$firstName = trim($data["firstName"]);
$lastName  = trim($data["lastName"]);
$email = trim($data["email"]);
$phone = trim($data["phone"]);

if (empty($firstName) || empty($lastName)) {
    http_response_code(400);
    response(400, "Missing required fields: firstName and lastName are required.", null);
}

try{
    $db = getDB();

    //Prepares MySQL query
    $query = $db->prepare("INSERT INTO contacts (FirstName,LastName,EmailAddress,PhoneNumber,DateCreated,UserID) VALUES (:firstName, :lastName, :email, :phone, NOW(), :userId)");
    $query->execute([':firstName' => $firstName, ':lastName' => $lastName, ':email' => $email, ':phone' => $phone, ':userId' => $userId]);

    $contactId = $db->lastInsertId();

    http_response_code(201);
    response(201,"Contact Created",["id" => $contactId,"firstName" => $firstName,"lastName" => $lastName,"email" => $email,"phone" => $phone,"userId" => $userId, "dateCreated" => date('Y-m-d H:i:s')]);
}catch(PDOException $e){
    http_response_code(500);
    response(500,$e->getMessage(), null);
}
