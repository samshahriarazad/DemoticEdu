<?php
require_once __DIR__ . '/../Backend/config.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/* =============================
   GET ONE UNIVERSITY (DETAIL PAGE)
   ============================= */
if ($id > 0) {

    $stmt = db()->prepare("
        SELECT
            id,
            name,
            country,
            province,
            city,
            website,
            program,
            subject,
            scholarship,
            logo AS logo_url,
            hero_image AS hero_url,
            description
        FROM universities
        WHERE id = ?
        LIMIT 1
    ");
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    echo json_encode($row ?: []);
    exit;
}

/* =============================
   GET LIST OF UNIVERSITIES (HOME PAGE)
   ============================= */
$sql = "
    SELECT
        id,
        name,
        country,
        province,
        city,
        website,
        program,
        subject,
        scholarship,
        logo AS logo_url,
        hero_image AS hero_url
    FROM universities
    ORDER BY id DESC
";

$res  = db()->query($sql);
$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
