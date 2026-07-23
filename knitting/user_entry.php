<?php
session_start();
include 'config.php';

$success = "";
$editId = "";
$editValue = "";

// Get current user type – fallback to username if user_type not set
$rawUserType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : (isset($_SESSION['username']) ? $_SESSION['username'] : '');
// Normalize to lowercase for consistent comparison
$urTYP = strtolower(trim($rawUserType));

// Define allowed types (all lowercase)
$allowedTypes = ['admin', 'ppq30', 'ppq34', 'ppq70'];
$isAllowed = in_array($urTYP, $allowedTypes);

// If not allowed, prevent any POST action (submit, update, delete)
if (!$isAllowed && $_SERVER["REQUEST_METHOD"] == "POST") {
    $success = "You are not authorized to perform this action.";
} else {
    // Process form submissions only if allowed
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
        $user_id = strtolower(trim($_POST['user_id']));
        if (!empty($user_id)) {
            $check = $db->prepare("SELECT id FROM date_show_user WHERE LOWER(user_id) = ?");
            if (!$check) {
                $success = "DB prepare error (check): " . $db->error;
            } else {
                $check->bind_param("s", $user_id);
                if (!$check->execute()) {
                    $success = "DB execute error (check): " . $check->error;
                } else {
                    $check->store_result();
                    if ($check->num_rows > 0) {
                        $success = "User already exists.";
                    } else {
                        $stmt = $db->prepare("INSERT INTO date_show_user (user_id) VALUES (?)");
                        if (!$stmt) {
                            $success = "DB prepare error (insert): " . $db->error;
                        } else {
                            $stmt->bind_param("s", $user_id);
                            if (!$stmt->execute()) {
                                $success = "Insert failed: " . $stmt->error;
                            } else {
                                $success = "User entry successfully completed.";
                            }
                            $stmt->close();
                        }
                    }
                }
                $check->close();
            }
        }
    }

    if (isset($_POST['delete'])) {
        $id = intval($_POST['delete_id']);
        $stmt = $db->prepare("DELETE FROM date_show_user WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $success = "User deleted successfully.";
    }

    if (isset($_GET['edit'])) {
        $editId = intval($_GET['edit']);
        $stmt = $db->prepare("SELECT user_id FROM date_show_user WHERE id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $editValue = $row['user_id'];
        }
        $stmt->close();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
        $new_id = intval($_POST['edit_id']);
        $new_user_id = strtolower(trim($_POST['user_id']));
        $stmt = $db->prepare("UPDATE date_show_user SET user_id = ? WHERE id = ?");
        $stmt->bind_param("si", $new_user_id, $new_id);
        $stmt->execute();
        $stmt->close();
        $success = "User ID updated successfully.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>User Entry</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: #eef2f7;
            padding: 30px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            padding: 25px 30px 35px 30px;
        }

        .visible-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .visible-table th {
            background-color: #1976D2 !important;
            color: white !important;
            font-weight: 600;
            padding: 14px 16px;
            font-size: 0.9rem;
            border: none;
            text-align: center !important;
        }

        .visible-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e0e7ed;
            vertical-align: middle;
            text-align: center !important;
        }

        .visible-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .visible-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .visible-table tbody tr:hover {
            background-color: #e3f2fd !important;
            transition: 0.2s;
        }

        .btn-edit,
        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-edit {
            background-color: #2196F3;
            color: white;
        }

        .btn-edit:hover {
            background-color: #0b7dda;
            transform: translateY(-1px);
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
            margin-left: 8px;
        }

        .btn-delete:hover {
            background-color: #d32f2f;
            transform: translateY(-1px);
        }

        .form-box {
            background: #f9fbfd;
            padding: 20px 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            border: 1px solid #dce5ef;
        }

        .input-group {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin-top: 6px;
        }

        .input-group .w3-input {
            flex: 1;
            max-width: 400px;
            margin-bottom: 0 !important;
        }

        .input-group button {
            margin-top: 0;
        }

        .back-wrapper {
            text-align: center;
            margin-top: 35px;
        }

        .back-btn {
            display: inline-block;
            margin-top: 10px;
            margin-bottom: 10px;
            padding: 10px 18px;
            background: black;
            color: white;
            border: 2px solid black;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.25s ease;
        }

        .back-btn:hover {
            background: white;
            color: black;
        }

        .success-msg {
            animation: fadeOut 3s forwards;
        }

        @keyframes fadeOut {
            0% {
                opacity: 1;
            }

            70% {
                opacity: 1;
            }

            100% {
                opacity: 0;
                display: none;
            }
        }

        .total-badge {
            background: #e9ecef;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: normal;
            margin-left: 12px;
        }

        .user-id-badge {
            background: #eef2fa;
            padding: 4px 12px;
            border-radius: 30px;
            display: inline-block;
        }

        .unauthorized-msg {
            text-align: center;
            padding: 40px 20px;
            background: #fff3e0;
            border-radius: 16px;
            color: #e65100;
        }
    </style>
