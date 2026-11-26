<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once 'config.php';

function loadKnittingProgramAllocations($db, $booking)
{
    $stmt = mysqli_prepare(
        $db,
        "SELECT TRIM(COALESCE(KNIT_M_DESCRIPTION, '')) AS DESCRIPTION,
                UPPER(TRIM(COALESCE(COLOR, ''))) AS COLOR,
                IFNULL(SUM(QTY), 0) AS ALLOCATED_QTY
         FROM knitting_program
         WHERE PO_NUMBER = ?
         GROUP BY TRIM(COALESCE(KNIT_M_DESCRIPTION, '')), UPPER(TRIM(COALESCE(COLOR, '')))"
    );
    if (!$stmt) {
        throw new RuntimeException('Could not prepare knitting color allocation lookup: ' . mysqli_error($db));
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $booking) || !mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException('Could not load knitting color allocations: ' . $error);
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new RuntimeException('Could not read knitting color allocations: ' . $error);
    }

    $allocatedByDescription = [];
    $allocatedByDescriptionColor = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $description = trim((string)$row['DESCRIPTION']);
        $descriptionKey = strtoupper($description);
        $color = strtoupper(trim((string)$row['COLOR']));
        $quantity = (float)$row['ALLOCATED_QTY'];
        $allocatedByDescription[$description] = ($allocatedByDescription[$description] ?? 0) + $quantity;
        if ($color !== '') {
            if (!isset($allocatedByDescriptionColor[$descriptionKey])) {
                $allocatedByDescriptionColor[$descriptionKey] = [];
            }
            $allocatedByDescriptionColor[$descriptionKey][$color] =
                ($allocatedByDescriptionColor[$descriptionKey][$color] ?? 0) + $quantity;
        }
    }
    mysqli_free_result($result);
    mysqli_stmt_close($stmt);
    return [$allocatedByDescription, $allocatedByDescriptionColor];
}

if (!$db) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database connection failed'
    ]);
    exit();
}

$search = trim($_GET['program_no'] ?? ($_GET['booking'] ?? ($_GET['search'] ?? ($_GET['po_number'] ?? ($_POST['program_no'] ?? '')))));

