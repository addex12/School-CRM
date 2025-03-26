<?php
function handleFileUpload($inputName) {
    // Check if file was actually uploaded
    if (!isset($_FILES[$inputName]) {
        return null;
    }

    $file = $_FILES[$inputName];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception(getUploadErrorMessage($file['error']));
    }

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

    // Validate file type
    if (!array_key_exists($mimeType, $allowedTypes)) {
        throw new Exception("Invalid file type. Allowed types: PDF, JPG, PNG, TXT, DOC/DOCX");
    }

    // Validate file size
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

    // Move file to permanent location
    $destination = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception("Failed to move uploaded file");
    }

    return $filename;
}

function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
    }
}