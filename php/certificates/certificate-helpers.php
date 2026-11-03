<?php

require_once __DIR__ . "/../notifications/notification-helpers.php";
require_once __DIR__ . "/name-case.php";

const CERT_FONTS = ['playfair', 'cinzel', 'script', 'garamond', 'cormorant', 'montserrat', 'georgia', 'arial'];

// Share of the session a learner must attend to earn a certificate (time in to time out)
const CERT_MIN_ATTENDANCE_PERCENT = 80;

const CERT_DEFAULT_BODY = 'For completing the **{title}**. Given this **{day}^^{ordinal}^^** day of **{month}**, in the year **{year}**, UAAGI Auto Group commends his/her dedication in continuous learning and growth.';

// ---------------------------------------------------------------- database

function certFetchAll($conn, string $sql, string $types = "", array $params = []): array
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($err);
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);

    return $rows;
}

// Runs an INSERT / UPDATE / DELETE and returns the number of affected rows
function certExec($conn, string $sql, string $types = "", array $params = []): int
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($err);
    }

    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    return $affected;
}

// ---------------------------------------------------------------- layout

function certNum($v, float $default, float $min, float $max): float
{
    return max($min, min($max, is_numeric($v) ? (float) $v : $default));
}

function certColor($v, string $default): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $v) ? $v : $default;
}

function certFont($v, string $default = 'georgia'): string
{
    $v = (string) $v;
    return (in_array($v, CERT_FONTS, true) || preg_match('/^custom:\d{1,9}$/', $v)) ? $v : $default;
}

function certAlign($v): string
{
    return in_array($v, ['left', 'center', 'right'], true) ? $v : 'center';
}

