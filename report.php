<?php
session_start();

$user = strtolower($_SESSION['username'] ?? '');

// $adminUsers = ['admin', 'ppq30', 'ppq34', 'ppq70'];

// if (in_array($user, $adminUsers)) {
//   $rejectionPage = "rejection_report_admin.php";
// } else {
//   $rejectionPage = "rejection_report.php";
// }
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Page</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    crossorigin="anonymous">

  <style>
    body {
      background: linear-gradient(145deg, #dceaf5 0%, #e9f2f9 100%);
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
      padding: 20px;
    }

    .card {
      border-radius: 18px;
      box-shadow: 0 20px 40px rgba(27, 38, 76, 0.08);
      padding: 40px;
      background-color: white;
      max-width: 780px;
      width: 100%;
    }

    .card-title {
      color: #343a40;
      margin-bottom: 30px;
      font-weight: 700;
      text-align: center;
    }

    .report-card {
      border-radius: 16px;
      padding: 10px;
      box-shadow: inset 0 0 0 1px rgba(90, 100, 255, 0.15),
        0 8px 20px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 120px;
    }

    .report-card:hover {
      transform: translateY(-4px);
      box-shadow: inset 0 0 0 1px rgba(90, 100, 255, 0.3),
        0 12px 28px rgba(0, 0, 0, 0.12);
      color: white;
    }

    .btn-report {
      width: 100%;
      height: 100%;
      padding: 12px;
      font-size: 16px;
      font-weight: 500;
      border-radius: 14px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: #fff;
      border: none;
      transition: all 0.35s ease;
    }

    .btn-report i {
      font-size: 26px;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      box-shadow: 0 6px 14px rgba(0, 0, 0, 0.25);
      transition: all 0.35s ease;
    }

    .btn-report:hover {
      transform: translateY(-6px) scale(1.02);
      box-shadow: 0 14px 34px rgba(0, 0, 0, 0.18);
    }

    .btn-report span {
      display: block;
      margin-top: 4px;
      transition: all 0.3s ease;
    }

    .btn-report:hover span {
      letter-spacing: 1px;
      font-weight: 600;
      transform: translateY(-2px);
    }

    .card-btn {
      background: #fbfbfb;
      color: black;
    }

    .btn-report:hover i {
      transform: scale(1.25) rotate(-6deg);
      filter: brightness(1.2);
      box-shadow: 0 0 18px rgba(0, 0, 0, 0.35),
        0 0 25px rgba(255, 255, 255, 0.25);
    }

    .floor-text {
      display: inline;
      white-space: nowrap;
      text-align: center;
    }

    .floor-text .line1,
    .floor-text .line2 {
      display: inline;
    }

    @media (max-width: 576px) {
      .floor-text {
        display: inline-block;
        white-space: normal;
      }

      .floor-text .line1,
      .floor-text .line2 {
        display: block;
        font-size: 14px;
        line-height: 1.2;
      }
    }

    .back-btn {
      display: inline-block;
      margin-top: 20px;
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

    .card-bg {
      background: linear-gradient(135deg, #4b0082, #8a2be2);
    }
  </style>
</head>

<body>
  <div class="report-container">
    <div class="card">
      <h2 class="card-title text-center" style="color: #6d28d9;">Report Selection</h2>
      <div class="row d-flex align-items-stretch">

        <!-- 1. Knitting Input Details Report (Amber) -->
        <div class="col-md-4 mb-3 d-flex">
          <div class="report-card w-100 card-bg">
            <form method="POST" action="knitting_input.php" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid fa-file-import card-btn"></i>
                <span class="floor-text">
                  <span>Knitting Input Details</span>
                </span>
              </button>
            </form>
          </div>
        </div>

        <!-- 2. Knitting Program Report (Amber) -->
        <div class="col-md-4 mb-3 d-flex">
          <div class="report-card w-100 card-bg">
            <form method="POST" action="knitting_program_report.php" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid fa-file-lines card-btn"></i>
                <span class="floor-text">
                  <span>Knitting Program Report</span>
                </span>
              </button>
            </form>
          </div>
        </div>

        <!-- 2. Knit Card Report (Amber) -->
        <div class="col-md-4 mb-3 d-flex">
          <div class="report-card w-100 card-bg">
            <form method="POST" action="knit_card_report.php" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid fa-id-card card-btn"></i>
                <span class="floor-text">
                  <span>Knit Card Report</span>
                </span>
              </button>
            </form>
          </div>
        </div>
        <!-- 1. DHU Report (Teal) -->
        <!-- <i class="fa-solid fa-chart-line card-btn"></i> -->

        <!-- 2. Manpower Report (Orange) -->
        <!-- <i class="fa-solid fa-users card-btn"></i> -->

        <!-- 3. Production Report (Blue) -->
        <!-- <i class="fa-solid fa-industry card-btn"></i> -->

        <!-- 4. Production Summary (Purple) -->
        <!-- <i class="fa-solid fa-file-lines card-btn"></i> -->

        <!-- 5. All Wip (Cyan) -->
        <!-- <i class="fa-solid fa-boxes card-btn"></i> -->


        <!-- 6. Rejection Report (Red) -->
        <!-- <i class="fa-solid fa-ban card-btn"></i> -->

        <!-- 7. Adjustment Report (Amber) -->
        <!-- <i class="fa-solid fa-sliders-h card-btn"></i> -->

        <!-- 8. Transfer Report (Amber) -->
        <!-- <i class="fa-solid fa-exchange-alt card-btn"></i> -->

        <!-- 9. Production Hourly Report (Amber) -->
        <!-- <i class="fa-solid fa-clock card-btn"></i> -->

      </div>

      <div class="text-center">
        <a href="initialPage.php" class="back-btn">
          <i class="fa-solid fa-arrow-left"></i> Back to Inital Page
        </a>
      </div>
    </div>


  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>