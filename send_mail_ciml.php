<?php
require_once __DIR__ . '/mail_shared.php';

$p = getPersonalFields();

// ── CIML-specific programme fields ──
$appType  = clean($_POST['ciml_appType']  ?? '');
$grade    = clean($_POST['ciml_grade']    ?? '');
$cert     = clean($_POST['ciml_cert']     ?? '');
$qual     = clean($_POST['ciml_qual']     ?? '');
$mode     = clean($_POST['ciml_mode']     ?? '');
$referral = clean($_POST['ciml_referral'] ?? '');
$ref      = $p['ref'];

if (!$p['firstName'] || !$p['lastName'] || !$p['email'] || !$appType || !$ref) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// ── Uploads ──
$uploadDir = __DIR__ . '/uploads/applications/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
$uploads = processUploads($uploadDir, $ref);

// ── Determine what was selected ──
$selectionDetail = '';
if ($grade)    $selectionDetail = "Membership Grade     : {$grade}";
elseif ($cert) $selectionDetail = "Certification Track  : {$cert}";
elseif ($qual)  $selectionDetail = "Qualification Level  : {$qual}";

// ── Build email body ──
$nl      = "\r\n";
$now     = date('l, d F Y \a\t H:i T');
$subject = "New Application | CIML | {$appType} | {$p['firstName']} {$p['lastName']} [{$ref}]";

$body  = "CIML — CHARTERED INSTITUTE OF MANAGEMENT & LEADERSHIP{$nl}";
$body .= "ONLINE APPLICATION{$nl}";
$body .= "====================================================={$nl}";
$body .= "Reference Number : {$ref}{$nl}";
$body .= "Submitted On     : {$now}{$nl}{$nl}{$nl}";

$body .= "[ 1 ] APPLICATION DETAILS{$nl}";
$body .= "--------------------------{$nl}";
$body .= "Application Type     : {$appType}{$nl}";
if ($selectionDetail) $body .= "{$selectionDetail}{$nl}";
$body .= "Preferred Study Mode : " . ($mode ?: 'Not specified') . "{$nl}";
$body .= "Referral Source      : " . ($referral ?: 'Not provided') . "{$nl}{$nl}{$nl}";

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
    'noreply@cimlglobal.us', 'CIML Admissions Portal',
    'info@cimlglobal.us',    'CIML Admissions',
    $p['email'], trim("{$p['firstName']} {$p['lastName']}"),
    $subject, $body, $uploads
);

echo json_encode([
    'success' => $result['sent'],
    'ref'     => $ref,
    'warning' => $result['warning'] ?: null,
]);
