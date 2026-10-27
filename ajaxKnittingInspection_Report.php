<?php
// ajaxKnittingInspection_Report.php - Server-side data provider with multi-parameter filtering & roll suffixing
include 'config.php';

header('Content-Type: application/json');

if (!isset($db) || !$db) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$category  = isset($_GET['category']) ? strtolower(trim($_GET['category'])) : 'all';
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date   = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$shift     = isset($_GET['shift']) ? trim($_GET['shift']) : '';
$qc_grade  = isset($_GET['qc_grade']) ? trim($_GET['qc_grade']) : '';
$buyer     = isset($_GET['buyer']) ? trim($_GET['buyer']) : '';
$mcno      = isset($_GET['mcno']) ? trim($_GET['mcno']) : '';

$whereClauses = [];

if ($search !== '') {
    $s = mysqli_real_escape_string($db, $search);
    $cleanRoll = mysqli_real_escape_string($db, preg_replace('/-(R|P)$/i', '', $search));
    $whereClauses[] = "(
        TRIM(i.ROLL) LIKE '%$s%'
        OR TRIM(i.ROLL) LIKE '%$cleanRoll%'
        OR TRIM(i.PO_NUMBER) LIKE '%$s%'
        OR TRIM(i.SONO) LIKE '%$s%'
        OR TRIM(i.BUYER) LIKE '%$s%'
        OR TRIM(i.STYLE) LIKE '%$s%'
        OR TRIM(i.CUSTOMER) LIKE '%$s%'
    )";
}

// Category filter:
// Rejection: only the split reject roll (-R)
// Pass: the split pass roll (-P) AND every single (not split) roll - a single roll keeps its
//       good (net) qty, the reject qty is just deducted from it.
if ($category === 'pass') {
    $whereClauses[] = "(TRIM(i.ROLL) NOT LIKE '%-R')";
} elseif ($category === 'rejection') {
    $whereClauses[] = "(TRIM(i.ROLL) LIKE '%-R')";
}

if ($from_date !== '') {
    $fd = mysqli_real_escape_string($db, $from_date);
    $whereClauses[] = "i.BUDAT >= '$fd'";
}

if ($to_date !== '') {
    $td = mysqli_real_escape_string($db, $to_date);
    $whereClauses[] = "i.BUDAT <= '$td'";
}

if ($shift !== '' && strtolower($shift) !== 'all') {
    $sh = mysqli_real_escape_string($db, $shift);
    $whereClauses[] = "TRIM(i.SHIFT) = '$sh'";
}

if ($qc_grade !== '' && strtolower($qc_grade) !== 'all') {
    $qcg = mysqli_real_escape_string($db, $qc_grade);
    $whereClauses[] = "TRIM(i.QC_GRADE) = '$qcg'";
}

if ($buyer !== '' && strtolower($buyer) !== 'all') {
    $by = mysqli_real_escape_string($db, $buyer);
    $whereClauses[] = "TRIM(i.BUYER) = '$by'";
}

if ($mcno !== '' && strtolower($mcno) !== 'all') {
    $mc = mysqli_real_escape_string($db, $mcno);
    $whereClauses[] = "TRIM(i.MCNO) = '$mc'";
}

$where = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$sql = "SELECT i.*,
               COALESCE(
                   NULLIF(TRIM(i.YBRAND), ''),
                   (SELECT NULLIF(TRIM(k.YBRAND), '') FROM knit_card k WHERE TRIM(k.PO_NUMBER) = TRIM(i.PO_NUMBER) LIMIT 1),
                   (SELECT NULLIF(TRIM(p.YBRAND), '') FROM knitting_program p WHERE TRIM(p.PO_NUMBER) = TRIM(i.PO_NUMBER) LIMIT 1)
               ) AS YBRAND
        FROM knitting_inspection i
        $where
        ORDER BY i.KITID DESC
        LIMIT 1000";

try {
    $result = mysqli_query($db, $sql);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    mysqli_close($db);
    exit;
}

if (!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => mysqli_error($db)]);
    mysqli_close($db);
    exit;
}

$data = [];
$total_rolls = 0;
$pass_rolls = 0;
$reject_rolls = 0;
$sum_main_qty = 0.0;
$sum_reject_qty = 0.0;
$sum_update_qty = 0.0;

while ($row = mysqli_fetch_assoc($result)) {
    $rawRoll = isset($row['ROLL']) ? trim($row['ROLL']) : '';
    // Strip trailing -R or -P if already present to prevent duplicate appending
    $baseRoll = preg_replace('/-(R|P)$/i', '', $rawRoll);

    $rqty = floatval($row['RQTY'] ?? 0);
    $oqty = floatval($row['OQTY'] ?? ($row['QTY'] ?? 0));
    $uqty = floatval($row['UQTY'] ?? 0);

    // If roll was split into -P or -R, respect the split roll identity.
    // If user chose NOT to split, keep the original roll number as-is.
    if (preg_match('/-R$/i', $rawRoll)) {
        $cat = 'Rejection';
        $displayRoll = $rawRoll;
        $reject_rolls++;
    } elseif (preg_match('/-P$/i', $rawRoll)) {
        $cat = 'Pass';
        $displayRoll = $rawRoll;
        $pass_rolls++;
    } else {
        // Single (not split) roll: always Pass - the net good qty (UQTY) goes forward,
        // the reject qty is only deducted from it.
        $displayRoll = $rawRoll;
        $cat = 'Pass';
        $pass_rolls++;
    }

    $total_rolls++;
    $sum_main_qty += $oqty;
    $sum_reject_qty += $rqty;
    $sum_update_qty += $uqty;

    $row['RAW_ROLL']            = $baseRoll;
    $row['ROLL']                = $displayRoll;
    $row['INSPECTION_CATEGORY'] = $cat;
    $row['MAIN_QTY']            = $row['OQTY'] ?? '';
    $row['REJECT_QTY']          = $row['RQTY'] ?? '';
    $row['UPDATE_QTY']          = $row['UQTY'] ?? '';
    $data[] = $row;
}

echo json_encode([
    'success' => true,
    'count'   => count($data),
    'stats'   => [
        'total_rolls'      => $total_rolls,
        'pass_rolls'       => $pass_rolls,
        'reject_rolls'     => $reject_rolls,
        'total_main_qty'   => round($sum_main_qty, 2),
        'total_reject_qty' => round($sum_reject_qty, 2),
        'total_update_qty' => round($sum_update_qty, 2)
    ],
    'data'    => $data
]);

mysqli_close($db);
?>
