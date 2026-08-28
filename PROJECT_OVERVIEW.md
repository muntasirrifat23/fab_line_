# Fab Line Textile ERP System - Technical Documentation & Project Overview

---

## 1. Project Overview

### 1.1 Purpose & Domain
**Fab Line** is an enterprise-grade Textile Manufacturing & Execution ERP System designed specifically for the garment and textile manufacturing industry. It digitizes and manages the complete lifecycle of fabric manufacturing—from yarn allocation, knitting program scheduling, daily machine roll production, real-time quality control inspection, grey fabric warehouse stock allocation to dyeing batch creation.

### 1.2 Problems Solved
1. **Manual Roll Tracking & Errors:** Replaces paper logbooks with automated QR Code / Barcode generation and camera-based scanning for every individual fabric roll (`KNITCARD`).
2. **Fabric Defect & Quality Control:** Standardizes defect recording (ASTM 4-Point System) and automated roll grading (`Grade A`, `Grade B`, `Grade C`, `Reject`) to prevent defective rolls from entering the dyeing process.
3. **Inventory & Warehouse Transparency:** Provides real-time rack location tracking (`RACKNO`, `RACKLOCATION`) for grey fabric in warehouses and monitors inter-rack transfers.
4. **Planning vs. Execution Visibility:** Dynamically compares planned program weight against cumulative production weight per shift, machine, and operator to calculate remaining production quotas.
5. **Dyeing Batch Management:** Enables batching of inspected rolls into single Dyeing Batch Cards (`BCMTID`) with batch splitting capabilities to accommodate dyeing vessel capacities.

---

## 2. Tech Stack

### 2.1 Backend Architecture
* **Language & Version:** PHP 7.4+ / PHP 8.x (Procedural PHP with modular structural endpoints).
* **Framework:** Custom native PHP architecture (No external full-stack framework like Laravel/Symfony).
* **Database Engine:** MySQL / MariaDB (InnoDB storage engine, `utf8mb4_general_ci` collation).
* **Database Driver / Connection:** Native PHP `mysqli` extension with procedural queries and prepared statements (`mysqli_prepare`, `mysqli_stmt_bind_param`) in key transactional API endpoints.

### 2.2 Frontend Stack & Libraries
* **Core Technologies:** HTML5, CSS3, JavaScript (ES6+), jQuery 3.x.
* **UI Styling Frameworks:** Custom CSS (`style.css`, `tv.css`, `loginPRO.css`, `mycss.css`), Bootstrap (`css/bootstrap.min.css`), W3.CSS (`css/w3.css`), FontAwesome 6 (`all.min.css`).
* **Barcode & QR Code Libraries:**
  * `html5-qrcode` v2.3.8 (Browser camera-based live QR/Barcode scanning).
  * `qrcode.min.js` (Client-side QR code canvas rendering).
* **UI Feedback & Rendering:**
  * `SweetAlert2` v11 (Modern interactive modal alerts).
  * `html2canvas` v1.4.1 (DOM element screenshot capture).
  * `jsPDF` v2.5.1 (Client-side HTML-to-PDF generation).

### 2.3 Composer & Package Dependencies (`composer.json`)
| Package Name | Version Specifier | Purpose |
| :--- | :--- | :--- |
| `setasign/fpdf` | `^1.8` | Core PDF document generation engine |
| `phpoffice/phpspreadsheet` | `^4.3` | Reading, parsing, and exporting Excel (.xlsx/.csv) spreadsheets |
| `mpdf/mpdf` | `^8.2` | Advanced HTML-to-PDF export with dynamic CSS styling |

---

## 3. Folder & File Structure

