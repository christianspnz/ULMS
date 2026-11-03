<?php

const LIBRARY_MAX_VIDEO_MB = 500;
const LIBRARY_MAX_PDF_MB = 50;
const LIBRARY_MAX_IMAGE_MB = 5;

function libFetchAll($conn, string $sql, string $types = "", array $params = []): array
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

// INSERT / UPDATE / DELETE: returns the number of affected rows
function libExec($conn, string $sql, string $types = "", array $params = []): int
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

// "300M" -> bytes
function libIniBytes(string $key): int
{
    $v = trim((string) ini_get($key));

    if ($v === '') {
        return 0;
    }

    $n = (float) $v;

    switch (strtolower(substr($v, -1))) {
        case 'g':
            $n *= 1024;
            // no break
        case 'm':
            $n *= 1024;
            // no break
        case 'k':
            $n *= 1024;
    }

    return (int) $n;
}

function libUnlink(?string $path): void
{
    if ($path && str_starts_with($path, 'uploads/library/') && !str_contains($path, '..')) {
        @unlink(__DIR__ . "/../../" . $path);
    }
}

function libUploadDir(string $sub = ''): string
{
    $dir = __DIR__ . "/../../uploads/library/" . ($sub !== '' ? $sub . '/' : '');

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new Exception("Could not create the uploads/library folder. Please create it by hand.");
    }

    return $dir;
}

function libUploadProblem(array $file): ?string
{
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            return null;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return "The file is larger than this server allows (upload_max_filesize is " . ini_get('upload_max_filesize')
                . ", post_max_size is " . ini_get('post_max_size') . "). Ask your administrator to raise them in php.ini.";
        case UPLOAD_ERR_PARTIAL:
            return "The upload was interrupted. Please try again.";
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
            return "The server couldn't save the file (temporary folder problem).";
        default:
            return "The file could not be uploaded.";
    }
}

// Reads what the file really is (its first bytes), not what its name says
function libDetectFile(string $tmp): array
{
    $h = (string) file_get_contents($tmp, false, null, 0, 16);

    if (strncmp($h, "%PDF-", 5) === 0) {
        return ['type' => 'PDF', 'ext' => 'pdf', 'mime' => 'application/pdf'];
    }

    if (strncmp($h, "\x1A\x45\xDF\xA3", 4) === 0) {
        return ['type' => 'Video', 'ext' => 'webm', 'mime' => 'video/webm'];
    }

    if (substr($h, 4, 4) === 'ftyp') {
        if (substr($h, 8, 4) === 'qt  ') {
            throw new Exception("QuickTime (.mov) videos often don't play in browsers. Please export the video as MP4.");
        }

        return ['type' => 'Video', 'ext' => 'mp4', 'mime' => 'video/mp4'];
    }

    throw new Exception("Please upload an MP4 or WebM video, or a PDF file.");
}

// Saves an uploaded video or PDF
function libStoreFile(array $file): array
{
    if ($problem = libUploadProblem($file)) {
        throw new Exception($problem);
    }

    $kind = libDetectFile($file['tmp_name']);
    $limitMb = $kind['type'] === 'Video' ? LIBRARY_MAX_VIDEO_MB : LIBRARY_MAX_PDF_MB;

    if ($file['size'] > $limitMb * 1048576) {
        throw new Exception("That {$kind['type']} is too large. The limit is {$limitMb} MB.");
    }

    $name = bin2hex(random_bytes(12)) . '.' . $kind['ext'];

    if (!move_uploaded_file($file['tmp_name'], libUploadDir() . $name)) {
        throw new Exception("Could not save the file.");
    }

    $original = preg_replace('/[^\p{L}\p{N}\-_. ()]/u', '_', basename((string) $file['name']));

    return [
        'path'     => "uploads/library/{$name}",
        'type'     => $kind['type'],
        'mime'     => $kind['mime'],
        'size'     => (int) $file['size'],
        'original' => substr($original, 0, 255),
    ];
}

// Saves a thumbnail picture (JPG / PNG / WEBP)
function libStoreImage(array $file): string
{
    if ($problem = libUploadProblem($file)) {
        throw new Exception($problem);
    }

    if ($file['size'] > LIBRARY_MAX_IMAGE_MB * 1048576) {
        throw new Exception("The thumbnail must be " . LIBRARY_MAX_IMAGE_MB . " MB or smaller.");
    }

    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

    if (!$info || !isset($types[$info[2]])) {
        throw new Exception("The thumbnail must be a JPG, PNG or WEBP image.");
    }

    $name = bin2hex(random_bytes(12)) . '.' . $types[$info[2]];

    if (!move_uploaded_file($file['tmp_name'], libUploadDir('thumbs') . $name)) {
        throw new Exception("Could not save the thumbnail.");
    }

    return "uploads/library/thumbs/{$name}";
}

// ---------------------------------------------------------------- learner side

const LIBRARY_VIEW_VIDEO_PERCENT = 80;     // share of the video that must be played for a view to count
const LIBRARY_VIEW_VIDEO_MIN_SECONDS = 10;  // floor for very short clips
const LIBRARY_VIEW_PDF_SECONDS = 10;   // seconds a PDF must stay open and visible
const LIBRARY_NEW_DAYS = 7;            // "New" badge on items published this recently

