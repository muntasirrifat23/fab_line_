-- =====================================================================
-- KNITTING PROGRAM
-- For database: knittingdb (XAMPP / PHP project)
-- Naming convention matched to existing tables (knitting_input, users):
--   snake_case table & column names, `id` as primary key
-- =====================================================================

CREATE TABLE knitting_program (
    id INT NOT NULL AUTO_INCREMENT,
    program_date DATE NOT NULL,
    mc_no VARCHAR(50) NOT NULL,
    mc_dia VARCHAR(20),
    mc_gauge VARCHAR(20),
    finish_dia VARCHAR(20),
    open_tube VARCHAR(10),
    buyer VARCHAR(100),
    supplier VARCHAR(100),
    booking_no VARCHAR(50),
    style_no VARCHAR(100),
    so_no VARCHAR(50),
    so_item VARCHAR(20),
    shipment_date DATE,
    tna_start DATE,
    tna_end DATE,
    yarn_type VARCHAR(100),
    yarn_count VARCHAR(50),
    lot_no VARCHAR(150),
    fabrics_type VARCHAR(100),
    grey_gsm VARCHAR(20),
    finish_gsm VARCHAR(20),
    sl_vdq DECIMAL(5,2),
    colour VARCHAR(100),
    req_qty DECIMAL(12,3),
    previous_knit DECIMAL(12,3) DEFAULT 0,
    a_shift INT NOT NULL DEFAULT 0,
    b_shift INT NOT NULL DEFAULT 0,
    c_shift INT NOT NULL DEFAULT 0,
    total INT NOT NULL DEFAULT 0,
    balance INT NOT NULL DEFAULT 0,
    remarks VARCHAR(255),
    card_generated TINYINT(1) NOT NULL DEFAULT 0,   -- flags whether a Knit Card has been created from this row
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- ---------------------------------------------------------------------
-- Sample data
-- Rows 1-3: mirrors the real SAP export (Buyer: HEMA, Supplier: KARIM,
-- Booking 230043287, Style 236860 - 3 colour variants of one booking)
-- Row 4: mirrors the handwritten Production Card (Buyer: Next, Purbani Fab.)
-- Rows 5-8: additional realistic variety for testing the Knit Card flow
-- ---------------------------------------------------------------------

INSERT INTO knitting_program
(program_date, mc_no, mc_dia, mc_gauge, finish_dia, open_tube, buyer, supplier, booking_no, style_no,
 so_no, so_item, shipment_date, tna_start, tna_end, yarn_type, yarn_count, lot_no, fabrics_type,
 grey_gsm, finish_gsm, sl_vdq, colour, req_qty, previous_knit,
 a_shift, b_shift, c_shift, total, balance, remarks, card_generated)
VALUES
('2026-04-01', '87', '34X24', '28', '68', 'O', 'HEMA', 'KARIM', '230043287', '236860',
 '4160027259', '10', '2026-11-30', '2026-06-01', '2026-11-30', 'CB CMPT YD', '30/1 CB CMPT YD',
 'S.WASH=26216404 / PEACOAT=26216502', 'SJ', '-', '150', 2.75, 'RED', 3097.198, 0,
 0, 0, 0, 0, 3097, 'New booking, awaiting first knit', 0),

('2026-04-01', '87', '34X24', '28', '68', 'O', 'HEMA', 'KARIM', '230043287', '236860',
 '4160027259', '20', '2026-11-30', '2026-06-01', '2026-11-30', 'CB CMPT YD', '30/1 CB CMPT YD',
 'S.WASH=26216403 / PEACOAT=26216503', 'SJ', '-', '150', 2.75, 'BLUE', 538.000, 0,
 0, 0, 0, 0, 538, NULL, 0),

('2026-04-02', '87', '34X24', '28', '72', 'O', 'HEMA', 'KARIM', '230043287', '236860',
 '4160027259', '30', '2026-11-30', '2026-06-01', '2026-10-30', 'CB CMPT YD', '30/1 CB CMPT YD',
 'S.WASH=26216403 / PEACOAT=26216503', 'SJ', '-', '150', 2.75, 'GREEN', 2753.307, 0,
 0, 0, 0, 0, 2753, NULL, 0),

('2026-06-09', '76', '36', '20', '72', 'T', 'NEXT', 'KARIM SPINNING MILLS', '2347160', 'Y3095A',
 NULL, NULL, NULL, '2026-06-09', NULL, '20/1 COMBED SLUB', '20/1 CB Slub',
 'KARIM-614.5', 'S/J-Slub', '-', '200', 3.05, '18-5206 TEX', 161.000, 0,
 0, 0, 0, 0, 161, 'Handwritten production card - Purbani Fabrics Ltd, Knit section', 1),

('2026-04-05', '92', '30X20', '24', '64', 'O', 'ZARA', 'KARIM', '230044110', '241200',
 '4160028010', '10', '2026-12-15', '2026-06-10', '2026-11-20', 'PC CMPT YD', '24/1 PC CMPT YD',
 'S.WASH=26217001', 'RIB 1X1', '-', '180', 2.60, 'BLACK', 1850.500, 0,
 0, 0, 0, 0, 1850, NULL, 0),

('2026-04-06', '65', '34X28', '28', '70', 'T', 'GAP', 'DESH YARN', '230044200', '241500',
 '4160028100', '10', '2026-12-20', '2026-06-12', '2026-12-01', 'CVC YD', '20/1 CVC YD',
 'CVC-2026-070', 'INTERLOCK', '-', '220', 3.10, 'NAVY', 2200.000, 0,
 0, 0, 0, 0, 2200, NULL, 0),

('2026-04-08', '18', '30X24', '24', '66', 'O', 'HEMA', 'KARIM', '230043290', '236900',
 '4160027300', '10', '2026-12-01', '2026-06-05', '2026-11-25', 'CB CMPT YD', '32/1 CB CMPT YD',
 'S.WASH=26216600', 'SJ', '-', '145', 2.70, 'WHITE', 3400.000, 0,
 0, 0, 0, 0, 3400, NULL, 0),

('2026-04-10', '54', '26X20', '20', '60', 'T', 'PUMA', 'ALIF SPINNING', '230044350', '241800',
 '4160028250', '10', '2026-12-25', '2026-06-15', '2026-12-05', 'PES YD', '30/1 PES YD',
 'PES-2026-014', 'PIQUE', '-', '190', 2.80, 'GREY MELANGE', 1500.000, 0,
 0, 0, 0, 0, 1500, 'Trial order - small quantity', 0);
