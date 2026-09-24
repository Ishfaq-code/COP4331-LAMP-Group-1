<?php

require_once __DIR__ . "/services/db.php";
require_once __DIR__ . "/services/util.php";

// Validate the incoming request is a GET Method
if($_SERVER['REQUEST_METHOD'] != 'GET'){
    http_response_code(405);
    response(405,"Method not Allowed",null);
}

// Read search parameters from the URL
$userId = $_GET['userId'] ?? null;
$search = $_GET['search'] ?? '';

//Ensures that there is a User we are searching from 
if (!$userId) {
    http_response_code(400);
    response(400, "Missing required parameter: userId", null);
}

try{
    $db = getDB();

    //Sets up database query
    $stmt = $db->prepare('SELECT ID, FirstName, LastName, EmailAddress, PhoneNumber FROM contacts WHERE UserID = :userId AND (FirstName LIKE :search1 OR LastName LIKE :search2 OR EmailAddress LIKE :search3)');
    $searcher = '%'.$search.'%';
    $stmt->execute([':userId' => $userId, ':search1' => $searcher,':search2' => $searcher,':search3' => $searcher ]);

    $contacts = $stmt->fetchAll();

    http_response_code(200);
    response(200, "Contacts retrieved successfully", $contacts);

}catch(PDOException $e){
    http_response_code(500);
    response(500, $e->getMessage(), null);
}