### 3.1 Directory Tree
```
fab_line/
└── fab_line_-main/
    ├── css/                        # Stylesheets (Bootstrap, W3.CSS, FontAwesome, custom themes)
    │   ├── bootstrap.min.css
    │   ├── font-awesome.min.css
    │   ├── loginPRO.css
    │   ├── report.css
    │   └── tv.css
    ├── js/                         # JavaScript libraries & client-side scripts
    │   ├── qrcode.min.js
    │   ├── auto_logout.js
    │   ├── jquery.min.js
    │   └── jquery.cookie.min.js
    ├── image/                      # Project logos & UI graphic assets
    ├── tmp/                        # Temporary PDF & session export storage
    ├── vendor/                     # Composer managed PHP dependencies (mPDF, PhpSpreadsheet, FPDF)
    ├── config.php                  # Global database configuration & session control
    ├── index.php                   # Homepage & session authentication router
    ├── login.php                   # Authentication login interface
    ├── loginPOST.php               # Login credential verification endpoint
    ├── initialPage.php             # Main dashboard navigation menu
    ├── knitting_program.php        # Knitting program planning list & entry form
    ├── save_knitting_program.php   # Backend program save handler
    ├── knitting_input.php          # Yarn input & allocation tracking
    ├── subcontract_input.php       # External subcontract yarn & fabric input
    ├── knitting_production.php     # Floor roll production entry & QR scanner interface
    ├── knit_card.php               # Knit card generator dashboard
    ├── knit_card_print.php         # Printable Knit Card layout (Barcode/QR)
    ├── knitting_inspection.php     # Fabric roll QC inspection & defect grading UI
    ├── knitting_store.php          # Grey fabric store inventory & rack assignment
    ├── knitting_rack_transfer.php  # Warehouse rack-to-rack roll transfer module
    ├── dyeing_batch_card.php       # Dyeing batch card grouping & print manager
    ├── dyeing_batch_split.php      # Dyeing batch splitting utility
    ├── report.php                  # Central reporting dashboard
    ├── upload.php                  # Excel/CSV master data uploader
    ├── user_management.php         # User administration panel
    ├── composer.json               # PHP dependency specifications
    └── knittingdb_backup.sql       # Full MariaDB database schema & seed dump
```

---

## 4. Database Schema (`knittingdb`)

The database `knittingdb` consists of 15 relational and transaction tables. Below is the comprehensive schema documentation.

### 4.1 Table Definitions

#### 1. `users`
* **Purpose:** Stores system users, credentials, and access credentials.
* **Columns:**
  * `id`: `INT(11)` | `NOT NULL` | `AUTO_INCREMENT` | **PRIMARY KEY**
  * `USER_NAME`: `VARCHAR(10)` | `NOT NULL`
  * `USER_ID`: `VARCHAR(10)` | `NOT NULL` | `DEFAULT ''`
  * `email`: `VARCHAR(50)` | `NOT NULL` | `DEFAULT 'sarwar.alam@purbanigroup.com'`
  * `password`: `VARCHAR(100)` | `NOT NULL` (MD5 Hashed)
  * `created`: `TIMESTAMP` | `NOT NULL` | `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`