</head>

<body>

    <div class="main-container">
        <h2 style="color: #0d47a1; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-user-pen"></i> User Entry Form
        </h2>

        <?php if (!empty($success)): ?>
            <div class="w3-panel w3-pale-green w3-text-green w3-border w3-border-green w3-round success-msg" id="successMsg">
                <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($isAllowed): ?>
            <div id="idUserEntry" style="display: block;">
                <div class="form-box">
                    <form method="post" action="user_entry.php">
                        <label class="w3-text-dark-grey" style="font-weight: 600;"><i class="fa-regular fa-id-card"></i> User ID</label>
                        <div class="input-group">
                            <input type="text" name="user_id"
                                class="w3-input w3-border w3-round"
                                placeholder="Enter User ID"
                                required
                                value="<?php echo htmlspecialchars($editValue); ?>">

                            <?php if ($editId): ?>
                                <input type="hidden" name="edit_id" value="<?php echo $editId; ?>">
                                <button type="submit" name="update" class="w3-button w3-blue w3-round" style="background-color: #1976D2;">
                                    <i class="fa-solid fa-pen"></i> Update User
                                </button>
                            <?php else: ?>
                                <button type="submit" name="submit" class="w3-button w3-green w3-round" style="background-color: #2e7d32;">
                                    <i class="fa-solid fa-plus"></i> Add User
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <hr style="border-color: #dce5ef;">

                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-table-list" style="color: #1976D2;"></i> Registered Users
                    <?php
                    $countRes = $db->query("SELECT COUNT(*) as cnt FROM date_show_user");
                    $countRow = $countRes->fetch_assoc();
                    ?>
                    <span class="total-badge"><?php echo $countRow['cnt']; ?> total</span>
                </h3>

                <div class="w3-responsive">
                    <table class="visible-table">
                        <thead>
                            <tr>
                                <th>SERIAL</th>
                                <th>USER ID</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $db->query("SELECT * FROM date_show_user ORDER BY id ASC");
                            if ($result && $result->num_rows > 0):
                                $serial = 1;  // Start serial number from 1
                                while ($row = $result->fetch_assoc()):
                            ?>
                                    <tr>
                                        <td><strong><?php echo $serial++; ?></strong></td>
                                        <td><span class="user-id-badge"><?php echo htmlspecialchars($row['user_id']); ?></span></td>
                                        <td>
                                            <a href="user_entry.php?edit=<?php echo $row['id']; ?>" class="btn-edit">
                                                <i class="fa-solid fa-edit"></i> Edit
                                            </a>
                                            <form method="post" action="user_entry.php" style="display:inline;">
                                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="delete" class="btn-delete" onclick="return confirm('Are you sure you want to delete this user?');">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 40px;">No users found. Add your first user.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="unauthorized-msg">
                <i class="fa-solid fa-ban" style="font-size: 2rem;"></i>
                <h3>Access Restricted</h3>
                <p>You do not have permission to view or manage users.</p>
            </div>
        <?php endif; ?>

        <div class="back-wrapper">
            <a href="user_management.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back to User Management
            </a>
        </div>
    </div>

    <script>
        window.onload = function() {
            const msgBox = document.getElementById("successMsg");
            if (msgBox) {
                setTimeout(() => {
                    msgBox.style.display = "none";
                }, 3000);
            }
        };
    </script>

</body>

</html>