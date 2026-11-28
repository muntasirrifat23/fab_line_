<?php
session_start();
require_once 'config.php';
require_once 'dyeing_process_fields.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

$batch = isset($_SESSION['dyeing_batch']) && is_array($_SESSION['dyeing_batch'])
    ? $_SESSION['dyeing_batch']
    : [];
$items = isset($batch['items']) && is_array($batch['items']) ? $batch['items'] : [];
$dyeingMcno = isset($batch['dymcno']) ? trim((string)$batch['dymcno']) : '';
$poNumber = isset($batch['po_number']) ? trim((string)$batch['po_number']) : '';
$color = isset($batch['color']) ? trim((string)$batch['color']) : '';
$selectedProcesses = isset($batch['processes']) && is_array($batch['processes'])
    ? $batch['processes']
    : [];
$processFields = dyeingBatchCardProcessFieldNames();

if (!$items || $poNumber === '' || $color === '' || $dyeingMcno === '' || !$selectedProcesses) {
    echo json_encode(['success' => false, 'message' => 'Select fabric quantities, PO Number, Color, Dyeing MC No and process before creating the batch card.']);
    exit;
}

try {
    ensureDyeingBatchQuantityColumns($db);
    ensureDyeingBatchLineSchema($db);
    ensureDyeingProcessColumns($db, 'dyeing_batch_card');
    ensureDyeingBatchCreatorColumns($db);
    $creator = dyeingBatchCardCreatorDetails(
        $db,
        isset($_SESSION['username']) ? $_SESSION['username'] : '',
        isset($_SESSION['user_type']) ? $_SESSION['user_type'] : ''
    );
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
    exit;
}

$columns = [];
$columnResult = $db->query('SHOW COLUMNS FROM `dyeing_batch_card`');
if (!$columnResult) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not inspect dyeing batch columns: ' . $db->error]);
    exit;
}
while ($column = $columnResult->fetch_assoc()) {
    $columns[strtoupper($column['Field'])] = true;
}
$columnResult->free();

if (!isset($columns['DYMCNO']) && isset($columns['MCNO'])) {
    if (!$db->query("ALTER TABLE `dyeing_batch_card` CHANGE COLUMN `MCNO` `DYMCNO` VARCHAR(100) DEFAULT NULL")) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not migrate dyeing machine column: ' . $db->error]);
        exit;
    }
    $columns['DYMCNO'] = true;
} elseif (!isset($columns['DYMCNO'])) {
    if (!$db->query("ALTER TABLE `dyeing_batch_card` ADD COLUMN `DYMCNO` VARCHAR(100) DEFAULT NULL AFTER `COLOR`")) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not add dyeing machine column: ' . $db->error]);
        exit;
    }
}

foreach (['CATEGORY' => 'VARCHAR(100) DEFAULT NULL', 'YBRAND' => 'VARCHAR(100) DEFAULT NULL'] as $name => $definition) {
    if (isset($columns[$name])) {
        continue;
    }
    if (!$db->query('ALTER TABLE `dyeing_batch_card` ADD COLUMN `' . $name . '` ' . $definition)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not add batch card ' . $name . ' column: ' . $db->error]);
        exit;
    }
}

