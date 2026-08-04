<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('config.php');

// CSV File
$filepath = "./Knitting.csv";

if (!file_exists($filepath)) {
    die("CSV file not found.");
}

$handle = fopen($filepath, "r");
if (!$handle) {
    die("Unable to open CSV.");
}

/* ==========================
   Read Header
========================== */
$header = fgetcsv($handle, 1000000, ",");
$cleanHeader = [];

foreach ($header as $h) {
    $h = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h);
    $h = strtoupper(trim($h));
    $h = str_replace(
        [' ', '-', '/'],
        '_',
        $h
    );
    $cleanHeader[] = $h;
}

$H = array_flip($cleanHeader);

/* ==========================
   Date Convert - FIXED for DDMMYYYY & DDMYYYY format
========================== */
function parseDate($raw)
{
    $raw = trim($raw);
    if ($raw == '') {
        return null;
    }
    
    // Remove everything after space or !
    $raw = preg_replace('/[ !].*$/', '', $raw);
    
    // Check if it's a date string
    if (preg_match('/^\d{7,8}$/', $raw)) {
        // For 7-digit dates like 1062026 (which is 01-06-2026)
        if (strlen($raw) == 7) {
            // Add leading zero to make it 8 digits
            // Example: 1062026 -> 01062026
            $raw = '0' . $raw;
        }
        
        // Now parse as DDMMYYYY
        $d = substr($raw, 0, 2);
        $m = substr($raw, 2, 2);
        $y = substr($raw, 4, 4);
        
        if (checkdate($m, $d, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }
        
        // If DDMMYYYY fails, try YYYYMMDD
        $y = substr($raw, 0, 4);
        $m = substr($raw, 4, 2);
        $d = substr($raw, 6, 2);
        
        if (checkdate($m, $d, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }
    }
    
    // Try other common formats
    $formats = [
        'd-m-Y',
        'd/m/Y',
        'Y-m-d',
        'Y/m/d',
        'dmY'
    ];
    
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $raw);
        if ($dt !== false) {
            return $dt->format('Y-m-d');
        }
    }
    
    return null;
}


$columnMap = [
    'PO_NUMBER'            => 'PO_NUMBER',
    'SONO'                 => 'SONO',
    'BUYER'                => 'BUYER',
    'STYLE'                => 'STYLE',
    'COLOR'                => 'COLOR',
    'QTY'                  => 'QTY',
    'FINISH_GSM'           => 'FINISH_GSM',
    'FINISH_DIA'           => 'FINISH_DIA',
    'OPEN_TUBE'            => 'OPEN_TUBE',
    'FABRICS_TYPE'         => 'FABRICS_TYPE',
    'YARN_TYPE'            => 'YARN_TYPE',
    'KNIT_MATERIAL_CODE'   => 'KNIT_MATERIAL_CODE',
    'KNIT_M_DESCRIPTION'   => 'KNIT_M_DESCRIPTION'
];


$dbColumns = [
    "PO_NUMBER",
    "SONO",
    "BUYER",
    "STYLE",
    "COLOR",
    "QTY",
    "FINISH_GSM",
    "FINISH_DIA",
    "OPEN_TUBE",
    "FABRICS_TYPE",
    "YARN_TYPE",
    "KNIT_MATERIAL_CODE",
    "KNIT_M_DESCRIPTION",
    "BUDAT",
    "CBUDAT"
];

$sql = "INSERT INTO knitting_input
(
" . implode(",", $dbColumns) . "
)
VALUES
(
" . implode(",", array_fill(0, count($dbColumns), "?")) . "
)";

$stmt = $db->prepare($sql);
if (!$stmt) {
    die($db->error);
}

$rowNo = 1;
$success = 0;
$fail = 0;

while (($row = fgetcsv($handle, 1000000, ",")) !== FALSE) {
    $rowNo++;
    $values = [];
    $types  = "";

    foreach ($columnMap as $csvCol => $dbCol) {
        $value = isset($H[$csvCol]) ? trim($row[$H[$csvCol]]) : null;
        if ($value === '') {
            $value = null;
        }
        $values[] = $value;
        $types .= "s";
    }

    date_default_timezone_set("Asia/Dhaka");

    // BUDAT
    $values[] = date("Y-m-d");
    $types .= "s";

    // CBUDAT
    $values[] = date("Y-m-d H:i:s");
    $types .= "s";

    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {

        $success++;
        echo "Row {$rowNo} inserted<br>";

    } else {

        $fail++;
        echo "<span style='color:red'>
        Row {$rowNo} Failed : {$stmt->error}
        </span><br>";

    }

}

fclose($handle);
$stmt->close();

/* ==========================================================
   DELETE CSV AFTER PROCESSING
========================================================== */
if (file_exists($filepath)) {
    clearstatcache();
    $temp = $filepath . "_" . time() . ".tmp";
    if (@rename($filepath, $temp)) {
        if (@unlink($temp)) {
            echo "<b>CSV deleted successfully.</b><br>";
        } else {
            echo "<b>Could not delete temporary CSV.</b><br>";
        }
    } else {
        echo "<b>Rename failed - CSV still locked.</b><br>";
    }
}

echo "<hr>";
echo "<h2>";
echo "Success : " . $success;
echo "<br>";
echo "Failed : " . $fail;
echo "</h2>";
?>