if (isset($_GET['action']) && $_GET['action'] === 'finish_gsm_list') {
    $fgRes = mysqli_query($db, "SELECT DISTINCT TRIM(FINISH_GSM) AS FG
                                FROM knitting_input
                                WHERE FINISH_GSM IS NOT NULL AND TRIM(FINISH_GSM) <> ''
                                ORDER BY CAST(TRIM(FINISH_GSM) AS UNSIGNED), TRIM(FINISH_GSM)");
    $fgList = [];
    if ($fgRes) {
        while ($fgRow = mysqli_fetch_assoc($fgRes)) {
            $fgList[] = $fgRow['FG'];
        }
    }
    echo json_encode(['success' => true, 'data' => $fgList]);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'color_list') {
    $colorRes = mysqli_query($db, "SELECT DISTINCT TRIM(COLOR) AS COLOR_VALUE
                                   FROM knitting_input
                                   WHERE COLOR IS NOT NULL AND TRIM(COLOR) <> ''
                                   ORDER BY TRIM(COLOR)");
    $colorList = [];
    if ($colorRes) {
        while ($colorRow = mysqli_fetch_assoc($colorRes)) {
            $colorList[] = $colorRow['COLOR_VALUE'];
        }
    }
    echo json_encode(['success' => true, 'data' => $colorList]);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'fabric_type_list') {
    $fabricTypeSearch = trim((string)($_GET['search'] ?? ''));
    $searchTerm = '%' . $fabricTypeSearch . '%';
    $fabricTypeStmt = mysqli_prepare(
        $db,
        "SELECT DISTINCT TRIM(FABRICS_TYPE) AS FABRIC_TYPE
         FROM knitting_input
         WHERE FABRICS_TYPE IS NOT NULL
           AND TRIM(FABRICS_TYPE) <> ''
           AND UPPER(TRIM(FABRICS_TYPE)) LIKE UPPER(?)
         ORDER BY FABRIC_TYPE"
    );
    if (!$fabricTypeStmt ||
        !mysqli_stmt_bind_param($fabricTypeStmt, 's', $searchTerm) ||
        !mysqli_stmt_execute($fabricTypeStmt)) {
        $error = $fabricTypeStmt ? mysqli_stmt_error($fabricTypeStmt) : mysqli_error($db);
        if ($fabricTypeStmt) mysqli_stmt_close($fabricTypeStmt);
        error_log('Fabric type lookup failed: ' . $error);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Unable to load fabric types.']);
        exit();
    }

    $fabricTypeResult = mysqli_stmt_get_result($fabricTypeStmt);
    if (!$fabricTypeResult) {
        $error = mysqli_stmt_error($fabricTypeStmt);
        mysqli_stmt_close($fabricTypeStmt);
        error_log('Fabric type lookup result failed: ' . $error);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Unable to load fabric types.']);
        exit();
    }

    $fabricTypes = [];
    while ($fabricTypeRow = mysqli_fetch_assoc($fabricTypeResult)) {
        $fabricTypes[] = $fabricTypeRow['FABRIC_TYPE'];
    }
    mysqli_free_result($fabricTypeResult);
    mysqli_stmt_close($fabricTypeStmt);
    echo json_encode(['success' => true, 'data' => $fabricTypes]);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'yarn_material_list') {
    $materialSearch = trim((string)($_GET['search'] ?? ''));
    $materialOffset = max(0, (int)($_GET['offset'] ?? 0));
    $pageSize = 100;
    $searchTerm = '%' . $materialSearch . '%';
    $materialStmt = mysqli_prepare(
        $db,
        "SELECT DISTINCT TRIM(MATERIAL_DESCRIPTION) AS MATERIAL_DESCRIPTION
         FROM yarn_material
         WHERE MATERIAL_DESCRIPTION IS NOT NULL
           AND TRIM(MATERIAL_DESCRIPTION) <> ''
           AND MATERIAL_DESCRIPTION LIKE ?
         ORDER BY MATERIAL_DESCRIPTION
         LIMIT " . ($pageSize + 1) . " OFFSET " . $materialOffset
    );
    if (!$materialStmt ||
        !mysqli_stmt_bind_param($materialStmt, 's', $searchTerm) ||
        !mysqli_stmt_execute($materialStmt)) {
        $error = $materialStmt ? mysqli_stmt_error($materialStmt) : mysqli_error($db);
        if ($materialStmt) mysqli_stmt_close($materialStmt);
        error_log('Yarn material lookup failed: ' . $error);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Unable to load yarn materials.']);
        exit();
    }

    $materialResult = mysqli_stmt_get_result($materialStmt);
    if (!$materialResult) {
        $error = mysqli_stmt_error($materialStmt);
        mysqli_stmt_close($materialStmt);
        error_log('Yarn material lookup result failed: ' . $error);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Unable to load yarn materials.']);
        exit();
    }

    $materials = [];
    while ($materialRow = mysqli_fetch_assoc($materialResult)) {
        $materials[] = $materialRow['MATERIAL_DESCRIPTION'];
    }
    mysqli_free_result($materialResult);
    mysqli_stmt_close($materialStmt);
    $hasMore = count($materials) > $pageSize;
    if ($hasMore) {
        array_pop($materials);
    }
    echo json_encode([
        'success' => true,
        'data' => $materials,
        'has_more' => $hasMore
    ]);
    exit();
}

if ($search === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'PROGRAM_NO or PO/Booking number is required'
    ]);
    exit();
}

$s = mysqli_real_escape_string($db, $search);


$progQuery = "SELECT * FROM knitting_program WHERE PROGRAM_NO = '$s' OR KPTID = '$s' LIMIT 1";
$progRes = mysqli_query($db, $progQuery);

