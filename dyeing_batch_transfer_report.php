<?php
$reportConfig = [
    'title' => 'Dyeing Batch Transfer Report',
    'from' => 'dyeing_batch_delivery t',
    'machine' => "COALESCE(t.DYMCNO, '')",
    'operator' => "COALESCE(t.DOP, '')",
    'operator_id' => "COALESCE(t.DID, '')",
    'process_alias' => 't',
    'order' => 't.BCARD, t.DBDTID',
    'theme_start' => '#4c1d95',
    'theme_end' => '#7c3aed'
];
require 'dyeing_report_template.php';
