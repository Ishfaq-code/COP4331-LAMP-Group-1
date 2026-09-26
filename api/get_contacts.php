<?php

require_once __DIR__ . "/services/db.php";
require_once __DIR__ . "/services/util.php";

// Validate the incoming request is a GET Method
if($_SERVER['REQUEST_METHOD'] != 'GET'){
    http_response_code(405);
    response(405,"Method not Allowed",null);
}

$userId = checkAuth();
if ($userId === null) {
    http_response_code(401);
    response(401, "Unauthenticated", null);
}

// Read search parameters from the URL
$search = $_GET['search'] ?? '';

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
