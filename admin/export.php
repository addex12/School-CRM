<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/database.php'; // Ensure this file contains the getPDO function

require_once __DIR__ . '/../vendor/autoload.php';

// Validate inputs
$survey_id = filter_input(INPUT_GET, 'survey_id', FILTER_VALIDATE_INT) ?? 0;
$type = strtolower(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING)) ?? 'csv';
$allowed_types = ['csv', 'excel', 'pdf'];

try {
    // Validate export type
    if (!in_array($type, $allowed_types)) {
        throw new Exception('Invalid export type');
    }

    // Get survey info
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->execute([$survey_id]);
    $survey = $stmt->fetch();

    if (!$survey) {
        header("Location: results.php");
        exit();
    }

    // Get survey fields
    $stmt = $pdo->prepare("SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
    $stmt->execute([$survey_id]);
    $fields = $stmt->fetchAll();

    // Get all responses with user info
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.email, u.role
        FROM survey_responses r
        JOIN users u ON r.user_id = u.id
        WHERE r.survey_id = ?
        ORDER BY r.submitted_at DESC
    ");
    $stmt->execute([$survey_id]);
    $responses = $stmt->fetchAll();

    // Get response data
    $response_data = [];
    foreach ($responses as $response) {
        $stmt = $pdo->prepare("
            SELECT rd.*, sf.field_name, sf.field_label
            FROM response_data rd
            JOIN survey_fields sf ON rd.field_id = sf.id
            WHERE rd.response_id = ?
        ");
        $stmt->execute([$response['id']]);
        $response_data[$response['id']] = $stmt->fetchAll();
    }

    // Handle export
    switch ($type) {
        case 'csv':
            exportCSV($survey, $fields, $responses, $response_data);
            break;
        case 'excel':
            exportExcel($survey, $fields, $responses, $response_data);
            break;
        case 'pdf':
            exportPDF($survey, $fields, $responses, $response_data);
            break;
    }

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    header("Location: results.php?error=database");
    exit();
} catch (Exception $e) {
    error_log("Export Error: " . $e->getMessage());
    header("Location: results.php?survey_id=$survey_id&error=export");
    exit();
}

function exportCSV($survey, $fields, $responses, $response_data): never {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="survey_' . $survey['id'] . '_results.csv"');
    header('Pragma: no-cache');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Survey Title', $survey['title']]);
    fputcsv($output, ['Survey Description', $survey['description']]);
    fputcsv($output, []); // Empty row
    
    // Headers
    $header = ['Response ID', 'Username', 'Email', 'Role', 'Submitted At'];
    foreach ($fields as $field) {
        $header[] = $field['field_label'] . ' (' . $field['field_name'] . ')';
    }
    fputcsv($output, $header);
    
    // Data rows
    foreach ($responses as $response) {
        $row = [
            $response['id'],
            $response['username'],
            $response['email'],
            $response['role_name'],
            $response['submitted_at']
        ];
        
        foreach ($fields as $field) {
            $value = '';
            foreach ($response_data[$response['id']] as $data) {
                if ($data['field_id'] == $field['id']) {
                    $value = $data['field_value'];
                    break;
                }
            }
            $row[] = $value;
        }
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

function exportExcel($survey, $fields, $responses, $response_data): never {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Meta data
    $sheet->setTitle('Survey Results')
        ->setCellValue('A1', 'Survey Title')
        ->setCellValue('B1', $survey['title'])
        ->setCellValue('A2', 'Survey Description')
        ->setCellValue('B2', $survey['description']);
    
    // Headers
    $header = ['Response ID', 'Username', 'Email', 'Role', 'Submitted At'];
    foreach ($fields as $field) {
        $header[] = $field['field_label'] . ' (' . $field['field_name'] . ')';
    }
    $sheet->fromArray($header, null, 'A4');
    
    // Data rows
    $row = 5;
    foreach ($responses as $response) {
        $data = [
            $response['id'],
            $response['username'],
            $response['email'],
            $response['role'],
            $response['submitted_at']
        ];
        
        foreach ($fields as $field) {
            $value = '';
            foreach ($response_data[$response['id']] as $d) {
                if ($d['field_id'] == $field['id']) {
                    $value = $d['field_value'];
                    break;
                }
            }
            $data[] = $value;
        }
        
        $sheet->fromArray($data, null, "A$row");
        $row++;
    }
    
    // Styling
    $sheet->getStyle('A4:' . $sheet->getHighestColumn() . '4')
            ->getFont()->setBold(true);
        $sheet->getStyle('A4:' . $sheet->getHighestColumn() . '4')
            ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
    
    foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="survey_' . $survey['id'] . '_results.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

function exportPDF($survey, $fields, $responses, $response_data): never {
    $mpdf = new \Mpdf\Mpdf([
        'tempDir' => __DIR__ . '/../tmp',
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font' => 'dejavusans'
    ]);
    
    $html = '<h1>' . htmlspecialchars($survey['title']) . '</h1>';
    $html .= '<p>' . nl2br(htmlspecialchars($survey['description'])) . '</p>';
    $html .= '<p><strong>Total Responses:</strong> ' . number_format(count($responses)) . '</p><hr>';
    
    foreach ($responses as $index => $response) {
        $html .= '<h2>Response from ' . htmlspecialchars($response['username']) . '</h2>';
        $html .= '<div style="margin-bottom:15px;">';
        $html .= '<strong>Email:</strong> ' . htmlspecialchars($response['email']) . '<br>';
        $html .= '<strong>Role:</strong> ' . htmlspecialchars($response['role']) . '<br>';
        $html .= '<strong>Submitted:</strong> ' . date('M j, Y g:i a', strtotime($response['submitted_at'])) . '</div>';
        
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width:100%;margin-bottom:20px;border-collapse:collapse;">';
        $html .= '<tr><th style="width:30%;background:#f0f0f0;">Question</th><th style="width:70%;background:#f0f0f0;">Response</th></tr>';
        
        foreach ($response_data[$response['id']] as $data) {
            $html .= '<tr>';
            $html .= '<td style="padding:8px;">' . htmlspecialchars($data['field_label']) . '</td>';
            $html .= '<td style="padding:8px;">' . nl2br(htmlspecialchars($data['field_value'])) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        
        if ($index !== count($responses) - 1) {
            $html .= '<pagebreak />';
        }
    }
    
    $mpdf->WriteHTML($html);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="survey_' . $survey['id'] . '_results.pdf"');
    $mpdf->Output('php://output', 'D');
    exit();
}