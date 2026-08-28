<?php
// db_admin.php - Database Viewer & Management for knittingdb
require_once 'config.php';

$current_table = isset($_GET['table']) ? trim($_GET['table']) : '';
$action = isset($_GET['action']) ? trim($_GET['action']) : 'view';
$sql_query = isset($_POST['sql_query']) ? trim($_POST['sql_query']) : '';

// Get all tables in database
$tables = array();
$t_res = mysqli_query($db, "SHOW TABLES");
if ($t_res) {
    while ($r = mysqli_fetch_row($t_res)) {
        $tables[] = $r[0];
    }
}

// Get row count per table
$table_counts = array();
foreach ($tables as $t) {
    $c_res = mysqli_query($db, "SELECT COUNT(*) FROM `$t`");
    if ($c_res) {
        $c_row = mysqli_fetch_row($c_res);
        $table_counts[$t] = $c_row[0];
    } else {
        $table_counts[$t] = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>knittingdb - Database Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #f1f5f9; color: #1e293b; display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #94a3b8; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 20px; background: #020617; border-bottom: 1px solid #1e293b; }
        .sidebar-header h2 { font-size: 1.1rem; color: #38bdf8; display: flex; align-items: center; gap: 10px; }
        .sidebar-header p { font-size: 0.8rem; color: #64748b; margin-top: 4px; }
        .table-list { list-style: none; padding: 10px 0; overflow-y: auto; flex: 1; }
        .table-list li a { display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; color: #cbd5e1; text-decoration: none; font-size: 0.9rem; transition: background 0.15s; }
        .table-list li a:hover, .table-list li.active a { background: #1e293b; color: #38bdf8; font-weight: 600; }
        .badge { background: #334155; color: #f8fafc; font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; font-weight: 500; }
        .main-content { flex: 1; padding: 24px; overflow-y: auto; }
        .card { background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 24px; border: 1px solid #e2e8f0; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }
        .card-header h3 { font-size: 1.25rem; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 6px; text-decoration: none; font-size: 0.875rem; cursor: pointer; font-weight: 500; }
        .btn:hover { background: #0369a1; }
        .btn-secondary { background: #64748b; }
        .btn-secondary:hover { background: #475569; }
        .table-container { overflow-x: auto; max-width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th { background: #f8fafc; padding: 10px 14px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
        td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; white-space: nowrap; max-width: 300px; overflow: hidden; text-overflow: ellipsis; }
        tr:hover td { background: #f8fafc; }
        textarea { width: 100%; height: 100px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: monospace; font-size: 0.9rem; margin-bottom: 12px; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 0.9rem; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .tabs { display: flex; gap: 8px; margin-bottom: 16px; }
        .tab { padding: 8px 16px; background: #e2e8f0; border-radius: 6px; text-decoration: none; color: #475569; font-weight: 500; font-size: 0.875rem; }
        .tab.active { background: #0284c7; color: white; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fa-solid fa-database"></i> knittingdb</h2>
        <p>MySQL Database Viewer</p>
    </div>
    <ul class="table-list">
        <?php foreach ($tables as $t): ?>
            <li class="<?= ($current_table === $t) ? 'active' : '' ?>">
                <a href="db_admin.php?table=<?= urlencode($t) ?>">
                    <span><i class="fa-solid fa-table me-2"></i> <?= htmlspecialchars($t) ?></span>
                    <span class="badge"><?= number_format($table_counts[$t]) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="main-content">
    <!-- SQL Query Box -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-terminal"></i> SQL Query Console</h3>
        </div>
        <form method="POST" action="db_admin.php<?= $current_table ? '?table='.urlencode($current_table) : '' ?>">
            <textarea name="sql_query" placeholder="SELECT * FROM users LIMIT 10;"><?= htmlspecialchars($sql_query) ?></textarea>
            <button type="submit" class="btn"><i class="fa-solid fa-play"></i> Execute Query</button>
        </form>
    </div>

    <!-- SQL Query Result -->
    <?php if ($sql_query !== ''): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fa-solid fa-list-check"></i> Query Results</h3>
            </div>
            <?php
            $q_res = mysqli_query($db, $sql_query);
            if (!$q_res) {
                echo '<div class="alert alert-error"><strong>SQL Error:</strong> ' . htmlspecialchars(mysqli_error($db)) . '</div>';
            } elseif (is_bool($q_res)) {
                echo '<div class="alert alert-success">Query executed successfully. Affected rows: ' . mysqli_affected_rows($db) . '</div>';
            } else {
                $fields = mysqli_fetch_fields($q_res);
                $num_rows = mysqli_num_rows($q_res);
                echo '<div class="alert alert-success">Returned ' . $num_rows . ' rows.</div>';
                echo '<div class="table-container"><table><thead><tr>';
                foreach ($fields as $f) {
                    echo '<th>' . htmlspecialchars($f->name) . '</th>';
                }
                echo '</tr></thead><tbody>';
                while ($row = mysqli_fetch_assoc($q_res)) {
                    echo '<tr>';
                    foreach ($row as $val) {
                        echo '<td title="' . htmlspecialchars($val ?? 'NULL') . '">' . htmlspecialchars($val ?? 'NULL') . '</td>';
                    }
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- Table View -->
    <?php if ($current_table !== ''): ?>
        <div class="tabs">
            <a href="db_admin.php?table=<?= urlencode($current_table) ?>&action=view" class="tab <?= ($action === 'view') ? 'active' : '' ?>"><i class="fa-solid fa-table-cells"></i> Browse Data</a>
            <a href="db_admin.php?table=<?= urlencode($current_table) ?>&action=structure" class="tab <?= ($action === 'structure') ? 'active' : '' ?>"><i class="fa-solid fa-sitemap"></i> Structure</a>
        </div>

        <?php if ($action === 'structure'): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-sitemap"></i> Structure of `<?= htmlspecialchars($current_table) ?>`</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Key</th>
                                <th>Default</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $s_res = mysqli_query($db, "DESCRIBE `" . mysqli_real_escape_string($db, $current_table) . "`");
                            while ($s_row = mysqli_fetch_assoc($s_res)):
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s_row['Field']) ?></strong></td>
                                <td><code><?= htmlspecialchars($s_row['Type']) ?></code></td>
                                <td><?= htmlspecialchars($s_row['Null']) ?></td>
                                <td><span class="badge"><?= htmlspecialchars($s_row['Key']) ?></span></td>
                                <td><?= htmlspecialchars($s_row['Default'] ?? 'NULL') ?></td>
                                <td><?= htmlspecialchars($s_row['Extra']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-table"></i> Data in `<?= htmlspecialchars($current_table) ?>`</h3>
                    <span class="badge" style="font-size:0.9rem; padding:6px 12px;"><?= number_format($table_counts[$current_table]) ?> Records</span>
                </div>
                <div class="table-container">
                    <?php
                    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                    $limit = 50;
                    $offset = ($page - 1) * $limit;
                    
                    $d_res = mysqli_query($db, "SELECT * FROM `" . mysqli_real_escape_string($db, $current_table) . "` LIMIT $limit OFFSET $offset");
                    if ($d_res && mysqli_num_rows($d_res) > 0) {
                        $fields = mysqli_fetch_fields($d_res);
                        echo '<table><thead><tr>';
                        foreach ($fields as $f) {
                            echo '<th>' . htmlspecialchars($f->name) . '</th>';
                        }
                        echo '</tr></thead><tbody>';
                        while ($row = mysqli_fetch_assoc($d_res)) {
                            echo '<tr>';
                            foreach ($row as $val) {
                                echo '<td title="' . htmlspecialchars($val ?? 'NULL') . '">' . htmlspecialchars($val ?? 'NULL') . '</td>';
                            }
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p style="padding:20px; text-align:center; color:#64748b;">No records found in table.</p>';
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="card" style="text-align:center; padding:40px 20px;">
            <i class="fa-solid fa-database" style="font-size:3rem; color:#0284c7; margin-bottom:16px;"></i>
            <h2>Welcome to knittingdb Database Manager</h2>
            <p style="color:#64748b; margin-top:8px;">Select a table from the sidebar to view data and structure, or use the SQL Console above to run queries.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