if ($progRes && mysqli_num_rows($progRes) > 0) {
    $progData = mysqli_fetch_assoc($progRes);
    $bookingValue = (string)($progData['PO_NUMBER'] ?? '');
    $bookingVal = mysqli_real_escape_string($db, $bookingValue);
    
    $inputData = [];
    $programInputRows = [];
    if ($bookingVal !== '') {
        $inQ = mysqli_query($db, "SELECT * FROM knitting_input WHERE PO_NUMBER = '$bookingVal' ORDER BY KITID ASC");
        if ($inQ && mysqli_num_rows($inQ) > 0) {
            while ($inputRow = mysqli_fetch_assoc($inQ)) {
                $programInputRows[] = $inputRow;
                if (!$inputData) {
                    $inputData = $inputRow;
                }
            }
            mysqli_free_result($inQ);
        }
    }
    
    $mergedData = array_merge($inputData, array_filter($progData, function($val) { return $val !== null && $val !== ''; }));
    $mergedData['KNITTING_TARGET_QTY'] = $progData['QTY'] ?? ($inputData['QTY'] ?? 0);
    $mergedData['PROGRAM_NO'] = $progData['PROGRAM_NO'];
    $mergedData['PO_NUMBER'] = $progData['PO_NUMBER'];
    $mergedData['BOOKING'] = $progData['PO_NUMBER'];
    
    try {
        list($allocatedByDescription, $allocatedByDescriptionColor) =
            loadKnittingProgramAllocations($db, $bookingValue);
    } catch (Throwable $error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $error->getMessage()]);
        exit();
    }
    $poTotalQty = 0;
    foreach ($programInputRows as $inputRow) {
        $poTotalQty += (float)($inputRow['QTY'] ?? 0);
    }
    echo json_encode([
        'success' => true,
        'data' => $mergedData,
        'all_data' => $programInputRows ?: [$mergedData],
        'descriptions' => [$mergedData['KNIT_M_DESCRIPTION'] ?? ''],
        'allocated_qty' => (float)($progData['QTY'] ?? 0),
        'allocated_by_description' => $allocatedByDescription,
        'allocated_by_description_color' => $allocatedByDescriptionColor,
        'po_total_qty' => $poTotalQty > 0 ? $poTotalQty : (float)($progData['QTY'] ?? ($mergedData['QTY'] ?? 0)),
        'remaining_qty' => 0
    ]);
    exit();
}

$query = "SELECT 
    PO_NUMBER, 
    BUYER, 
    SONO,
    STYLE,
    COLOR,
    CUSTOMER,
    YARN_TYPE,
    FABRICS_TYPE,
    FINISH_GSM, 
    FINISH_DIA, 
    OPEN_TUBE, 
    KNIT_MATERIAL_CODE,
    KNIT_M_DESCRIPTION, 
    QTY,
    BUDAT
FROM knitting_input 
WHERE PO_NUMBER = '$s'";

$result = mysqli_query($db, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Query error: ' . mysqli_error($db)
    ]);
    exit();
}

$allData = [];
$descriptions = [];
$firstRow = null;
$totalPoQty = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $row['BOOKING'] = isset($row['PO_NUMBER']) ? $row['PO_NUMBER'] : '';
    $row['CUSTOMER'] = isset($row['CUSTOMER']) ? $row['CUSTOMER'] : '';
    $row['SONO'] = isset($row['SONO']) ? $row['SONO'] : '';
    $row['SL_VDQ'] = isset($row['SL_VDQ']) ? $row['SL_VDQ'] : '';
    $row['MC_DIA'] = isset($row['MC_DIA']) ? $row['MC_DIA'] : '';
    $row['YARN_COUNT'] = isset($row['YARN_COUNT']) ? $row['YARN_COUNT'] : '';
    $totalPoQty += (float)($row['QTY'] ?? 0);
    
    $allData[] = $row;
    if ($firstRow === null) {
        $firstRow = $row;
    }
    if (!empty($row['KNIT_M_DESCRIPTION'])) {
        $descriptions[] = $row['KNIT_M_DESCRIPTION'];
    }
}

if (empty($allData)) {
    echo json_encode([
        'success' => false, 
        'error' => 'No data found for PO NO: ' . $search
    ]);
    exit();
}

$data = $firstRow;

$response = [
    'success' => true,
    'data' => $data,
    'all_data' => $allData,
    'descriptions' => array_values(array_unique($descriptions))
];

$allocated = 0;
try {
    list($allocatedByDesc, $allocatedByDescColor) =
        loadKnittingProgramAllocations($db, $search);
    $allocated = array_sum($allocatedByDesc);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $error->getMessage()]);
    exit();
}

$response['allocated_qty'] = $allocated;
$response['allocated_by_description'] = $allocatedByDesc;
$response['allocated_by_description_color'] = $allocatedByDescColor;
$poTotalTarget = $totalPoQty > 0 ? $totalPoQty : ((float)($data['KNITTING_TARGET_QTY'] ?? $data['QTY'] ?? 0));
$response['po_total_qty'] = $poTotalTarget;
$response['remaining_qty'] = $poTotalTarget - ($allocatedByDesc[$data['KNIT_M_DESCRIPTION']] ?? 0);

echo json_encode($response);
?>