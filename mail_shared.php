<?php
/**
 * mail_shared.php — Shared helpers for all Fountain PTS send_mail_*.php files
 * Include this at the top of each institution mailer.
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// ── PHPMailer loader ──────────────────────────────────────────────────────────
function loadPHPMailer() {
    $composerAutoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($composerAutoload)) {
        require $composerAutoload;
        return true;
    }
    $dir   = __DIR__ . '/phpmailer/';
    $files = ['Exception.php', 'PHPMailer.php', 'SMTP.php'];
    $allExist = true;
    foreach ($files as $f) { if (!file_exists($dir . $f)) { $allExist = false; break; } }
    if (!$allExist) {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $base = 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/';
        foreach ($files as $f) {
            $dest = $dir . $f;
            if (!file_exists($dest)) {
                $c = @file_get_contents($base . $f);
                if ($c) file_put_contents($dest, $c);
            }
        }
    }
    foreach ($files as $f) {
        $p = $dir . $f;
        if (file_exists($p)) require_once $p;
        else return false;
    }
    return true;
}

// ── Sanitise ─────────────────────────────────────────────────────────────────
function clean($v) {
    return htmlspecialchars(strip_tags(trim($v ?? '')), ENT_QUOTES, 'UTF-8');
}

// ── Common personal fields (same across all institutions) ─────────────────────
function getPersonalFields() {
    return [
        'ref'          => clean($_POST['ref']           ?? ''),
        'title'        => clean($_POST['title']         ?? ''),
        'firstName'    => clean($_POST['firstName']     ?? ''),
        'lastName'     => clean($_POST['lastName']      ?? ''),
        'middleName'   => clean($_POST['middleName']    ?? ''),
        'dob'          => clean($_POST['dob']           ?? ''),
        'gender'       => clean($_POST['gender']        ?? ''),
        'nationality'  => clean($_POST['nationality']   ?? ''),
        'passportNum'  => clean($_POST['passportNum']   ?? ''),
        'country'      => clean($_POST['country']       ?? ''),
        'state'        => clean($_POST['state']         ?? ''),
        'email'        => filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL),
        'phone'        => clean($_POST['phone']         ?? ''),
        'whatsapp'     => clean($_POST['whatsapp']      ?? ''),
        'address'      => clean($_POST['address']       ?? ''),
        // Academic
        'highestQual'  => clean($_POST['highestQual']   ?? ''),
        'fieldStudy'   => clean($_POST['fieldStudy']    ?? ''),
        'institution1' => clean($_POST['institution1']  ?? ''),
        'instCountry1' => clean($_POST['instCountry1']  ?? ''),
        'gradYear'     => clean($_POST['gradYear']      ?? ''),
        'grade'        => clean($_POST['grade']         ?? ''),
        'instrLang'    => clean($_POST['instrLang']     ?? ''),
        'qual2'        => clean($_POST['qual2']         ?? ''),
        'body2'        => clean($_POST['body2']         ?? ''),
        'englishMethod'=> clean($_POST['englishMethod'] ?? ''),
        'englishScore' => clean($_POST['englishScore']  ?? ''),
        'englishDate'  => clean($_POST['englishDate']   ?? ''),
        // Professional
        'jobTitle'     => clean($_POST['jobTitle']      ?? ''),
        'employer'     => clean($_POST['employer']      ?? ''),
        'industry'     => clean($_POST['industry']      ?? ''),
        'yearsExp'     => clean($_POST['yearsExp']      ?? ''),
        'mgmtExp'      => clean($_POST['mgmtExp']       ?? ''),
        'motivation'   => clean($_POST['motivation']    ?? ''),
        'goals'        => clean($_POST['goals']         ?? ''),
        // Reference
        'ref1name'     => clean($_POST['ref1name']      ?? ''),
        'ref1email'    => clean($_POST['ref1email']     ?? ''),
        'ref1title'    => clean($_POST['ref1title']     ?? ''),
        'ref1org'      => clean($_POST['ref1org']       ?? ''),
    ];
}

// ── File upload handler ───────────────────────────────────────────────────────
function handleUpload($fieldName, $uploadDir, $ref, $maxBytes, $allowedExts) {
    if (empty($_FILES[$fieldName]['name'])) return ['ok' => false, 'path' => null, 'origName' => null];
    $file    = $_FILES[$fieldName];
    $ext     = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));
    $newName = $ref . '_' . $fieldName . '.' . $ext;
    $dest    = $uploadDir . $newName;
    if ($file['error'] !== UPLOAD_ERR_OK)  return ['ok' => false, 'path' => null, 'origName' => $file['name']];
    if ($file['size'] > $maxBytes)         return ['ok' => false, 'path' => null, 'origName' => $file['name']];
    if (!in_array($ext, $allowedExts))     return ['ok' => false, 'path' => null, 'origName' => $file['name']];
    if (!move_uploaded_file($file['tmp_name'], $dest)) return ['ok' => false, 'path' => null, 'origName' => $file['name']];
    return ['ok' => true, 'path' => $dest, 'name' => $newName, 'origName' => $file['name']];
}

function processUploads($uploadDir, $ref) {
    $imgExts = ['pdf', 'jpg', 'jpeg', 'png'];
    $docExts = ['pdf', 'doc', 'docx'];
    return [
        'upload_id'          => handleUpload('upload_id',          $uploadDir, $ref, 5*1024*1024, $imgExts),
        'upload_transcripts' => handleUpload('upload_transcripts', $uploadDir, $ref, 5*1024*1024, $imgExts),
        'upload_cv'          => handleUpload('upload_cv',          $uploadDir, $ref, 5*1024*1024, $docExts),
        'upload_english'     => handleUpload('upload_english',     $uploadDir, $ref, 5*1024*1024, $imgExts),
        'upload_refs'        => handleUpload('upload_refs',        $uploadDir, $ref, 5*1024*1024, $imgExts),
    ];
}

$uploadLabels = [
    'upload_id'          => 'Passport / ID',
    'upload_transcripts' => 'Academic Transcripts',
    'upload_cv'          => 'CV / Resume',
    'upload_english'     => 'English Certificate',
    'upload_refs'        => 'Reference Letters',
];

// ── Reusable body sections ────────────────────────────────────────────────────
function buildPersonalSection($p, $nl) {
    $fullName = trim("{$p['title']} {$p['firstName']} {$p['middleName']} {$p['lastName']}");
    $body  = "[ 2 ] PERSONAL INFORMATION{$nl}";
    $body .= "---------------------------{$nl}";
    $body .= "Full Name            : {$fullName}{$nl}";
    $body .= "Date of Birth        : {$p['dob']}{$nl}";
    $body .= "Gender               : {$p['gender']}{$nl}";
    $body .= "Nationality          : {$p['nationality']}{$nl}";
    $body .= "ID / Passport No.    : {$p['passportNum']}{$nl}";
    $body .= "Country of Residence : {$p['country']}{$nl}";
    $body .= "State / Province     : " . ($p['state'] ?: 'Not provided') . "{$nl}";
    $body .= "Email Address        : {$p['email']}{$nl}";
    $body .= "Phone Number         : {$p['phone']}{$nl}";
    $body .= "WhatsApp             : " . ($p['whatsapp'] ?: 'Same as phone') . "{$nl}";
    $body .= "Mailing Address      : {$p['address']}{$nl}{$nl}{$nl}";
    return $body;
}

function buildAcademicSection($p, $nl) {
    $body  = "[ 3 ] ACADEMIC BACKGROUND{$nl}";
    $body .= "--------------------------{$nl}";
    $body .= "Highest Qualification    : {$p['highestQual']}{$nl}";
    $body .= "Field of Study           : {$p['fieldStudy']}{$nl}";
    $body .= "Institution              : {$p['institution1']}{$nl}";
    $body .= "Country of Institution   : {$p['instCountry1']}{$nl}";
    $body .= "Year of Graduation       : {$p['gradYear']}{$nl}";
    $body .= "Grade / GPA              : " . ($p['grade'] ?: 'Not provided') . "{$nl}";
    $body .= "Language of Instruction  : {$p['instrLang']}{$nl}";
    $body .= "Additional Qualification : " . ($p['qual2'] ?: 'None') . "{$nl}";
    $body .= "Awarding Body            : " . ($p['body2'] ?: 'N/A') . "{$nl}";
    $body .= "English Proficiency      : {$p['englishMethod']}{$nl}";
    $body .= "English Test Score       : " . ($p['englishScore'] ?: 'N/A') . "{$nl}";
    $body .= "English Test Date        : " . ($p['englishDate'] ?: 'N/A') . "{$nl}{$nl}{$nl}";
    return $body;
}

function buildProfessionalSection($p, $nl) {
    $body  = "[ 4 ] PROFESSIONAL EXPERIENCE & GOALS{$nl}";
    $body .= "--------------------------------------{$nl}";
    $body .= "Current Job Title        : " . ($p['jobTitle']  ?: 'Not provided') . "{$nl}";
    $body .= "Current Employer         : " . ($p['employer']  ?: 'Not provided') . "{$nl}";
    $body .= "Industry / Sector        : " . ($p['industry']  ?: 'Not provided') . "{$nl}";
    $body .= "Years of Work Experience : " . ($p['yearsExp']  ?: 'Not provided') . "{$nl}";
    $body .= "Years in Management      : " . ($p['mgmtExp']   ?: 'Not provided') . "{$nl}{$nl}";
    $body .= "STATEMENT OF PURPOSE:{$nl}";
    $body .= "---------------------{$nl}";
    $body .= ($p['motivation'] ?: 'Not provided') . "{$nl}{$nl}";
    $body .= "CAREER GOALS:{$nl}";
    $body .= "-------------{$nl}";
    $body .= ($p['goals'] ?: 'Not provided') . "{$nl}{$nl}{$nl}";
    return $body;
}

function buildReferenceSection($p, $nl) {
    $body  = "[ 5 ] REFERENCE{$nl}";
    $body .= "----------------{$nl}";
    $body .= "Name         : " . ($p['ref1name']  ?: 'Not provided') . "{$nl}";
    $body .= "Email        : " . ($p['ref1email'] ?: 'Not provided') . "{$nl}";
    $body .= "Position     : " . ($p['ref1title'] ?: 'Not provided') . "{$nl}";
    $body .= "Organisation : " . ($p['ref1org']   ?: 'Not provided') . "{$nl}{$nl}{$nl}";
    return $body;
}

function buildUploadsSection($uploads, $uploadLabels, $nl) {
    $body  = "[ 6 ] UPLOADED DOCUMENTS{$nl}";
    $body .= "-------------------------{$nl}";
    foreach ($uploads as $key => $up) {
        $label = $uploadLabels[$key] ?? $key;
        $body .= str_pad($label . ' :', 25) . ($up['ok'] ? $up['origName'] . ' (attached)' : 'Not uploaded') . "{$nl}";
    }
    return $body;
}

// ── Send via PHPMailer with mail() fallback ───────────────────────────────────
function sendEmail($fromEmail, $fromName, $toEmail, $toName, $replyToEmail, $replyToName, $subject, $body, $uploads) {
    $phpMailerLoaded = loadPHPMailer();
    $sent    = false;
    $warning = '';

    if ($phpMailerLoaded) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSendmail();
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo($replyToEmail, $replyToName);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->isHTML(false);
            foreach ($uploads as $up) {
                if ($up['ok'] && $up['path'] && file_exists($up['path'])) {
                    $mail->addAttachment($up['path'], $up['origName']);
                }
            }
            $mail->send();
            $sent = true;
        } catch (\Exception $e) {
            $warning         = 'PHPMailer: ' . $e->getMessage();
            $phpMailerLoaded = false;
        }
    }

    if (!$sent) {
        $headers  = "From: {$fromEmail}\r\n";
        $headers .= "Reply-To: {$replyToEmail}\r\n";
        $fallbackNote = "\r\n\r\nNOTE: File attachments failed (PHPMailer unavailable). Retrieve from server: uploads/applications/\r\n" . ($warning ? "Error: $warning" : '');
        $sent = mail($toEmail, $subject, $body . $fallbackNote, $headers);
    }

    return ['sent' => $sent, 'warning' => $warning];
}
