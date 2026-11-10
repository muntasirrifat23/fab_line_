<?php
session_start();

$user = strtolower($_SESSION['username'] ?? '');
$isDyeingUser = $user === 'd01';
$isKnittingUser = $user === 'k01';

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
      border-color: black;
    }

    .card-bg {
      background: linear-gradient(135deg, #4b0082, #8a2be2);
    }

    .dyeing-report-card {
      background: linear-gradient(135deg, var(--report-bg-start), var(--report-bg-end));
      background: linear-gradient(
        135deg,
        color-mix(in srgb, var(--report-bg-start) 90%, transparent),
        color-mix(in srgb, var(--report-bg-end) 84%, transparent)
      );
      border: 1px solid color-mix(in srgb, var(--report-border) 82%, white);
      backdrop-filter: blur(14px) saturate(145%);
      -webkit-backdrop-filter: blur(14px) saturate(145%);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7),
        0 9px 20px rgba(15, 23, 42, 0.11);
    }

    .dyeing-report-card .btn-report {
      color: var(--report-ink);
      background: transparent;
    }

    .dyeing-report-card .card-btn {
      color: var(--report-ink);
      background: rgba(255, 255, 255, 0.9);
    }

    .dyeing-report-card--card {
      --report-bg-start: #dbeafe;
      --report-bg-end: #bfdbfe;
      --report-border: #93c5fd;
      --report-ink: #1e40af;
    }

    .dyeing-report-card--delivery {
      --report-bg-start: #d1fae5;
      --report-bg-end: #a7f3d0;
      --report-border: #6ee7b7;
      --report-ink: #047857;
    }

    .dyeing-report-card--split {
      --report-bg-start: #ffedd5;
      --report-bg-end: #fed7aa;
      --report-border: #fdba74;
      --report-ink: #c2410c;
    }

    .dyeing-report-card--transfer {
      --report-bg-start: #ede9fe;
      --report-bg-end: #ddd6fe;
      --report-border: #c4b5fd;
      --report-ink: #6d28d9;
    }

    .dyeing-report-card--verification {
      --report-bg-start: #cffafe;
      --report-bg-end: #a5f3fc;
      --report-border: #67e8f9;
      --report-ink: #0e7490;
    }

    .dyeing-report-card--roll-verification {
      --report-bg-start: #fce7f3;
      --report-bg-end: #fbcfe8;
      --report-border: #f9a8d4;
      --report-ink: #be185d;
    }

    .dyeing-report-card--load {
      --report-bg-start: #ecfccb;
      --report-bg-end: #d9f99d;
      --report-border: #bef264;
      --report-ink: #4d7c0f;
    }

    .dyeing-report-card--unload {
      --report-bg-start: #ffe4e6;
      --report-bg-end: #fecdd3;
      --report-border: #fda4af;
      --report-ink: #be123c;
    }

    .knitting-report-card {
      border: 1px solid var(--knit-card-border);
      background: linear-gradient(135deg, var(--knit-card-start), var(--knit-card-end));
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2),
        0 8px 18px rgba(15, 23, 42, 0.12);
    }

    .knitting-report-card .btn-report {
      color: #fff;
      background: transparent;
    }

    .knitting-report-card .card-btn {
      color: var(--knit-card-end);
      background: rgba(255, 255, 255, 0.96);
    }

    .knitting-report-card--input {
      --knit-card-start: #0f3b73;
      --knit-card-end: #2563eb;
      --knit-card-border: #60a5fa;
    }

    .knitting-report-card--program {
      --knit-card-start: #075e54;
      --knit-card-end: #0f9f8f;
      --knit-card-border: #5eead4;
    }

    .knitting-report-card--status {
      --knit-card-start: #713f12;
      --knit-card-end: #d97706;
      --knit-card-border: #fbbf24;
    }

    .knitting-report-card--card {
      --knit-card-start: #43308a;
      --knit-card-end: #7c3aed;
      --knit-card-border: #c4b5fd;
    }

    .knitting-report-card--production {
      --knit-card-start: #164e63;
      --knit-card-end: #0891b2;
      --knit-card-border: #67e8f9;
    }

    .knitting-report-card--inspection {
      --knit-card-start: #881337;
      --knit-card-end: #e11d48;
      --knit-card-border: #fda4af;
    }

    .knitting-report-card--store {
      --knit-card-start: #365314;
      --knit-card-end: #65a30d;
      --knit-card-border: #bef264;
    }
  </style>
</head>

