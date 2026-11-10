<?php
$reportConfig = [
    'title' => 'Dyeing Batch Card Report',
    'from' => 'dyeing_batch_card t',
    'machine' => "COALESCE(t.DYMCNO, '')",
    'operator' => "''",
    'operator_id' => "''",
    'process_alias' => 't',
    'order' => 't.BCARD, t.DBCTID',
    'theme_start' => '#1e3a8a',
    'theme_end' => '#2563eb'
];
require 'dyeing_report_template.php';
