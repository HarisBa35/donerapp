<?php
// Sprečavanje ubacivanja reklama ili nepotrebnog izlaza sa besplatnog hostinga
ob_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit();
}

$host = 'sql303.infinityfree.com';
$db_name = 'if0_42989266_donerapp';
$username = 'if0_42989266';
$password = 'Im1uNdr2467mMNt';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_menu' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY id ASC");
        $menu = $stmt->fetchAll();
        ob_end_clean();
        echo json_encode(["success" => true, "data" => $menu]);
        exit();
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
        exit();
    }
}
else if ($action === 'create_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $items_summary = '';
    $total_price = 0;

    if (!empty($_POST['items_summary'])) {
        $items_summary = trim($_POST['items_summary']);
        $total_price = floatval($_POST['total_price']);
    } else if ($input && isset($input['items_summary'])) {
        $items_summary = trim($input['items_summary']);
        $total_price = floatval($input['total_price']);
    }

    if (empty($items_summary) || $total_price <= 0) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Invalid order data. items_summary and total_price are required.",
            "received_post" => $_POST
        ]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO orders (items_summary, total_price) VALUES (?, ?)");
        $stmt->execute([$items_summary, $total_price]);
        $order_id = $pdo->lastInsertId();

        ob_end_clean();
        echo json_encode([
            "success" => true,
            "message" => "Narudžba uspješno spašena u MySQL bazu!",
            "order_id" => $order_id
        ]);
        exit();
    } catch (Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to save order: " . $e->getMessage()]);
        exit();
    }
}
else {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid action or request method."]);
    exit();
}
?>
