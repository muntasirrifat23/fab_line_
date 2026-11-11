<?php
session_start();
require_once 'config.php';
require_once 'dyeing_process_fields.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['dyeing_redye_csrf'])) {
    $_SESSION['dyeing_redye_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['dyeing_redye_csrf'];
$rows = [];
$errorMessage = '';

try {
    ensureDyeingCountColumn($db, 'dyeing_unload');
    ensureDyeingCountColumn($db, 'dyeing_batch_verification');

    $result = $db->query(
        "SELECT u.BCARD, u.DCOUNT, COUNT(*) AS ROLL_COUNT,
                GROUP_CONCAT(u.ROLL ORDER BY u.DULTID SEPARATOR ', ') AS ROLLS
         FROM dyeing_unload u
         WHERE u.DCOUNT = (
             SELECT MAX(latest.DCOUNT)
             FROM dyeing_unload latest
             WHERE TRIM(latest.BCARD) = TRIM(u.BCARD)
         )
         GROUP BY u.BCARD, u.DCOUNT
         HAVING u.DCOUNT = (
             SELECT COALESCE(MAX(v.DCOUNT), 0)
             FROM dyeing_batch_verification v
             WHERE TRIM(v.BCARD) = TRIM(u.BCARD)
         )
         ORDER BY MAX(u.DULTID) DESC"
    );
    if (!$result) {
        throw new RuntimeException('Could not load unloaded batches for re-dyeing: ' . $db->error);
    }
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
} catch (Throwable $error) {
    error_log('Dyeing Re Dyeing page error: ' . $error->getMessage());
    $errorMessage = 'Could not load batches ready for re-dyeing. Please try again or contact the administrator.';
    http_response_code(500);
}

function dyeingRedyeEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Re Dyeing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 18px; background: #f1f5f9; color: #1e293b; font-family: 'Segoe UI', Roboto, system-ui, sans-serif; }
        main { max-width: 1280px; margin: 0 auto; }
        header { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: linear-gradient(115deg, #6d28d9, #9333ea); color: #fff; box-shadow: 0 8px 20px #581c8733; }
        h1 { flex: 1; margin: 0; text-align: center; font-size: 1.5rem; }
        .button { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 13px; border: 0; border-radius: 8px; background: #111827; color: #fff; font: inherit; font-weight: 700; text-decoration: none; white-space: nowrap; cursor: pointer; }
        .redye-button { background: #7c3aed; }
        .redye-button:disabled { opacity: .6; cursor: wait; }
        .table-card { overflow: auto; border: 1px solid #dbe3ee; border-radius: 10px; background: #fff; box-shadow: 0 4px 12px #0f172a0a; }
        table { width: 100%; min-width: 650px; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: middle; }
        th { background: #334155; color: #fff; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .rolls { min-width: 220px; white-space: normal; }
        .message { margin: 16px 0; padding: 16px; border-radius: 8px; background: #fff; color: #64748b; font-weight: 700; text-align: center; }
        .error { color: #b91c1c; }
        @media (max-width: 640px) {
            body { padding: 10px; }
            header { flex-wrap: wrap; }
            header h1 { order: -1; flex-basis: 100%; font-size: 1.25rem; }
        }
    </style>
</head>
<body>
    <main>
        <header>
            <a class="button" href="initialPage.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <h1><i class="fa-solid fa-rotate"></i> Re Dyeing</h1>
            <span><?php echo count($rows); ?> batch(es)</span>
        </header>

        <?php if ($errorMessage !== ''): ?>
            <p class="message error"><?php echo dyeingRedyeEscape($errorMessage); ?></p>
        <?php elseif (!$rows): ?>
            <p class="message">No unloaded batches are currently ready for re-dyeing.</p>
        <?php else: ?>
            <div class="table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Batch</th>
                            <th>DCOUNT</th>
                            <th>Roll Count</th>
                            <th>Rolls</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo dyeingRedyeEscape($row['BCARD']); ?></td>
                                <td><?php echo dyeingRedyeEscape($row['DCOUNT']); ?></td>
                                <td><?php echo dyeingRedyeEscape($row['ROLL_COUNT']); ?></td>
                                <td class="rolls"><?php echo dyeingRedyeEscape($row['ROLLS']); ?></td>
                                <td>
                                    <button
                                        class="button redye-button"
                                        type="button"
                                        data-batch="<?php echo dyeingRedyeEscape($row['BCARD']); ?>"
                                    ><i class="fa-solid fa-rotate"></i> Re Dyeing</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <script>
        var csrfToken = <?php echo json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        document.querySelectorAll('.redye-button').forEach(function(button) {
            button.addEventListener('click', function() {
                var batch = button.dataset.batch;
                if (!window.confirm('Send Batch ' + batch + ' back to Batch Verification for re-dyeing?')) return;
                button.disabled = true;
                fetch('ajaxDyeing_redye.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ card_no: batch, csrf_token: csrfToken })
                })
                    .then(function(response) {
                        return response.json().then(function(data) {
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Could not start re-dyeing.');
                            }
                            window.location.href = 'dyeing_batch_verification.php?batch=' +
                                encodeURIComponent(batch) + '&search=1';
                        });
                    })
                    .catch(function(error) {
                        button.disabled = false;
                        window.alert(error.message);
                    });
            });
        });
    </script>
</body>
</html>
