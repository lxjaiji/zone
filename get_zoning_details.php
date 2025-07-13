<?php
session_start();
header('Content-Type: application/json');

// Require login to access this endpoint
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['error' => 'Access Denied: You must be logged in.']);
    exit;
}

require_once "config.php";

$response = [];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['error'] = 'Invalid request: No valid ID provided.';
    echo json_encode($response);
    exit;
}

$cert_id = intval($_GET['id']);

$sql = "SELECT applicant_name, owner_name, address, tax_declaration, project_type, project_location, purpose
        FROM zoning_certificates
        WHERE id = ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $cert_id);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $response = mysqli_fetch_assoc($result);
        } else {
            $response['error'] = 'No certificate found with the specified ID.';
        }
    } else {
        $response['error'] = 'Database query failed.';
    }
    mysqli_stmt_close($stmt);
} else {
    $response['error'] = 'Database statement preparation failed.';
}

mysqli_close($link);
echo json_encode($response);
?>
