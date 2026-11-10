<?php
$reportConfig = [
    'title' => 'Dyeing Batch Split Report',
    'from' => 'dyeing_batch_delivery t',
    'machine' => "COALESCE(t.DYMCNO, '')",
    'operator' => "COALESCE(t.DOP, '')",
    'operator_id' => "COALESCE(t.DID, '')",
    'process_alias' => 't',
    'where' => "(t.BCARD LIKE '%-A' OR t.BCARD LIKE '%-B')",
    'order' => 't.BCARD, t.DBDTID',
    'theme_start' => '#9a3412',
    'theme_end' => '#f97316'
];
require 'dyeing_report_template.php';
