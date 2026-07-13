<?php
/*
 * ============================================================
 * serve_cert.php — Secure PDF Delivery
 * ============================================================
 * Copy to: C:\xampp\htdocs\CertsVerify\serve_cert.php
 *
 * Usage:
 *   http://localhost/CertsVerify/serve_cert.php?id=CV-2024-STR-00001
 *
 * Replace all direct PDF links in your dashboards with:
 *   serve_cert.php?id=CV-2024-STR-00001
 *
 * Security checks:
 * 1. User must be logged in (session check)
 * 2. Graduate can only access their own certificate
 * 3. File is served through PHP, never exposed directly
 * 4. Filename is never revealed to the browser
 * ============================================================
 */
session_start();

// ── Must be logged in ──
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(403);
    die("Access denied. Please log in first.");
}

$mysqli  = require 'processes/db.php';
$certID  = trim($_GET['id'] ?? '');
$role    = $_SESSION['role'];

if ($certID === '') {
    http_response_code(400);
    die("No certificate ID provided.");
}

$safe = mysqli_real_escape_string($mysqli, $certID);

// ── Fetch certificate record ──
$result = mysqli_query($mysqli,
    "SELECT * FROM certificate WHERE certID='$safe' LIMIT 1"
);

if (!$result || mysqli_num_rows($result) === 0) {
    http_response_code(404);
    die("Certificate not found.");
}

$cert = mysqli_fetch_assoc($result);

// ── Graduate ownership check ──
// A graduate can only access their OWN certificate
if ($role === 'Graduate') {
    $gradResult = mysqli_query($mysqli,
        "SELECT admissionNumber FROM graduate WHERE id='{$_SESSION['user_id']}' LIMIT 1"
    );
    $grad = mysqli_fetch_assoc($gradResult);

    if (strtolower(trim($grad['admissionNumber'])) !== strtolower(trim($cert['admissionNumber']))) {
        http_response_code(403);
        die("Access denied. You can only view your own certificate.");
    }
}

// ── Find the file ──
// Use secure_filename if available, fall back to old fileName
$filename = !empty($cert['secure_filename'])
    ? $cert['secure_filename']
    : $cert['fileName'] . '.pdf';

$filepath = __DIR__ . '/uploads/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    die("Certificate file not found on server.");
}

// ── Serve the PDF ──
// The actual filename is NEVER sent to the browser
// Browser sees it as "certificate.pdf" regardless of real name
$display_name = "CertsVerify_Certificate_{$cert['certID']}.pdf";

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $display_name . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, no-cache');
header('X-Content-Type-Options: nosniff');

// Stream the file
readfile($filepath);
exit;
?>
