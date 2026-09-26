<?php

require_once __DIR__ . "/services/db.php";
require_once __DIR__ . "/services/util.php";

// Validate the incoming request is a PUT Method
if($_SERVER['REQUEST_METHOD'] != 'PUT'){
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

try{
    $db = getDB();
    //Prepares MySQL query
    $stmt = $db->prepare('UPDATE contacts SET FirstName = :FirstName, LastName = :LastName, EmailAddress = :EmailAddress, PhoneNumber = :PhoneNumber, DateUpdated = NOW() WHERE ID = :ID AND UserID = :UserID');

    $stmt->execute([':FirstName' => $data['FirstName'], ':LastName' => $data['LastName'], ':EmailAddress' => $data['EmailAddress'], ':PhoneNumber' => $data['PhoneNumber'] ?? null, ':ID' => $data['ID'], ':UserID' => $userId]);

    //Checks if Contact was updtated
    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        response(200, "Contact updated successfully", $data);
    } else {
        http_response_code(404);
        response(404, "Contact not found or no changes were made", null);
    }

}
catch(PDOException $e){
    http_response_code(500);
    response(500, "Database error", null);
}
