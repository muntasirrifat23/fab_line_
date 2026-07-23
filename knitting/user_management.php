<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>User Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(135deg, #e0f2fe, #eef2ff, #e6fffa);
      font-family: 'Poppins', 'Segoe UI', system-ui, 'Inter', -apple-system, BlinkMacSystemFont, 'Roboto', sans-serif;
      position: relative;
      min-height: 100vh;
    }

    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: radial-gradient(rgba(25, 60, 90, 0.03) 1.5px, transparent 1.5px);
      background-size: 28px 28px;
      pointer-events: none;
      z-index: 0;
    }

    .report-container {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 2rem 1.5rem;
      position: relative;
      z-index: 2;
    }

    .card {
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(0px);
      border-radius: 32px;
      border: 1px solid rgba(255, 255, 255, 0.6);
      box-shadow: 0 25px 45px -12px rgba(0, 0, 0, 0.15), 0 4px 12px rgba(0, 0, 0, 0.05);
      padding: 2rem 2rem;
      transition: all 0.3s ease;
      max-width: 880px;
      width: 100%;
    }

    .card:hover {
      box-shadow: 0 30px 55px -15px rgba(0, 0, 0, 0.2);
      border-color: rgba(255, 255, 255, 0.9);
    }

    .card-title {
      font-size: 2rem;
      font-weight: 800;
      background: linear-gradient(130deg, #1F2B48, #2D3A5E);
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      letter-spacing: -0.3px;
      margin-bottom: 2rem;
      text-align: center;
      position: relative;
      display: inline-block;
      width: 100%;
    }

    .card-title:after {
      content: '';
      display: block;
      width: 70px;
      height: 4px;
      background: linear-gradient(90deg, #3b82f6, #a855f7);
      border-radius: 4px;
      margin: 12px auto 0;
    }

    .report-card-item {
      border-radius: 28px;
      padding: 0.5rem;
      transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
      box-shadow: 0 12px 22px rgba(0, 0, 0, 0.12);
      cursor: pointer;
      height: 140px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: none;
    }

    .report-card-item:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 26px 38px -14px rgba(0, 0, 0, 0.28);
    }

    .btn-report-style {
      width: 100%;
      height: 100%;
      padding: 0.8rem;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 1.8rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
      border: none;
      background: inherit;
      transition: all 0.3s ease;
      letter-spacing: 0.3px;
      gap: 12px;
    }

    .btn-report-style i {
      font-size: 2.2rem;
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background: rgba(0, 0, 0, 0.2);
      backdrop-filter: blur(2px);
      box-shadow: 0 12px 18px -8px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.3);
      transition: all 0.35s ease;
      color: white;
    }

    .btn-report-style:hover i {
      transform: scale(1.2) rotate(2deg);
      background: rgba(0, 0, 0, 0.3);
      box-shadow: 0 20px 25px -10px rgba(0, 0, 0, 0.4), 0 0 0 2px rgba(255, 255, 255, 0.5);
    }

    .btn-report-style span {
      display: block;
      font-weight: 600;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
      transition: all 0.25s;
    }

    .btn-report-style:hover span {
      letter-spacing: 1.2px;
      transform: translateY(-2px);
    }

    .floor-text span {
      font-size: 1rem;
      font-weight: 700;
    }

    @media (max-width: 576px) {
      .btn-report-style i {
        width: 48px;
        height: 48px;
        font-size: 1.8rem;
      }

      .floor-text span {
        font-size: 0.9rem;
      }

      .report-card-item {
        height: 120px;
      }

      .card-title {
        font-size: 1.7rem;
      }
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

    .row {
      margin-top: 0.5rem;
    }

    @media (max-width: 768px) {
      .card {
        padding: 1.5rem;
      }
    }
  </style>
</head>

<body>

  <div class="report-container">
    <div class="card">
      <h2 class="card-title">
        <i class="fa-solid fa-users"></i> User Management
      </h2>

      <div class="row d-flex align-items-stretch justify-content-center g-4">

        <div class="col-12 col-md-4 d-flex">
          <div class="report-card-item w-100" style="background: linear-gradient(135deg, #1e3c72, #2a5298);">
            <button type="button" class="btn-report-style" data-bs-toggle="modal" data-bs-target="#passwordModalReset">
              <i class="fa-solid fa-user-plus"></i>
              <span class="floor-text">
                <span>Reset/ Add Users</span>
              </span>
            </button>
          </div>
        </div>

        <div class="col-12 col-md-4 d-flex">
          <div class="report-card-item w-100" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
            <form method="POST" action="user_entry.php" class="h-100 w-100">
              <button type="submit" class="btn-report-style">
                <i class="fa-solid fa-user-lock"></i>
                <span class="floor-text">
                  <span>Lock/ Unlock Users</span>
                </span>
              </button>
            </form>
          </div>
        </div>

      </div>

      <div class="text-center mt-4">
        <a href="initialPage.php" class="back-btn">
          <i class="fa-solid fa-arrow-left"></i> Back to Initial Page
        </a>
      </div>
    </div>
  </div>

  <!-- Password Modal for Reset/Add Users -->
  <div class="modal fade" id="passwordModalReset" tabindex="-1" aria-labelledby="passwordModalResetLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content shadow-lg rounded-4">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold" id="passwordModalResetLabel">
            <i class="fa-solid fa-lock me-2"></i> Authentication Required
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted">Please enter the password to access <strong>Reset/Add Users</strong>.</p>
          <input type="password" id="resetPassword" class="form-control form-control-lg rounded-3" placeholder="Enter password" autocomplete="off">
          <div id="resetPasswordError" class="text-danger mt-2 small" style="display: none;">Wrong password! Access denied.</div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="verifyResetBtn" class="btn btn-primary">Verify & Proceed</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Hidden form for Reset/Add Users submission -->
  <form id="resetForm" method="POST" action="loginUpdate.php" style="display: none;"></form>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('verifyResetBtn').addEventListener('click', function() {
      const enteredPassword = document.getElementById('resetPassword').value;
      const errorDiv = document.getElementById('resetPasswordError');

      if (enteredPassword === "786") {
        document.getElementById('resetForm').submit();
      } else {
        errorDiv.style.display = 'block';
        document.getElementById('resetPassword').value = '';
      }
    });

    const resetPasswordInput = document.getElementById('resetPassword');
    const resetModal = document.getElementById('passwordModalReset');

    resetPasswordInput.addEventListener('input', function() {
      document.getElementById('resetPasswordError').style.display = 'none';
    });

    resetModal.addEventListener('hidden.bs.modal', function() {
      resetPasswordInput.value = '';
      document.getElementById('resetPasswordError').style.display = 'none';
    });
  </script>
</body>

</html>