#### 2. `knitting_operator`
* **Purpose:** Directory of machine operators for production logging.
* **Columns:**
  * `KOTID`: `INT(11)` | `NOT NULL` | `AUTO_INCREMENT` | **PRIMARY KEY**
  * `OPERATOR_ID`: `VARCHAR(20)` | `NOT NULL` | **UNIQUE KEY** (`UK_OPERATOR_ID`)
  * `OPERATOR_NAME`: `VARCHAR(100)` | `NOT NULL`
  * `OPERATOR_EMAIL`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL`
  * `OPERATOR_PASSWORD`: `VARCHAR(255)` | `NOT NULL`
  * `CREATED`: `TIMESTAMP` | `NOT NULL` | `DEFAULT CURRENT_TIMESTAMP`

#### 3. `mcno`
* **Purpose:** Knitting machine master registry.
* **Columns:**
  * `MCNOID`: `INT(11)` | `NOT NULL` | `AUTO_INCREMENT` | **PRIMARY KEY**
  * `MCNO`: `VARCHAR(50)` | `NULL` | `DEFAULT NULL` (e.g., `K-M/C-001`, `FLAT-01`)
  * `CBUDAT`: `DATE` | `NULL` | `DEFAULT (CURRENT_DATE)`

#### 4. `knitting_program`
* **Purpose:** Master production planning records for orders.
* **Columns:**
  * `KPTID`: `INT(11)` | `NOT NULL` | `AUTO_INCREMENT` | **PRIMARY KEY**
  * `PROGRAM_NO`: `BIGINT(20)` | `NOT NULL` | **INDEX** (`idx_kp_program_no`)
  * `PO_NUMBER`: `VARCHAR(50)` | `NOT NULL`
  * `SONO`: `VARCHAR(50)` | `NULL` | `DEFAULT NULL`
  * `BUYER`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL`
  * `STYLE`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL`
  * `COLOR`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL`
  * `QTY`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL` (Target weight in KG)
  * `FGSM`: `VARCHAR(20)` | `NULL` | `DEFAULT NULL` (Finished GSM)
  * `FDIA`: `VARCHAR(20)` | `NULL` | `DEFAULT NULL` (Finished DIA)
  * `O_T`: `VARCHAR(20)` | `NULL` | `DEFAULT NULL` (Open / Tube fabric)
  * `FTYPE`: `VARCHAR(50)` | `NULL` | `DEFAULT NULL` (Fabric Type e.g., Single Jersey, Pique)
  * `YTYPE`: `VARCHAR(50)` | `NULL` | `DEFAULT NULL` (Yarn composition)
  * `CUSTOMER`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL`
  * `YCOUNT`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL` (Yarn Count)
  * `SL`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL` (Stitch Length)
  * `MCDIA`: `VARCHAR(20)` | `NULL` | `DEFAULT NULL` (Machine Dia)
  * `GGSM`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL` (Grey GSM)
  * `FEEDER_PLAN`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL`
  * `LOT`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL` (Yarn Lot No)
  * `SHIFT`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL`
  * `KNIT_MATERIAL_CODE`: `VARCHAR(50)` | `NULL` | `DEFAULT NULL`
  * `KNIT_M_DESCRIPTION`: `VARCHAR(200)` | `NULL` | `DEFAULT NULL`
  * `CREATED_DATE`: `TIMESTAMP` | `NOT NULL` | `DEFAULT CURRENT_TIMESTAMP`
  * `UNAME`: `VARCHAR(100)` | `NULL` | `DEFAULT NULL`

#### 5. `knit_card`
* **Purpose:** Individual roll identifier records generated from a Knitting Program.
* **Columns:**
  * `KCTID`: `BIGINT(20)` | `NOT NULL` | `AUTO_INCREMENT` | **PRIMARY KEY**
  * `KPTID`: `BIGINT(20)` | `NOT NULL` (FK reference to `knitting_program.KPTID`)
  * `KNITCARD`: `BIGINT(20)` | `NOT NULL` (Unique Roll Barcode/QR number e.g., `300000001`)
  * `MCNO`: `VARCHAR(50)` | `NOT NULL`
  * `QTY`: `INT(11)` | `NOT NULL` (Target roll weight)
  * `PO_NUMBER`, `SONO`, `BUYER`, `STYLE`, `COLOR`, `FGSM`, `FDIA`, `O_T`, `FTYPE`, `YTYPE`, `CUSTOMER`, `YCOUNT`, `SL`, `MCDIA`, `GGSM`, `FEEDER_PLAN`, `LOT`, `SHIFT`, `KNIT_MATERIAL_CODE`, `KNIT_M_DESCRIPTION`: Inherited from `knitting_program`
  * `CREATED_DATE`: `TIMESTAMP` | `NOT NULL` | `DEFAULT CURRENT_TIMESTAMP`
  * `UNAME`, `UID`: `VARCHAR(100)`

#### 6. `knitting_input`
* **Purpose:** Stores raw yarn inputs received for a sales order/program.
* **Columns:**
  * `KITID`: `INT(11)` | `NOT NULL` | `AUTO_INCREMENT` | **PRIMARY KEY**
  * `BUDAT`: `DATE` | `NULL` | `DEFAULT (CURRENT_DATE)`
  * `PO_NUMBER`, `SONO`, `BUYER`, `STYLE`, `COLOR`, `CUSTOMER`, `QTY`, `FINISH_GSM`, `FINISH_DIA`, `OPEN_TUBE`, `FABRICS_TYPE`, `YARN_TYPE`, `KNIT_MATERIAL_CODE`, `KNIT_M_DESCRIPTION`: Specs of input yarn
  * `CBUDAT`: `DATETIME` | `DEFAULT CURRENT_TIMESTAMP`

