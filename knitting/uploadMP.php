<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration file
include 'config.php';

// Load PhpSpreadsheet classes
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Check if a file was submitted
if ($_FILES['excelFile']['name']) {
    // Temporary file name
    $filename = $_FILES['excelFile']['tmp_name'];

    try {
        // Load the spreadsheet
        $spreadsheet = IOFactory::load($filename);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $recordModified = false;

        for ($row = 2; $row <= $highestRow; $row++) {
            // Get Budat value (column 1 => A)
            $budatValue = $sheet->getCell(Coordinate::stringFromColumnIndex(1) . $row)->getValue();

            if (is_numeric($budatValue)) {
                $budatDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($budatValue)->format('d-m-Y');
            } else {
                echo "Invalid Budat value on row $row: $budatValue<br>";
                continue;
            }

            // LineNo (column 2 => B)
            $lineNoValue = $sheet->getCell(Coordinate::stringFromColumnIndex(2) . $row)->getValue();

            $checkQuery = "SELECT * FROM manpower WHERE Budat = '$budatDate' AND LineNo = '$lineNoValue'";
            $result = mysqli_query($db, $checkQuery);

            $updateQuery = "UPDATE manpower SET ";
            $insertQuery = "INSERT INTO manpower (`Budat`, `LineNo`, `08-09`, `09-10`, `10-11`, `11-12`, `12-13`, `13-14`, `14-15`, `15-16`, `16-17`, `17-18`, `18-19`, `19-20`, `20-21`, `21-22`, `22-23`, `23-24`) VALUES ('$budatDate', '$lineNoValue', ";

            for ($col = 3; $col <= 18; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $columnName = $sheet->getCell($colLetter . '1')->getValue(); // header row
                $cellValue = $sheet->getCell($colLetter . $row)->getCalculatedValue();

                // Escape strings to prevent SQL injection
                if (is_numeric($cellValue)) {
                    $updateQuery .= "`$columnName` = $cellValue, ";
                    $insertQuery .= "$cellValue, ";
                } else {
                    $safeValue = mysqli_real_escape_string($db, $cellValue);
                    $updateQuery .= "`$columnName` = '$safeValue', ";
                    $insertQuery .= "'$safeValue', ";
                }
            }

            // Remove trailing commas
            $updateQuery = rtrim($updateQuery, ', ') . " WHERE Budat = '$budatDate' AND LineNo = '$lineNoValue'";
            $insertQuery = rtrim($insertQuery, ', ') . ")";

            if (mysqli_num_rows($result) > 0) {
                mysqli_query($db, $updateQuery);
                $recordModified = true;
            } else {
                mysqli_query($db, $insertQuery);
                $recordModified = true;
            }
        }

        // Display result message
        if ($recordModified) {
            echo '<script>alert("Manpower upload successful."); window.location.href = "initialPage.php";</script>';
        } else {
            echo '<script>alert("No records were inserted or updated.");</script>';
        }
    } catch (Throwable $e) {
        // Catch any error and display
        echo '<pre>Error: ' . $e->getMessage() . '</pre>';
    }

    // Close connection
    mysqli_close($db);
} else {
    echo '<script>alert("No file selected.");</script>';
}
?>
