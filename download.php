<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/app/services/LegislativeContentFormatter.php';

function sanitizeDocxFileName(string $title): string
{
    $safe = preg_replace('/[^A-Za-z0-9\-_]+/', '-', trim($title));
    $safe = trim((string)$safe, '-');
    return $safe !== '' ? $safe : 'legislative-bill';
}

function detectDocxTitle(string $content, string $fallback = ''): string
{
    $plainText = html_entity_decode(strip_tags(preg_replace('/<\/(p|h[1-6]|br)>/i', "\n", $content)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $lines = preg_split('/\R+/', $plainText) ?: [];
    foreach ($lines as $line) {
        $line = trim(preg_replace('/\s+/', ' ', $line));
        if ($line === '' || preg_match('/^(LEGISLATIVE RECORD|BILL CONTENT|={3,}|-{3,})$/i', $line)) {
            continue;
        }
        if (preg_match('/^title\s*:\s*(.+)$/i', $line, $match)) {
            return trim($match[1]);
        }
        if (preg_match('/^(AN|A)\s+(ACT|BILL|ORDINANCE|RESOLUTION)\b/i', $line)) {
            return substr($line, 0, 255);
        }
        if (strlen($line) >= 3 && strlen($line) <= 255 && !preg_match('/^(TYPE|STATUS|REFERENCE|DESCRIPTION|CREATED|UPDATED|SECTION\s+\d+)/i', $line)) {
            return $line;
        }
    }
    return trim($fallback);
}

function escapeXml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function buildSignatureBlockXml(array $signature): string
{
    $escape = static fn($value): string => htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $signedTimestamp = strtotime((string)($signature['created_at'] ?? ''));
    $signedDate = $signedTimestamp === false ? (string)($signature['created_at'] ?? '') : date('F j, Y', $signedTimestamp);
    $details = [
        'Digitally Signed:' => $signedDate,
        'Document ID:' => (string)($signature['document_id_display'] ?? ''),
        'Version:' => (string)($signature['version_number'] ?? ''),
        'Signature Algorithm:' => (string)($signature['algorithm'] ?? ''),
        'SHA-256:' => (string)($signature['document_hash'] ?? ''),
        'Digital Signature:' => !empty($signature['verified']) ? 'VALID' : 'INVALID',
    ];
    $detailParagraphs = '';
    foreach ($details as $label => $value) {
        $detailParagraphs .= '<w:p><w:pPr><w:tabs><w:tab w:val="left" w:pos="2100"/></w:tabs><w:spacing w:after="30"/></w:pPr>'
            . '<w:r><w:rPr><w:b/></w:rPr><w:t>' . $escape($label) . '</w:t></w:r><w:r><w:tab/><w:t>' . $escape($value) . '</w:t></w:r></w:p>';
    }
    $signerName = $escape($signature['signer_name'] ?? 'Authorized signer');
    $signerRole = $escape(ucfirst((string)($signature['signer_role'] ?? 'Authorized signer')));
    $verificationUrl = $escape($signature['verification_url'] ?? '');

    return '<w:p><w:pPr><w:spacing w:after="80"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="28"/></w:rPr><w:t>CERTIFIED CORRECT:</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:spacing w:before="80" w:after="20"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Segoe Script" w:hAnsi="Segoe Script"/><w:i/><w:sz w:val="34"/><w:color w:val="17366E"/></w:rPr><w:t>' . $signerName . '</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="1" w:color="777777"/></w:pBdr><w:spacing w:after="30"/></w:pPr><w:r><w:t xml:space="preserve"> </w:t></w:r></w:p>'
        . '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>' . $signerName . '</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:spacing w:after="100"/></w:pPr><w:r><w:t>' . $signerRole . '</w:t></w:r></w:p>'
        . '<w:tbl><w:tblPr><w:tblW w:w="9360" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="6" w:color="777777"/><w:left w:val="single" w:sz="6" w:color="777777"/><w:bottom w:val="single" w:sz="6" w:color="777777"/><w:right w:val="single" w:sz="6" w:color="777777"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders></w:tblPr>'
        . '<w:tblGrid><w:gridCol w:w="9360"/></w:tblGrid><w:tr>'
        . '<w:tc><w:tcPr><w:tcW w:w="9360" w:type="dxa"/><w:tcMar><w:top w:w="120" w:type="dxa"/><w:left w:w="180" w:type="dxa"/><w:bottom w:w="120" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tcMar></w:tcPr>' . $detailParagraphs . '</w:tc></w:tr></w:tbl>'
        . '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t>E-Legis Document Verification URL:</w:t></w:r></w:p>'
        . '<w:p><w:r><w:t>' . $verificationUrl . '</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="80"/></w:pPr><w:r><w:rPr><w:i/><w:sz w:val="16"/><w:color w:val="555555"/></w:rPr><w:t>The signature image is a visual representation; authenticity is proven by the PKI digital signature and SHA-256 hash.</w:t></w:r></w:p>';
}

function buildDocxXml(string $content, ?array $signature = null): string
{
    $lines = preg_split('/\r\n|\r|\n/', $content) ?: [''];
    $paragraphs = [];

    foreach ($lines as $line) {
        $paragraphs[] = '<w:p><w:pPr><w:spacing w:after="80"/><w:jc w:val="left"/></w:pPr><w:r><w:t xml:space="preserve">' . escapeXml($line) . '</w:t></w:r></w:p>';
    }

    $signatureBlock = $signature === null ? '' : buildSignatureBlockXml($signature);
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" mc:Ignorable="w14 wp14">
  <w:body>
    <w:sectPr>
    ' . implode("\n", $paragraphs) . '\n' . $signatureBlock . '\n    <w:sectPr>
      <w:pgSz w:w="12240" w:h="15840"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>
    </w:sectPr>
  </w:body>
</w:document>';
}

function createDocxFromText(string $title, string $content, ?array $signature = null): string
{
    $tempPath = tempnam(sys_get_temp_dir(), 'docx_bill_');
    if ($tempPath === false) {
        throw new RuntimeException('Unable to create temporary document file.');
    }

    $docxPath = $tempPath . '.docx';
    rename($tempPath, $docxPath);

    $zip = new ZipArchive();
    if ($zip->open($docxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Unable to create .docx package.');
    }

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>');

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>');

    $zip->addFromString('word/document.xml', buildDocxXml($content, $signature));
    $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Microsoft Office Word</Application>
  <DocSecurity>0</DocSecurity>
  <ScaleCrop>false</ScaleCrop>
  <HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Title</vt:lpstr></vt:variant><vt:variant><vt:i4>1</vt:i4></vt:variant></vt:vector></HeadingPairs>
  <TitlesOfParts><vt:vector size="1" baseType="lpstr"><vt:lpstr>' . escapeXml($title) . '</vt:lpstr></vt:vector></TitlesOfParts>
</Properties>');

    $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>' . escapeXml($title) . '</dc:title>
  <dc:creator>Admin Portal</dc:creator>
  <cp:lastModifiedBy>Admin Portal</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:modified>
</cp:coreProperties>');

    $zip->close();

    return $docxPath;
}

function buildLegislativeExportText(array $bill): string
{
    $lines = [
        'LEGISLATIVE RECORD',
        '==================',
        'Title: ' . ($bill['title'] ?? ''),
        'Type: ' . ($bill['type'] ?? ''),
        'Status: ' . ($bill['status'] ?? ''),
        'Reference: ' . ($bill['ref'] ?? ''),
        'Description: ' . ($bill['desc'] ?? ''),
        'Created by: ' . ($bill['createdByName'] ?? ''),
        'Created at: ' . ($bill['createdAt'] ?? ''),
        'Updated at: ' . ($bill['updatedAt'] ?? ''),
        'Intake classification: ' . ($bill['intake_classification'] ?? ''),
        'Routing status: ' . ($bill['routing_status'] ?? ''),
        'Stream type: ' . ($bill['stream_type'] ?? ''),
        'Assigned committee: ' . ($bill['assigned_committee'] ?? ''),
        'Date referred: ' . ($bill['date_referred'] ?? ''),
        'Committee report status: ' . ($bill['committee_report_status'] ?? ''),
        'Hearing date: ' . ($bill['hearing_date'] ?? ''),
        'Control number: ' . ($bill['control_number'] ?? ''),
        'Workflow status: ' . ($bill['workflow_status'] ?? ''),
        'SHA-256: ' . ($bill['documentHash'] ?? ''),
        'File name: ' . ($bill['file_name'] ?? ''),
        'File type: ' . ($bill['file_type'] ?? ''),
        'File revision hash: ' . ($bill['fileRevisionHash'] ?? ''),
        '',
        'BILL CONTENT',
        '============',
        \App\Services\LegislativeContentFormatter::toPlainText((string)($bill['content'] ?? '')),
    ];

    return implode("\n", $lines);
}

if (isset($_GET['docx']) && ($_GET['docx'] === '1' || $_GET['docx'] === 'true')) {
    $title = trim((string)($_GET['title'] ?? 'Legislative Bill'));
    $content = trim((string)($_GET['content'] ?? ''));

    $signatureBlock = null;
    $legislativeId = trim((string)($_GET['legislative_id'] ?? ''));
    if ($legislativeId !== '') {
        require_once __DIR__ . '/app/core/Database.php';
        require_once __DIR__ . '/app/models/AdminModel.php';
        $model = new App\Models\AdminModel();
        $bill = $model->findLegislative($legislativeId);
        if ($bill === null) {
            header('HTTP/1.1 404 Not Found');
            echo 'Legislative record not found.';
            exit;
        }

        $requestedVersion = isset($_GET['version']) ? max(1, (int)$_GET['version']) : null;
        $signature = $requestedVersion !== null
            ? $model->getLegislativeSignatureForVersion($legislativeId, $requestedVersion)
            : $model->getLatestLegislativeSignature($legislativeId);
        if (isset($_GET['version']) && !$signature) {
            header('HTTP/1.1 404 Not Found');
            echo 'Signed version not found.';
            exit;
        }

        if ($signature) {
            $versionNumber = (int)$signature['version_number'];
            $signedVersion = null;
            foreach ($model->getLegislativeVersions($legislativeId) as $candidate) {
                if ((int)$candidate['version_number'] === $versionNumber) {
                    $signedVersion = $candidate;
                    break;
                }
            }
            if (!$signedVersion) {
                header('HTTP/1.1 404 Not Found');
                echo 'Signed version content not found.';
                exit;
            }

            $fileHash = '';
            if (!empty($signedVersion['file_path'])) {
                $signedFile = __DIR__ . '/storage/uploads/' . basename((string)$signedVersion['file_path']);
                $fileHash = is_file($signedFile) ? (string)hash_file('sha256', $signedFile) : '';
            }
            $versionHash = hash('sha256', json_encode([
                'control_number' => $bill['control_number'] ?? '',
                'title' => $signedVersion['title'] ?? '',
                'content' => $signedVersion['content'] ?? '',
                'file_hash' => $fileHash,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $publicKeyPem = (string)($signature['public_key_pem'] ?? '');
            $publicKey = $publicKeyPem !== '' ? openssl_pkey_get_public($publicKeyPem) : false;
            $rawSignature = base64_decode((string)($signature['signature'] ?? ''), true);
            $signatureBlock = $signature;
            $signatureBlock['document_id_display'] = $bill['control_number'] ?? $bill['id'];
            $signatureBlock['verified'] = $publicKey && $rawSignature !== false
                && hash_equals((string)$signature['document_hash'], $versionHash)
                && hash_equals((string)$signature['certificate_fingerprint'], hash('sha256', $publicKeyPem))
                && openssl_verify((string)$signature['document_hash'], $rawSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
            if (!preg_match('/^[A-Za-z0-9.:-]+$/', $host)) {
                $host = (string)($_SERVER['SERVER_NAME'] ?? 'localhost');
            }
            $scriptDirectory = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
            $signatureBlock['verification_url'] = $scheme . '://' . $host . $scriptDirectory . '/index.php?verify='
                . rawurlencode((string)($bill['control_number'] ?? '')) . '&version=' . $versionNumber;
            $bill = array_merge($bill, [
                'title' => $signedVersion['title'] ?? $bill['title'],
                'content' => $signedVersion['content'] ?? '',
                'file_path' => $signedVersion['file_path'] ?? '',
                'documentHash' => $signature['document_hash'],
                'fileRevisionHash' => $fileHash,
                'status' => 'Signed',
            ]);
        }
        $title = trim((string)($bill['title'] ?? $title));
        $content = buildLegislativeExportText($bill);
    } elseif ($content !== '') {
        if ($title === '' || strtolower($title) === 'legislative-bill') {
            $title = detectDocxTitle($content, $title);
        }
        $content = buildLegislativeExportText([
            'title' => $title,
            'type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'ref' => $_GET['ref'] ?? '',
            'desc' => $_GET['desc'] ?? '',
            'content' => $content,
        ]);
    }

    if ($content === '') {
        $content = 'No content provided for this legislative bill preview.';
    }

    try {
        $docxPath = createDocxFromText($title, $content, $signatureBlock);
        $fileName = sanitizeDocxFileName($title) . '.docx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($docxPath));
        readfile($docxPath);
        unlink($docxPath);
        exit;
    } catch (Throwable $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Unable to generate the bill document.';
        exit;
    }
}

if (!isset($_GET['file']) || trim($_GET['file']) === '') {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid file request.';
    exit;
}

$fileName = basename($_GET['file']);
$filePath = __DIR__ . '/storage/uploads/' . $fileName;

if (!file_exists($filePath) || !is_file($filePath)) {
    header('HTTP/1.1 404 Not Found');
    echo 'File not found.';
    exit;
}

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/services/SigningKeyStorage.php';
require_once __DIR__ . '/app/models/AdminModel.php';
try {
    $model = new App\Models\AdminModel();
    $bill = $model->findLegislativeByFilePath($fileName);
    if ($bill) {
        $integrity = $model->verifyLegislativeFileIntegrity($bill, $filePath);
        if (($integrity['status'] ?? '') === 'tampered') {
            http_response_code(409);
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store');
            echo 'Download blocked: this bill attachment failed its SHA-256 integrity check. The bill owner has been notified.';
            exit;
        }
    }
} catch (Throwable $exception) {
    error_log('Bill attachment integrity check failed: ' . $exception->getMessage());
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Download unavailable because file integrity could not be checked.';
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$contentType = finfo_file($finfo, $filePath) ?: 'application/octet-stream';
finfo_close($finfo);
$inline = isset($_GET['inline']) && ($_GET['inline'] === '1' || $_GET['inline'] === 'true');

header('Content-Type: ' . $contentType);
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurldecode($fileName) . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