#### 7. `knitting_production`
* **Purpose:** Logs actual physical roll production entries recorded by operators.
* **Columns:**
  * `PID`: `INT(11)` | `NOT NULL` | `AUTO_INCREMENT` | **PRIMARY KEY**
  * `BUDAT`: `DATE` | `NULL` | `DEFAULT NULL` (Production Date)
  * `ROLL`: `VARCHAR(100)` | `NULL` | **INDEX** (`ROLL`) (Matches `KNITCARD`)
  * `PO_NUMBER`: `VARCHAR(100)` | `NULL` | **INDEX** (`PO_NUMBER`)
  * `PQTY`: `DECIMAL(10,2)` | `DEFAULT 0.00` (Actual produced roll weight)
  * `SONO`, `BUYER`, `STYLE`, `COLOR`, `MCNO`, `MC_DIA`, `CUSTOMER`, `SHIFT`, `YARN_TYPE`, `YARN_COUNT`, `FABRICS_TYPE`, `FINISH_GSM`, `FINISH_DIA`, `OPEN_TUBE`, `SL_VDQ`, `GRAY_GSM`, `FEEDER_PLAN`, `LOT_NO`, `KNIT_MATERIAL_CODE`, `KNIT_M_DES`, `UNAME`, `UID`: Production specs
  * `CREATED_AT`: `TIMESTAMP` | `NOT NULL` | `DEFAULT CURRENT_TIMESTAMP`

#### 8. `knitting_inspection`
* **Purpose:** Quality control inspection results and 16-point defect evaluation for rolls.
* **Columns:**
  * `KITID`: `INT(11)` | `NOT NULL` | `AUTO_INCREMENT` | **PRIMARY KEY**
  * `BUDAT`: `DATE` | `NULL`
  * `ROLL`: `VARCHAR(100)` | **UNIQUE KEY** (`uniq_roll`), **INDEX** (`idx_roll`)
  * `PO_NUMBER`: `VARCHAR(100)` | **INDEX** (`idx_po_number`)
  * `QTY`, `SONO`, `BUYER`, `STYLE`, `COLOR`, `MCNO`, `MC_DIA`, `CUSTOMER`, `SHIFT`, `YTYPE`, `YCOUNT`, `FTYPE`, `FGSM`, `FDIA`, `O_T`, `SL`, `GGSM`, `FPLAN`, `LOTNO`, `MATERIAL_CODE`, `M_DES`: Production specs
  * **Defect Penalty Columns (16 Types):** `TT`, `PATTA`, `SLUB`, `YC_SPOT`, `OILSPOT`, `FF`, `SEEDS`, `MSTITCH`, `SINKERMARK`, `NEEDLEMARK`, `LYCOUT`, `OILLINE`, `HOLE`, `LOOP`, `SETUP`, `CMARK` (`VARCHAR(50)`)
  * `TPOINT`: `VARCHAR(50)` | `DEFAULT NULL` (Total Penalty Points)
  * `QC_GRADE`: `VARCHAR(50)` | `DEFAULT NULL` (`Grade A`, `Grade B`, `Reject`)
  * `QC_STATUS`: `VARCHAR(50)` | `DEFAULT NULL` (`Passed`, `Failed`)
  * `UNAME`, `UID`: `VARCHAR(100)`
  * `P_CREATED`: `DATETIME` | `DEFAULT CURRENT_TIMESTAMP`

#### 9. `knitting_store`
* **Purpose:** Warehouse inventory tracking inspected grey fabric rolls assigned to storage racks.
* **Columns:**
  * `KSTID`: `INT(11)` | `NOT NULL` | `AUTO_INCREMENT` | **PRIMARY KEY**
  * `BUDAT`: `DATE` | `NULL`
  * `RACKNO`: `VARCHAR(50)` | `NULL` (Rack ID e.g., `20`)
  * `RACKLOCATION`: `VARCHAR(100)` | `NULL` (Rack Cell Location e.g., `A1`, `B3`)
  * `ROLL`: `VARCHAR(50)` | `NOT NULL`
  * `PO_NUMBER`: `VARCHAR(50)` | `NOT NULL`
  * `QTY`: `VARCHAR(50)` | `NOT NULL`
  * `SONO`, `SHIFT`, `BUYER`, `STYLE`, `COLOR`, `MCNO`, `MCDIA`, `CUSTOMER`, `YTYPE`, `YCOUNT`, `O_T`, `SL`, `FTYPE`, `FGSM`, `FDIA`, `GGSM`, `FEEDER_PLAN`, `LOT_NO`, `TPOINT`, `MCODE`, `MDESCRIPTION`, `UNAME`, `UID`: Full roll metadata
  * `CREATED_DATE`: `TIMESTAMP` | `NOT NULL` | `DEFAULT CURRENT_TIMESTAMP`

