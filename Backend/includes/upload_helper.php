<?php
function save_upload(array $file, string $destDir, array $allowed=['image/jpeg','image/png','image/webp'], int $maxMB=3): array {
  if (!isset($file['error']) || $file['error']===UPLOAD_ERR_NO_FILE) return [null, null]; // optional
  if ($file['error']!==UPLOAD_ERR_OK) return [null, 'Upload failed (code '.$file['error'].').'];
  $mime = mime_content_type($file['tmp_name']);
  if (!in_array($mime,$allowed,true)) return [null,'Only JPG, PNG or WEBP allowed.'];
  if ($file['size'] > $maxMB*1024*1024) return [null,'File too large (max '.$maxMB.'MB).'];
  if (!is_dir($destDir)) mkdir($destDir,0777,true);
  $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  $name  = bin2hex(random_bytes(8)).'.'.$ext;
  $to    = rtrim($destDir,'/\\').DIRECTORY_SEPARATOR.$name;
  if (!move_uploaded_file($file['tmp_name'],$to)) return [null,'Could not save file.'];
  return [$name, null];
}
