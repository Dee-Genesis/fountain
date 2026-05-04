<?php
require_once __DIR__ . '/mail_shared.php';

$p = getPersonalFields();

// ── CIET-specific programme fields ──
$appType  = clean($_POST['ciet_type']     ?? '');
$referral = clean($_POST['ciet_referral'] ?? '');
$ref      = $p['ref'];

if (!$p['firstName'] || !$p['lastName'] || !$p['email'] || !$appType || !$ref) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// ── Uploads ──
$uploadDir = __DIR__ . '/uploads/applications/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
$uploads = processUploads($uploadDir, $ref);

// ── Build email body ──
$nl      = "\r\n";
$now     = date('l, d F Y \a\t H:i T');
$subject = "New Application | CIET | {$appType} | {$p['firstName']} {$p['lastName']} [{$ref}]";

$body  = "CIET GLOBAL — CHARTERED INSTITUTE OF EDUCATION & TRAINING{$nl}";
$body .= "ONLINE APPLICATION{$nl}";
$body .= "=========================================================={$nl}";
$body .= "Reference Number : {$ref}{$nl}";
$body .= "Submitted On     : {$now}{$nl}{$nl}{$nl}";

$body .= "[ 1 ] APPLICATION TYPE{$nl}";
$body .= "-----------------------{$nl}";
$body .= "Application Type : {$appType}{$nl}";
$body .= "Referral Source  : " . ($referral ?: 'Not provided') . "{$nl}{$nl}{$nl}";

$body .= buildPersonalSection($p, $nl);
$body .= buildAcademicSection($p, $nl);
$body .= buildProfessionalSection($p, $nl);
$body .= buildReferenceSection($p, $nl);
$body .= buildUploadsSection($uploads, $GLOBALS['uploadLabels'], $nl);

$body .= "{$nl}=========================================================={$nl}";
$body .= "Files saved on server : uploads/applications/{$nl}";
$body .= "Contact applicant     : {$p['email']}{$nl}";
$body .= "Reference             : {$ref}{$nl}";

// ── Send ──
$result = sendEmail(
    'noreply@cietglobal.us', 'CIET Admissions Portal',
    'info@cietglobal.us',    'CIET Admissions',
    $p['email'], trim("{$p['firstName']} {$p['lastName']}"),
    $subject, $body, $uploads
);

echo json_encode([
    'success' => $result['sent'],
    'ref'     => $ref,
    'warning' => $result['warning'] ?: null,
]);