#### 10. `dyeing_batch_card`
* **Purpose:** Stores Dyeing Batch Cards created by batching grey rolls from `knitting_store`.
* **Columns:**
  * `DBCTID`: `INT(11)` | `NOT NULL` | `AUTO_INCREMENT` | **PRIMARY KEY**
  * `BUDAT`: `DATE` | `NULL`
  * `BCMTID`: `VARCHAR(30)` | `NULL` (Batch Card Master ID e.g., `4000000001`)
  * `ROLL`: `VARCHAR(50)` | `NOT NULL`
  * `PO_NUMBER`, `RACK`, `QTY`, `SONO`, `SHIFT`, `BUYER`, `STYLE`, `COLOR`, `MCNO`, `MCDIA`, `CUSTOMER`, `YTYPE`, `YCOUNT`, `O_T`, `SL`, `FTYPE`, `FGSM`, `FDIA`, `GGSM`, `FEEDER_PLAN`, `LOT_NO`, `TPOINT`, `MCODE`, `MDESCRIPTION`, `UNAME`: Batch card roll specs
  * `CREATED_DATE`: `TIMESTAMP` | `NOT NULL` | `DEFAULT CURRENT_TIMESTAMP`

#### 11. `dyeing_batch_split`
* **Purpose:** Audit log of dyeing batches split into sub-batches.
* **Columns:**
  * `SPLIT_ID`: `INT(11)` | `NOT NULL` | `AUTO_INCREMENT` | **PRIMARY KEY**
  * `ORIGINAL_BCMTID`: `VARCHAR(30)` | `DEFAULT NULL`
  * `CARD_A`: `VARCHAR(30)` | `DEFAULT NULL`
  * `CARD_B`: `VARCHAR(30)` | `DEFAULT NULL`
  * `QTY_A`: `DECIMAL(12,2)` | `DEFAULT NULL`
  * `QTY_B`: `DECIMAL(12,2)` | `DEFAULT NULL`
  * `ROLL_A_COUNT`: `INT(11)` | `DEFAULT NULL`
  * `ROLL_B_COUNT`: `INT(11)` | `DEFAULT NULL`
  * `UNAME`: `VARCHAR(100)`
  * `CREATED_DATE`: `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP`

#### 12. `date_show_user`
* **Purpose:** User preferences for date visibility formats.
* **Columns:** `id` (PK), `user_id` (Unique).

#### 13. `knit_card_production`
* **Purpose:** Shift-wise aggregated production tracking per knit card.
* **Columns:** `KCPID` (PK), `KCID` (FK to `knit_card`), `LOG_DATE`, `A_SHIFT_QTY`, `B_SHIFT_QTY`, `C_SHIFT_QTY`, `PRODUCTION_QTY`, `CUM_TOTAL`, `BALANCE`, `OPERATOR_A`, `OPERATOR_B`, `OPERATOR_C`, `CREATED_AT`.

#### 14. `knit_card_test` & 15. `knitting_inspection_test`
* **Purpose:** Staging/testing tables mirroring `knit_card` and `knitting_inspection` for trial features and CSV uploads.

---

### 4.2 Entity Relationship Diagram (Text/Conceptual Format)

```
 [knitting_program] 1 ──── N [knit_card]
        │                         │
        │                         ▼
        └─────────────────► [knitting_production] (ROLL = KNITCARD)
                                  │
                                  ▼
                         [knitting_inspection] (ROLL)
                                  │
                                  ▼
                         [knitting_store] (ROLL, RACKNO, RACKLOCATION)
                                  │
                                  ▼
                         [dyeing_batch_card] (BCMTID, ROLL)
                                  │
                                  ▼
                         [dyeing_batch_split] (ORIGINAL_BCMTID -> CARD_A, CARD_B)
```

