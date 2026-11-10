<?php
// ajaxDyeing_batch_split.php - Split a dyeing batch card into Part A and Part B
session_start();
require_once 'config.php';
require_once 'dyeing_process_fields.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit;
}

if (empty($_SESSION['batch_split_authorized'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Batch Split password verification is required.']);
    exit;
}

try {
    ensureDyeingProcessColumns($db, 'dyeing_batch_card');
    ensureDyeingProcessColumns($db, 'dyeing_batch_delivery');
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not initialize Batch Split process fields: ' . $error->getMessage()]);
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
    $vDYMCNO = isset($row['DYMCNO']) ? $row['DYMCNO'] : null;
    $vMCDIA = isset($row['MCDIA']) ? $row['MCDIA'] : null;
    $vCUSTOMER = isset($row['CUSTOMER']) ? $row['CUSTOMER'] : null;
    $vYTYPE = isset($row['YTYPE']) ? $row['YTYPE'] : null;
    $vYCOUNT = isset($row['YCOUNT']) ? $row['YCOUNT'] : null;
    $vYBRAND = isset($row['YBRAND']) ? $row['YBRAND'] : null;
    $vCATEGORY = isset($row['CATEGORY']) ? $row['CATEGORY'] : null;
    $vO_T = isset($row['O_T']) ? $row['O_T'] : null;
    $vSL = isset($row['SL']) ? $row['SL'] : null;
    $vFTYPE = isset($row['FTYPE']) ? $row['FTYPE'] : null;
    $vFGSM = isset($row['FGSM']) ? $row['FGSM'] : null;
    $vFDIA = isset($row['FDIA']) ? $row['FDIA'] : null;
    $vFEEDER_PLAN = isset($row['FEEDER_PLAN']) ? $row['FEEDER_PLAN'] : null;
    $vLOT_NO = isset($row['LOT_NO']) ? $row['LOT_NO'] : null;
    $vTPOINT = isset($row['TPOINT']) ? $row['TPOINT'] : null;
    $uname = isset($_SESSION['username']) ? $_SESSION['username'] : '';
    $processValues = [];
    foreach (dyeingProcessFieldNames() as $processField) {
        $processValues[$processField] = isset($row[$processField]) ? $row[$processField] : '';
    }

    $stmt = $db->prepare(
        "INSERT INTO dyeing_batch_card
         (BUDAT, BCARD, ROLL, PO_NUMBER, RACK, QTY, SONO, SHIFT, BUYER, STYLE, COLOR,
          DYMCNO, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO,
          YTYPE, YCOUNT, YBRAND, CATEGORY, TPOINT, UNAME, SILICON, NONSILICON,
          NWASH, EWASH, ENZYME, NONENZYME, PEACH, BRUSH, AOP, SOFTENER,
          NONSOFTENER, OTHERS)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                 ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return ['ok' => false, 'error' => 'Database prepare error: ' . $db->error];
    }

    $stmt->bind_param(
        str_repeat('s', 39),
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
        $vDYMCNO,
        $vMCDIA,
        $vCUSTOMER,
        $vO_T,
        $vSL,
        $vFTYPE,
        $vFGSM,
        $vFDIA,
        $vFEEDER_PLAN,
        $vLOT_NO,
        $vYTYPE,
        $vYCOUNT,
        $vYBRAND,
        $vCATEGORY,
        $vTPOINT,
        $uname,
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
        $processValues['OTHERS']
    );

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        return ['ok' => false, 'error' => 'Insert error for roll ' . $rollNo . ': ' . $err];
    }
    $stmt->close();
    return ['ok' => true];
}

function splitFinalVerificationCondition($alias) {
    $passCount = "(COALESCE({$alias}.SINCHARGE = 'PASS', 0) +
                   COALESCE({$alias}.SUPERVISOR = 'PASS', 0) +
                   COALESCE({$alias}.BINCHARGE = 'PASS', 0) +
                   COALESCE({$alias}.BMANAGER = 'PASS', 0))";
    $failCount = "(COALESCE({$alias}.SINCHARGE = 'FAIL', 0) +
                   COALESCE({$alias}.SUPERVISOR = 'FAIL', 0) +
                   COALESCE({$alias}.BINCHARGE = 'FAIL', 0) +
                   COALESCE({$alias}.BMANAGER = 'FAIL', 0))";

    return "(({$passCount} >= 2 AND {$passCount} > {$failCount}) OR
             ({$failCount} >= 2 AND {$failCount} > {$passCount}))";
}

$finalVerificationCondition = splitFinalVerificationCondition('v');

function splitHasFinalVerification($db, $card, $lock = false) {
    global $finalVerificationCondition;

    $sql = "SELECT 1
            FROM dyeing_batch_verification v
            WHERE v.BCARD = ?
              AND {$finalVerificationCondition}
            LIMIT 1";
    if ($lock) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Could not check Batch Verification status: ' . $db->error);
    }
    $stmt->bind_param('s', $card);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Could not check Batch Verification status: ' . $error);
    }
    $result = $stmt->get_result();
    $verified = $result->num_rows > 0;
    $stmt->close();
    return $verified;
}

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

