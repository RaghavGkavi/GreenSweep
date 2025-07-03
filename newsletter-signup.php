<?php
header('Content-Type: application/json');

$file = __DIR__ . '/newsletter-signups.csv';
$isSuccess = false;
$message = "";

try {
    // Ensure file exists and has header
    if (!file_exists($file) || filesize($file) === 0) {
        if (file_put_contents($file, "Email,Date\n", FILE_APPEND | LOCK_EX) === false) {
            throw new Exception("Could not create CSV file.");
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
        $email = trim($_POST['email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $entry = [$email, date('Y-m-d H:i:s')];
            $fp = fopen($file, 'a');
            if ($fp) {
                if (fputcsv($fp, $entry) === false) {
                    throw new Exception("Could not write to CSV file.");
                }
                fclose($fp);
                $isSuccess = true;
                $message = "Thank you for subscribing!";
            } else {
                throw new Exception("Could not open CSV file for writing.");
            }
        } else {
            $message = "Please enter a valid email address.";
        }
    } else {
        $message = "Invalid request.";
    }
} catch (Exception $e) {
    $message = "An error occurred. Please try again later.";
}

echo json_encode([
    'success' => $isSuccess,
    'message' => $message
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Newsletter Signup</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="content-section" style="text-align:center; margin-top:100px;">
        <h2><?php echo htmlspecialchars($message); ?></h2>
        <p><a href="index.html">Return to homepage</a></p>
    </div>
</body>
</html>
