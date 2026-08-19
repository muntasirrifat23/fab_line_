<?php
// ajaxDyeing_batch_split.php - Split a dyeing batch card into Part A and Part B
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

function insertSplitRow($db, $budat, $bcmtid, $rollNo, $row) {
    $vPO_NUMBER = isset($row['PO_NUMBER']) ? $row['PO_NUMBER'] : null;
    $vRACK = isset($row['RACK']) ? $row['RACK'] : null;
    $vQTY = isset($row['QTY']) ? $row['QTY'] : null;
    $vSONO = isset($row['SONO']) ? $row['SONO'] : null;
    $vSHIFT = isset($row['SHIFT']) ? $row['SHIFT'] : null;
    $vBUYER = isset($row['BUYER']) ? $row['BUYER'] : null;
    $vSTYLE = isset($row['STYLE']) ? $row['STYLE'] : null;
    $vCOLOR = isset($row['COLOR']) ? $row['COLOR'] : null;
    $vMCNO = isset($row['MCNO']) ? $row['MCNO'] : null;
    $vMCDIA = isset($row['MCDIA']) ? $row['MCDIA'] : null;
    $vSUPPLIER = isset($row['SUPPLIER']) ? $row['SUPPLIER'] : null;
    $vYTYPE = isset($row['YTYPE']) ? $row['YTYPE'] : null;
    $vYCOUNT = isset($row['YCOUNT']) ? $row['YCOUNT'] : null;
    $vO_T = isset($row['O_T']) ? $row['O_T'] : null;
    $vSL = isset($row['SL']) ? $row['SL'] : null;
    $vFTYPE = isset($row['FTYPE']) ? $row['FTYPE'] : null;
    $vFGSM = isset($row['FGSM']) ? $row['FGSM'] : null;
    $vFDIA = isset($row['FDIA']) ? $row['FDIA'] : null;
    $vGGSM = isset($row['GGSM']) ? $row['GGSM'] : null;
    $vFEEDER_PLAN = isset($row['FEEDER_PLAN']) ? $row['FEEDER_PLAN'] : null;
    $vLOT_NO = isset($row['LOT_NO']) ? $row['LOT_NO'] : null;
    $vTPOINT = isset($row['TPOINT']) ? $row['TPOINT'] : null;
    $vMCODE = isset($row['MCODE']) ? $row['MCODE'] : null;
    $vMDESCRIPTION = isset($row['MDESCRIPTION']) ? $row['MDESCRIPTION'] : null;
    $uname = isset($_SESSION['username']) ? $_SESSION['username'] : '';

    $stmt = $db->prepare(
        "INSERT INTO dyeing_batch_card
         (BUDAT, BCMTID, ROLL, PO_NUMBER, RACK, QTY, SONO, SHIFT, BUYER, STYLE, COLOR,
          MCNO, MCDIA, SUPPLIER, YTYPE, YCOUNT, O_T, SL, FTYPE, FGSM, FDIA, GGSM,
          FEEDER_PLAN, LOT_NO, TPOINT, MCODE, MDESCRIPTION, UNAME)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return ['ok' => false, 'error' => 'Database prepare error: ' . $db->error];
    }

    $stmt->bind_param(
        'ssssssssssssssssssssssssssss',
        $budat,
        $bcmtid,
        $rollNo,
        $vPO_NUMBER,
        $vRACK,
        $vQTY,
        $vSONO,
        $vSHIFT,
        $vBUYER,
        $vSTYLE,
        $vCOLOR,
        $vMCNO,
        $vMCDIA,
        $vSUPPLIER,
        $vYTYPE,
        $vYCOUNT,
        $vO_T,
        $vSL,
        $vFTYPE,
        $vFGSM,
        $vFDIA,
        $vGGSM,
        $vFEEDER_PLAN,
        $vLOT_NO,
        $vTPOINT,
        $vMCODE,
        $vMDESCRIPTION,
        $uname
    );

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['ok' => false, 'error' => 'Insert error for roll ' . $rollNo . ': ' . $err];
    }
    $stmt->close();
    return ['ok' => true];
}

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