// Published, and either for every brand (no brand rows) or sharing a brand with the learner. Needs one ? = the user id.
function libVisibleClause(string $alias = 'li'): string
{
    return "{$alias}.status = 'Published' AND (
                NOT EXISTS (SELECT 1 FROM library_item_brands b WHERE b.library_id = {$alias}.library_id)
                OR EXISTS (SELECT 1 FROM library_item_brands b JOIN user_brands ub ON ub.brand_id = b.brand_id
                           WHERE b.library_id = {$alias}.library_id AND ub.user_id = ?))";
}

const LIB_ITEM_SELECT = "SELECT li.library_id, li.title, li.description, li.category, li.item_type, li.file_path, li.file_size,
            li.duration_seconds, li.thumbnail, li.published_at,
            (SELECT COUNT(*) FROM library_views v WHERE v.library_id = li.library_id) AS views,
            (SELECT COUNT(DISTINCT v.user_id) FROM library_views v WHERE v.library_id = li.library_id) AS learners,
            (SELECT COUNT(*) FROM library_feedback f WHERE f.library_id = li.library_id) AS rating_count,
            (SELECT ROUND(AVG(f.rating), 2) FROM library_feedback f WHERE f.library_id = li.library_id) AS rating_avg,
            lp.percent AS my_percent, lp.position_seconds AS my_position, lp.updated_at AS my_updated_at
         FROM library_items li
         LEFT JOIN library_progress lp ON lp.library_id = li.library_id AND lp.user_id = ?";

function libShapeItem(array $r): array
{
    return [
        'library_id'       => (int) $r['library_id'],
        'title'            => $r['title'],
        'description'      => $r['description'],
        'category'         => $r['category'],
        'item_type'        => $r['item_type'],
        'duration_seconds' => $r['duration_seconds'] !== null ? (int) $r['duration_seconds'] : null,
        'file_size'        => (int) ($r['file_size'] ?? 0),
        'thumbnail'        => $r['thumbnail'],
        'published_at'     => $r['published_at'],
        'is_new'           => (bool) ($r['published_at'] && strtotime($r['published_at']) >= time() - LIBRARY_NEW_DAYS * 86400),
        'views'            => (int) ($r['views'] ?? 0),
        'learners'         => (int) ($r['learners'] ?? 0),
        'my_percent'       => (int) ($r['my_percent'] ?? 0),
        'my_position'      => (int) ($r['my_position'] ?? 0),
        'my_updated_at'    => $r['my_updated_at'] ?? null,
        'rating_count'     => (int) ($r['rating_count'] ?? 0),
        'rating_avg'       => ((int) ($r['rating_count'] ?? 0) >= LIBRARY_MIN_RATINGS && $r['rating_avg'] !== null) ? (float) $r['rating_avg'] : null,
    ];
}

function libCounts($conn, int $libraryId): array
{
    $r = libFetchAll(
        $conn,
        "SELECT COUNT(*) AS views, COUNT(DISTINCT user_id) AS learners FROM library_views WHERE library_id = ?",
        "i",
        [$libraryId]
    )[0];

    return ['views' => (int) $r['views'], 'learners' => (int) $r['learners']];
}

// ---------------------------------------------------------------- ratings and feedback

const LIBRARY_MIN_RATINGS = 3;    // learners see the average only after this many ratings
const LIBRARY_LOW_RATING_MAX = 2; // a rating at or below this alerts the superadmins

function libRatingSummary($conn, int $libraryId): array
{
    $r = libFetchAll(
        $conn,
        "SELECT COUNT(*) AS n, ROUND(AVG(rating), 2) AS avg_rating,
                COALESCE(SUM(rating = 1), 0) AS r1, COALESCE(SUM(rating = 2), 0) AS r2, COALESCE(SUM(rating = 3), 0) AS r3,
                COALESCE(SUM(rating = 4), 0) AS r4, COALESCE(SUM(rating = 5), 0) AS r5
         FROM library_feedback WHERE library_id = ?",
        "i",
        [$libraryId]
    )[0];

    $count = (int) $r['n'];
    $distribution = [];

    for ($i = 5; $i >= 1; $i--) {
        $distribution[] = ['rating' => $i, 'count' => (int) $r["r{$i}"]];
    }

    return [
        'count'        => $count,
        'avg'          => $count > 0 ? (float) $r['avg_rating'] : null,
        'avg_visible'  => $count >= LIBRARY_MIN_RATINGS,
        'distribution' => $distribution,
    ];
}

// A learner may rate an item once it has counted as a view for them
function libCanRate($conn, int $libraryId, int $userId): bool
{
    return !empty(libFetchAll(
        $conn,
        "SELECT view_id FROM library_views WHERE library_id = ? AND user_id = ? LIMIT 1",
        "ii",
        [$libraryId, $userId]
    ));
}

const LIBRARY_FEEDBACK_POINTS = 2; // points for the FIRST rating of an item