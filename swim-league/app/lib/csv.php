<?php

function parse_csv_with_header(string $filepath): array {
    $rows = [];
    if (!is_readable($filepath)) return $rows;
    $h = fopen($filepath, 'r');
    if ($h === false) return $rows;
    $header = null;
    while (($data = fgetcsv($h)) !== false) {
        if ($header === null) {
            $header = $data;
            continue;
        }
        if (count($data) === 1 && $data[0] === null) continue; // skip empty
        $row = [];
        foreach ($header as $i => $col) {
            $row[$col] = $data[$i] ?? null;
        }
        $rows[] = $row;
    }
    fclose($h);
    return $rows;
}

function is_valid_csv_upload(array $file): bool {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return false;
    if (($file['size'] ?? 0) <= 0) return false;
    $ext_ok = preg_match('/\.csv$/i', $file['name']);
    $mime = $file['type'] ?? '';
    $mime_ok = in_array($mime, ['text/csv', 'application/vnd.ms-excel', 'application/csv', 'text/plain']);
    return $ext_ok || $mime_ok;
}

