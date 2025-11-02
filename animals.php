<?php
// C:\xampp\htdocs\animal-tracker\api\animals.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// include DB connection (must set $pdo in this file)
require_once __DIR__ . '/../config/db.php';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // --- determine which column exists in areas table: area_name or name ---
    $nameCol = 'name'; // default fallback
    $stmtCols = $pdo->prepare("
        SELECT COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'areas'
          AND COLUMN_NAME IN ('area_name','name')
    ");
    $stmtCols->execute();
    $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
    // prefer area_name if present, otherwise name if present
    if (in_array('area_name', $cols)) $nameCol = 'area_name';
    elseif (in_array('name', $cols)) $nameCol = 'name';
    else {
        // no appropriate column found -> respond with an error
        respond(['error' => "areas table does not have 'area_name' or 'name' column"], 500);
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    // ===== GET: list of areas (with total animals) =====
    if ($method === 'GET' && $action === 'areas') {
        // build query using detected column name (safe because it's only area_name or name)
        $orderCol = $nameCol === 'area_name' ? 'a.area_name' : 'a.name';
        $selectName = $nameCol === 'area_name' ? 'a.area_name' : 'a.name';

        $sql = "
            SELECT a.id,
                   {$selectName} AS name,
                   a.description,
                   IFNULL(SUM(an.count_est),0) AS total_animals
            FROM areas a
            LEFT JOIN animals an ON an.area_id = a.id
            GROUP BY a.id
            ORDER BY {$orderCol}
        ";
        $stmt = $pdo->query($sql);
        $areas = $stmt->fetchAll();
        respond($areas);
    }

    // ===== GET: animals (optionally by area_id) =====
    if ($method === 'GET' && $action === 'animals') {
        $area_id = isset($_GET['area_id']) ? (int) $_GET['area_id'] : 0;
        if ($area_id) {
            $stmt = $pdo->prepare("SELECT * FROM animals WHERE area_id = ? ORDER BY common_name");
            $stmt->execute([$area_id]);
            $animals = $stmt->fetchAll();
        } else {
            // include area name for each animal
            $sql = "
                SELECT an.*, a.{$nameCol} AS area_name
                FROM animals an
                LEFT JOIN areas a ON an.area_id = a.id
                ORDER BY an.id DESC
            ";
            $stmt = $pdo->query($sql);
            $animals = $stmt->fetchAll();
        }
        respond($animals);
    }

    // ===== GET: areasummary =====
    if ($method === 'GET' && $action === 'areasummary') {
        $area_id = isset($_GET['area_id']) ? (int) $_GET['area_id'] : 0;
        if (!$area_id) respond(['error' => 'area_id required'], 400);

        $stmt = $pdo->prepare("SELECT species, SUM(count_est) AS total_count, COUNT(*) AS records FROM animals WHERE area_id = ? GROUP BY species ORDER BY total_count DESC");
        $stmt->execute([$area_id]);
        $bySpecies = $stmt->fetchAll();

        $stmt2 = $pdo->prepare("SELECT id, {$nameCol} AS name, description FROM areas WHERE id = ?");
        $stmt2->execute([$area_id]);
        $area = $stmt2->fetch();

        respond(['area' => $area, 'bySpecies' => $bySpecies]);
    }

    // read JSON body
    $raw = file_get_contents('php://input');
    $json = $raw ? json_decode($raw, true) : null;

    // ===== POST: create animal =====
    if ($method === 'POST' && $action === 'animal') {
        if (!is_array($json)) respond(['error' => 'Invalid JSON body'], 400);

        $sql = "INSERT INTO animals (area_id, common_name, species, count_est, average_age_years, notes, last_seen)
                VALUES (:area_id, :common_name, :species, :count_est, :average_age_years, :notes, :last_seen)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':area_id' => $json['area_id'] ?? null,
            ':common_name' => $json['common_name'] ?? null,
            ':species' => $json['species'] ?? null,
            ':count_est' => $json['count_est'] ?? 0,
            ':average_age_years' => $json['average_age_years'] ?? null,
            ':notes' => $json['notes'] ?? null,
            ':last_seen' => $json['last_seen'] ?? null,
        ]);
        respond(['success' => true, 'insert_id' => (int)$pdo->lastInsertId()]);
    }

    // ===== PUT: update animal =====
    if ($method === 'PUT' && $action === 'animal') {
        if (!is_array($json) || empty($json['id'])) respond(['error' => 'id required'], 400);

        $sql = "UPDATE animals SET area_id = :area_id, common_name = :common_name, species = :species,
                count_est = :count_est, average_age_years = :average_age_years, notes = :notes, last_seen = :last_seen
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':area_id' => $json['area_id'] ?? null,
            ':common_name' => $json['common_name'] ?? null,
            ':species' => $json['species'] ?? null,
            ':count_est' => $json['count_est'] ?? 0,
            ':average_age_years' => $json['average_age_years'] ?? null,
            ':notes' => $json['notes'] ?? null,
            ':last_seen' => $json['last_seen'] ?? null,
            ':id' => $json['id'],
        ]);
        respond(['success' => true]);
    }

    // ===== DELETE: delete animal =====
    if ($method === 'DELETE' && $action === 'animal') {
        // allow id via query or in body
        $id = isset($_GET['id']) ? (int) $_GET['id'] : ($json['id'] ?? 0);
        if (!$id) respond(['error' => 'id required'], 400);
        $stmt = $pdo->prepare("DELETE FROM animals WHERE id = ?");
        $stmt->execute([$id]);
        respond(['success' => true]);
    }

    // unknown endpoint
    respond(['error' => 'Unknown endpoint.'], 404);

} catch (Exception $e) {
    respond(['error' => 'Server error', 'details' => $e->getMessage()], 500);
}
