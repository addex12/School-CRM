<?php
// Include mPDF manually
// Manually load PhpSpreadsheet classes
require_once __DIR__ . '/../vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
require_once __DIR__ . '/../vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Writer/Xlsx.php';

// Declare class aliases for IDE support
class_alias(\PhpOffice\PhpSpreadsheet\Spreadsheet::class, 'PhpOffice\PhpSpreadsheet\Spreadsheet');
class_alias(\PhpOffice\PhpSpreadsheet\Writer\Xlsx::class, 'PhpOffice\PhpSpreadsheet\Writer\Xlsx');
require_once __DIR__ . '/../vendor/mpdf/autoload.php';

$mpdf = new \Mpdf\Mpdf();
$mpdf->Output();
require_once '../includes/auth.php';
requireAdmin();

$survey_id = $_GET['survey_id'] ?? 0;
$type = $_GET['type'] ?? 'csv';

// Get survey info
$stmt = $pdo->prepare(query: "SELECT * FROM surveys WHERE id = ?");
$stmt->execute(params: [$survey_id]);
$survey = $stmt->fetch();

if (!$survey) {
    header(header: "Location: results.php");
    exit();
}

// Get survey fields
$stmt = $pdo->prepare(query: "SELECT * FROM survey_fields WHERE survey_id = ? ORDER BY display_order");
$stmt->execute(params: [$survey_id]);
$fields = $stmt->fetchAll();

// Get all responses for this survey
$stmt = $pdo->prepare(query: "
    SELECT r.*, u.username, u.email, u.role
    FROM survey_responses r
    JOIN users u ON r.user_id = u.id
    WHERE r.survey_id = ?
    ORDER BY r.submitted_at DESC
");
$stmt->execute(params: [$survey_id]);
$responses = $stmt->fetchAll();

// Get response data for all responses
$response_data = [];
foreach ($responses as $response) {
    $stmt = $pdo->prepare(query: "
        SELECT rd.*, sf.field_name, sf.field_label
        FROM response_data rd
        JOIN survey_fields sf ON rd.field_id = sf.id
        WHERE rd.response_id = ?
    ");
    $stmt->execute(params: [$response['id']]);
    $data = $stmt->fetchAll();
    
    $response_data[$response['id']] = $data;
}

// Handle different export types
switch ($type) {
    case 'csv':
        exportCSV(survey: $survey, fields: $fields, responses: $responses, response_data: $response_data);
        break;
    case 'excel':
        exportExcel(survey: $survey, fields: $fields, responses: $responses, response_data: $response_data);
        break;
    case 'pdf':
        exportPDF(survey: $survey, fields: $fields, responses: $responses, response_data: $response_data);
        break;
    default:
        header(header: "Location: results.php?survey_id=$survey_id");
        exit();
}

function exportCSV($survey, $fields, $responses, $response_data): never {
    header(header: 'Content-Type: text/csv');
    header(header: 'Content-Disposition: attachment; filename="survey_' . $survey['id'] . '_results.csv"');
    
    $output = fopen(filename: 'php://output', mode: 'w');
    
    // CSV header
    $header = ['Response ID', 'Username', 'Email', 'Role', 'Submitted At'];
    foreach ($fields as $field) {
        $header[] = $field['field_label'] . ' (' . $field['field_name'] . ')';
    }
    fputcsv(stream: $output, fields: $header);
    
    // CSV data
    foreach ($responses as $response) {
        $row = [
            $response['id'],
            $response['username'],
            $response['email'],
            $response['role'],
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
        
        fputcsv(stream: $output, fields: $row);
    }
    
    fclose(stream: $output);
    exit();
}

function exportExcel($survey, $fields, $responses, $response_data): never {
    require_once '../vendor/autoload.php';
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Excel header
    $header = ['Response ID', 'Username', 'Email', 'Role', 'Submitted At'];
    foreach ($fields as $field) {
        $header[] = $field['field_label'] . ' (' . $field['field_name'] . ')';
    }
    $sheet->fromArray($header, null, 'A1');
    
    // Excel data
    $row = 2;
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
    
    // Auto-size columns
    foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Set headers and save
    header(header: 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header(header: 'Content-Disposition: attachment; filename="survey_' . $survey['id'] . '_results.xlsx"');
    
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}

function exportPDF($survey, $fields, $responses, $response_data): never {
    require_once '../vendor/autoload.php';
    
    $mpdf = new \Mpdf\Mpdf();
    
    // PDF content
    $html = '<h1>' . htmlspecialchars($survey['title']) . '</h1>';
    $html .= '<p>' . htmlspecialchars($survey['description']) . '</p>';
    $html .= '<p><strong>Total Responses:</strong> ' . count($responses) . '</p>';
    $html .= '<hr>';
    
    foreach ($responses as $response) {
        $html .= '<h2>Response from ' . htmlspecialchars($response['username']) . '</h2>';
        $html .= '<p><strong>Role:</strong> ' . htmlspecialchars($response['role']) . '</p>';
        $html .= '<p><strong>Submitted:</strong> ' . $response['submitted_at'] . '</p>';
        
        $html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">';
        $html .= '<tr><th width="30%">Question</th><th width="70%">Response</th></tr>';
        
        foreach ($response_data[$response['id']] as $data) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($data['field_label']) . '</td>';
            $html .= '<td>' . nl2br(htmlspecialchars($data['field_value'])) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $html .= '<div style="page-break-after: always;"></div>';
    }
    
    $mpdf->WriteHTML($html);
    
    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="survey_' . $survey['id'] . '_results.pdf"');
    $mpdf->Output('php://output');
    exit();
}