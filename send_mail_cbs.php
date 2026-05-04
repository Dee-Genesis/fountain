<?php
require_once __DIR__ . '/mail_shared.php';

$p = getPersonalFields();

// ── CBS-specific programme fields ──
// HTML posts: cbs_program, cbs_spec, cbs_intakeYear, cbs_intakeSem, cbs_studyMode, cbs_referral
$program  = clean($_POST['cbs_program']    ?? '');
$spec     = clean($_POST['cbs_spec']       ?? '');
$year     = clean($_POST['cbs_intakeYear'] ?? '');
$sem      = clean($_POST['cbs_intakeSem']  ?? '');
$mode     = clean($_POST['cbs_studyMode']  ?? '');
$referral = clean($_POST['cbs_referral']   ?? '');
$ref      = $p['ref'];

if (!$p['firstName'] || !$p['lastName'] || !$p['email'] || !$program || !$ref) {
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
$intake  = trim("{$sem} {$year}");
$subject = "New Application | CBS | {$program} | {$p['firstName']} {$p['lastName']} [{$ref}]";

$body  = "CIML BUSINESS SCHOOL — ONLINE APPLICATION{$nl}";
$body .= "=========================================={$nl}";
$body .= "Reference Number : {$ref}{$nl}";
$body .= "Submitted On     : {$now}{$nl}{$nl}{$nl}";

$body .= "[ 1 ] PROGRAMME SELECTION{$nl}";
$body .= "--------------------------{$nl}";
$body .= "Program Applied For  : {$program}{$nl}";
$body .= "Specialization       : " . ($spec ?: 'Not specified') . "{$nl}";
$body .= "Intake               : {$intake}{$nl}";
$body .= "Study Mode           : {$mode}{$nl}";
$body .= "Referral Source      : " . ($referral ?: 'Not provided') . "{$nl}{$nl}{$nl}";

$body .= buildPersonalSection($p, $nl);
$body .= buildAcademicSection($p, $nl);
$body .= buildProfessionalSection($p, $nl);
$body .= buildReferenceSection($p, $nl);
$body .= buildUploadsSection($uploads, $GLOBALS['uploadLabels'], $nl);

$body .= "{$nl}=========================================={$nl}";
$body .= "Files saved on server : uploads/applications/{$nl}";
$body .= "Contact applicant     : {$p['email']}{$nl}";
$body .= "Reference             : {$ref}{$nl}";

// ── Send ──
$result = sendEmail(
    'noreply@cbsedu.us', 'CIML Admissions Portal',
    'info@cbsedu.us',    'CIML Admissions',
    $p['email'], trim("{$p['firstName']} {$p['lastName']}"),
    $subject, $body, $uploads
);

echo json_encode([
    'success' => $result['sent'],
    'ref'     => $ref,
    'warning' => $result['warning'] ?: null,
]);
