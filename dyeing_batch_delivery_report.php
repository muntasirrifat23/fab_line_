<?php
$reportConfig = [
    'title' => 'Dyeing Batch Delivery Report',
    'from' => 'dyeing_batch_delivery t',
    'machine' => "COALESCE(t.DYMCNO, '')",
    'operator' => "COALESCE(t.DOP, '')",
    'operator_id' => "COALESCE(t.DID, '')",
    'process_alias' => 't',
    'order' => 't.BCARD, t.DBDTID',
    'theme_start' => '#065f46',
    'theme_end' => '#10b981'
];
require 'dyeing_report_template.php';