---

## 5. Module-by-Module Breakdown

### 5.1 Authentication & User Management
* **Key Files:** [login.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/login.php), [loginPOST.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/loginPOST.php), [user_management.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/user_management.php), [create_user_ajax.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/create_user_ajax.php), [restore_user_ajax.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/restore_user_ajax.php).
* **Role/Responsibility:** Validates user credentials against `users` table, manages user creation, updates password hashes, and enforces session timeouts.
* **Database Tables:** `users`, `knitting_operator`.
* **Communication Format:** Standard POST form submit on login; JSON payload via AJAX for user management actions.

### 5.2 Knitting Program (Planning)
* **Key Files:** [knitting_program.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_program.php), [knitting_program_form.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_program_form.php), [save_knitting_program.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/save_knitting_program.php), [ajax_save_knitting_program.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/ajax_save_knitting_program.php), `knitting_program_status.php`.
* **Role/Responsibility:** Creation and tracking of master knitting orders (`PROGRAM_NO`). Configures fabric structure, yarn count, machine dia/gauge, stitch length, and target weight.
* **Database Tables:** `knitting_program`.
* **Core Logic:** Generates unique `PROGRAM_NO` (10-digit identifier). Tracks order progress by querying total produced weight from `knitting_production`.
* **Communication Format:** AJAX POST submitting serialized form data, returning `{ "success": true, "message": "..." }`.

### 5.3 Knitting Input & Subcontract
* **Key Files:** [knitting_input.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_input.php), [ajaxKnittingInput.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/ajaxKnittingInput.php), [subcontract_input.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/subcontract_input.php), [input_details.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/input_details.php).
* **Role/Responsibility:** Logs raw yarn allocated to a knitting program or receives externally knitted fabric from subcontractors.
* **Database Tables:** `knitting_input`.

### 5.4 Knitting Production & Knit Card
* **Key Files:** [knitting_production.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_production.php), [ajaxKnittingProductionInsert.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/ajaxKnittingProductionInsert.php), [knit_card.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knit_card.php), [knit_card_generate.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knit_card_generate.php), [knit_card_print.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knit_card_print.php).
* **Role/Responsibility:** Floor operators scan/select `KNITCARD` rolls, record actual roll weights (`PQTY`), assign operator ID and shift (A/B/C), and print physical barcode/QR cards.
* **Database Tables:** `knitting_production`, `knit_card`.
* **Business Rules & Calculation:**
  $$\text{Produced Qty} = \sum \text{PQTY where KNITCARD} = \text{roll\_id}$$
  $$\text{Remaining Qty} = \max(\text{ORIGINAL\_QTY} - \text{PRODUCED\_QTY}, 0)$$

### 5.5 Quality Control Inspection
* **Key Files:** [knitting_inspection.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_inspection.php), [knitting_inspection_report.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_inspection_report.php), [ajaxK_test_inspection_Report.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/ajaxK_test_inspection_Report.php).
* **Role/Responsibility:** QC inspectors evaluate fabric rolls for 16 distinct defect types, calculate penalty points, and assign roll grades.
* **Database Tables:** `knitting_inspection`.
* **Business Rule - Grading Logic:**
  * **Total Defect Points Calculation:**
    $$\text{Total Points} = \sum_{i=1}^{16} \text{Penalty Points}_i$$
  * **Grade & QC Status Rules:**
    * If $\text{Total Points} \le 10 \implies$ **Grade A** (`Passed`)
    * If $10 < \text{Total Points} \le 25 \implies$ **Grade B** (`Passed`)
    * If $\text{Total Points} > 25 \implies$ **Reject** (`Failed`)

### 5.6 Store & Warehouse Rack Management
* **Key Files:** [knitting_store.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_store.php), [ajaxKnittingStore_Insert.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/ajaxKnittingStore_Insert.php), [knitting_rack_transfer.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_rack_transfer.php), [ajaxKnitting_rack_transfer.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/ajaxKnitting_rack_transfer.php).
* **Role/Responsibility:** Stores inspected grey fabric rolls into warehouse locations with specified `RACKNO` and `RACKLOCATION` (e.g., Rack `20`, Cell `A1`). Handles inter-rack roll movement.
* **Database Tables:** `knitting_store`.

