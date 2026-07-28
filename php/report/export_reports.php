<?php 
include ('../config/config.php');
require "../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'User ID');
$sheet->setCellValue('B1', 'Name');
$sheet->setCellValue('C1', 'Email');
$sheet->setCellValue('D1', 'Designation');
$sheet->setCellValue('E1', 'Status');

$sql = "
SELECT
    u.user_id,
    CONCAT(u.first_name, ' ', u.last_name) AS fullname,
    u.email,
    d.designation_name,
    u.status
FROM users u
JOIN designations d
ON u.designation_id = d.designation_id
ORDER BY u.last_name
";

$result = mysqli_query($conn, $sql);

$row = 2;

while ($user = mysqli_fetch_assoc($result)) {

    $sheet->setCellValue("A$row", $user["user_id"]);
    $sheet->setCellValue("B$row", $user["fullname"]);
    $sheet->setCellValue("C$row", $user["email"]);
    $sheet->setCellValue("D$row", $user["designation_name"]);
    $sheet->setCellValue("E$row", $user["status"]);

    $row++;
}

foreach (range('A', 'E') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$filename = "Users_Report_" . date("Y-m-d") . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;