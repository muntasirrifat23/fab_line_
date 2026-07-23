<?php
include 'config.php';

if (isset($_POST['submit'])) {

    $username = trim(mysqli_real_escape_string($db, $_POST['username']));
    $email = trim(mysqli_real_escape_string($db, $_POST['email']));
    // Auto password for created users
    $autoPassword = '123';
    $password = $autoPassword;
    $confirmPassword = $autoPassword;

    // default email (if empty)
    if (empty($email)) {
        $email = "default@mail.com";
    }

    $checkEmail = mysqli_query($db, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($checkEmail) > 0) {
        echo "<script>alert('Email already used');</script>";
    } else {
        // validation: only username required (email is optional / defaulted)
        if (empty($username)) {
            echo "<script>alert('User ID is required');</script>";
        }

        // username length check (varchar 10)
        else if (strlen($username) > 10) {
            echo "<script>alert('Username max 10 characters');</script>";
        }
        // duplicate check
        $check = mysqli_query($db, "SELECT id FROM users WHERE username='$username'");
        if (mysqli_num_rows($check) > 0) {
            echo "<script>alert('User already exists');</script>";
        } else {

            // ✅ MD5 (same as your system) - use auto password '123'
            $hashedPassword = md5(trim($password));

            // insert
            $sql = "INSERT INTO users (username, email, password) 
                    VALUES ('$username', '$email', '$hashedPassword')";

            if (mysqli_query($db, $sql)) {
                echo "<script>alert('New ID Created Successfully (Password: 123)'); window.location.href='initialPage.php';</script>";
            } else {
                echo "<script>alert('Database Error');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ID Restore/ Create</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #e0f7fa, #e8f5e9);
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #009688, #26a69a);
            border: none;
        }

        .nav-tabs .nav-link.active {
            background-color: #26a69a;
            color: white;
        }
    </style>
</head>

<body>

    <div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
        <div class="card p-4" style="width:400px;">
            <h3 class="text-center mb-3">Password Reset/ Create</h3>

            <!-- Tabs -->
            <div class="row text-center mb-3" id="tabMenu">
                <div class="col-6">
                    <a class="nav-link active d-block" href="#" data-target="#restoreForm">
                        Password Reset
                    </a>
                </div>

                <div class="col-6">
                    <a class="nav-link d-block" href="#" data-target="#userForm">
                        Create New ID
                    </a>
                </div>
            </div>

            <!-- Forms -->
            <div id="msgBox" class="mb-3"></div>
            <form id="restoreForm" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label fw-bold">User ID</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter User ID to Reset Password" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-warning w-100">Password Reset</button>
                <button type="button" class="btn back-btn w-100" onclick="window.location.href='user_management.php'">
                    <i class="bi bi-arrow-left-circle"></i> Back to User Management
                </button>
            </form>

            <form id="userForm" style="display:none;" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label fw-bold">New ID</label>
                    <input type="text" name="username" class="form-control"
                        placeholder="Enter New User ID" autocomplete="off">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="email" class="form-control"
                        placeholder="Enter New Email" autocomplete="off">
                </div>

                <!-- Passwords are auto-generated (123) -->
                <button type="submit" class="btn btn-primary w-100">Create User</button>
                <button type="button" class="btn back-btn-create w-100" onclick="window.location.href='user_management.php'">
                    <i class="bi bi-arrow-left-circle"></i> Back to User Management
                </button>

            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#restoreForm").show();
            $("#userForm").hide();
            $("#tabMenu .nav-link").removeClass("active");
            $("#tabMenu .nav-link[data-target='#restoreForm']").addClass("active");
        });

        // Password visibility toggle removed (passwords are auto-generated)

        // Tab toggle
        $("#tabMenu .nav-link").click(function(e) {
            e.preventDefault();

            $("#tabMenu .nav-link").removeClass("active");
            $(this).addClass("active");

            let target = $(this).data("target");

            $("#userForm, #restoreForm").hide();
            $(target).show();

            $("#msgBox").html("");
        });

        // Create User
        $("#userForm").on("submit", function(e) {
            e.preventDefault();
            $.ajax({
                url: "create_user_ajax.php",
                type: "POST",
                data: $(this).serialize() + "&submit=1",
                dataType: "json",
                success: function(res) {
                    if (res.status === "success") {
                        $("#msgBox").html("<div class='alert alert-success'>" + res.message + "</div>");
                        $("#userForm")[0].reset();
                    } else {
                        $("#msgBox").html("<div class='alert alert-danger'>" + res.message + "</div>");
                    }
                },
                error: function() {
                    $("#msgBox").html("<div class='alert alert-danger'>Server Error</div>");
                }
            });
        });

        // Restore ID
        $("#restoreForm").on("submit", function(e) {
            e.preventDefault();
            $.ajax({
                url: "restore_user_ajax.php",
                type: "POST",
                data: $(this).serialize() + "&submit=1",
                dataType: "json",
                success: function(res) {
                    if (res.status === "success") {
                        $("#msgBox").html("<div class='alert alert-success'>" + res.message + "</div>");
                        $("#restoreForm")[0].reset();
                    } else {
                        $("#msgBox").html("<div class='alert alert-danger'>" + res.message + "</div>");
                    }
                },
                error: function() {
                    $("#msgBox").html("<div class='alert alert-danger'>Server Error</div>");
                }
            });
        });
    </script>
    <style>
        body {
            background: linear-gradient(-45deg, #0f2027, #203a43, #2c5364, #1c1c1c);
            background-size: 400% 400%;
            animation: gradientBG 10s ease infinite;
            font-family: 'Segoe UI', sans-serif;
        }

        /* background animation */
        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .back-btn {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            padding: 8px;
            transition: 0.3s;
            margin-top: 10px;
        }

        .back-btn i {
            margin-right: 6px;
            font-size: 18px;
        }

        .back-btn:hover {
            transform: scale(1.03);
            box-shadow: 0 0 15px rgba(37, 117, 252, 0.5);
        }

        /* glass card */
        .card {
            width: 420px;
            padding: 25px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            color: white;
        }

        /* title */
        h3 {
            font-weight: 700;
            letter-spacing: 1px;
            color: #ffffff;
        }

        /* inputs */
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            border-radius: 10px;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: none;
            border: 1px solid #00e5ff;
        }

        /* placeholder color */
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        /* buttons */
        .btn-primary {
            background: linear-gradient(135deg, #00c6ff, #0072ff);
            border: none;
            font-weight: bold;
            border-radius: 10px;
        }

        .btn-primary:hover {
            transform: scale(1.03);
            transition: 0.3s;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ff9800, #ff5722);
            border: none;
            font-weight: bold;
            border-radius: 10px;
        }

        .back-btn-create {
            background: linear-gradient(135deg, #00c9a7, #92fe9d);
            color: #0f2d2e;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            padding: 8px;
            transition: 0.3s;
            margin-top: 10px;
        }

        .back-btn-create i {
            margin-right: 6px;
            font-size: 18px;
        }

        .back-btn-create:hover {
            transform: scale(1.03);
            box-shadow: 0 0 15px rgba(0, 201, 167, 0.5);
        }

        /* tabs */
        #tabMenu .nav-link {
            background: rgba(255, 255, 255, 0.08);
            padding: 12px;
            border-radius: 12px;
            font-weight: bold;
            color: #ddd;
            transition: 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        #tabMenu .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        #tabMenu .nav-link.active {
            background: linear-gradient(135deg, #00c6ff, #0072ff);
            color: white;
            box-shadow: 0 0 15px rgba(0, 198, 255, 0.5);
        }

        /* alerts */
        .alert {
            border-radius: 10px;
            font-weight: bold;
        }
    </style>
</body>

</html>