// Keeps only the known elements and clamps every value, so a template can never store anything odd
function sanitizeCertificateLayout($raw): array
{
    $e = (is_array($raw) && is_array($raw['elements'] ?? null)) ? $raw['elements'] : [];
    $vis = fn($x) => !isset($x['visible']) || !empty($x['visible']);

    $text = function ($x, array $d) use ($vis) {
        $x = is_array($x) ? $x : [];

        return [
            'visible'  => $vis($x),
            'x'        => certNum($x['x'] ?? null, $d['x'], 0, 100),
            'y'        => certNum($x['y'] ?? null, $d['y'], 0, 100),
            'size'     => certNum($x['size'] ?? null, $d['size'], 0.5, 15),
            'maxWidth' => certNum($x['maxWidth'] ?? null, $d['maxWidth'], 10, 100),
            'color'    => certColor($x['color'] ?? null, '#111111'),
            'font'     => certFont($x['font'] ?? null, $d['font']),
            'weight'   => (($x['weight'] ?? 'normal') === 'bold') ? 'bold' : 'normal',
            'align'    => certAlign($x['align'] ?? 'center'),
        ];
    };

    $name = $text($e['name'] ?? [], ['x' => 50, 'y' => 49.5, 'size' => 8.5, 'maxWidth' => 78, 'font' => 'script']);
    $number = $text($e['number'] ?? [], ['x' => 50, 'y' => 97.6, 'size' => 0.9, 'maxWidth' => 50, 'font' => 'montserrat']);

    $b = is_array($e['body'] ?? null) ? $e['body'] : [];
    $body = $text($b, ['x' => 50, 'y' => 64.5, 'size' => 2.05, 'maxWidth' => 66, 'font' => 'garamond']);
    $body['lineHeight'] = certNum($b['lineHeight'] ?? null, 1.3, 1, 3);
    $bodyText = is_string($b['text'] ?? null) ? trim($b['text']) : '';
    $body['text'] = $bodyText !== ''
        ? (function_exists('mb_substr') ? mb_substr($bodyText, 0, 600) : substr($bodyText, 0, 600))
        : CERT_DEFAULT_BODY;

    $l = is_array($e['brandLogo'] ?? null) ? $e['brandLogo'] : [];
    $brandLogo = [
        'visible' => $vis($l),
        'x'       => certNum($l['x'] ?? null, 80.5, 0, 100),
        'y'       => certNum($l['y'] ?? null, 16.3, 0, 100),
        'width'   => certNum($l['width'] ?? null, 20, 3, 60),
        'logoId'  => max(0, (int) ($l['logoId'] ?? 0)), // 0 = the certificate's brand's main logo
    ];

    $x2 = is_array($e['extraLogo'] ?? null) ? $e['extraLogo'] : [];
    $extraLogo = [
        'visible' => !empty($x2['visible']),            // off until you turn it on
        'x'       => certNum($x2['x'] ?? null, 50, 0, 100),
        'y'       => certNum($x2['y'] ?? null, 16.3, 0, 100),
        'width'   => certNum($x2['width'] ?? null, 14, 3, 60),
        'logoId'  => max(0, (int) ($x2['logoId'] ?? 0)),
    ];

    $q = is_array($e['qr'] ?? null) ? $e['qr'] : [];
    $qr = [
        'visible' => $vis($q),
        'x'       => certNum($q['x'] ?? null, 50, 0, 100),
        'y'       => certNum($q['y'] ?? null, 92.2, 0, 100),
        'size'    => certNum($q['size'] ?? null, 6.5, 3, 20),
    ];

    $s = is_array($e['signatures'] ?? null) ? $e['signatures'] : [];
    $slotsIn = is_array($s['slots'] ?? null) ? array_values($s['slots']) : [];
    $defaultX = [26, 50.4, 74.7];
    $slots = [];

    for ($i = 0; $i < 3; $i++) {
        $slot = is_array($slotsIn[$i] ?? null) ? $slotsIn[$i] : [];
        $slots[] = [
            'signatory_id' => max(0, (int) ($slot['signatory_id'] ?? 0)),
            'x'            => certNum($slot['x'] ?? null, $defaultX[$i], 0, 100),
        ];
    }

    $signatures = [
        'visible'    => $vis($s),
        'slots'      => $slots,
        'sigY'       => certNum($s['sigY'] ?? null, 77.8, 0, 100),
        'sigWidth'   => certNum($s['sigWidth'] ?? null, 15, 4, 40),
        'nameY'      => certNum($s['nameY'] ?? null, 83.6, 0, 100),
        'titleY'     => certNum($s['titleY'] ?? null, 86.2, 0, 100),
        'nameSize'   => certNum($s['nameSize'] ?? null, 2.0, 0.5, 8),
        'titleSize'  => certNum($s['titleSize'] ?? null, 1.6, 0.5, 8),
        'nameFont'   => certFont($s['nameFont'] ?? null, 'cormorant'),
        'titleFont'  => certFont($s['titleFont'] ?? null, 'cormorant'),
        'nameWeight' => (($s['nameWeight'] ?? 'bold') === 'normal') ? 'normal' : 'bold',
        'color'      => certColor($s['color'] ?? null, '#111111'),
    ];

    return ['elements' => compact('name', 'body', 'number', 'brandLogo', 'extraLogo', 'qr', 'signatures')];
}

// ---------------------------------------------------------------- uploads

function certUnlink(?string $path): void
{
    if ($path && str_starts_with($path, 'uploads/') && !str_contains($path, '..')) {
        @unlink(__DIR__ . "/../../" . $path);
    }
}

function certUploadDir(string $subdir): string
{
    $dir = __DIR__ . "/../../uploads/{$subdir}/";

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new Exception("Could not create the uploads/{$subdir} folder. Please create it by hand.");
    }

    return $dir;
}

function certCheckUpload(array $file, int $maxBytes): void
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("The file could not be uploaded. It may be larger than the server allows.");
    }

    if ($file['size'] > $maxBytes) {
        throw new Exception("The file is too large (maximum " . round($maxBytes / 1048576) . " MB).");
    }
}

// Saves an uploaded PNG/JPG(/WEBP) and returns its path, e.g. "uploads/logos/ab12.png"
function certStoreImage(array $file, string $subdir, int $maxBytes, int $minWidth = 0, bool $allowWebp = false): string
{
    certCheckUpload($file, $maxBytes);

    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];

    if ($allowWebp) {
        $types[IMAGETYPE_WEBP] = 'webp';
    }

    if (!$info || !isset($types[$info[2]])) {
        throw new Exception("Please upload a PNG or JPG image" . ($allowWebp ? " (WEBP also works)." : "."));
    }

    if ($info[0] < $minWidth) {
        throw new Exception("The image is too small. Please use one at least {$minWidth} px wide.");
    }

    $name = bin2hex(random_bytes(10)) . '.' . $types[$info[2]];

    if (!move_uploaded_file($file['tmp_name'], certUploadDir($subdir) . $name)) {
        throw new Exception("Could not save the image.");
    }

    return "uploads/{$subdir}/{$name}";
}

