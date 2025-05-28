<?php
header('Content-Type: application/json');
session_start();

require_once('db.php');

// Get user ID from request
$userId = isset($_POST['userId']) ? intval($_POST['userId']) : (isset($_GET['userId']) ? intval($_GET['userId']) : null);

if (!$userId) {
    echo json_encode(['error' => 'User ID not provided']);
    exit;
}

// Create watchlist table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS user_watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_movie (user_id, movie_id)
)";

if (!$conn->query($createTable)) {
    echo json_encode(['error' => 'Failed to create table']);
    exit;
}

// Handle GET request - retrieve watchlist
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT movie_id FROM user_watchlist WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $watchlist = [];
    while ($row = $result->fetch_assoc()) {
        $watchlist[] = intval($row['movie_id']);
    }
    
    echo json_encode(['success' => true, 'watchlist' => $watchlist]);
    exit;
}

// Handle POST request - add/remove from watchlist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $movieId = isset($_POST['movieId']) ? intval($_POST['movieId']) : null;

    if (!$movieId) {
        echo json_encode(['error' => 'Movie ID not provided']);
        exit;
    }

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT IGNORE INTO user_watchlist (user_id, movie_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $movieId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Movie added to watchlist']);
        } else {
            echo json_encode(['error' => 'Failed to add movie to watchlist']);
        }
    }
    elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM user_watchlist WHERE user_id = ? AND movie_id = ?");
        $stmt->bind_param("ii", $userId, $movieId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Movie removed from watchlist']);
        } else {
            echo json_encode(['error' => 'Failed to remove movie from watchlist']);
        }
    }
    else {
        echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}
?>