switch ($action) {

    // List all non-split batch cards
    case 'list_cards':
        $result = mysqli_query($db,
            "SELECT BCMTID, COUNT(*) AS roll_count, ROUND(SUM(CAST(QTY AS DECIMAL(12,2))), 2) AS total_qty
             FROM dyeing_batch_card
             WHERE BCMTID NOT LIKE '%-%'
             GROUP BY BCMTID
             ORDER BY CAST(BCMTID AS UNSIGNED) DESC");
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db)]);
            exit;
        }
        $cards = [];
        while ($x = mysqli_fetch_assoc($result)) {
            $cards[] = $x;
        }
        echo json_encode(['success' => true, 'cards' => $cards]);
        exit;

    // Get one card's rolls
    case 'get_card':
        $card = isset($_GET['card']) ? trim($_GET['card']) : '';
        if ($card === '') {
            echo json_encode(['success' => false, 'message' => 'Card number is required']);
            exit;
        }
        $stmt = $db->prepare("SELECT * FROM dyeing_batch_card WHERE BCMTID = ? ORDER BY DBCTID ASC");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
            exit;
        }
        $stmt->bind_param('s', $card);
        $stmt->execute();
        $result = $stmt->get_result();
        $rolls = [];
        $total = 0;
        while ($row = $result->fetch_assoc()) {
            $rolls[] = $row;
            $total += floatval($row['QTY']);
        }
        echo json_encode([
            'success' => true,
            'card' => $card,
            'rolls' => $rolls,
            'total' => round($total, 2)
        ]);
        exit;

    // Perform the split
    case 'split':
        $input = json_decode(file_get_contents('php://input'), true);
        $card = isset($input['card']) ? trim($input['card']) : '';
        $sel = isset($input['rolls_sel']) && is_array($input['rolls_sel']) ? $input['rolls_sel'] : [];

        if ($card === '' || count($sel) === 0) {
            echo json_encode(['success' => false, 'message' => 'Select a batch card and choose rolls to split']);
            exit;
        }

        // Load all rows of this card
        $stmt = $db->prepare("SELECT * FROM dyeing_batch_card WHERE BCMTID = ? ORDER BY DBCTID ASC");
        $stmt->bind_param('s', $card);
        $stmt->execute();
        $result = $stmt->get_result();
        $rolls = [];
        while ($row = $result->fetch_assoc()) {
            $rolls[] = $row;
        }
        $stmt->close();

        if (count($rolls) === 0) {
            echo json_encode(['success' => false, 'message' => 'Batch card not found']);
            exit;
        }

        // Split into selected and remaining
        $selSet = array_fill_keys(array_map('strval', $sel), true);
        $selRolls = [];
        $remRolls = [];
        foreach ($rolls as $row) {
            if (isset($selSet[strval($row['ROLL'])])) {
                $selRolls[] = $row;
            } else {
                $remRolls[] = $row;
            }
        }

        if (count($selRolls) === 0) {
            echo json_encode(['success' => false, 'message' => 'No selected roll matched this card']);
            exit;
        }
        if (count($remRolls) === 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot split: all rolls of the card are selected. Keep at least one roll for part B.']);
            exit;
        }

        $selSum = 0;
        $remSum = 0;
        foreach ($selRolls as $r) { $selSum += floatval($r['QTY']); }
        foreach ($remRolls as $r) { $remSum += floatval($r['QTY']); }
        $selSum = round($selSum, 2);
        $remSum = round($remSum, 2);

        $cardA = $card . '-A';
        $cardB = $card . '-B';

        // Refuse if this card was already split
        $chk = mysqli_query($db, "SELECT COUNT(*) AS c FROM dyeing_batch_card WHERE BCMTID IN ('" . mysqli_real_escape_string($db, $cardA) . "', '" . mysqli_real_escape_string($db, $cardB) . "')");
        if ($chk) {
            $crow = mysqli_fetch_assoc($chk);
            if ((int)$crow['c'] > 0) {
                echo json_encode(['success' => false, 'message' => 'This card was already split (' . $cardA . ' / ' . $cardB . ').']);
                exit;
            }
        }

        $budat = date('Y-m-d');

        $db->autocommit(false);
        $ok = true;
        $errorMsg = '';

        // Part A: keep each selected roll with its own ROLL number
        foreach ($selRolls as $row) {
            $res = insertSplitRow($db, $budat, $cardA, strval($row['ROLL']), $row);
            if (!$res['ok']) {
                $ok = false;
                $errorMsg = $res['error'];
                break;
            }
        }

        // Part B: merge remaining rolls into one roll
        if ($ok) {
            $bRow = $remRolls[0];
            $bRow['QTY'] = (string)$remSum;
            $res = insertSplitRow($db, $budat, $cardB, $cardB, $bRow);
            if (!$res['ok']) {
                $ok = false;
                $errorMsg = $res['error'];
            }
        }

        // Remove the original card
        if ($ok) {
            $dl = $db->prepare("DELETE FROM dyeing_batch_card WHERE BCMTID = ?");
            $dl->bind_param('s', $card);
            if (!$dl->execute()) {
                $ok = false;
                $errorMsg = 'Delete original card error: ' . $dl->error;
            }
            $dl->close();
        }

        if ($ok) {
            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Batch card ' . $card . ' split into ' . $cardA . ' (' . count($selRolls) . ' roll(s), qty ' . number_format($selSum, 2) . ') and ' . $cardB . ' (1 roll, qty ' . number_format($remSum, 2) . ').'
            ]);
        } else {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $errorMsg !== '' ? $errorMsg : 'Database error during split']);
        }
        $db->autocommit(true);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
        exit;
}
?>