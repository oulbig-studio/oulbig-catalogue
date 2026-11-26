<?php
// Sert le contenu du site : la version éditée dans le back-office (storage/) si elle existe,
// sinon le contenu de départ livré avec le site (data/).
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
$edited = __DIR__ . '/../storage/content.json';
$file = is_file($edited) ? $edited : __DIR__ . '/../data/content.json';
$etag = '"' . md5_file($file) . '"';
header('ETag: ' . $etag);
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }
readfile($file);
