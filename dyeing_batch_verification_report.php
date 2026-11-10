<?php
$reportConfig = [
    'title' => 'Dyeing Batch Verification Report',
    'from' => 'dyeing_batch_verification t',
    'machine' => "COALESCE(t.DYMCNO, '')",
    'operator' => "COALESCE(t.DOP, '')",
    'operator_id' => "COALESCE(t.DID, '')",
    'process_alias' => 't',
    'order' => 't.BCARD, t.DBVTID',
    'theme_start' => '#155e75',
    'theme_end' => '#0891b2'
];
require 'dyeing_report_template.php';
