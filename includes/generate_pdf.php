<?php
require_once 'config.php';

class ReportPDF {
    private $title;
    private $data;
    private $type;
    
    public function __construct($title, $data, $type) {
        $this->title = $title;
        $this->data = $data;
        $this->type = $type;
    }
    
    public function generate() {
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $this->title . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Start PDF generation
        echo '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
/Resources <<
/Font <<
/F1 5 0 R
>>
>>
>>
endobj

4 0 obj
<<
/Length ' . (strlen($this->getPDFContent()) + 100) . '
>>
stream
BT
/F1 12 Tf
50 750 Td
(' . $this->escapeString($this->title) . ') Tj
0 -20 Td
(' . date('F j, Y') . ') Tj
0 -40 Td
(Generated from Aksum Rental Management System) Tj
0 -40 Td
';

        // Add data based on report type
        $yPosition = 650;
        foreach ($this->data as $item) {
            if ($yPosition < 100) {
                echo 'ET
endstream
endobj';
                exit;
            }
            
            echo 'BT
/F1 10 Tf
50 ' . $yPosition . ' Td
(' . $this->formatItemForPDF($item) . ') Tj
ET
';
            $yPosition -= 20;
        }
        
        echo 'ET
endstream
endobj

5 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj

xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000264 00000 n 
0000000400 00000 n 
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
500
%%EOF';
    }
    
    private function escapeString($string) {
        return str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $string);
    }
    
    private function formatItemForPDF($item) {
        switch ($this->type) {
            case 'property_listing':
                return $this->escapeString($item['title'] . ' - ' . $item['status'] . ' - $' . $item['monthly_rent']);
            case 'rental_history':
                return $this->escapeString($item['property_title'] . ' - ' . $item['tenant_name'] . ' - ' . $item['rental_status']);
            case 'payments':
                return $this->escapeString($item['property_title'] . ' - ' . $item['tenant_name'] . ' - $' . $item['amount']);
            case 'requests':
                return $this->escapeString($item['property_title'] . ' - ' . $item['tenant_name'] . ' - ' . $item['status']);
            case 'maintenance':
                return $this->escapeString($item['property_title'] . ' - ' . $item['issue_type'] . ' - ' . $item['status']);
            default:
                return $this->escapeString(print_r($item, true));
        }
    }
}

// CSV Generator Class
class ReportCSV {
    private $title;
    private $data;
    private $headers;
    
    public function __construct($title, $data, $headers = []) {
        $this->title = $title;
        $this->data = $data;
        $this->headers = $headers;
    }
    
    public function generate() {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $this->title . '.csv"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Add headers
        if (!empty($this->headers)) {
            fputcsv($output, $this->headers);
        }
        
        // Add data
        foreach ($this->data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
}
?>
