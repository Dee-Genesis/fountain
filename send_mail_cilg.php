<?php
require_once __DIR__ . '/mail_shared.php';

$p = getPersonalFields();

// ── CILG-specific programme fields ──
$appType  = clean($_POST['cilg_appType']  ?? '');
$dip      = clean($_POST['cilg_dip']      ?? '');
$mem      = clean($_POST['cilg_mem']      ?? '');
$cert     = clean($_POST['cilg_cert']     ?? '');
$referral = clean($_POST['cilg_referral'] ?? '');
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
$subject = "New Application | CILG | {$appType} | {$p['firstName']} {$p['lastName']} [{$ref}]";

$body  = "CILG — CHARTERED INSTITUTE OF LEADERSHIP & GOVERNANCE{$nl}";
$body .= "ONLINE APPLICATION{$nl}";
$body .= "====================================================={$nl}";
$body .= "Reference Number : {$ref}{$nl}";
$body .= "Submitted On     : {$now}{$nl}{$nl}{$nl}";

$body .= "[ 1 ] APPLICATION DETAILS{$nl}";
$body .= "--------------------------{$nl}";
$body .= "Application Type         : {$appType}{$nl}";
if ($appType === 'Diploma' && $dip) {
    $body .= "Diploma Level            : {$dip}{$nl}";
}
if ($appType === 'Membership' && $mem) {
    $body .= "Membership Grade         : {$mem}{$nl}";
}
if ($appType === 'Professional Certification' && $cert) {
    $body .= "Certification Programme  : {$cert}{$nl}";
}
$body .= "Referral Source          : " . ($referral ?: 'Not provided') . "{$nl}{$nl}{$nl}";

$body .= buildPersonalSection($p, $nl);
$body .= buildAcademicSection($p, $nl);
$body .= buildProfessionalSection($p, $nl);
$body .= buildReferenceSection($p, $nl);
$body .= buildUploadsSection($uploads, $GLOBALS['uploadLabels'], $nl);

$body .= "{$nl}====================================================={$nl}";
$body .= "Files saved on server : uploads/applications/{$nl}";
$body .= "Contact applicant     : {$p['email']}{$nl}";
$body .= "Reference             : {$ref}{$nl}";

// ── Send ──
$result = sendEmail(
    'noreply@cilgglobal.org', 'CILG Admissions Portal',
    'info@cilgglobal.org',    'CILG Admissions',
    $p['email'], trim("{$p['firstName']} {$p['lastName']}"),
    $subject, $body, $uploads
);

echo json_encode([
    'success' => $result['sent'],
    'ref'     => $ref,
    'warning' => $result['warning'] ?: null,
]);
