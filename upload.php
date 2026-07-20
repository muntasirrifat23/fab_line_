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

/* ==========================
   CSV -> Database Map
========================== */
$columnMap = [
    'SUPPLIER'              => 'SUPPLIER',
    'BUYER'                 => 'BUYER',
    'YARN_TYPE'             => 'YARN_TYPE',
    'YARN_COUNT'            => 'YARN_COUNT',
    'FINISH_DIA'            => 'FINISH_DIA',
    'FABRICS_TYPE'          => 'FABRICS_TYPE',
    'BOOKING'               => 'BOOKING',
    'STYLE'                 => 'STYLE',
    'FINISH_GSM'            => 'FINISH_GSM',
    'OPEN_TUBE'             => 'OPEN_TUBE',
    'SONO'                  => 'SONO',
    'SO_ITEM'               => 'SO_ITEM',
    'KNIT_MATERIAL_CODE'    => 'KNIT_MATERIAL_CODE',
    'KNIT_M_DESCRIPTION'    => 'KNIT_M_DESCRIPTION',
    'ORDER_TYPE'            => 'ORDER_TYPE',
    'KNITTING_TARGET_QTY'   => 'KNITTING_TARGET_QTY',
    'FIRST_SHIPMENT_DATE'   => 'FIRST_SHIPMENT_DATE',
    'LAST_SHIPMENT_DATE'    => 'LAST_SHIPMENT_DATE',
    'KNIT_TNA_START'        => 'KNIT_TNA_START',
    'KNIT_TNA_END'          => 'KNIT_TNA_END',
    'LOT_NO'                => 'LOT_NO',
];

/* ==========================
   Prepare Insert - BUDAT (only date) & CBUDAT (with time)
========================== */
$dbColumns = array_values($columnMap);
$dbColumns[] = "BUDAT";   // Add BUDAT (only date)
$dbColumns[] = "CBUDAT";  // Add CBUDAT (with time)

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

/* ==========================
   Import
========================== */
$rowNo = 1;
$success = 0;
$fail = 0;

while (($row = fgetcsv($handle, 1000000, ",")) !== FALSE) {
    $rowNo++;
    
    $values = [];
    $types = "";
    
    // Process each column based on the map
    foreach ($columnMap as $csv => $dbcol) {
        if (isset($H[$csv])) {
            $val = trim($row[$H[$csv]]);
        } else {
            $val = null;
        }
        
        if ($val === '') {
            $val = null;
        }
        
        // Convert dates
        if (in_array($dbcol, [
            'FIRST_SHIPMENT_DATE',
            'LAST_SHIPMENT_DATE',
            'KNIT_TNA_START',
            'KNIT_TNA_END'
        ])) {
            $original = $val;
            $val = parseDate($val);
            
            // Debug output - uncomment to see what's happening
            // echo "Original: " . $original . " -> Converted: " . $val . "<br>";
        }
        
        $values[] = $val;
        $types .= "s";
    }
    
    // Add current date only for BUDAT (without time)
    date_default_timezone_set('Asia/Dhaka');
    $values[] = date('Y-m-d');        // BUDAT - only date (e.g., 2026-07-16)
    $types .= "s";
    
    // Add current datetime for CBUDAT (with time)
    $values[] = date('Y-m-d H:i:s');  // CBUDAT - with time (e.g., 2026-07-16 14:30:25)
    $types .= "s";
    
    // Bind parameters and execute
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        $success++;
        echo "Row " . $rowNo . ": Knitting Input Inserted<br>";
    } else {
        $fail++;
        echo "<span style='color:red'>Row " . $rowNo . " Failed : " . $stmt->error . "</span><br>";
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