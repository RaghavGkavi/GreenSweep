<?php
header('Access-Control-Allow-Origin: http://green-sweep.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method Not Allowed. Please use the form to submit your email.'
    ]);
    exit;
}

$file = __DIR__ . '/newsletter-signups.csv';
$isSuccess = false;
$message = "";

// Use explicit fgetcsv parameters to avoid PHP 8.1+ deprecation warnings
$fgetcsv_delim = ',';
$fgetcsv_encl = '"';
$fgetcsv_escape = '\\';

try {
    // Ensure file exists and has header
    if (!file_exists($file)) {
        if (file_put_contents($file, "Email,Date\n") === false) {
            throw new Exception("Could not create CSV file. Check file permissions.");
        }
    }

    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Prevent duplicate emails (case-insensitive, ignore header)
            $alreadyExists = false;
            $existing = fopen($file, 'r');
            if ($existing) {
                // Skip header
                fgetcsv($existing, 0, $fgetcsv_delim, $fgetcsv_encl, $fgetcsv_escape);
                while (($row = fgetcsv($existing, 0, $fgetcsv_delim, $fgetcsv_encl, $fgetcsv_escape)) !== false) {
                    if (is_array($row) && isset($row[0]) && $row[0] !== '' && strcasecmp(trim($row[0]), $email) === 0) {
                        $alreadyExists = true;
                        break;
                    }
                }
                fclose($existing);
            } else {
                throw new Exception("Could not open CSV file for reading. Check file permissions.");
            }
            if ($alreadyExists) {
                $isSuccess = true;
                $message = "You are already subscribed!";
            } else {
                $fp = fopen($file, 'a');
                if ($fp) {
                    if (fputcsv($fp, [$email, date('Y-m-d H:i:s')]) === false) {
                        fclose($fp);
                        throw new Exception("Could not write to CSV file. Check file permissions.");
                    }
                    fclose($fp);
                    $isSuccess = true;
                    $message = "Thank you for subscribing!";
                } else {
                    throw new Exception("Could not open CSV file for writing. Check file permissions.");
                }
            }
        } else {
            $message = "Please enter a valid email address.";
        }
    } else {
        $message = "Invalid request.";
    }
} catch (Exception $e) {
    $message = $e->getMessage();
}

echo json_encode([
    'success' => $isSuccess,
    'message' => $message
]);
exit;
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
<body>
    <div class="content-section" style="text-align:center; margin-top:100px;">
        <h2><?php echo htmlspecialchars($message); ?></h2>
        <p><a href="index.html">Return to homepage</a></p>
    </div>
</body>
</html>
