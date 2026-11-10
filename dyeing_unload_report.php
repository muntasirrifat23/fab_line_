<?php
$reportConfig = [
    'title' => 'Dyeing Unload Report',
    'from' => 'dyeing_unload t',
    'machine' => "COALESCE(t.MCNO, '')",
    'operator' => "COALESCE(t.DOP, '')",
    'operator_id' => "COALESCE(t.DID, '')",
    'process_alias' => 't',
    'order' => 't.BCARD, t.DULTID',
    'theme_start' => '#9f1239',
    'theme_end' => '#e11d48'
];
require 'dyeing_report_template.php';