// Saves an uploaded font file (.ttf .otf .woff .woff2) after checking what the file really is
function certStoreFont(array $file): string
{
    certCheckUpload($file, 4 * 1048576);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ['ttf', 'otf', 'woff', 'woff2'], true)) {
        throw new Exception("Please upload a .ttf, .otf, .woff or .woff2 font file.");
    }

    $head = (string) file_get_contents($file['tmp_name'], false, null, 0, 4);

    if (!in_array($head, ["\x00\x01\x00\x00", "true", "OTTO", "wOFF", "wOF2"], true)) {
        throw new Exception("That doesn't look like a valid font file.");
    }

    $name = bin2hex(random_bytes(10)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], certUploadDir('fonts') . $name)) {
        throw new Exception("Could not save the font.");
    }

    return "uploads/fonts/{$name}";
}

// ---------------------------------------------------------------- assets (shared by every page that draws a certificate)

function getCertificateAssets($conn): array
{
    $templates = [];
    $defaults = [];

    foreach (certFetchAll($conn, "SELECT template_id, name, brand_id, applies_to, is_default, background_path, layout, valid_months, archived_at, updated_at FROM certificate_templates") as $r) {
        $id = (int) $r['template_id'];

        $templates[$id] = [
            'template_id'     => $id,
            'name'            => $r['name'],
            'brand_id'        => (int) $r['brand_id'],
            'is_default'      => (int) $r['is_default'],
            'background_path' => $r['background_path'],
            'valid_months'    => $r['valid_months'] !== null ? (int) $r['valid_months'] : null,
            'archived'        => $r['archived_at'] !== null,
            'version'         => strtotime($r['updated_at']),
            'layout'          => sanitizeCertificateLayout(json_decode($r['layout'], true)),
            'applies_to'      => $r['applies_to'],
        ];

        if ((int) $r['is_default'] === 1 && $r['archived_at'] === null) {
            $defaults[(int) $r['brand_id']] = $id;
        }
    }

    $logos = [];
    foreach (certFetchAll($conn, "SELECT logo_id, brand_id, label, logo_path, is_primary FROM certificate_logos ORDER BY brand_id, is_primary DESC, logo_id") as $r) {
        $logos[(int) $r['logo_id']] = [
            'logo_id'    => (int) $r['logo_id'],
            'brand_id'   => (int) $r['brand_id'],
            'label'      => $r['label'],
            'logo_path'  => $r['logo_path'],
            'is_primary' => (int) $r['is_primary'],
        ];
    }

    $signatories = [];
    foreach (certFetchAll($conn, "SELECT signatory_id, name, position, signature_path FROM certificate_signatories ORDER BY name") as $r) {
        $signatories[(int) $r['signatory_id']] = [
            'signatory_id'   => (int) $r['signatory_id'],
            'name'           => $r['name'],
            'position'       => $r['position'],
            'signature_path' => $r['signature_path'],
        ];
    }

    $fonts = array_map(
        fn($r) => ['font_id' => (int) $r['font_id'], 'family' => $r['family'], 'file_path' => $r['file_path']],
        certFetchAll($conn, "SELECT font_id, family, file_path FROM certificate_fonts ORDER BY family")
    );

    return [
        'templates'   => (object) $templates,
        'defaults'    => (object) $defaults,
        'logos'       => (object) $logos,
        'signatories' => (object) $signatories,
        'fonts'       => $fonts,
    ];
}

// ---------------------------------------------------------------- issuing

function formatRecipientName(array $u): string
{
    $first = certProperCase((string) $u['first_name']);
    $last = certProperCase((string) $u['last_name']);
    $middle = trim((string) ($u['middle_name'] ?? ''));
    $initial = '';

    if ($middle !== '' && preg_match('/./u', $middle, $m)) {
        $initial = certUpper($m[0]) . '. ';
    }

    return trim($first . ' ' . $initial . $last);
}

