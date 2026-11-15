<?php
declare(strict_types=1);

const UPLOAD_MAX_IMAGE = 8 * 1024 * 1024;   // 8 MB
const UPLOAD_MAX_VIDEO = 60 * 1024 * 1024;  // 60 MB

/** Normalize $_FILES[name] (single or multiple) into a list of file arrays. */
function files_list(string $field): array {
    if (empty($_FILES[$field])) return [];
    $f = $_FILES[$field];
    if (!is_array($f['name'])) return $f['error'] === UPLOAD_ERR_NO_FILE ? [] : [$f];
    $out = [];
    foreach ($f['name'] as $i => $n) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

/**
 * Validate and store an uploaded image. The image is re-encoded with GD, which strips metadata
 * and any hidden payload, and is resized to at most $maxW px wide.
 * Returns the stored path relative to /uploads, or throws RuntimeException with a friendly message.
 */
function store_image(array $file, string $dir = 'site', int $maxW = 1800): string {
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (code ' . $file['error'] . '). The file may be too large for the server.');
    if ($file['size'] > UPLOAD_MAX_IMAGE) throw new RuntimeException('Image is larger than 8 MB.');
    if (!is_uploaded_file($file['tmp_name']) && PHP_SAPI !== 'cli') throw new RuntimeException('Invalid upload.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG, WEBP or GIF images are allowed.');
    $info = @getimagesize($file['tmp_name']);
    if (!$info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 40_000_000) throw new RuntimeException('This image could not be read.');
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png' => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        'image/gif' => @imagecreatefromgif($file['tmp_name']),
    };
    if (!$src) throw new RuntimeException('This image could not be read.');
    // Respect phone camera orientation
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $o = (int)(@exif_read_data($file['tmp_name'])['Orientation'] ?? 1);
        if ($o === 3) $src = imagerotate($src, 180, 0); elseif ($o === 6) $src = imagerotate($src, -90, 0); elseif ($o === 8) $src = imagerotate($src, 90, 0);
    }
    $w = imagesx($src); $h = imagesy($src);
    if ($w > $maxW) {
        $nh = (int)round($h * $maxW / $w);
        $dst = imagecreatetruecolor($maxW, $nh);
        imagealphablending($dst, false); imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxW, $nh, $w, $h);
        imagedestroy($src); $src = $dst;
    }
    $dir = preg_replace('/[^a-z0-9_\/-]/', '', $dir);
    $sub = $dir . '/' . date('Y/m');
    $abs = ROOT_DIR . '/uploads/' . $sub;
    if (!is_dir($abs) && !mkdir($abs, 0755, true)) throw new RuntimeException('Upload folder is not writable.');
    $name = rand_token(10);
    if (function_exists('imagewebp')) {
        imagesavealpha($src, true);
        $ok = imagewebp($src, "$abs/$name.webp", 84); $ext = 'webp';
    } else {
        $ok = imagejpeg($src, "$abs/$name.jpg", 86); $ext = 'jpg';
    }
    imagedestroy($src);
    if (!$ok) throw new RuntimeException('Could not save the image.');
    return "$sub/$name.$ext";
}

function store_video(array $file, string $dir = 'site'): string {
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Video upload failed (code ' . $file['error'] . ').');
    if ($file['size'] > UPLOAD_MAX_VIDEO) throw new RuntimeException('Video is larger than 60 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Only MP4 or WEBM videos are allowed.');
    $sub = preg_replace('/[^a-z0-9_\/-]/', '', $dir) . '/' . date('Y/m');
    $abs = ROOT_DIR . '/uploads/' . $sub;
    if (!is_dir($abs)) mkdir($abs, 0755, true);
    $name = rand_token(10) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], "$abs/$name") && !(PHP_SAPI === 'cli' && rename($file['tmp_name'], "$abs/$name"))) throw new RuntimeException('Could not save the video.');
    return "$sub/$name";
}

function delete_upload(?string $path): void {
    if (!$path || preg_match('~^https?://|\.\.~', $path)) return;
    $abs = ROOT_DIR . '/uploads/' . $path;
    if (is_file($abs)) @unlink($abs);
}

/** Upload helper for a single optional image field; returns new path, or $current if none uploaded. */
function maybe_image(string $field, ?string $current, string $dir = 'site', int $maxW = 1800): ?string {
    $files = files_list($field);
    if (!$files) {
        if (post($field . '_remove') === '1') { delete_upload($current); return null; }
        return $current;
    }
    $p = store_image($files[0], $dir, $maxW);
    if ($current) delete_upload($current);
    return $p;
}