### 5.7 Dyeing Batch Card & Batch Splitting
* **Key Files:** [dyeing_batch_card.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/dyeing_batch_card.php), [ajaxDyeing_batch_card_Insert.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/ajaxDyeing_batch_card_Insert.php), [dyeing_batch_split.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/dyeing_batch_split.php), [ajaxDyeing_batch_split.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/ajaxDyeing_batch_split.php).
* **Role/Responsibility:** Groups multiple inspected grey rolls from `knitting_store` into a unified Dyeing Batch Master ID (`BCMTID`) for dyeing house dispatch. Allows splitting batches into sub-cards (`CARD_A`, `CARD_B`).
* **Database Tables:** `dyeing_batch_card`, `dyeing_batch_split`.

### 5.8 Reporting & Master Uploads
* **Key Files:** [report.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/report.php), [upload.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/upload.php), [uploadMP.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/uploadMP.php).
* **Role/Responsibility:** Consolidates analytics across production, inspection, store, and dyeing modules. Imports bulk Excel spreadsheets into MySQL tables using PhpSpreadsheet.

---

## 6. Authentication & Authorization

### 6.1 User Roles & Access Hierarchy
1. **Admin / Super User (`admin`, `main user`):** Full access to user management, program creation, production overrides, storage deletion, and report exports.
2. **Operator (`knitting_operator`):** Access restricted to [knitting_production.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_production.php) for roll weight entry and card printing.
3. **QC Inspector:** Access to [knitting_inspection.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_inspection.php) for defect point scoring and roll grading.
4. **Store / Warehouse User:** Access to [knitting_store.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_store.php) and [knitting_rack_transfer.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/knitting_rack_transfer.php).
5. **TV Wallboard User (`tv`):** Read-only dashboard view exempt from session timeouts for real-time floor display monitors.

### 6.2 Session Management
* Session initialized via `session_start()` in [config.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/config.php).
* Checks `$_SESSION['expire_time']`. If expired:
  * AJAX requests receive HTTP `401 Unauthorized` with text `'SESSION_EXPIRED'`.
  * Standard web pages redirect to `login.php`.
* `auto_logout.js` is automatically injected into HTML responses before `</body>` using PHP output buffering (`ob_start`).

---

## 7. API / AJAX Endpoints List

Below is a complete registry of all server-side AJAX endpoints handling asynchronous client requests:

| Endpoint Filename | HTTP Method | Input Parameters | Output / Response Format | Function & Description |
| :--- | :--- | :--- | :--- | :--- |
| `ajax_save_knitting_program.php` | POST | `PROGRAM_NO`, `PO_NUMBER`, `SONO`, `BUYER`, `STYLE`, `QTY`, `FGSM`, etc. | `JSON: {success: bool, message: str}` | Saves or updates a master knitting program record |
| `knitting_production.php?action=get_roll` | GET | `knitcard` or `roll` | `JSON: {success: bool, data: obj}` | Fetches roll specs and calculates original vs. produced vs. remaining qty |
| `knitting_production.php?action=get_operator` | GET | `operator_id` | `JSON: {success: bool, data: obj}` | Verifies operator ID against `knitting_operator` table |
| `ajaxKnittingProductionInsert.php` | POST | `ROLL`, `PO_NUMBER`, `PQTY`, `SHIFT`, `OPERATOR_ID`, `MCNO`, etc. | `JSON: {success: bool, message: str}` | Records a new production roll entry into `knitting_production` |
| `ajaxKnittingProduction.php` | GET/POST | `roll` or `po_number` | `JSON / HTML Table` | Searches production records for display |
| `ajaxKnittingInput.php` | GET/POST | `po_number` | `JSON` | Returns yarn input details for a program |
| `ajaxK_test_inspection_Report.php` | GET | `from_date`, `to_date`, `buyer` | `JSON / HTML Table` | Generates QC inspection summary report |
| `ajaxKnittingInspection_Report.php` | GET/POST| Filter criteria | `JSON / HTML Table` | Fetches filtered inspection logs |
| `ajaxKnittingStore_Insert.php` | POST | `ROLL`, `RACKNO`, `RACKLOCATION`, `QTY`, etc. | `JSON: {success: bool}` | Stores inspected roll into `knitting_store` with rack location |
| `ajaxKnitting_rack_transfer.php` | POST | `ROLL`, `NEW_RACKNO`, `NEW_RACKLOCATION` | `JSON: {success: bool}` | Updates rack location for an existing roll in store |
| `ajaxKnitting_store.php` | GET | `roll` | `JSON` | Searches stored roll details by barcode |
| `ajaxDyeing_batch_card_Insert.php` | POST | `BCMTID`, `ROLL`, `BUYER`, `COLOR`, `QTY`, etc. | `JSON: {success: bool}` | Groups selected stored rolls into a new Dyeing Batch |
| `ajaxDyeing_batch_split.php` | POST | `ORIGINAL_BCMTID`, `QTY_A`, `QTY_B`, `ROLL_A_COUNT`, `ROLL_B_COUNT` | `JSON: {success: bool}` | Records a dyeing batch split into `dyeing_batch_split` |
| `fetch_card_details.php` | GET | `card_id` | `JSON` | Fetches master specs for a knit card or batch card |
| `create_user_ajax.php` | POST | `USER_NAME`, `USER_ID`, `email`, `password` | `JSON: {success: bool}` | Creates a new system user |
| `restore_user_ajax.php` | POST | `user_id` | `JSON: {success: bool}` | Resets user credentials |
| `session_status.php` | GET | None | `JSON: {status: 'active' \| 'expired'}` | Endpoint queried by JS to verify session status |

