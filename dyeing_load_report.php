<?php
$reportConfig = [
    'title' => 'Dyeing Load Report',
    'from' => 'dyeing_load t',
    'machine' => "COALESCE(t.MCNO, '')",
    'operator' => "COALESCE(t.DOP, '')",
    'operator_id' => "COALESCE(t.DID, '')",
    'process_alias' => 't',
    'order' => 't.BCARD, t.DLTID',
    'theme_start' => '#3f6212',
    'theme_end' => '#65a30d'
];
require 'dyeing_report_template.php';
