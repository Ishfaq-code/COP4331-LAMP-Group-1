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
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
if ($page === false || $page < 1) {
    http_response_code(400);
    response(400, "The page must be a positive integer.", null);
}
$pageSize = 9;
$offset = ($page - 1) * $pageSize;

try{
    $db = getDB();

    $where = 'UserID = :userId AND (FirstName LIKE :search1 OR LastName LIKE :search2 OR EmailAddress LIKE :search3 OR PhoneNumber LIKE :search4)';
    $searcher = '%'.$search.'%';
    $params = [':userId' => $userId, ':search1' => $searcher, ':search2' => $searcher, ':search3' => $searcher, ':search4' => $searcher];

    $countStmt = $db->prepare("SELECT COUNT(*) FROM Contacts WHERE $where");
    $countStmt->execute($params);
    $totalItems = (int) $countStmt->fetchColumn();

    $stmt = $db->prepare("SELECT ID, FirstName, LastName, EmailAddress, PhoneNumber FROM Contacts WHERE $where ORDER BY ID LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $contacts = $stmt->fetchAll();

    http_response_code(200);
    response(200, "Contacts retrieved successfully", $contacts, [
        "page" => $page,
        "pageSize" => $pageSize,
        "totalItems" => $totalItems,
        "totalPages" => max(1, (int) ceil($totalItems / $pageSize))
    ]);

}catch(PDOException $e){
    http_response_code(500);
    response(500, $e->getMessage(), null);
}
