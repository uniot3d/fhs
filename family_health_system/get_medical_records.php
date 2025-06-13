<?php
require_once 'includes/functions.php';
header('Content-Type: application/json');
if (!isLoggedIn()) {
    echo json_encode([]); exit;
}
$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
if (!$member_id) {
    echo json_encode([]); exit;
}
$sql = "SELECT id, visit_date, diagnosis FROM medical_records WHERE member_id = ? ORDER BY visit_date DESC, id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$records = [];
while ($row = mysqli_fetch_assoc($result)) {
    $records[] = $row;
}
echo json_encode($records); 