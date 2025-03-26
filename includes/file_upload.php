<?php
function handleFileUpload($inputName) {
    // Check if file was uploaded without errors
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$inputName];
    
    // Security checks
    $allowedTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'text/plain' => 'txt',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
    ];
    
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($file['tmp_name']);

    // Validate file type and size
    if (!array_key_exists($mimeType, $allowedTypes)) {
        throw new Exception("Invalid file type. Allowed types: PDF, JPG, PNG, TXT, DOC/DOCX");
    }

    if ($file['size'] > $maxFileSize) {
        throw new Exception("File size exceeds 5MB limit");
    }

    // Generate unique filename
    $extension = $allowedTypes[$mimeType];
    $filename = uniqid('file_', true) . '.' . $extension;
    
    // Define upload directory
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!is_writable($uploadDir)) {
        throw new Exception("Upload directory is not writable");
    }

    // Move file to permanent location
    $destination = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Failed to move uploaded file");
    }

    return $filename;
}