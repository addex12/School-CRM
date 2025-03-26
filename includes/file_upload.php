<?php
function handleFileUpload($inputName) {
    if (!isset($_FILES[$inputName])) {
        return null;
    }

    $file = $_FILES[$inputName];
    validateFile($file);

    $allowedTypes = getAllowedMimeTypes();
    $filename = generateFilename($file, $allowedTypes);

    validateFileSize($file['size']);

    $destination = moveFileToUploadDir($file['tmp_name'], $filename);

    return $filename;
}

function validateFile($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error: " . $file['error']);
    }
}

function getAllowedMimeTypes() {
    return [
        'application/pdf' => 'pdf',
        'application/x-pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/x-png' => 'png',
        'text/plain' => 'txt',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-word.document.macroEnabled.12' => 'docm',
        'application/octet-stream' => null
    ];
}

function generateFilename($file, $allowedTypes) {
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($file['tmp_name']);

    if (!array_key_exists($mimeType, $allowedTypes)) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $validExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'txt', 'doc', 'docx'];
        if (!in_array($extension, $validExtensions)) {
            throw new Exception("Invalid file type. Detected: $mimeType. Allowed types: PDF, JPG, PNG, TXT, DOC/DOCX");
        }
        return uniqid('file_', true) . '.' . $extension;
    }

    $extension = $allowedTypes[$mimeType] ?? pathinfo($file['name'], PATHINFO_EXTENSION);
    return uniqid('file_', true) . '.' . $extension;
}

function validateFileSize($size) {
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    if ($size > $maxFileSize) {
        throw new Exception("File size exceeds 5MB limit");
    }
}

function moveFileToUploadDir($tmpName, $filename) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . $filename;
    if (!move_uploaded_file($tmpName, $destination)) {
        throw new Exception("Failed to move uploaded file");
    }

    return $destination;
}