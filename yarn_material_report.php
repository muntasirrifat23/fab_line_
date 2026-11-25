<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

$search = trim($_GET['search'] ?? '');
$rows = [];
$queryError = '';

if (!isset($db) || !($db instanceof mysqli)) {
    $queryError = 'Database connection is unavailable.';
} else {
    $sql = 'SELECT BUDAT, MATERIAL_NO, MATERIAL_DESCRIPTION FROM yarn_material';
    if ($search !== '') {
        $sql .= ' WHERE MATERIAL_NO LIKE ? OR MATERIAL_DESCRIPTION LIKE ?';
    }
    $sql .= ' ORDER BY YMTID DESC';

    $statement = $db->prepare($sql);
    if (!$statement) {
        error_log('Yarn materials report prepare failed: ' . $db->error);
        $queryError = 'Unable to load yarn materials.';
    } else {
        if ($search !== '') {
            $searchTerm = '%' . $search . '%';
            $statement->bind_param('ss', $searchTerm, $searchTerm);
        }

        if (!$statement->execute()) {
            error_log('Yarn materials report query failed: ' . $statement->error);
            $queryError = 'Unable to load yarn materials.';
        } else {
            $result = $statement->get_result();
            if (!$result) {
                error_log('Yarn materials report result failed: ' . $statement->error);
                $queryError = 'Unable to load yarn materials.';
            } else {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                $result->free();
            }
        }
        $statement->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report | Yarn Materials</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mycss.css">
    <link rel="stylesheet" href="css/knitting-report-theme.css">

    <style>
        html,
        body {
            min-height: 100%;
        }

        body {
            box-sizing: border-box;
            min-height: 100vh;
            padding: 18px;
            background: #f5f7fa;
        }

        .container-fluid {
            min-height: calc(100vh - 36px);
            display: flex;
            flex-direction: column;
        }

        .knitting-report-header i,
        .yarn-material-search i {
            border: 0 !important;
            border-width: 0 !important;
            padding: 0 !important;
        }

        .knitting-report-header #backBtn {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px;
            padding: 10px 18px !important;
            background: #000 !important;
            border: 2px solid #000 !important;
            border-radius: 8px !important;
            color: #fff !important;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .knitting-report-header #backBtn:hover {
            background: #fff !important;
            border-color: #000 !important;
            color: #000 !important;
        }

        .yarn-material-search,
        .yarn-material-table-panel {
            background: #fff;
            padding: 18px;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(20, 30, 50, 0.06);
        }

        .yarn-material-search {
            margin-bottom: 16px;
        }

        .yarn-material-table-panel {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            margin-bottom: 0;
        }

        .yarn-material-search form {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .yarn-material-search input {
            width: min(680px, 100%);
            min-width: 0;
        }

        .yarn-material-search .btn-search {
            background: #2563eb;
            border: 1px solid #2563eb;
            color: #fff;
            border-radius: 8px;
            min-width: 110px;
        }

        .yarn-material-search .btn-clear {
            background: #6b7280;
            border: 1px solid #6b7280;
            color: #fff;
            border-radius: 8px;
            min-width: 100px;
        }

        .yarn-material-table-wrap {
            flex: 1;
            min-height: 0;
            overflow: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .yarn-material-table {
            margin: 0;
            text-align: center;
            border-radius: 8px;
            overflow: hidden;
        }

        .yarn-material-table th,
        .yarn-material-table td {
            vertical-align: middle;
            text-align: center;
        }

        .yarn-material-table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            white-space: nowrap;
        }

        .yarn-material-table .serial-column {
            width: 90px;
        }

        .record-count {
            margin-bottom: 12px;
            color: #475569;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .container-fluid {
                min-height: calc(100vh - 20px);
            }

            .yarn-material-search,
            .yarn-material-table-panel {
                padding: 12px;
            }

            .yarn-material-search form {
                flex-wrap: wrap;
            }

            .yarn-material-search input {
                flex-basis: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3 knitting-report-header">
            <button type="button" class="back-btn" id="backBtn">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Report
            </button>
            <h1 class="title">Input Yarn Materials Details</h1>
            <div></div>
        </div>

        <section class="yarn-material-search">
            <form method="GET" action="yarn_material_report.php">
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                    placeholder="Enter Material No/ Material Description"
                    aria-label="Search material number or description">
                <button type="submit" class="btn btn-search">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Search
                </button>
                <a class="btn btn-clear d-inline-flex align-items-center justify-content-center"
                    href="yarn_material_report.php">
                    <i class="fa-solid fa-rotate-left me-1"></i> Clear
                </a>
            </form>
        </section>

        <section class="yarn-material-table-panel">
            <?php if ($queryError !== ''): ?>
                <div class="alert alert-danger text-center mb-0">
                    <?php echo htmlspecialchars($queryError, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php else: ?>
                <div class="record-count">
                    Showing <?php echo count($rows); ?> record(s)
                </div>
                <div class="table-responsive yarn-material-table-wrap">
                    <table class="table table-bordered table-striped table-hover table-sm yarn-material-table">
                        <thead class="table-dark">
                            <tr>
                                <th class="serial-column">Serial</th>
                                <th>Date</th>
                                <th>Material No</th>
                                <th>Material Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No Data Found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $index => $row): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars((string)($row['BUDAT'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string)($row['MATERIAL_NO'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars((string)($row['MATERIAL_DESCRIPTION'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        document.getElementById('backBtn').addEventListener('click', function () {
            window.location.href = 'report.php';
        });
        window.addEventListener('pageshow', function () {
            var tableWrap = document.querySelector('.yarn-material-table-wrap');
            if (tableWrap) {
                tableWrap.scrollTop = 0;
            }
        });
    </script>
</body>

</html>
