<?php

/**
 * Processes the uploaded recipients file.
 *
 * @param string $filePath Path to the uploaded file.
 * @return array Parsed recipients data or an error message.
 */
function processRecipientsFile($filePath) {
    if (!file_exists($filePath)) {
        return ['error' => 'File not found.'];
    }

    $recipients = [];
    $file = fopen($filePath, 'r');

    if ($file === false) {
        return ['error' => 'Unable to open the file.'];
    }

    // Assuming the file is a CSV with headers: Name, Email
    $headers = fgetcsv($file);
    if ($headers === false || count($headers) < 2) {
        fclose($file);
        return ['error' => 'Invalid file format.'];
    }

    while (($row = fgetcsv($file)) !== false) {
        if (count($row) >= 2) {
            $recipients[] = [
                'name' => $row[0],
                'email' => $row[1],
            ];
        }
    }

    fclose($file);
    return $recipients;
}
