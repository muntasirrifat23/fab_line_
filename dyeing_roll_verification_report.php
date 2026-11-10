<?php
$reportConfig = [
    'title' => 'Dyeing Roll Verification Report',
    'from' => 'dyeing_roll_verification t LEFT JOIN dyeing_batch_delivery p ON p.BCARD = t.BCARD AND p.ROLL = t.ROLL',
    'machine' => "COALESCE(t.MCNO, p.DYMCNO, '')",
    'operator' => "COALESCE(t.DOP, '')",
    'operator_id' => "COALESCE(t.DID, '')",
    'process_alias' => 'p',
    'order' => 't.BCARD, t.DRVTID',
    'theme_start' => '#9d174d',
    'theme_end' => '#db2777'
];
require 'dyeing_report_template.php';