// Which brand the certificate belongs to: the schedule's brand that the learner also has, else the learner's own
function pickCertificateBrand($conn, int $userId, int $scheduleId): int
{
    $scheduleBrands = array_map('intval', array_column(
        certFetchAll($conn, "SELECT brand_id FROM schedule_brands WHERE schedule_id = ?", "i", [$scheduleId]),
        'brand_id'
    ));

    $userBrands = array_map('intval', array_column(
        certFetchAll($conn, "SELECT brand_id FROM user_brands WHERE user_id = ? ORDER BY brand_id", "i", [$userId]),
        'brand_id'
    ));

    $candidates = !empty($scheduleBrands) ? array_values(array_intersect($scheduleBrands, $userBrands)) : $userBrands;

    if (empty($candidates)) {
        $candidates = $scheduleBrands;
    }

    if (empty($candidates)) {
        return 0;
    }

    foreach ($candidates as $brandId) {
        $has = certFetchAll(
            $conn,
            "SELECT template_id FROM certificate_templates WHERE brand_id = ? AND is_default = 1 AND archived_at IS NULL LIMIT 1",
            "i",
            [$brandId]
        );

        if (!empty($has)) {
            return $brandId;
        }
    }

    return $candidates[0];
}

// The template the schedule/course picked, else the brand's default for this kind, else the "all brands" default, else null (built-in design)
function resolveCertificateTemplateId($conn, ?int $explicitTemplateId, int $brandId, string $kind = 'Training'): ?int
{
    if ($explicitTemplateId) {
        $r = certFetchAll(
            $conn,
            "SELECT template_id FROM certificate_templates
             WHERE template_id = ? AND archived_at IS NULL AND applies_to IN ('Both', ?)",
            "is",
            [$explicitTemplateId, $kind]
        );

        if (!empty($r)) {
            return (int) $r[0]['template_id'];
        }
    }

    foreach ([$brandId, 0] as $b) {
        // A default made for exactly this kind wins over a "Both" default
        $r = certFetchAll(
            $conn,
            "SELECT template_id FROM certificate_templates
             WHERE brand_id = ? AND is_default = 1 AND archived_at IS NULL AND applies_to IN ('Both', ?)
             ORDER BY (applies_to = ?) DESC LIMIT 1",
            "iss",
            [$b, $kind, $kind]
        );

        if (!empty($r)) {
            return (int) $r[0]['template_id'];
        }
    }

    return null;
}

// A brand-specific template puts its own brand's logo on the certificate
function certBrandForTemplate($conn, ?int $templateId, int $fallbackBrandId): int
{
    if (!$templateId) {
        return $fallbackBrandId;
    }

    $r = certFetchAll($conn, "SELECT brand_id FROM certificate_templates WHERE template_id = ?", "i", [$templateId]);

    return (!empty($r) && (int) $r[0]['brand_id'] > 0) ? (int) $r[0]['brand_id'] : $fallbackBrandId;
}

// Did the learner stay long enough? Time in / out are clamped to the session so early joiners get no bonus.
function certificateAttendanceQualifies($conn, int $userId, int $scheduleId): array
{
    $r = certFetchAll(
        $conn,
        "SELECT s.event_date, s.start_time, s.end_time, sa.time_in, sa.time_out
         FROM schedule_attendance sa JOIN schedules s ON s.schedule_id = sa.schedule_id
         WHERE sa.user_id = ? AND sa.schedule_id = ?",
        "ii",
        [$userId, $scheduleId]
    );

    if (empty($r) || !$r[0]['time_in'] || !$r[0]['time_out']) {
        return [false, 0];
    }

    $start = strtotime($r[0]['event_date'] . ' ' . $r[0]['start_time']);
    $end = strtotime($r[0]['event_date'] . ' ' . $r[0]['end_time']);
    $in = max(strtotime($r[0]['time_in']), $start);
    $out = min(strtotime($r[0]['time_out']), $end);
    $percent = (int) round(max(0, $out - $in) / max(1, $end - $start) * 100);

    return [$percent >= CERT_MIN_ATTENDANCE_PERCENT, $percent];
}

/**
 * One certificate per person per training.
 * Returns ['certificate' => [...]|null, 'reason' => null|'exists'|'disabled'|'attendance'|'missing', 'percent' => int|null]
 */
