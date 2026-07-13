<?php
include "../config/config.php";

$data = json_decode(file_get_contents("php://input"), true);

$brands = $data['brands'];

if (empty($brands)) {
    echo json_encode([]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($brands), '?'));

$sql = "
SELECT
    bd.id,
    d.dealership_name
FROM brand_dealerships bd
INNER JOIN dealerships d
ON bd.dealership_id = d.dealership_id
WHERE bd.brand_id IN ($placeholders)
ORDER BY d.dealership_name ASC
";

$stmt = mysqli_prepare($conn, $sql);

$types = str_repeat("i", count($brands));

mysqli_stmt_bind_param($stmt, $types, ...$brands);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$dealerships = [];

while($row = mysqli_fetch_assoc($result)){
    $dealerships[] = $row;
}

mysqli_stmt_close($stmt);

header("Content-Type: application/json");

echo json_encode($dealerships);