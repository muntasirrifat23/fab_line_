<?php

function dyeingProcessFieldNames()
{
    return [
        'SILICON', 'NONSILICON', 'NWASH', 'EWASH', 'ENZYME', 'NONENZYME',
        'PEACH', 'BRUSH', 'AOP', 'SOFTENER', 'NONSOFTENER', 'OTHERS'
    ];
}

function ensureDyeingCountColumn($db, $table)
{
    $allowedTables = [
        'dyeing_batch_verification',
        'dyeing_roll_verification',
        'dyeing_load',
        'dyeing_unload'
    ];
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Unsupported dyeing count table.');
    }

    $result = $db->query('SHOW COLUMNS FROM `' . $table . '` LIKE \'DCOUNT\'');
    if (!$result) {
        throw new RuntimeException('Could not inspect ' . $table . ' DCOUNT column: ' . $db->error);
    }
    $exists = $result->num_rows > 0;
    $result->free();
    if (!$exists && !$db->query(
        'ALTER TABLE `' . $table . '` ADD COLUMN `DCOUNT` INT NOT NULL DEFAULT 1'
    )) {
        throw new RuntimeException('Could not add DCOUNT to ' . $table . ': ' . $db->error);
    }
}

function ensureDyeingProcessColumns($db, $table)
{
    $allowedTables = [
        'dyeing_batch_card',
        'dyeing_batch_delivery',
        'dyeing_batch_verification',
        'dyeing_load',
        'dyeing_unload'
    ];
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Unsupported dyeing process table.');
    }

    $columnsResult = $db->query('SHOW COLUMNS FROM `' . $table . '`');
    if (!$columnsResult) {
        throw new RuntimeException('Could not inspect ' . $table . ' columns: ' . $db->error);
    }
    $columns = [];
    while ($column = $columnsResult->fetch_assoc()) {
        $columns[] = strtoupper($column['Field']);
    }
    $columnsResult->free();

    $addedFields = [];
    foreach (dyeingProcessFieldNames() as $field) {
        if (in_array($field, $columns, true)) {
            continue;
        }
        if (!$db->query(
            'ALTER TABLE `' . $table . '` ADD COLUMN `' . $field . '` VARCHAR(100) DEFAULT NULL'
        )) {
            throw new RuntimeException('Could not add ' . $field . ' to ' . $table . ': ' . $db->error);
        }
        $addedFields[] = $field;
    }

    $sources = [
        'dyeing_batch_delivery' => 'dyeing_batch_card',
        'dyeing_batch_verification' => 'dyeing_batch_delivery',
        'dyeing_load' => 'dyeing_batch_card',
        'dyeing_unload' => 'dyeing_load'
    ];
    if (!$addedFields || !isset($sources[$table])) {
        return;
    }

    $sourceFields = [];
    $assignments = [];
    $emptyChecks = [];
    foreach ($addedFields as $field) {
        $sourceFields[] = 'MAX(`' . $field . '`) AS `' . $field . '`';
        $assignments[] = '`destination`.`' . $field . '` = CASE
            WHEN `destination`.`' . $field . '` IS NULL OR `destination`.`' . $field . '` = \'\'
            THEN `source`.`' . $field . '`
            ELSE `destination`.`' . $field . '`
        END';
        $emptyChecks[] = '(`destination`.`' . $field . '` IS NULL OR `destination`.`' . $field . '` = \'\')';
    }
    $sourceTable = $sources[$table];
    $backfillSql = 'UPDATE `' . $table . '` AS `destination`
        INNER JOIN (
            SELECT `BCARD`, `ROLL`, ' . implode(', ', $sourceFields) . '
            FROM `' . $sourceTable . '`
            GROUP BY `BCARD`, `ROLL`
        ) AS `source`
            ON TRIM(`source`.`BCARD`) = TRIM(`destination`.`BCARD`)
            AND TRIM(`source`.`ROLL`) = TRIM(`destination`.`ROLL`)
        SET ' . implode(', ', $assignments) . '
        WHERE (' . implode(' OR ', $emptyChecks) . ')';
    if (!$db->query($backfillSql)) {
        throw new RuntimeException('Could not backfill dyeing process fields in ' . $table . ': ' . $db->error);
    }
}
