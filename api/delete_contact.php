<?php

require_once __DIR__ . "/services/db.php";
require_once __DIR__ . "/services/util.php";

// Validate the incoming request is a DELETE Method
if($_SERVER['REQUEST_METHOD'] != 'DELETE'){
    http_response_code(405);
    response(405,"Method not Allowed",null);
}

// Get data from the request
$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"];
$userId = $data['userId'];

// Validate required data 
if (empty($id) || empty($userId)) {
    http_response_code(400);
    response(400, "Missing required fields id and userId are required.", null);
}

try{
    $db = getDB();

    //Prepares MySQL query
    $query = $db->prepare("DELETE FROM contacts WHERE ID = :id AND UserID = :userId");
    $query->execute([':id' => $id, ':userId' => $userId]);

    //Checks if Contact was deleted
    if($query->rowCount() > 0){
        http_response_code(200);
        response(200, "Contact deleted successfully", ["id" => $id]);
    }
    else{
        http_response_code(404);
        response(404, "Contact not found", null);
    }

}catch(PDOException $e){
    http_response_code(500);
    response(500, "Database error", null);
}