switch ($action) {

    // List all non-split batch cards
    case 'list_cards':
        $result = mysqli_query($db,
            "SELECT d.BCARD, COUNT(*) AS roll_count, ROUND(SUM(CAST(d.QTY AS DECIMAL(12,2))), 2) AS total_qty
             FROM dyeing_batch_delivery d
             WHERE d.BCARD NOT LIKE '%-%'
               AND NOT EXISTS (
                   SELECT 1
                   FROM dyeing_batch_verification v
                   WHERE v.BCARD = d.BCARD
                     AND {$finalVerificationCondition}
               )
             GROUP BY d.BCARD
             ORDER BY CAST(d.BCARD AS UNSIGNED) DESC");
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
        try {
            if (splitHasFinalVerification($db, $card)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Batch card not found or already passed/failed in Batch Verification.'
                ]);
                exit;
            }
        } catch (RuntimeException $error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $error->getMessage()]);
            exit;
        }
        $stmt = $db->prepare(
            "SELECT ROLL, PO_NUMBER, QTY, COLOR, SONO, BUYER, STYLE, MCDIA, CUSTOMER,
                    O_T, SL, FTYPE, FGSM, FDIA, YTYPE, YCOUNT, YBRAND, SILICON,
                    NONSILICON, NWASH, EWASH, ENZYME, NONENZYME, PEACH, BRUSH, AOP,
                    SOFTENER, NONSOFTENER, OTHERS
             FROM dyeing_batch_delivery
             WHERE BCARD = ?
             ORDER BY DBDTID ASC"
        );
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
        $splitTransactionStarted = false;
        try {
        $input = json_decode(file_get_contents('php://input'), true);
        $card = isset($input['card']) ? trim($input['card']) : '';
        $sel = isset($input['rolls_sel']) && is_array($input['rolls_sel']) ? $input['rolls_sel'] : [];

        if ($card === '' || count($sel) === 0) {
            echo json_encode(['success' => false, 'message' => 'Select a batch card and choose rolls to split']);
            exit;
        }

        try {
            if (splitHasFinalVerification($db, $card)) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Batch card not found or already passed/failed in Batch Verification.'
                ]);
                exit;
            }
        } catch (RuntimeException $error) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $error->getMessage()]);
            exit;
        }

        // Load all rows of this card
        $stmt = $db->prepare("SELECT * FROM dyeing_batch_card WHERE BCARD = ? ORDER BY DBCTID ASC");
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
        $chk = mysqli_query($db, "SELECT COUNT(*) AS c FROM dyeing_batch_card WHERE BCARD IN ('" . mysqli_real_escape_string($db, $cardA) . "', '" . mysqli_real_escape_string($db, $cardB) . "')");
        if ($chk) {
            $crow = mysqli_fetch_assoc($chk);
            if ((int)$crow['c'] > 0) {
                echo json_encode(['success' => false, 'message' => 'This card was already split (' . $cardA . ' / ' . $cardB . ').']);
                exit;
            }
        }
        $deliveryCheck = $db->prepare("SELECT COUNT(*) AS c FROM dyeing_batch_delivery WHERE BCARD IN (?, ?)");
        if (!$deliveryCheck) {
            echo json_encode(['success' => false, 'message' => 'Could not verify delivery cards: ' . $db->error]);
            exit;
        }
        $deliveryCheck->bind_param('ss', $cardA, $cardB);
        if (!$deliveryCheck->execute()) {
            $error = $deliveryCheck->error;
            $deliveryCheck->close();
            echo json_encode(['success' => false, 'message' => 'Could not verify delivery cards: ' . $error]);
            exit;
        }
        $deliveryCheckResult = $deliveryCheck->get_result();
        $deliveryCheckRow = $deliveryCheckResult->fetch_assoc();
        $deliveryCheck->close();
        if ((int)$deliveryCheckRow['c'] > 0) {
            echo json_encode(['success' => false, 'message' => 'This card was already split (' . $cardA . ' / ' . $cardB . ').']);
            exit;
        }

        $budat = date('Y-m-d');

        $db->autocommit(false);
        $splitTransactionStarted = true;
        $ok = true;
        $errorMsg = '';

        try {
            if (splitHasFinalVerification($db, $card, true)) {
                $db->rollback();
                $db->autocommit(true);
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Batch card not found or already passed/failed in Batch Verification.'
                ]);
                exit;
            }
        } catch (RuntimeException $error) {
            $db->rollback();
            $db->autocommit(true);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $error->getMessage()]);
            exit;
        }

        // Part A: keep each selected roll with its own ROLL number
        foreach ($selRolls as $row) {
            $res = insertSplitRow($db, $budat, $cardA, strval($row['ROLL']), $row);
            if (!$res['ok']) {
                $ok = false;
                $errorMsg = $res['error'];
                break;
            }
        }

        // Part B: keep each remaining roll as its own row under card-B (same data, only BCARD changes)
        if ($ok) {
            foreach ($remRolls as $row) {
                $res = insertSplitRow($db, $budat, $cardB, strval($row['ROLL']), $row);
                if (!$res['ok']) {
                    $ok = false;
                    $errorMsg = $res['error'];
                    break;
                }
            }
        }

        // Remove the original card
        if ($ok) {
            $dl = $db->prepare("DELETE FROM dyeing_batch_card WHERE BCARD = ?");
            $dl->bind_param('s', $card);
            if (!$dl->execute()) {
                $ok = false;
                $errorMsg = 'Delete original card error: ' . $dl->error;
            }
            $dl->close();
        }

        // Copy delivery rows to the split cards before removing the original rows.
        if ($ok) {
            $deliveryInsert = $db->prepare(
                "INSERT INTO dyeing_batch_delivery
                 (BUDAT, BCARD, ROLL, PO_NUMBER, QTY, SONO, BUYER, STYLE, COLOR, DYMCNO,
                  MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA, FEEDER_PLAN, LOT_NO,
                  YTYPE, YCOUNT, YBRAND, CATEGORY, DOP, DID, SILICON, NONSILICON,
                  NWASH, EWASH, ENZYME, NONENZYME, PEACH, BRUSH, AOP, SOFTENER,
                  NONSOFTENER, OTHERS)
                 SELECT BUDAT, ?, ROLL, PO_NUMBER, QTY, SONO, BUYER, STYLE, COLOR,
                        DYMCNO, MCDIA, CUSTOMER, O_T, SL, FTYPE, FGSM, FDIA,
                        FEEDER_PLAN, LOT_NO, YTYPE, YCOUNT, YBRAND, CATEGORY, DOP,
                        DID, SILICON, NONSILICON, NWASH, EWASH, ENZYME, NONENZYME,
                        PEACH, BRUSH, AOP, SOFTENER, NONSOFTENER, OTHERS
                 FROM dyeing_batch_delivery
                 WHERE BCARD = ? AND ROLL = ?"
            );
            if (!$deliveryInsert) {
                $ok = false;
                $errorMsg = 'Delivery row insert prepare error: ' . $db->error;
            } else {
                $deliveryCard = '';
                $deliveryRoll = '';
                $deliveryInsert->bind_param('sss', $deliveryCard, $card, $deliveryRoll);
                $expectedDeliveryRows = count($selRolls) + count($remRolls);
                $insertedDeliveryRows = 0;
                foreach ([[$selRolls, $cardA], [$remRolls, $cardB]] as $group) {
                    foreach ($group[0] as $row) {
                        $deliveryCard = $group[1];
                        $deliveryRoll = strval($row['ROLL']);
                        if (!$deliveryInsert->execute()) {
                            $ok = false;
                            $errorMsg = 'Delivery row insert error for roll ' . $deliveryRoll . ': ' . $deliveryInsert->error;
                            break 2;
                        }
                        if ($deliveryInsert->affected_rows !== 1) {
                            $ok = false;
                            $errorMsg = 'Delivery row was not found for original batch ' . $card . ', roll ' . $deliveryRoll . '.';
                            break 2;
                        }
                        $insertedDeliveryRows += $deliveryInsert->affected_rows;
                    }
                }
                $deliveryInsert->close();

                if ($ok && $insertedDeliveryRows !== $expectedDeliveryRows) {
                    $ok = false;
                    $errorMsg = 'Not all split rolls were inserted into dyeing_batch_delivery.';
                }

                if ($ok) {
                    $deliveryDelete = $db->prepare("DELETE FROM dyeing_batch_delivery WHERE BCARD = ?");
                    if (!$deliveryDelete) {
                        $ok = false;
                        $errorMsg = 'Original delivery row delete prepare error: ' . $db->error;
                    } else {
                        $deliveryDelete->bind_param('s', $card);
                        if (!$deliveryDelete->execute()) {
                            $ok = false;
                            $errorMsg = 'Original delivery row delete error: ' . $deliveryDelete->error;
                        } elseif ($deliveryDelete->affected_rows !== $expectedDeliveryRows) {
                            $ok = false;
                            $errorMsg = 'Original delivery rows changed during split; no changes were saved.';
                        }
                        $deliveryDelete->close();
                    }
                }
            }
        }

        if ($ok) {
            if (!$db->commit()) {
                $ok = false;
                $errorMsg = 'Could not commit the batch split: ' . $db->error;
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Batch card ' . $card . ' split into ' . $cardA . ' (' . count($selRolls) . ' roll(s), qty ' . number_format($selSum, 2) . ') and ' . $cardB . ' (' . count($remRolls) . ' roll(s), qty ' . number_format($remSum, 2) . ').'
                ]);
            }
        }

        if (!$ok) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $errorMsg !== '' ? $errorMsg : 'Database error during split']);
        }
        $db->autocommit(true);
        $splitTransactionStarted = false;
        } catch (Throwable $error) {
            if ($splitTransactionStarted) {
                $db->rollback();
                $db->autocommit(true);
            }
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Batch split failed: ' . $error->getMessage()
            ]);
        }
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
        exit;
}
?>