<body>
  <div class="report-container">
    <div class="card">
      <h2 class="card-title text-center" style="color: #6d28d9;">
        <?php echo $isDyeingUser ? 'Dyeing Report Section' : 'Knitting Report Section'; ?>
      </h2>
      <div class="row d-flex align-items-stretch">

        <?php if (!$isDyeingUser): ?>
        <!-- 1. Knitting Input Details Report -->
        <div class="col-6 col-md-4 mb-3 d-flex">
          <div class="report-card w-100 knitting-report-card knitting-report-card--input">
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

        <!-- 2. Knitting Program Report -->
        <div class="col-6 col-md-4 mb-3 d-flex">
          <div class="report-card w-100 knitting-report-card knitting-report-card--program">
            <form method="POST" action="knitting_program_report.php" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid fa-clipboard-list card-btn"></i>
                <span class="floor-text">
                  <span>Knitting Program Report</span>
                </span>
              </button>
            </form>
          </div>
        </div>

        <!-- 3. Knitting Program Status -->
        <div class="col-6 col-md-4 mb-3 d-flex">
          <div class="report-card w-100 knitting-report-card knitting-report-card--status">
            <form method="POST" action="knitting_program_status.php" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid fa-clipboard-list card-btn"></i>
                <span class="floor-text">
                  <span>Knitting Program Status</span>
                </span>
              </button>
            </form>
          </div>
        </div>

        <!-- 4. Knit Card Report -->
        <div class="col-6 col-md-4 mb-3 d-flex">
          <div class="report-card w-100 knitting-report-card knitting-report-card--card">
            <form method="POST" action="knit_card_report.php" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid fa-id-card-clip card-btn"></i>
                <span class="floor-text">
                  <span>Knit Card Report</span>
                </span>
              </button>
            </form>
          </div>
        </div>

        <!-- 5. Knitting production Report -->
        <div class="col-6 col-md-4 mb-3 d-flex">
          <div class="report-card w-100 knitting-report-card knitting-report-card--production">
            <form method="POST" action="knitting_production_report.php" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid fa-chart-line card-btn"></i>
                <span class="floor-text">
                  <span>Knitting Production Report</span>
                </span>
              </button>
            </form>
          </div>
        </div>

        <!-- 6. Knitting Inspection Report -->
        <div class="col-6 col-md-4 mb-3 d-flex">
          <div class="report-card w-100 knitting-report-card knitting-report-card--inspection">
            <form method="POST" action="knitting_inspection_report.php" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid fa-clipboard-check card-btn"></i>
                <span class="floor-text">
                  <span>Knitting Inspection Report</span>
                </span>
              </button>
            </form>
          </div>
        </div>

        <!-- 7. Knitting Store Report -->
        <div class="col-6 col-md-4 mb-3 d-flex">
          <div class="report-card w-100 knitting-report-card knitting-report-card--store">
            <form method="POST" action="knitting_store_report.php" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid fa-warehouse  card-btn"></i>
                <span class="floor-text">
                  <span>Knitting Store Report</span>
                </span>
              </button>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <!-- Dyeing Part Start -->
        <?php if (!$isKnittingUser): ?>
        <!-- Dyeing workflow reports -->
        <div class="col-6 col-md-4 mb-3 d-flex">
          <div class="report-card w-100 dyeing-report-card dyeing-report-card--card">
            <form method="POST" action="dyeing_batch_card_report.php" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid fa-id-card card-btn"></i>
                <span class="floor-text">
                  <span>Batch Card Report</span>
                </span>
              </button>
            </form>
          </div>
        </div>

        <div class="col-6 col-md-4 mb-3 d-flex">
          <div class="report-card w-100 dyeing-report-card dyeing-report-card--delivery">
            <form method="POST" action="dyeing_batch_delivery_report.php" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid fa-truck card-btn"></i>
                <span class="floor-text">
                  <span>Batch Delivery Report</span>
                </span>
              </button>
            </form>
          </div>
        </div>

        <?php
        $dyeingReports = [
          ['dyeing_batch_split_report.php', 'Batch Split Report', 'fa-scissors', 'split'],
          ['dyeing_batch_transfer_report.php', 'Batch Transfer Report', 'fa-right-left', 'transfer'],
          ['dyeing_batch_verification_report.php', 'Batch Verification Report', 'fa-clipboard-check', 'verification'],
          ['dyeing_roll_verification_report.php', 'Roll Verification Report', 'fa-qrcode', 'roll-verification'],
          ['dyeing_load_report.php', 'Load Report', 'fa-dolly', 'load'],
          ['dyeing_unload_report.php', 'Unload Report', 'fa-box-open', 'unload']
        ];
        foreach ($dyeingReports as $dyeingReport):
        ?>
        <div class="col-6 col-md-4 mb-3 d-flex">
          <div class="report-card w-100 dyeing-report-card dyeing-report-card--<?php echo htmlspecialchars($dyeingReport[3], ENT_QUOTES, 'UTF-8'); ?>">
            <form method="POST" action="<?php echo htmlspecialchars($dyeingReport[0], ENT_QUOTES, 'UTF-8'); ?>" class="h-100 w-100">
              <button type="submit" class="btn btn-report">
                <i class="fa-solid <?php echo htmlspecialchars($dyeingReport[2], ENT_QUOTES, 'UTF-8'); ?> card-btn"></i>
                <span class="floor-text">
                  <span><?php echo htmlspecialchars($dyeingReport[1], ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
              </button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>


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