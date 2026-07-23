<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    echo "<script>alert('You must be logged in'); window.location.href='login.php';</script>";
    exit();
}

$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;

if ($program_id <= 0) {
    header("Location: knitting_program_list.php?error=Invalid+program+ID");
    exit();
}

// Check if card already generated for this program (using real FK: KPTID)
$chk = $db->prepare("SELECT KCID FROM knit_card WHERE KPTID = ? LIMIT 1");
if ($chk) {
    $chk->bind_param("i", $program_id);
    $chk->execute();
    $res_chk = $chk->get_result();
    if ($res_chk && $res_chk->num_rows > 0) {
        $existing_card = $res_chk->fetch_assoc();
        header("Location: knit_card_view.php?id=" . $existing_card['KCID'] . "&msg=Knit+Card+already+exists+for+this+program");
        exit();
    }
    $chk->close();
}

// Fetch program data by KPTID
$stmt = $db->prepare("SELECT * FROM knitting_program WHERE KPTID = ?");
if (!$stmt) {
    header("Location: knitting_program_list.php?error=" . urlencode("Database prepare failed: " . $db->error));
    exit();
}
$stmt->bind_param("i", $program_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows == 0) {
    header("Location: knitting_program_list.php?error=Knitting+Program+not+found");
    exit();
}

$prog = $res->fetch_assoc();
$stmt->close();

// Map program fields → knit_card fields (using real column names)
$card_date          = date('Y-m-d');  // today
$p_kptid            = intval($prog['KPTID']);
$p_mcno             = $prog['MCNO']              ?? '';
$p_finish_dia       = $prog['FINISH_DIA']         ?? '';
$p_finish_gsm       = $prog['FINISH_GSM']         ?? '';
$p_grey_gsm         = $prog['FINISH_GSM']         ?? ''; // no separate grey_gsm in program
$p_open_tube        = $prog['OPEN_TUBE']          ?? 'O';
$p_buyer            = $prog['BUYER']              ?? '';
$p_supplier         = $prog['SUPPLIER']           ?? '';
$p_booking          = $prog['BOOKING']            ?? '';
$p_sono             = $prog['SONO']               ?? '';
$p_style            = $prog['STYLE']              ?? '';
$p_fabrics          = $prog['FABRICS_TYPE']       ?? '';
$p_yarn_type        = $prog['YARN_TYPE']          ?? '';
$p_yarn_count       = $prog['YARN_COUNT']         ?? '';
$p_lot_no           = $prog['LOT_NO']             ?? '';
$p_knit_m_desc      = $prog['KNIT_M_DESCRIPTION'] ?? '';
$p_req_qty          = floatval($prog['QTY']       ?? 0);
$p_sl_vdq           = 0.00; // not in program table
$prepared_by        = $_SESSION['username'] ?? '';
$authorised_by      = '';

// INSERT into knit_card using real column names
$ins = $db->prepare("
    INSERT INTO knit_card (
        KPTID, CARD_DATE, MCNO, FINISH_DIA, FINISH_GSM, GREY_GSM, SL_VDQ,
        OPEN_TUBE, BUYER, SUPPLIER, BOOKING, SONO, STYLE,
        FABRICS_TYPE, YARN_TYPE, YARN_COUNT, LOT_NO, KNIT_M_DESCRIPTION,
        REQ_QTY, PREPARED_BY, AUTHORISED_BY
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$ins) {
    header("Location: knitting_program_list.php?error=" . urlencode("Failed to prepare knit_card insert: " . $db->error));
    exit();
}

$ins->bind_param(
    "isssssdsssssssssssdss",
    $p_kptid,
    $card_date,
    $p_mcno,
    $p_finish_dia,
    $p_finish_gsm,
    $p_grey_gsm,
    $p_sl_vdq,
    $p_open_tube,
    $p_buyer,
    $p_supplier,
    $p_booking,
    $p_sono,
    $p_style,
    $p_fabrics,
    $p_yarn_type,
    $p_yarn_count,
    $p_lot_no,
    $p_knit_m_desc,
    $p_req_qty,
    $prepared_by,
    $authorised_by
);

if ($ins->execute()) {
    $new_kcid = $ins->insert_id;
    $ins->close();

    // Set CARD_GENERATED = 1 on the source program
    $upd = $db->prepare("UPDATE knitting_program SET CARD_GENERATED = 1 WHERE KPTID = ?");
    if ($upd) {
        $upd->bind_param("i", $program_id);
        $upd->execute();
        $upd->close();
    }

    header("Location: knit_card_view.php?id=" . $new_kcid . "&msg=Knit+Card+generated+successfully!");
    exit();
} else {
    header("Location: knitting_program_list.php?error=" . urlencode("Failed to generate Knit Card: " . ($ins->error ?: $db->error)));
    exit();
}
?>
