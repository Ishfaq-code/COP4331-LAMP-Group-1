<?php


// Allow any endpoint to access backend for now
header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

function response(int $statusCode, $msg, $data){
    $response = [
        "statusCode" => $statusCode,
        "message" => $msg,
        "data" => $data
    ];

    echo json_encode($response);
    exit();
}

function validatePassword($password){
    
    if(strlen($password) < 8){
        echo("Too short");
        return False;
    }
    if(!preg_match('/\d/', $password)){
         echo("No number");
        return False;
    }
    if((!preg_match('/[^a-zA-Z0-9]/', $password))){
         echo("no symbol");
        return False;
    }
    return True;
}

?>