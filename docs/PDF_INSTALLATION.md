# PDF Export Installation Guide

## TCPDF Library Installation

The enhanced reports module requires TCPDF library for PDF export functionality. Follow these steps to install it:

### Option 1: Manual Download (Recommended)

1. **Download TCPDF** from: https://github.com/tecnickcom/tcpdf/releases
2. Extract the ZIP file
3. Copy the `tcpdf` folder to: `c:/xampp/htdocs/aksum-rental/libraries/tcpdf/`
4. Ensure the folder structure is:
   ```
   libraries/
   └── tcpdf/
       ├── examples/
       ├── include/
       ├── tcpdf.php
       ├── tcpdf_autoconfig.php
       ├── tcpdf_config.php
       └── tcpdf_parser.php
   ```

### Option 2: Composer (Advanced)

1. Navigate to project root: `cd c:/xampp/htdocs/aksum-rental`
2. Run composer command:
   ```bash
   composer require tecnickcom/tcpdf
   ```
3. The library will be installed in `vendor/tecnickcom/tcpdf/`

### Option 3: Using XAMPP with Composer

1. Open XAMPP Shell
2. Navigate to project: `cd htdocs/aksum-rental`
3. Run: `composer require tecnickcom/tcpdf`

## Configuration Update

After installation, update the reports files to use the correct path:

### For Manual Installation:
```php
require_once '../libraries/tcpdf/tcpdf.php';
```

### For Composer Installation:
```php
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
```

## Testing the Installation

1. Access the employee reports page
2. Try generating any report
3. Click "Export PDF" button
4. If PDF downloads successfully, installation is complete

## Troubleshooting

### Common Issues:
- **"Class TCPDF not found"**: Check file path in require_once statement
- **"Permission denied"**: Ensure XAMPP has write permissions
- **"Blank PDF"**: Check error logs in XAMPP

### Alternative PDF Libraries:
If TCPDF doesn't work, you can use:
- FPDF (lighter, simpler)
- DomPDF (alternative)
- mPDF (feature-rich)

## Security Note

Ensure only authorized employees can access PDF export functionality by checking user authentication in the reports files.