---

## 8. Known Issues & Architectural Observations

### 8.1 Security Risks & Vulnerabilities
1. **SQL Injection Vulnerabilities:** Several legacy AJAX handlers construct queries using raw string concatenation (e.g., `$q = "SELECT * FROM knit_card WHERE KNITCARD = '$roll'"`). While newer endpoints (`knitting_production.php`) use `mysqli_prepare`, older files require refactoring to parameterized queries.
2. **Weak Password Hashing:** User passwords in the `users` table are stored using MD5 hashes (`md5($password)`). MD5 is cryptographically broken. Passwords should be upgraded to `password_hash()` (bcrypt / Argon2).
3. **Hardcoded Database Credentials:** Database credentials (`hostname`, `username`, `password`, `databaseName`) are hardcoded directly in [config.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/config.php) instead of loading from environment variables (`.env`).
4. **Missing CSRF Protection:** Forms and AJAX POST endpoints lack Anti-CSRF tokens, leaving state-changing actions vulnerable to Cross-Site Request Forgery.

### 8.2 Code Duplication & Inconsistencies
1. **Repository Duplication:** A redundant nested subfolder `fab_line_-main/fab_line_-main/` exists inside the main root, containing duplicate source files.
2. **Data Type Mismatches:** Attributes such as `ROLL`, `QTY`, `FGSM`, `FDIA` are defined as `VARCHAR(50)` / `VARCHAR(100)` in `knitting_store` and `dyeing_batch_card`, but stored as `DECIMAL(10,2)` or `BIGINT(20)` in `knitting_production` and `knit_card`. Standardizing column types across tables will optimize index performance.

---

## 9. Config & Environment Architecture (`config.php`)

The application configuration is centralized in [config.php](file:///C:/Users/Bayzid/fab_line/fab_line_-main/config.php). The operational architecture includes:

```php
// 1. Session Management
session_start();
if (isset($_SESSION['expire_time'])) {
    if (!(isset($_SESSION['username']) && strcasecmp($_SESSION['username'], 'tv') === 0)) {
        if (time() > $_SESSION['expire_time']) {
            session_unset();
            session_destroy();
            // Returns HTTP 401 for AJAX or redirects standard browser traffic
        }
    }
}

// 2. Output Buffering & Auto Logout Script Injection
// Automatically injects <script src="auto_logout.js"></script> before </body> for HTML responses

// 3. Database Connection Parameters Structure
$hostname = "localhost";
$username = "root";
$password = "...";
$databaseName = "knittingdb";

$db = mysqli_connect($hostname, $username, $password, $databaseName);
mysqli_set_charset($db, 'utf8mb4');

// 4. Global Application Base URL Definition
define('APP_BASE_URL', "http://" . $host . $scriptDir);
```
