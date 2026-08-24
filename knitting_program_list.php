<?php
// Permanent redirect from old knitting_program_list.php to knit_card.php
$qs = !empty($_SERVER["QUERY_STRING"]) ? "?" . $_SERVER["QUERY_STRING"] : "";
header("Location: knit_card.php" . $qs, true, 301);
exit();