function issueTrainingCertificate($conn, int $userId, int $scheduleId, bool $notify = true, bool $checkAttendance = true): array
{
    $result = ['certificate' => null, 'reason' => null, 'percent' => null];

    $existing = certFetchAll(
        $conn,
        "SELECT certificate_id FROM certificates WHERE user_id = ? AND source_type = 'Training' AND source_id = ?",
        "ii",
        [$userId, $scheduleId]
    );

    if (!empty($existing)) {
        $result['reason'] = 'exists';
        return $result;
    }

    $schedule = certFetchAll(
        $conn,
        "SELECT title, event_date, certificate_template_id, issue_certificate FROM schedules WHERE schedule_id = ?",
        "i",
        [$scheduleId]
    );
    $user = certFetchAll($conn, "SELECT first_name, middle_name, last_name FROM users WHERE user_id = ?", "i", [$userId]);

    if (empty($schedule) || empty($user)) {
        $result['reason'] = 'missing';
        return $result;
    }

    if ((int) $schedule[0]['issue_certificate'] !== 1) {
        $result['reason'] = 'disabled';
        return $result;
    }

    if ($checkAttendance) {
        [$ok, $percent] = certificateAttendanceQualifies($conn, $userId, $scheduleId);
        $result['percent'] = $percent;

        if (!$ok) {
            $result['reason'] = 'attendance';
            return $result;
        }
    }

    $title = $schedule[0]['title'];
    $date = $schedule[0]['event_date'];
    $name = formatRecipientName($user[0]);
    $brandId = pickCertificateBrand($conn, $userId, $scheduleId);
    $templateId = resolveCertificateTemplateId(
        $conn,
        $schedule[0]['certificate_template_id'] !== null ? (int) $schedule[0]['certificate_template_id'] : null,
        $brandId
    );

    $brandId = certBrandForTemplate($conn, $templateId, $brandId);

    $validUntil = null;

    if ($templateId) {
        $t = certFetchAll($conn, "SELECT valid_months FROM certificate_templates WHERE template_id = ?", "i", [$templateId]);

        if (!empty($t[0]['valid_months'])) {
            $validUntil = date('Y-m-d', strtotime("+" . (int) $t[0]['valid_months'] . " months", strtotime($date)));
        }
    }

    $year = date('Y', strtotime($date));

    for ($attempt = 0; $attempt < 5; $attempt++) {

        // Random (not sequential), so numbers can't be guessed
        $number = sprintf('UAAGI-%s-%s', $year, strtoupper(bin2hex(random_bytes(4))));

        $stmt = mysqli_prepare(
            $conn,
            "INSERT IGNORE INTO certificates
                (certificate_no, user_id, source_type, source_id, brand_id, template_id, recipient_name, title, event_date, valid_until)
             VALUES (?, ?, 'Training', ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "siiiissss", $number, $userId, $scheduleId, $brandId, $templateId, $name, $title, $date, $validUntil);
        mysqli_stmt_execute($stmt);

        $inserted = mysqli_stmt_affected_rows($stmt) === 1;
        $id = (int) mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        if (!$inserted) {
            continue; // number collision: try another
        }

        if ($notify) {
            try {
                insertNotification(
                    $conn,
                    $userId,
                    'Certificate',
                    'Certificate earned',
                    "You earned a certificate for \"{$title}\".",
                    'certificates.php'
                );
            } catch (Throwable $e) {
                error_log("Certificate notification failed: " . $e->getMessage());
            }
        }

        $result['certificate'] = ['certificate_id' => $id, 'certificate_no' => $number, 'title' => $title];
        return $result;
    }

    $result['reason'] = 'missing';
    return $result;
}

const CERT_ISSUE_COURSE_CERTIFICATES = true; // set to false to stop certificates for passed courses

// Best Post-Test score at or above the passing score?
function courseTestPassed($conn, int $userId, int $courseId): bool
{
    $r = certFetchAll(
        $conn,
        "SELECT MAX(aa.score) AS best, MAX(a.passing_score) AS needed
         FROM assessment_attempts aa JOIN assessments a ON a.assessment_id = aa.assessment_id
         WHERE aa.user_id = ? AND a.course_id = ? AND a.assessment_type = 'Post-Test'",
        "ii",
        [$userId, $courseId]
    );

    return !empty($r) && $r[0]['best'] !== null && (float) $r[0]['best'] >= (float) $r[0]['needed'];
}

/**
 * A certificate for a completed course, following the course's own settings
 * (switch, template, requirement). Returns the new certificate, or null if none was issued.
 * $passed: pass the result when you know it; leave null to look it up.
 */
function issueCourseCertificate($conn, int $userId, int $courseId, bool $notify = true, ?bool $passed = null): ?array
{
    if (!CERT_ISSUE_COURSE_CERTIFICATES) {
        return null;
    }

    $existing = certFetchAll(
        $conn,
        "SELECT certificate_id FROM certificates WHERE user_id = ? AND source_type = 'Course' AND source_id = ?",
        "ii",
        [$userId, $courseId]
    );

    $course = certFetchAll(
        $conn,
        "SELECT course_title, issue_certificate, certificate_template_id, certificate_requirement FROM courses WHERE course_id = ?",
        "i",
        [$courseId]
    );
    $enrollment = certFetchAll(
        $conn,
        "SELECT completed_at FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'Completed'",
        "ii",
        [$userId, $courseId]
    );
    $user = certFetchAll($conn, "SELECT first_name, middle_name, last_name FROM users WHERE user_id = ?", "i", [$userId]);

    if (!empty($existing) || empty($course) || empty($enrollment) || empty($user)) {
        return null;
    }

    if ((int) $course[0]['issue_certificate'] !== 1) {
        return null;
    }

    if ($course[0]['certificate_requirement'] === 'Passed' && !($passed ?? courseTestPassed($conn, $userId, $courseId))) {
        return null;
    }

    $title = $course[0]['course_title'];
    $date = date('Y-m-d', strtotime($enrollment[0]['completed_at'] ?: 'now'));
    $name = formatRecipientName($user[0]);

    // The learner's own brand: the first one with a default template, else the first one
    $brands = array_map('intval', array_column(
        certFetchAll($conn, "SELECT brand_id FROM user_brands WHERE user_id = ? ORDER BY brand_id", "i", [$userId]),
        'brand_id'
    ));
    $brandId = $brands[0] ?? 0;

    foreach ($brands as $b) {
        $has = certFetchAll(
            $conn,
            "SELECT template_id FROM certificate_templates WHERE brand_id = ? AND is_default = 1 AND archived_at IS NULL LIMIT 1",
            "i",
            [$b]
        );

        if (!empty($has)) {
            $brandId = $b;
            break;
        }
    }

    $templateId = resolveCertificateTemplateId(
        $conn,
        $course[0]['certificate_template_id'] !== null ? (int) $course[0]['certificate_template_id'] : null,
        $brandId,
        'Course'
    );
    $brandId = certBrandForTemplate($conn, $templateId, $brandId);

    $validUntil = null;

    if ($templateId) {
        $t = certFetchAll($conn, "SELECT valid_months FROM certificate_templates WHERE template_id = ?", "i", [$templateId]);

        if (!empty($t[0]['valid_months'])) {
            $validUntil = date('Y-m-d', strtotime("+" . (int) $t[0]['valid_months'] . " months", strtotime($date)));
        }
    }

    for ($attempt = 0; $attempt < 5; $attempt++) {

        $number = sprintf('UAAGI-%s-%s', date('Y', strtotime($date)), strtoupper(bin2hex(random_bytes(4))));

        $stmt = mysqli_prepare(
            $conn,
            "INSERT IGNORE INTO certificates
                (certificate_no, user_id, source_type, source_id, brand_id, template_id, recipient_name, title, event_date, valid_until)
             VALUES (?, ?, 'Course', ?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "siiiissss", $number, $userId, $courseId, $brandId, $templateId, $name, $title, $date, $validUntil);
        mysqli_stmt_execute($stmt);

        $inserted = mysqli_stmt_affected_rows($stmt) === 1;
        $id = (int) mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        if (!$inserted) {
            continue;
        }

        if ($notify) {
            try {
                insertNotification(
                    $conn,
                    $userId,
                    'Certificate',
                    'Certificate earned',
                    "You earned a certificate of completion for \"{$title}\".",
                    'certificates.php'
                );
            } catch (Throwable $e) {
                error_log("Certificate notification failed: " . $e->getMessage());
            }
        }

        return ['certificate_id' => $id, 'certificate_no' => $number, 'title' => $title];
    }

    return null;
}
