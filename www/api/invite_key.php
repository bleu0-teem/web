<?php
// -------------------------------------------------------------------
// api/invite_keys.php
// Creates a new invite key in the invite_keys table.
//
// Expects POST fields:
//   • invite_key       (optional; if omitted, a random key is generated)
//   • uses_remaining   (integer ≥ 1; use 999 for "infinite")
//   • created_by       (integer user ID of the creator)
//
// Returns JSON + HTTP status code:
//   • 200 OK   → { success: true, invite_key: "...", uses_remaining: X, created_by: Y }
//   • 400 Bad Request → { error: "..." }
//   • 500 Internal Server Error → { error: "..." }
// -------------------------------------------------------------------

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST allowed']);
    exit;
}

// ------------------------------------------------------------
// 1) DATABASE CONNECTION (using environment variables)
// ------------------------------------------------------------
require_once 'db_connection.php';

// ------------------------------------------------------------
// 2) FETCH & VALIDATE POST DATA
// ------------------------------------------------------------
$custom_key     = isset($_POST['invite_key']) ? trim($_POST['invite_key']) : '';
$uses_remaining = isset($_POST['uses_remaining']) ? intval($_POST['uses_remaining']) : 1;
$created_by     = isset($_POST['created_by']) ? intval($_POST['created_by']) : 0;

// created_by must be a positive integer
if ($created_by <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid created_by']);
    exit;
}

// uses_remaining must be ≥ 1 (we treat 999 as infinite)
if ($uses_remaining < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'uses_remaining must be at least 1']);
    exit;
}

// If a custom key is provided, validate its format
if ($custom_key !== '') {
    if (!preg_match('/^[A-Z0-9_\-]{4,64}$/', $custom_key)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid invite_key format']);
        exit;
    }
    // Check for duplicate
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invite_keys WHERE invite_key = ?");
        $stmt->execute([$custom_key]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'invite_key already exists']);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit;
    }
} else {
    // Generate a random key if none provided
    // Format: BLUE16_ + 8 uppercase alphanumeric chars
    $randomBytes = random_bytes(4);
    $randomPart  = strtoupper(bin2hex($randomBytes));
    $custom_key  = 'BLUE16_' . substr($randomPart, 0, 8);
    // Ensure uniqueness by checking the table; regenerate if collision
    try {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM invite_keys WHERE invite_key = ?");
        while (true) {
            $checkStmt->execute([$custom_key]);
            if ($checkStmt->fetchColumn() === 0) {
                break;
            }
            // Collision (extremely unlikely), generate a new random part
            $randomBytes = random_bytes(4);
            $randomPart  = strtoupper(bin2hex($randomBytes));
            $custom_key  = 'BLUE16_' . substr($randomPart, 0, 8);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit;
    }
}

// ------------------------------------------------------------
// 3) INSERT INTO invite_keys TABLE
// ------------------------------------------------------------
try {
    $insert = $pdo->prepare("
        INSERT INTO invite_keys
            (invite_key, created_by, uses_remaining)
        VALUES
            (:invite_key, :created_by, :uses_remaining)
    ");
    $success = $insert->execute([
        ':invite_key'     => $custom_key,
        ':created_by'     => $created_by,
        ':uses_remaining' => $uses_remaining
    ]);

    if ($success) {
        http_response_code(200);
        echo json_encode([
            'success'       => true,
            'invite_key'    => $custom_key,
            'created_by'    => $created_by,
            'uses_remaining'=> $uses_remaining
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to insert invite key']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error on insert']);
    exit;
}