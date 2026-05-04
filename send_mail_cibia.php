<?php
require_once __DIR__ . '/mail_shared.php';

$p = getPersonalFields();

// ── CIBIA-specific programme fields ──
$appType  = clean($_POST['cibia_appType']  ?? '');
$cert     = clean($_POST['cibia_cert']     ?? '');
$mem      = clean($_POST['cibia_mem']      ?? '');
$referral = clean($_POST['cibia_referral'] ?? '');
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
$subject = "New Application | CIBIA | {$appType} | {$p['firstName']} {$p['lastName']} [{$ref}]";

$body  = "CIBIA — CHARTERED INSTITUTE OF BUSINESS INTELLIGENCE & ANALYTICS{$nl}";
$body .= "ONLINE APPLICATION{$nl}";
$body .= "================================================================{$nl}";
$body .= "Reference Number : {$ref}{$nl}";
$body .= "Submitted On     : {$now}{$nl}{$nl}{$nl}";

$body .= "[ 1 ] APPLICATION DETAILS{$nl}";
$body .= "--------------------------{$nl}";
$body .= "Application Type         : {$appType}{$nl}";
if ($appType === 'Professional Certification' && $cert) {
    $body .= "Certification Programme  : {$cert}{$nl}";
}
if ($appType === 'Membership Application' && $mem) {
    $body .= "Membership Grade         : {$mem}{$nl}";
}
$body .= "Referral Source          : " . ($referral ?: 'Not provided') . "{$nl}{$nl}{$nl}";

$body .= buildPersonalSection($p, $nl);
$body .= buildAcademicSection($p, $nl);
$body .= buildProfessionalSection($p, $nl);
$body .= buildReferenceSection($p, $nl);
$body .= buildUploadsSection($uploads, $GLOBALS['uploadLabels'], $nl);

$body .= "{$nl}================================================================{$nl}";
$body .= "Files saved on server : uploads/applications/{$nl}";
$body .= "Contact applicant     : {$p['email']}{$nl}";
$body .= "Reference             : {$ref}{$nl}";

// ── Send ──
$result = sendEmail(
    'noreply@cibiaglobal.org', 'CIBIA Admissions Portal',
    'info@cibiaglobal.org',    'CIBIA Admissions',
    $p['email'], trim("{$p['firstName']} {$p['lastName']}"),
    $subject, $body, $uploads
);

echo json_encode([
    'success' => $result['sent'],
    'ref'     => $ref,
    'warning' => $result['warning'] ?: null,
]);