$nextResult = $db->query(
    'SELECT COALESCE(MAX(CAST(BCARD AS UNSIGNED)), 4000000000) + 1 AS NEXT_CARD FROM `dyeing_batch_card`'
);
if (!$nextResult) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not generate the next batch card number: ' . $db->error]);
    exit;
}
$nextRow = $nextResult->fetch_assoc();
$nextResult->free();
if (!$nextRow || !isset($nextRow['NEXT_CARD'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not generate the next batch card number.']);
    exit;
}

$cardNo = (string)(int)$nextRow['NEXT_CARD'];
$batchDate = date('Y-m-d');
$processValues = [];
foreach ($processFields as $processField) {
    $processValues[$processField] = isset($selectedProcesses[$processField])
        ? (string)$selectedProcesses[$processField]
        : '';
}

$insertSql = "INSERT INTO `dyeing_batch_card`
    (BUDAT, BCARD, PO_NUMBER, KQTY, AQTY, RQTY, BODY_QTY, RIB_QTY, NECK_QTY,
     SONO, SHIFT, BUYER, STYLE, COLOR, DYMCNO, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA,
     FEEDER_PLAN, LOT_NO, YTYPE, YCOUNT, YBRAND, CATEGORY, TPOINT, SILICON, NONSILICON,
     NWASH, EWASH, ENZYME, NONENZYME, PEACH, BRUSH, AOP, SOFTENER, NONSOFTENER, OTHERS,
     SHEARING, SINGEING, HEAT_SET, DID, DNAME)
    VALUES (" . implode(',', array_fill(0, 46, '?')) . ')';
$stmt = $db->prepare($insertSql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not prepare batch card insert: ' . $db->error]);
    exit;
}

$db->begin_transaction();
$inserted = 0;
$allocatedByFabric = [];
$usedCategories = [];
try {
    foreach ($items as $item) {
        $category = isset($item['CATEGORY']) ? strtoupper(trim((string)$item['CATEGORY'])) : '';
        $fabricType = isset($item['FTYPE']) ? trim((string)$item['FTYPE']) : '';
        $fabricKey = strtoupper($fabricType);
        $categoryKey = $fabricKey . '|' . $category;
        $batchQty = isset($item['RQTY']) && is_numeric($item['RQTY']) ? round((float)$item['RQTY'], 3) : 0;
        $sourceQty = isset($item['TOTAL_QTY']) && is_numeric($item['TOTAL_QTY'])
            ? round((float)$item['TOTAL_QTY'], 3)
            : 0;
        $alreadyAllocated = isset($allocatedByFabric[$fabricKey]) ? $allocatedByFabric[$fabricKey] : 0;
        $remainingQty = max(0, round($sourceQty - $alreadyAllocated, 3));
        if (!in_array($category, ['BODY', 'RIB', 'NECK'], true) || $fabricType === '' ||
            $batchQty <= 0 || $sourceQty <= 0 || $batchQty - $remainingQty > 0.000001 ||
            isset($usedCategories[$categoryKey])) {
            throw new RuntimeException('Invalid F/T, Fabric Type or Batch QTY in the selected fabric data.');
        }

        $vPO = $poNumber;
        $vKqty = $sourceQty;
        $vAqty = max(0, round($remainingQty - $batchQty, 3));
        $vRqty = $batchQty;
        $vBodyQty = $category === 'BODY' ? $batchQty : 0;
        $vRibQty = $category === 'RIB' ? $batchQty : 0;
        $vNeckQty = $category === 'NECK' ? $batchQty : 0;
        $vSono = isset($item['SONO']) ? (string)$item['SONO'] : '';
        $vShift = '';
        $vBuyer = isset($item['BUYER']) ? (string)$item['BUYER'] : '';
        $vStyle = isset($item['STYLE']) ? (string)$item['STYLE'] : '';
        $vColor = $color;
        $vMcdia = isset($item['MCDIA']) ? (string)$item['MCDIA'] : '';
        $vCustomer = isset($item['CUSTOMER']) ? (string)$item['CUSTOMER'] : '';
        $vOt = isset($item['O_T']) ? (string)$item['O_T'] : '';
        $vSl = isset($item['SL']) ? (string)$item['SL'] : '';
        $vFtype = $fabricType;
        $vFgsm = isset($item['FGSM']) ? (string)$item['FGSM'] : '';
        $vFdia = isset($item['FDIA']) ? (string)$item['FDIA'] : '';
        $vFeederPlan = isset($item['FEEDER_PLAN']) ? (string)$item['FEEDER_PLAN'] : '';
        $vLotNo = isset($item['LOT_NO']) ? (string)$item['LOT_NO'] : '';
        $vYtype = isset($item['YTYPE']) ? (string)$item['YTYPE'] : '';
        $vYcount = isset($item['YCOUNT']) ? (string)$item['YCOUNT'] : '';
        $vYbrand = isset($item['YBRAND']) ? (string)$item['YBRAND'] : '';
        $vCategory = $category;
        $vTpoint = isset($item['TPOINT']) ? (string)$item['TPOINT'] : '';

        $stmt->bind_param(
            str_repeat('s', 46),
            $batchDate,
            $cardNo,
            $vPO,
            $vKqty,
            $vAqty,
            $vRqty,
            $vBodyQty,
            $vRibQty,
            $vNeckQty,
            $vSono,
            $vShift,
            $vBuyer,
            $vStyle,
            $vColor,
            $dyeingMcno,
            $vMcdia,
            $vCustomer,
            $vOt,
            $vSl,
            $vFtype,
            $vFgsm,
            $vFdia,
            $vFeederPlan,
            $vLotNo,
            $vYtype,
            $vYcount,
            $vYbrand,
            $vCategory,
            $vTpoint,
            $processValues['SILICON'],
            $processValues['NONSILICON'],
            $processValues['NWASH'],
            $processValues['EWASH'],
            $processValues['ENZYME'],
            $processValues['NONENZYME'],
            $processValues['PEACH'],
            $processValues['BRUSH'],
            $processValues['AOP'],
            $processValues['SOFTENER'],
            $processValues['NONSOFTENER'],
            $processValues['OTHERS'],
            $processValues['SHEARING'],
            $processValues['SINGEING'],
            $processValues['HEAT_SET'],
            $creator['DID'],
            $creator['DNAME']
        );
        if (!$stmt->execute()) {
            throw new RuntimeException('Insert error for ' . $category . ' / ' . $fabricType . ': ' . $stmt->error);
        }
        $allocatedByFabric[$fabricKey] = round($alreadyAllocated + $batchQty, 3);
        $usedCategories[$categoryKey] = true;
        $inserted++;
    }

    if (!$db->commit()) {
        throw new RuntimeException('Database commit failed: ' . $db->error);
    }
} catch (Throwable $error) {
    $db->rollback();
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
    exit;
}

$stmt->close();
unset(
    $_SESSION['dyeing_batch']['items'],
    $_SESSION['dyeing_batch']['rolls'],
    $_SESSION['dyeing_batch']['target_qty'],
    $_SESSION['dyeing_batch']['dymcno'],
    $_SESSION['dyeing_batch']['processes'],
    $_SESSION['dyeing_batch']['po_number'],
    $_SESSION['dyeing_batch']['color']
);

echo json_encode([
    'success' => true,
    'card_no' => $cardNo,
    'count' => $inserted,
    'message' => 'Batch Card created with ' . $inserted . ' fabric line(s).'
]);
exit;
?>
