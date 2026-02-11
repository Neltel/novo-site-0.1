<?php
// Other installation code...

// Function to execute SQL file
function executeSqlFile($pdo, $filePath) {
    $sql = file_get_contents($filePath);
    $pdo->exec($sql);
}

// Database connection (using PDO for example)
try {
    $pdo = new PDO('mysql:host=your_host;dbname=your_db','your_user','your_password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Execute schema.sql to create tables
    executeSqlFile($pdo, 'path/to/schema.sql');

    echo "Installation completed successfully.";
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>