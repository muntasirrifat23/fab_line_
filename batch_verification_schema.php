<?php

function ensureDyeingBatchVerificationDymcnoColumn(mysqli $db)
{
    $stmt = $db->prepare(
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'dyeing_batch_verification'
           AND COLUMN_NAME IN ('MCNO', 'DYMCNO')"
    );
    if (!$stmt || !$stmt->execute()) {
        $error = $stmt ? $stmt->error : $db->error;
        if ($stmt) {
            $stmt->close();
        }
        throw new RuntimeException('Could not inspect batch verification columns: ' . $error);
    }

    $columns = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['COLUMN_NAME'];
    }
    $stmt->close();

    if (in_array('MCNO', $columns, true) && !in_array('DYMCNO', $columns, true)) {
        if (!$db->query(
            'ALTER TABLE dyeing_batch_verification
             CHANGE COLUMN MCNO DYMCNO VARCHAR(100) DEFAULT NULL'
        )) {
            throw new RuntimeException('Could not rename MCNO to DYMCNO: ' . $db->error);
        }
    } elseif (!$columns) {
        $tableResult = $db->query(
            "SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = 'dyeing_batch_verification'
             LIMIT 1"
        );
        if (!$tableResult) {
            throw new RuntimeException('Could not check verification table: ' . $db->error);
        }
        $tableExists = $tableResult->num_rows > 0;
        $tableResult->free();
        if ($tableExists && !$db->query(
            'ALTER TABLE dyeing_batch_verification
             ADD COLUMN DYMCNO VARCHAR(100) DEFAULT NULL'
        )) {
            throw new RuntimeException('Could not add DYMCNO to batch verification: ' . $db->error);
        }
    }
}
