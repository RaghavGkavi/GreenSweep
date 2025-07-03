<?php
header('Content-Type: application/json');

$file = __DIR__ . '/newsletter-signups.csv';
$isSuccess = false;
$message = "";

try {
    // Ensure file exists and has header
    if (!file_exists($file)) {
        if (file_put_contents($file, "Email,Date\n") === false) {
            throw new Exception("Could not create CSV file. Check file permissions.");
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
        $email = trim($_POST['email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Prevent duplicate emails (case-insensitive, ignore header)
            $alreadyExists = false;
            $existing = fopen($file, 'r');
            if ($existing) {
                // Skip header
                fgetcsv($existing);
                while (($row = fgetcsv($existing)) !== false) {
                    // Defensive: check if row[0] exists and is not empty
                    if (isset($row[0]) && $row[0] !== '' && strcasecmp($row[0], $email) === 0) {
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
                    if (flock($fp, LOCK_EX)) {
                        if (fputcsv($fp, [$email, date('Y-m-d H:i:s')]) === false) {
                            flock($fp, LOCK_UN);
                            fclose($fp);
                            throw new Exception("Could not write to CSV file. Check file permissions.");
                        }
                        flock($fp, LOCK_UN);
                        fclose($fp);
                        $isSuccess = true;
                        $message = "Thank you for subscribing!";
                    } else {
                        fclose($fp);
                        throw new Exception("Could not lock CSV file for writing. Check file permissions.");
                    }
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
error_log("newsletter-signup.php: " . $message);
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
