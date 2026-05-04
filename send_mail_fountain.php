<?php
require_once __DIR__ . '/mail_shared.php';

$p        = getPersonalFields();
$ref      = $p['ref'];
$instName = clean($_POST['institution'] ?? 'Fountain PTS');

if (!$p['firstName'] || !$p['lastName'] || !$p['email'] || !$ref) {
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
$subject = "New Application | {$instName} | {$p['firstName']} {$p['lastName']} [{$ref}]";

$body  = "FOUNTAIN PROFESSIONAL TRAINING SERVICES{$nl}";
$body .= "ONLINE APPLICATION{$nl}";
$body .= "======================================={$nl}";
$body .= "Reference Number : {$ref}{$nl}";
$body .= "Institution      : {$instName}{$nl}";
$body .= "Submitted On     : {$now}{$nl}{$nl}{$nl}";

$body .= buildPersonalSection($p, $nl);
$body .= buildAcademicSection($p, $nl);
$body .= buildProfessionalSection($p, $nl);
$body .= buildReferenceSection($p, $nl);
$body .= buildUploadsSection($uploads, $GLOBALS['uploadLabels'], $nl);

$body .= "{$nl}======================================={$nl}";
$body .= "Files saved on server : uploads/applications/{$nl}";
$body .= "Contact applicant     : {$p['email']}{$nl}";
$body .= "Reference             : {$ref}{$nl}";

// ── Send ──
$result = sendEmail(
    'noreply@fountainpts.com', 'Fountain PTS Admissions Portal',
    'info@fountainpts.com',    'Fountain PTS Admissions',
    $p['email'], trim("{$p['firstName']} {$p['lastName']}"),
    $subject, $body, $uploads
);

echo json_encode([
    'success' => $result['sent'],
    'ref'     => $ref,
    'warning' => $result['warning'] ?: null,
]);
