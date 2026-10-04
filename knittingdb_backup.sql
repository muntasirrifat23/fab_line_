-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: knittingdb
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `date_show_user`
--

DROP TABLE IF EXISTS `date_show_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `date_show_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `date_show_user`
--

LOCK TABLES `date_show_user` WRITE;
/*!40000 ALTER TABLE `date_show_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `date_show_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dyeing_batch_card`
--

DROP TABLE IF EXISTS `dyeing_batch_card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dyeing_batch_card` (
  `DBCTID` int(11) NOT NULL AUTO_INCREMENT,
  `BUDAT` date DEFAULT NULL,
  `BCMTID` varchar(30) DEFAULT NULL,
  `ROLL` varchar(50) NOT NULL,
  `PO_NUMBER` varchar(50) DEFAULT NULL,
  `RACK` varchar(50) DEFAULT NULL,
  `QTY` varchar(50) DEFAULT NULL,
  `SONO` varchar(50) DEFAULT NULL,
  `SHIFT` varchar(50) DEFAULT NULL,
  `BUYER` varchar(100) DEFAULT NULL,
  `STYLE` varchar(100) DEFAULT NULL,
  `COLOR` varchar(100) DEFAULT NULL,
  `MCNO` varchar(100) DEFAULT NULL,
  `MCDIA` varchar(100) DEFAULT NULL,
  `CUSTOMER` varchar(100) DEFAULT NULL,
  `YTYPE` varchar(50) DEFAULT NULL,
  `YCOUNT` varchar(100) DEFAULT NULL,
  `O_T` varchar(20) DEFAULT NULL,
  `SL` varchar(100) DEFAULT NULL,
  `FTYPE` varchar(50) DEFAULT NULL,
  `FGSM` varchar(20) DEFAULT NULL,
  `FDIA` varchar(20) DEFAULT NULL,
  `GGSM` varchar(20) DEFAULT NULL,
  `FEEDER_PLAN` varchar(100) DEFAULT NULL,
  `LOT_NO` varchar(100) DEFAULT NULL,
  `TPOINT` varchar(100) DEFAULT NULL,
  `MCODE` varchar(50) DEFAULT NULL,
  `MDESCRIPTION` varchar(200) DEFAULT NULL,
  `CREATED_DATE` timestamp NOT NULL DEFAULT current_timestamp(),
  `UNAME` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`DBCTID`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dyeing_batch_card`
--

LOCK TABLES `dyeing_batch_card` WRITE;
/*!40000 ALTER TABLE `dyeing_batch_card` DISABLE KEYS */;
INSERT INTO `dyeing_batch_card` VALUES (18,'2026-08-17','4000000001','30000029','PO-10002','B3','100','SONO-20002','B','BUYER-B','STYLE-002','BLUE','K-M/C-002','32','SUP-002','POLYESTER','40/1','TUBE','3.10','PIQUE','210','32','205','PLAN-002','LOT-002','1','MAT-002','Material Description 002','2026-08-17 12:07:55','admin'),(19,'2026-08-17','4000000001','300000010','PO-10001','B3','155','SONO-20001','A','BUYER-A','STYLE-001','RED','K-M/C-001','30','SUP-001','COTTON','30/1','OPEN','2.50','SINGLE JERSEY','180','30','175','PLAN-001','LOT-001','0','MAT-001','Material Description 001','2026-08-17 12:07:55','admin'),(20,'2026-08-17','4000000002','300000009','PO-10002','B3','98','SONO-20002','B','BUYER-B','STYLE-002','BLUE','K-M/C-002','32','SUP-002','POLYESTER','40/1','TUBE','3.10','PIQUE','210','32','205','PLAN-002','LOT-002','1','MAT-002','Material Description 002','2026-08-17 12:08:17','admin'),(21,'2026-08-18','4000000002','300000011','PO-10003','A1','110','SONO-20003','A','BUYER-C','STYLE-003','BLACK','K-M/C-003','34','SUP-003','COTTON','32/1','OPEN','2.75','SINGLE JERSEY','190','34','185','PLAN-003','LOT-003','0','MAT-003','Material Description 003','2026-08-18 04:01:15','admin'),(22,'2026-08-18','4000000002','300000012','PO-10004','A2','145','SONO-20004','B','BUYER-D','STYLE-004','WHITE','K-M/C-004','36','SUP-004','POLYESTER','40/1','TUBE','3.20','PIQUE','210','36','205','PLAN-004','LOT-004','1','MAT-004','Material Description 004','2026-08-18 04:03:20','muntasir'),(23,'2026-08-18','4000000003','300000013','PO-10005','B1','88','SONO-20005','A','BUYER-E','STYLE-005','GREEN','K-M/C-005','38','SUP-005','COTTON COMB','24/1','OPEN','2.90','RIB','175','38','170','PLAN-005','LOT-005','0','MAT-005','Material Description 005','2026-08-18 04:05:11','admin'),(24,'2026-08-18','4000000003','300000014','PO-10006','B2','180','SONO-20006','B','BUYER-F','STYLE-006','NAVY','CIRCULAR-006','40','SUP-006','POLYESTER','40/1','TUBE','3.40','INTERLOCK','200','40','195','PLAN-006','LOT-006','1','MAT-006','Material Description 006','2026-08-18 04:07:45','muntasir'),(25,'2026-08-18','4000000003','300000015','PO-10007','B3','130','SONO-20007','A','BUYER-G','STYLE-007','YELLOW','CIRCULAR-007','42','SUP-007','COTTON','30/1','OPEN','2.60','SINGLE JERSEY','185','42','180','PLAN-007','LOT-007','0','MAT-007','Material Description 007','2026-08-18 04:09:12','admin'),(26,'2026-08-18','4000000003','300000016','PO-10008','C1','100','SONO-20008','B','BUYER-H','STYLE-008','PINK','K-M/C-008','44','SUP-008','COTTON COMB','26/1','TUBE','3.00','PIQUE','210','44','205','PLAN-008','LOT-008','1','MAT-008','Material Description 008','2026-08-18 04:11:30','muntasir'),(27,'2026-08-18','4000000003','300000017','PO-10009','C2','155','SONO-20009','A','BUYER-I','STYLE-009','ORANGE','CIRCULAR-009','46','SUP-009','POLYESTER','34/1','OPEN','2.85','RIB','195','46','190','PLAN-009','LOT-009','0','MAT-009','Material Description 009','2026-08-18 04:13:05','admin'),(32,'2026-08-18','4000000005','300000022','PO-10014','D3','100','SONO-20014','B','BUYER-N','STYLE-014','OLIVE','CIRCULAR-014','56','SUP-014','COTTON COMB','26/1','TUBE','3.10','RIB','195','56','190','PLAN-014','LOT-014','1','MAT-014','Material Description 014','2026-08-18 04:23:07','muntasir'),(33,'2026-08-18','4000000005','300000023','PO-10015','E1','150','SONO-20015','A','BUYER-O','STYLE-015','SKY BLUE','K-M/C-015','58','SUP-015','POLYESTER','36/1','OPEN','2.95','PIQUE','210','58','205','PLAN-015','LOT-015','0','MAT-015','Material Description 015','2026-08-18 04:25:41','admin'),(73,'2026-08-20','4000000006','300000026','PO-10018','A4','400','SONO-20018','B','BUYER-R','STYLE-018','MAGENTA','CIRCULAR-018','64','SUP-018','POLYESTER','40/1','TUBE','3.35','PIQUE','220','64','215','PLAN-018','LOT-018','1','MAT-018','Material Description 018','2026-08-20 05:13:31','admin'),(74,'2026-08-20','4000000006','300000027','PO-10019','B4','500','SONO-20019','A','BUYER-S','STYLE-019','CHARCOAL','K-M/C-019','66','SUP-019','COTTON','32/1','OPEN','2.75','RIB','190','66','185','PLAN-019','LOT-019','0','MAT-019','Material Description 019','2026-08-20 05:13:31','admin'),(75,'2026-08-20','4000000006','300000024','PO-10016','E2','500','SONO-20016','B','BUYER-P','STYLE-016','BEIGE','CIRCULAR-016','60','SUP-016','COTTON','30/1','TUBE','3.30','INTERLOCK','200','60','195','PLAN-016','LOT-016','1','MAT-016','Material Description 016','2026-08-20 05:13:31','admin'),(82,'2026-08-20','4000000004','300000018','PO-10010','C3','120','SONO-20010','B','BUYER-J','STYLE-010','MAROON','CIRCULAR-010','48','SUP-010','COTTON COMB','26/1','TUBE','3.15','SINGLE JERSEY','175','48','170','PLAN-010','LOT-010','1','MAT-010','Material Description 010','2026-08-20 05:17:02','admin'),(83,'2026-08-20','4000000004','300000021','PO-10013','D2','600','SONO-20013','A','BUYER-M','STYLE-013','TEAL','K-M/C-013','54','SUP-013','COTTON','32/1','OPEN','2.70','SINGLE JERSEY','185','54','180','PLAN-013','LOT-013','0','MAT-013','Material Description 013','2026-08-20 05:17:02','admin'),(84,'2026-08-20','4000000004','300000019','PO-10011','C4','140','SONO-20011','A','BUYER-K','STYLE-011','GREY','K-M/C-011','50','SUP-011','COTTON','28/1','OPEN','3.05','INTERLOCK','200','50','195','PLAN-011','LOT-011','0','MAT-011','Material Description 011','2026-08-20 05:17:02','admin'),(85,'2026-08-20','4000000004','300000020','PO-10012','D1','200','SONO-20012','B','BUYER-L','STYLE-012','PURPLE','CIRCULAR-012','52','SUP-012','POLYESTER','40/1','TUBE','3.25','PIQUE','215','52','210','PLAN-012','LOT-012','1','MAT-012','Material Description 012','2026-08-20 05:17:02','admin');
/*!40000 ALTER TABLE `dyeing_batch_card` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dyeing_batch_split`
--

DROP TABLE IF EXISTS `dyeing_batch_split`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dyeing_batch_split` (
  `SPLIT_ID` int(11) NOT NULL AUTO_INCREMENT,
  `ORIGINAL_BCMTID` varchar(30) DEFAULT NULL,
  `CARD_A` varchar(30) DEFAULT NULL,
  `CARD_B` varchar(30) DEFAULT NULL,
  `QTY_A` decimal(12,2) DEFAULT NULL,
  `QTY_B` decimal(12,2) DEFAULT NULL,
  `ROLL_A_COUNT` int(11) DEFAULT NULL,
  `ROLL_B_COUNT` int(11) DEFAULT NULL,
  `UNAME` varchar(100) DEFAULT NULL,
  `CREATED_DATE` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`SPLIT_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dyeing_batch_split`
--

LOCK TABLES `dyeing_batch_split` WRITE;
/*!40000 ALTER TABLE `dyeing_batch_split` DISABLE KEYS */;
/*!40000 ALTER TABLE `dyeing_batch_split` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knit_card`
--

DROP TABLE IF EXISTS `knit_card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knit_card` (
  `KCTID` int(11) NOT NULL AUTO_INCREMENT,
  `KPTID` int(11) NOT NULL,
  `KNITCARD` int(11) NOT NULL,
  `MCNO` varchar(50) DEFAULT NULL,
  `QTY` int(11) NOT NULL,
  `PO_NUMBER` varchar(50) NOT NULL,
  `SONO` varchar(50) DEFAULT NULL,
  `BUYER` varchar(100) DEFAULT NULL,
  `STYLE` varchar(100) DEFAULT NULL,
  `COLOR` varchar(100) DEFAULT NULL,
  `FGSM` varchar(20) DEFAULT NULL,
  `FDIA` varchar(20) DEFAULT NULL,
  `O_T` varchar(20) DEFAULT NULL,
  `FTYPE` varchar(50) DEFAULT NULL,
  `YTYPE` varchar(50) DEFAULT NULL,
  `CUSTOMER` varchar(100) DEFAULT NULL,
  `YCOUNT` varchar(100) DEFAULT NULL,
  `SL` varchar(100) DEFAULT NULL,
  `MCDIA` varchar(20) DEFAULT NULL,
  `GGSM` varchar(100) DEFAULT NULL,
  `FEEDER_PLAN` varchar(100) DEFAULT NULL,
  `LOT` varchar(100) DEFAULT NULL,
  `SHIFT` varchar(100) DEFAULT NULL,
  `YBRAND` varchar(100) DEFAULT NULL,
  `KNIT_MATERIAL_CODE` varchar(50) DEFAULT NULL,
  `KNIT_M_DESCRIPTION` varchar(200) DEFAULT NULL,
  `REMARKS` text DEFAULT NULL,
  `CREATED_DATE` timestamp NOT NULL DEFAULT current_timestamp(),
  `UNAME` varchar(100) DEFAULT NULL,
  `UID` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`KCTID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knit_card`
--

LOCK TABLES `knit_card` WRITE;
/*!40000 ALTER TABLE `knit_card` DISABLE KEYS */;
INSERT INTO `knit_card` VALUES (7,1000000001,200000001,'FLAT-02',500,'23-48326','4160027266','NEXT','457142CM1','B WHITE','250','6787','TUBE','1*1 HF LYC RIB','34/2CB C+N','KTL','40% CB ORG+20D/2','2.65','32*28','250','GO TO KTL','KARIM-618','A',NULL,'RIB|250|23|T|K26007','K|YD|C|100CMBCTN|STONE WASH+PEACOAT',NULL,'2026-08-30 05:43:18','admin',NULL),(8,1000000002,200000002,'FLAT-01',1800,'23-48329','4160027269','NEXT','470298K17','BIRCH AOP','250','69','OP','1*1 HF LYC RIB','40/CTN MCL','KTL','12','22222','11111','33333','3232','12','B','222','RIB|250|69|OP|K26010','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','wdcvs','2026-10-04 08:21:10','siam',NULL);
/*!40000 ALTER TABLE `knit_card` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knitting_input`
--

DROP TABLE IF EXISTS `knitting_input`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knitting_input` (
  `KITID` int(11) NOT NULL AUTO_INCREMENT,
  `BUDAT` date DEFAULT current_timestamp(),
  `PO_NUMBER` varchar(50) DEFAULT NULL,
  `SONO` varchar(50) DEFAULT NULL,
  `BUYER` varchar(50) DEFAULT NULL,
  `STYLE` varchar(50) DEFAULT NULL,
  `COLOR` varchar(50) DEFAULT NULL,
  `CUSTOMER` varchar(50) DEFAULT NULL,
  `QTY` varchar(50) DEFAULT NULL,
  `FINISH_GSM` varchar(50) DEFAULT NULL,
  `FINISH_DIA` varchar(50) DEFAULT NULL,
  `OPEN_TUBE` varchar(50) DEFAULT NULL,
  `FABRICS_TYPE` varchar(50) DEFAULT NULL,
  `YARN_TYPE` varchar(50) DEFAULT NULL,
  `KNIT_MATERIAL_CODE` varchar(100) DEFAULT NULL,
  `KNIT_M_DESCRIPTION` varchar(255) DEFAULT NULL,
  `CBUDAT` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`KITID`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knitting_input`
--

LOCK TABLES `knitting_input` WRITE;
/*!40000 ALTER TABLE `knitting_input` DISABLE KEYS */;
INSERT INTO `knitting_input` VALUES (111,'2026-08-30','23-48320','4160027260','DIM BRAND','LOFEK CL-1','A WATER-AOP','KTL','6257','180','69','OP','2*2 FF LYC RIB','30/CD C+N','RIB|180|44|OP|K26001','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 11:08:06'),(112,'2026-08-30','23-48321','4160027261','DIM BRAND','LOFEK CL-1','M CALCITE','PFL','3259','180','47','OP','2*2 FF LYC RIB','30/CD C+N','RIB|180|47|OP|K26002','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 11:08:06'),(113,'2026-08-30','23-48322','4160027262','DIM BRAND','LOFEK CL-1','A WATER','PYDL','83','220','22','T','1*1 HF LYC RIB','30/CD C+N','RIB|220|22|T|K26003','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 11:08:06'),(114,'2026-08-30','23-48323','4160027263','HIGLO TEX','LOFEB','M CALCITE','PYDL','847','220','22','T','1*1 HF LYC RIB','30/CD C+N','RIB|220|22|T|K26004','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 11:08:06'),(115,'2026-08-30','23-48324','4160027264','HIGLO TEX','LOFEB','C BLUE','PYDL','1603','160','64','OP','FF LYCRA S/J','34/2CB C+N','SJ|160|64|OP|K26005','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 11:08:06'),(116,'2026-08-30','23-48325','4160027265','HIGLO TEX','LOFEB','C BLUE','KTL','3698','160','64','OP','FF LYCRA S/J','34/2CB C+N','SJ|160|64|OP|K26006','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 11:08:06'),(117,'2026-08-30','23-48326','4160027266','NEXT','457142CM1','B WHITE','KTL','28','250','23','T','1*1 HF LYC RIB','34/2CB C+N','RIB|250|23|T|K26007','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 11:08:06'),(118,'2026-08-30','23-48327','4160027267','NEXT','457142CM2','B WHITE','PFL','324','160','73','OP','FF LYC S/J','40/CTN MCL','SJ|160|73|OP|K26008','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 11:08:06'),(119,'2026-08-30','23-48328','4160027268','NEXT','470298K16','BIRCH AOP','PFL','3154','160','73','OP','FF LYC S/J','40/CTN MCL','SJ|160|73|OP|K26009','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 11:08:06'),(120,'2026-08-30','23-48320','4160027260','NEXT','LOFEK CL-1','Red','KTL','2249','250','69','OP','1*1 HF LYC RIB','40/CTN MCL','RIB|250|69|OP|K26010','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 11:08:06'),(121,'2026-10-04','23-48320','4160027260','DIM BRAND','LOFEK CL-1','A WATER-AOP','KTL','6257','180','44','OP','2*2 FF LYC RIB','30/CD C+N','RIB|180|44|OP|K26001','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-10-04 14:18:01'),(122,'2026-10-04','23-48321','4160027261','DIM BRAND','LOFEK CL-1','M CALCITE','PFL','3259','180','47','OP','2*2 FF LYC RIB','30/CD C+N','RIB|180|47|OP|K26002','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-10-04 14:18:01'),(123,'2026-10-04','23-48322','4160027262','DIM BRAND','LOFEK CL-1','A WATER','PYDL','83','220','22','T','1*1 HF LYC RIB','30/CD C+N','RIB|220|22|T|K26003','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-10-04 14:18:01'),(124,'2026-10-04','23-48323','4160027263','HIGLO TEX','LOFEB','M CALCITE','PYDL','847','220','22','T','1*1 HF LYC RIB','30/CD C+N','RIB|220|22|T|K26004','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-10-04 14:18:01'),(125,'2026-10-04','23-48324','4160027264','HIGLO TEX','LOFEB','C BLUE','PYDL','1603','160','64','OP','FF LYCRA S/J','34/2CB C+N','SJ|160|64|OP|K26005','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-10-04 14:18:01'),(126,'2026-10-04','23-48325','4160027265','HIGLO TEX','LOFEB','C BLUE','KTL','3698','160','64','OP','FF LYCRA S/J','34/2CB C+N','SJ|160|64|OP|K26006','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-10-04 14:18:01'),(127,'2026-10-04','23-48326','4160027266','NEXT','457142CM1','B WHITE','KTL','28','250','23','T','1*1 HF LYC RIB','34/2CB C+N','RIB|250|23|T|K26007','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-10-04 14:18:01'),(128,'2026-10-04','23-48327','4160027267','NEXT','457142CM2','B WHITE','PFL','324','160','73','OP','FF LYC S/J','40/CTN MCL','SJ|160|73|OP|K26008','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-10-04 14:18:01'),(129,'2026-10-04','23-48328','4160027268','NEXT','470298K16','BIRCH AOP','PFL','3154','160','73','OP','FF LYC S/J','40/CTN MCL','SJ|160|73|OP|K26009','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-10-04 14:18:01'),(130,'2026-10-04','23-48329','4160027269','NEXT','470298K17','BIRCH AOP','KTL','2249','250','69','OP','1*1 HF LYC RIB','40/CTN MCL','RIB|250|69|OP|K26010','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-10-04 14:18:01');
/*!40000 ALTER TABLE `knitting_input` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knitting_inspection`
--

DROP TABLE IF EXISTS `knitting_inspection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knitting_inspection` (
  `KITID` int(11) NOT NULL AUTO_INCREMENT,
  `BUDAT` date DEFAULT NULL,
  `ROLL` varchar(100) DEFAULT NULL,
  `OQTY` varchar(100) DEFAULT NULL,
  `RQTY` varchar(100) DEFAULT NULL,
  `UQTY` varchar(100) DEFAULT NULL,
  `PO_NUMBER` varchar(100) DEFAULT NULL,
  `QTY` varchar(100) DEFAULT NULL,
  `SONO` varchar(100) DEFAULT NULL,
  `BUYER` varchar(100) DEFAULT NULL,
  `STYLE` varchar(100) DEFAULT NULL,
  `COLOR` varchar(100) DEFAULT NULL,
  `MCNO` varchar(50) DEFAULT NULL,
  `MC_DIA` varchar(50) DEFAULT NULL,
  `CUSTOMER` varchar(100) DEFAULT NULL,
  `SHIFT` varchar(100) DEFAULT NULL,
  `YTYPE` text DEFAULT NULL,
  `YCOUNT` varchar(100) DEFAULT NULL,
  `FTYPE` text DEFAULT NULL,
  `FGSM` varchar(50) DEFAULT NULL,
  `FDIA` varchar(50) DEFAULT NULL,
  `O_T` varchar(20) DEFAULT NULL,
  `SL` decimal(10,2) DEFAULT 0.00,
  `GGSM` varchar(100) DEFAULT NULL,
  `FPLAN` varchar(100) DEFAULT NULL,
  `LOTNO` varchar(100) DEFAULT NULL,
  `MATERIAL_CODE` varchar(50) DEFAULT NULL,
  `M_DES` varchar(200) DEFAULT NULL,
  `TT` varchar(50) DEFAULT NULL,
  `PATTA` varchar(50) DEFAULT NULL,
  `SLUB` varchar(50) DEFAULT NULL,
  `YC_SPOT` varchar(50) DEFAULT NULL,
  `OILSPOT` varchar(50) DEFAULT NULL,
  `FF` varchar(50) DEFAULT NULL,
  `SEEDS` varchar(50) DEFAULT NULL,
  `MSTITCH` varchar(50) DEFAULT NULL,
  `SINKERMARK` varchar(50) DEFAULT NULL,
  `NEEDLEMARK` varchar(50) DEFAULT NULL,
  `LYCOUT` varchar(50) DEFAULT NULL,
  `OILLINE` varchar(50) DEFAULT NULL,
  `HOLE` varchar(50) DEFAULT NULL,
  `LOOP` varchar(50) DEFAULT NULL,
  `SETUP` varchar(50) DEFAULT NULL,
  `CMARK` varchar(50) DEFAULT NULL,
  `TPOINT` varchar(50) DEFAULT NULL,
  `QC_GRADE` varchar(50) DEFAULT NULL,
  `QC_STATUS` varchar(50) DEFAULT NULL,
  `UNAME` varchar(100) DEFAULT NULL,
  `UID` varchar(100) DEFAULT NULL,
  `P_CREATED` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`KITID`),
  UNIQUE KEY `uniq_roll` (`ROLL`),
  KEY `idx_roll` (`ROLL`),
  KEY `idx_po_number` (`PO_NUMBER`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knitting_inspection`
--

LOCK TABLES `knitting_inspection` WRITE;
/*!40000 ALTER TABLE `knitting_inspection` DISABLE KEYS */;
/*!40000 ALTER TABLE `knitting_inspection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knitting_operator`
--

DROP TABLE IF EXISTS `knitting_operator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knitting_operator` (
  `KOTID` int(11) NOT NULL AUTO_INCREMENT,
  `OPERATOR_ID` varchar(20) NOT NULL,
  `OPERATOR_NAME` varchar(100) NOT NULL,
  `OPERATOR_EMAIL` varchar(100) DEFAULT NULL,
  `OPERATOR_PASSWORD` varchar(255) NOT NULL,
  `CREATED` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`KOTID`),
  UNIQUE KEY `UK_OPERATOR_ID` (`OPERATOR_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knitting_operator`
--

LOCK TABLES `knitting_operator` WRITE;
/*!40000 ALTER TABLE `knitting_operator` DISABLE KEYS */;
INSERT INTO `knitting_operator` VALUES (1,'OP01','Md. Rahim','rahim@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-17 09:47:02'),(2,'OP02','Md. Karim','karin@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-17 09:47:02'),(3,'OP03','Md. Hasan','hasan@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-17 09:47:02'),(4,'OP04','Md. Sohel','sohel@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-17 09:47:02'),(5,'OP05','Md. Rony','rony@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-17 09:47:02'),(6,'rifat001','muntasir','muntasir@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-17 09:50:59');
/*!40000 ALTER TABLE `knitting_operator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knitting_operator_qc`
--

DROP TABLE IF EXISTS `knitting_operator_qc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knitting_operator_qc` (
  `KQCTID` int(11) NOT NULL AUTO_INCREMENT,
  `KNITTING_QC_ID` varchar(20) NOT NULL,
  `KNITTING_QC_NAME` varchar(100) NOT NULL,
  `KNITTING_QC_EMAIL` varchar(100) DEFAULT NULL,
  `KNITTING_QC_PASSWORD` varchar(255) NOT NULL,
  `CREATED` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`KQCTID`),
  UNIQUE KEY `UNQ_KNITTING_QC_ID` (`KNITTING_QC_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knitting_operator_qc`
--

LOCK TABLES `knitting_operator_qc` WRITE;
/*!40000 ALTER TABLE `knitting_operator_qc` DISABLE KEYS */;
INSERT INTO `knitting_operator_qc` VALUES (1,'QC01','Md. Jamil','qc01@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-25 03:10:49'),(2,'QC02','Md. Rafiq','qc02@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-25 03:10:49'),(3,'QC03','Md. Shohag','qc03@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-25 03:10:49'),(4,'QC04','Md. Babul','qc04@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-25 03:10:49'),(5,'QC05','Md. Titu','qc05@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-25 03:10:49'),(6,'QC06','Md. Sumon','qc06@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-25 03:10:49'),(7,'QC07','Md. Jewel','qc07@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-25 03:10:49'),(8,'QC08','Md. Milon','qc08@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-25 03:10:49'),(9,'QC09','Md. Rasel','qc09@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-25 03:10:49'),(10,'QC10','Md. Sabbir','qc10@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-25 03:10:49');
/*!40000 ALTER TABLE `knitting_operator_qc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knitting_production`
--

DROP TABLE IF EXISTS `knitting_production`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knitting_production` (
  `PID` int(11) NOT NULL AUTO_INCREMENT,
  `BUDAT` date DEFAULT NULL,
  `ROLL` varchar(100) DEFAULT NULL,
  `KNITCARD` varchar(100) DEFAULT NULL,
  `PO_NUMBER` varchar(100) DEFAULT NULL,
  `PQTY` decimal(10,2) DEFAULT 0.00,
  `SONO` varchar(100) DEFAULT NULL,
  `BUYER` varchar(100) DEFAULT NULL,
  `STYLE` varchar(100) DEFAULT NULL,
  `COLOR` varchar(100) DEFAULT NULL,
  `MCNO` varchar(50) DEFAULT NULL,
  `MC_DIA` varchar(50) DEFAULT NULL,
  `CUSTOMER` varchar(100) DEFAULT NULL,
  `SHIFT` varchar(100) DEFAULT NULL,
  `YARN_TYPE` text DEFAULT NULL,
  `YARN_COUNT` varchar(100) DEFAULT NULL,
  `FABRICS_TYPE` text DEFAULT NULL,
  `FINISH_GSM` varchar(50) DEFAULT NULL,
  `FINISH_DIA` varchar(50) DEFAULT NULL,
  `OPEN_TUBE` varchar(20) DEFAULT NULL,
  `SL_VDQ` decimal(10,2) DEFAULT 0.00,
  `GRAY_GSM` varchar(100) DEFAULT NULL,
  `FEEDER_PLAN` varchar(100) DEFAULT NULL,
  `LOT_NO` varchar(100) DEFAULT NULL,
  `KNIT_MATERIAL_CODE` varchar(50) DEFAULT NULL,
  `KNIT_M_DES` varchar(200) DEFAULT NULL,
  `UNAME` varchar(100) DEFAULT NULL,
  `UID` varchar(100) DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`PID`),
  KEY `ROLL` (`ROLL`),
  KEY `PO_NUMBER` (`PO_NUMBER`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knitting_production`
--

LOCK TABLES `knitting_production` WRITE;
/*!40000 ALTER TABLE `knitting_production` DISABLE KEYS */;
INSERT INTO `knitting_production` VALUES (61,'2026-08-30','3000000001','200000001','23-48326',400.00,'4160027266','NEXT','457142CM1','B WHITE','FLAT-02','32*28','KTL','A','34/2CB C+N','40% CB ORG+20D/2','1*1 HF LYC RIB','250','6787','TUBE',2.65,'250','GO TO KTL','KARIM-618','RIB|250|23|T|K26007','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','Md. Karim','OP02','2026-08-30 05:58:14');
/*!40000 ALTER TABLE `knitting_production` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knitting_program`
--

DROP TABLE IF EXISTS `knitting_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knitting_program` (
  `KPTID` int(11) NOT NULL AUTO_INCREMENT,
  `PROGRAM_NO` bigint(20) NOT NULL,
  `PO_NUMBER` varchar(50) NOT NULL,
  `SONO` varchar(50) DEFAULT NULL,
  `BUYER` varchar(100) DEFAULT NULL,
  `STYLE` varchar(100) DEFAULT NULL,
  `COLOR` varchar(100) DEFAULT NULL,
  `QTY` varchar(100) DEFAULT NULL,
  `FGSM` varchar(20) DEFAULT NULL,
  `FDIA` varchar(20) DEFAULT NULL,
  `O_T` varchar(20) DEFAULT NULL,
  `FTYPE` varchar(50) DEFAULT NULL,
  `YTYPE` varchar(50) DEFAULT NULL,
  `CUSTOMER` varchar(100) DEFAULT NULL,
  `YCOUNT` varchar(100) DEFAULT NULL,
  `SL` varchar(100) DEFAULT NULL,
  `MCDIA` varchar(20) DEFAULT NULL,
  `GGSM` varchar(100) DEFAULT NULL,
  `FEEDER_PLAN` varchar(100) DEFAULT NULL,
  `LOT` varchar(100) DEFAULT NULL,
  `SHIFT` varchar(100) DEFAULT NULL,
  `YBRAND` varchar(100) DEFAULT NULL,
  `KNIT_MATERIAL_CODE` varchar(50) DEFAULT NULL,
  `KNIT_M_DESCRIPTION` varchar(200) DEFAULT NULL,
  `CREATED_DATE` timestamp NOT NULL DEFAULT current_timestamp(),
  `UNAME` varchar(100) DEFAULT NULL,
  `REMARKS` text DEFAULT NULL,
  PRIMARY KEY (`KPTID`),
  KEY `idx_kp_program_no` (`PROGRAM_NO`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knitting_program`
--

LOCK TABLES `knitting_program` WRITE;
/*!40000 ALTER TABLE `knitting_program` DISABLE KEYS */;
INSERT INTO `knitting_program` VALUES (79,1000000001,'23-48326','4160027266','NEXT','457142CM1','B WHITE','12','250','6787','TUBE','1*1 HF LYC RIB','34/2CB C+N','KTL','40% CB ORG+20D/2','2.65','32*28','0','GO TO KTL','KARIM-618','A','Addidas','RIB|250|23|T|K26007','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 05:37:57','admin',NULL),(86,1000000002,'23-48329','4160027269','NEXT','470298K17','BIRCH AOP','2000','250','69','OP','1*1 HF LYC RIB','40/CTN MCL','KTL','12','22222','11111','33333','3232','12','B','222','RIB|250|69|OP|K26010','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-10-04 08:19:39','siam','wdcvs');
/*!40000 ALTER TABLE `knitting_program` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knitting_store`
--

DROP TABLE IF EXISTS `knitting_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `knitting_store` (
  `KSTID` int(11) NOT NULL AUTO_INCREMENT,
  `BUDAT` date DEFAULT NULL,
  `RACKNO` varchar(50) DEFAULT NULL,
  `RACKLOCATION` varchar(100) DEFAULT NULL,
  `ROLL` varchar(50) NOT NULL,
  `PO_NUMBER` varchar(50) NOT NULL,
  `QTY` varchar(50) NOT NULL,
  `SONO` varchar(50) DEFAULT NULL,
  `SHIFT` varchar(50) NOT NULL,
  `BUYER` varchar(100) DEFAULT NULL,
  `STYLE` varchar(100) DEFAULT NULL,
  `COLOR` varchar(100) DEFAULT NULL,
  `MCNO` varchar(100) DEFAULT NULL,
  `MCDIA` varchar(100) DEFAULT NULL,
  `CUSTOMER` varchar(100) DEFAULT NULL,
  `YTYPE` varchar(50) DEFAULT NULL,
  `YCOUNT` varchar(100) DEFAULT NULL,
  `O_T` varchar(20) DEFAULT NULL,
  `SL` varchar(100) DEFAULT NULL,
  `FTYPE` varchar(50) DEFAULT NULL,
  `FGSM` varchar(20) DEFAULT NULL,
  `FDIA` varchar(20) DEFAULT NULL,
  `GGSM` varchar(20) DEFAULT NULL,
  `FEEDER_PLAN` varchar(100) DEFAULT NULL,
  `LOT_NO` varchar(100) DEFAULT NULL,
  `TPOINT` varchar(100) DEFAULT NULL,
  `MCODE` varchar(50) DEFAULT NULL,
  `MDESCRIPTION` varchar(200) DEFAULT NULL,
  `CREATED_DATE` timestamp NOT NULL DEFAULT current_timestamp(),
  `UNAME` varchar(100) DEFAULT NULL,
  `UID` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`KSTID`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knitting_store`
--

LOCK TABLES `knitting_store` WRITE;
/*!40000 ALTER TABLE `knitting_store` DISABLE KEYS */;
INSERT INTO `knitting_store` VALUES (56,'2026-08-30','12','C3','3000000001-A','23-48326','1','4160027266','A','NEXT','457142CM1','B WHITE','FLAT-02','32*28','KTL','34/2CB C+N','40% CB ORG+20D/2','TUBE','2.65','1*1 HF LYC RIB','250','6787','250','GO TO KTL','KARIM-618','0','RIB|250|23|T|K26007','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 06:28:38','main user','admin'),(57,'2026-08-30','12','C3','3000000001-B','23-48326','3','4160027266','A','NEXT','457142CM1','B WHITE','FLAT-02','32*28','KTL','34/2CB C+N','40% CB ORG+20D/2','TUBE','2.65','1*1 HF LYC RIB','250','6787','250','GO TO KTL','KARIM-618','0','RIB|250|23|T|K26007','K|YD|C|100CMBCTN|STONE WASH+PEACOAT','2026-08-30 06:50:07',NULL,NULL);
/*!40000 ALTER TABLE `knitting_store` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mcno`
--

DROP TABLE IF EXISTS `mcno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mcno` (
  `MCNOID` int(11) NOT NULL AUTO_INCREMENT,
  `MCNO` varchar(50) DEFAULT NULL,
  `CBUDAT` date DEFAULT curdate(),
  PRIMARY KEY (`MCNOID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mcno`
--

LOCK TABLES `mcno` WRITE;
/*!40000 ALTER TABLE `mcno` DISABLE KEYS */;
INSERT INTO `mcno` VALUES (1,'FLAT-01','2026-07-19'),(2,'FLAT-02','2026-07-19'),(3,'FLAT-03','2026-07-19'),(4,'FLAT-04','2026-07-19'),(5,'FLAT-05','2026-07-19'),(6,'K-M/C-001','2026-07-19'),(7,'K-M/C-002','2026-07-19'),(8,'K-M/C-003','2026-07-19'),(9,'K-M/C-004','2026-07-19'),(10,'K-M/C-005','2026-07-19');
/*!40000 ALTER TABLE `mcno` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rack_master`
--

DROP TABLE IF EXISTS `rack_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rack_master` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `rack_no` varchar(50) NOT NULL,
  `shelf` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `uq_rack_shelf` (`rack_no`,`shelf`)
) ENGINE=InnoDB AUTO_INCREMENT=451 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rack_master`
--

LOCK TABLES `rack_master` WRITE;
/*!40000 ALTER TABLE `rack_master` DISABLE KEYS */;
INSERT INTO `rack_master` VALUES (1,'01','A1',1),(2,'01','A2',1),(3,'01','A3',1),(4,'01','B1',1),(5,'01','B2',1),(6,'01','B3',1),(7,'01','C1',1),(8,'01','C2',1),(9,'01','C3',1),(10,'02','A1',1),(11,'02','A2',1),(12,'02','A3',1),(13,'02','B1',1),(14,'02','B2',1),(15,'02','B3',1),(16,'02','C1',1),(17,'02','C2',1),(18,'02','C3',1),(19,'03','A1',1),(20,'03','A2',1),(21,'03','A3',1),(22,'03','B1',1),(23,'03','B2',1),(24,'03','B3',1),(25,'03','C1',1),(26,'03','C2',1),(27,'03','C3',1),(28,'04','A1',1),(29,'04','A2',1),(30,'04','A3',1),(31,'04','B1',1),(32,'04','B2',1),(33,'04','B3',1),(34,'04','C1',1),(35,'04','C2',1),(36,'04','C3',1),(37,'05','A1',1),(38,'05','A2',1),(39,'05','A3',1),(40,'05','B1',1),(41,'05','B2',1),(42,'05','B3',1),(43,'05','C1',1),(44,'05','C2',1),(45,'05','C3',1),(46,'06','A1',1),(47,'06','A2',1),(48,'06','A3',1),(49,'06','B1',1),(50,'06','B2',1),(51,'06','B3',1),(52,'06','C1',1),(53,'06','C2',1),(54,'06','C3',1),(55,'07','A1',1),(56,'07','A2',1),(57,'07','A3',1),(58,'07','B1',1),(59,'07','B2',1),(60,'07','B3',1),(61,'07','C1',1),(62,'07','C2',1),(63,'07','C3',1),(64,'08','A1',1),(65,'08','A2',1),(66,'08','A3',1),(67,'08','B1',1),(68,'08','B2',1),(69,'08','B3',1),(70,'08','C1',1),(71,'08','C2',1),(72,'08','C3',1),(73,'09','A1',1),(74,'09','A2',1),(75,'09','A3',1),(76,'09','B1',1),(77,'09','B2',1),(78,'09','B3',1),(79,'09','C1',1),(80,'09','C2',1),(81,'09','C3',1),(82,'10','A1',1),(83,'10','A2',1),(84,'10','A3',1),(85,'10','B1',1),(86,'10','B2',1),(87,'10','B3',1),(88,'10','C1',1),(89,'10','C2',1),(90,'10','C3',1),(91,'11','A1',1),(92,'11','A2',1),(93,'11','A3',1),(94,'11','B1',1),(95,'11','B2',1),(96,'11','B3',1),(97,'11','C1',1),(98,'11','C2',1),(99,'11','C3',1),(100,'12','A1',1),(101,'12','A2',1),(102,'12','A3',1),(103,'12','B1',1),(104,'12','B2',1),(105,'12','B3',1),(106,'12','C1',1),(107,'12','C2',1),(108,'12','C3',1),(109,'13','A1',1),(110,'13','A2',1),(111,'13','A3',1),(112,'13','B1',1),(113,'13','B2',1),(114,'13','B3',1),(115,'13','C1',1),(116,'13','C2',1),(117,'13','C3',1),(118,'14','A1',1),(119,'14','A2',1),(120,'14','A3',1),(121,'14','B1',1),(122,'14','B2',1),(123,'14','B3',1),(124,'14','C1',1),(125,'14','C2',1),(126,'14','C3',1),(127,'15','A1',1),(128,'15','A2',1),(129,'15','A3',1),(130,'15','B1',1),(131,'15','B2',1),(132,'15','B3',1),(133,'15','C1',1),(134,'15','C2',1),(135,'15','C3',1),(136,'16','A1',1),(137,'16','A2',1),(138,'16','A3',1),(139,'16','B1',1),(140,'16','B2',1),(141,'16','B3',1),(142,'16','C1',1),(143,'16','C2',1),(144,'16','C3',1),(145,'17','A1',1),(146,'17','A2',1),(147,'17','A3',1),(148,'17','B1',1),(149,'17','B2',1),(150,'17','B3',1),(151,'17','C1',1),(152,'17','C2',1),(153,'17','C3',1),(154,'18','A1',1),(155,'18','A2',1),(156,'18','A3',1),(157,'18','B1',1),(158,'18','B2',1),(159,'18','B3',1),(160,'18','C1',1),(161,'18','C2',1),(162,'18','C3',1),(163,'19','A1',1),(164,'19','A2',1),(165,'19','A3',1),(166,'19','B1',1),(167,'19','B2',1),(168,'19','B3',1),(169,'19','C1',1),(170,'19','C2',1),(171,'19','C3',1),(172,'20','A1',1),(173,'20','A2',1),(174,'20','A3',1),(175,'20','B1',1),(176,'20','B2',1),(177,'20','B3',1),(178,'20','C1',1),(179,'20','C2',1),(180,'20','C3',1),(181,'21','A1',1),(182,'21','A2',1),(183,'21','A3',1),(184,'21','B1',1),(185,'21','B2',1),(186,'21','B3',1),(187,'21','C1',1),(188,'21','C2',1),(189,'21','C3',1),(190,'22','A1',1),(191,'22','A2',1),(192,'22','A3',1),(193,'22','B1',1),(194,'22','B2',1),(195,'22','B3',1),(196,'22','C1',1),(197,'22','C2',1),(198,'22','C3',1),(199,'23','A1',1),(200,'23','A2',1),(201,'23','A3',1),(202,'23','B1',1),(203,'23','B2',1),(204,'23','B3',1),(205,'23','C1',1),(206,'23','C2',1),(207,'23','C3',1),(208,'24','A1',1),(209,'24','A2',1),(210,'24','A3',1),(211,'24','B1',1),(212,'24','B2',1),(213,'24','B3',1),(214,'24','C1',1),(215,'24','C2',1),(216,'24','C3',1),(217,'25','A1',1),(218,'25','A2',1),(219,'25','A3',1),(220,'25','B1',1),(221,'25','B2',1),(222,'25','B3',1),(223,'25','C1',1),(224,'25','C2',1),(225,'25','C3',1),(226,'26','A1',1),(227,'26','A2',1),(228,'26','A3',1),(229,'26','B1',1),(230,'26','B2',1),(231,'26','B3',1),(232,'26','C1',1),(233,'26','C2',1),(234,'26','C3',1),(235,'27','A1',1),(236,'27','A2',1),(237,'27','A3',1),(238,'27','B1',1),(239,'27','B2',1),(240,'27','B3',1),(241,'27','C1',1),(242,'27','C2',1),(243,'27','C3',1),(244,'28','A1',1),(245,'28','A2',1),(246,'28','A3',1),(247,'28','B1',1),(248,'28','B2',1),(249,'28','B3',1),(250,'28','C1',1),(251,'28','C2',1),(252,'28','C3',1),(253,'29','A1',1),(254,'29','A2',1),(255,'29','A3',1),(256,'29','B1',1),(257,'29','B2',1),(258,'29','B3',1),(259,'29','C1',1),(260,'29','C2',1),(261,'29','C3',1),(262,'30','A1',1),(263,'30','A2',1),(264,'30','A3',1),(265,'30','B1',1),(266,'30','B2',1),(267,'30','B3',1),(268,'30','C1',1),(269,'30','C2',1),(270,'30','C3',1),(271,'31','A1',1),(272,'31','A2',1),(273,'31','A3',1),(274,'31','B1',1),(275,'31','B2',1),(276,'31','B3',1),(277,'31','C1',1),(278,'31','C2',1),(279,'31','C3',1),(280,'32','A1',1),(281,'32','A2',1),(282,'32','A3',1),(283,'32','B1',1),(284,'32','B2',1),(285,'32','B3',1),(286,'32','C1',1),(287,'32','C2',1),(288,'32','C3',1),(289,'33','A1',1),(290,'33','A2',1),(291,'33','A3',1),(292,'33','B1',1),(293,'33','B2',1),(294,'33','B3',1),(295,'33','C1',1),(296,'33','C2',1),(297,'33','C3',1),(298,'34','A1',1),(299,'34','A2',1),(300,'34','A3',1),(301,'34','B1',1),(302,'34','B2',1),(303,'34','B3',1),(304,'34','C1',1),(305,'34','C2',1),(306,'34','C3',1),(307,'35','A1',1),(308,'35','A2',1),(309,'35','A3',1),(310,'35','B1',1),(311,'35','B2',1),(312,'35','B3',1),(313,'35','C1',1),(314,'35','C2',1),(315,'35','C3',1),(316,'36','A1',1),(317,'36','A2',1),(318,'36','A3',1),(319,'36','B1',1),(320,'36','B2',1),(321,'36','B3',1),(322,'36','C1',1),(323,'36','C2',1),(324,'36','C3',1),(325,'37','A1',1),(326,'37','A2',1),(327,'37','A3',1),(328,'37','B1',1),(329,'37','B2',1),(330,'37','B3',1),(331,'37','C1',1),(332,'37','C2',1),(333,'37','C3',1),(334,'38','A1',1),(335,'38','A2',1),(336,'38','A3',1),(337,'38','B1',1),(338,'38','B2',1),(339,'38','B3',1),(340,'38','C1',1),(341,'38','C2',1),(342,'38','C3',1),(343,'39','A1',1),(344,'39','A2',1),(345,'39','A3',1),(346,'39','B1',1),(347,'39','B2',1),(348,'39','B3',1),(349,'39','C1',1),(350,'39','C2',1),(351,'39','C3',1),(352,'40','A1',1),(353,'40','A2',1),(354,'40','A3',1),(355,'40','B1',1),(356,'40','B2',1),(357,'40','B3',1),(358,'40','C1',1),(359,'40','C2',1),(360,'40','C3',1),(361,'41','A1',1),(362,'41','A2',1),(363,'41','A3',1),(364,'41','B1',1),(365,'41','B2',1),(366,'41','B3',1),(367,'41','C1',1),(368,'41','C2',1),(369,'41','C3',1),(370,'42','A1',1),(371,'42','A2',1),(372,'42','A3',1),(373,'42','B1',1),(374,'42','B2',1),(375,'42','B3',1),(376,'42','C1',1),(377,'42','C2',1),(378,'42','C3',1),(379,'43','A1',1),(380,'43','A2',1),(381,'43','A3',1),(382,'43','B1',1),(383,'43','B2',1),(384,'43','B3',1),(385,'43','C1',1),(386,'43','C2',1),(387,'43','C3',1),(388,'44','A1',1),(389,'44','A2',1),(390,'44','A3',1),(391,'44','B1',1),(392,'44','B2',1),(393,'44','B3',1),(394,'44','C1',1),(395,'44','C2',1),(396,'44','C3',1),(397,'45','A1',1),(398,'45','A2',1),(399,'45','A3',1),(400,'45','B1',1),(401,'45','B2',1),(402,'45','B3',1),(403,'45','C1',1),(404,'45','C2',1),(405,'45','C3',1),(406,'46','A1',1),(407,'46','A2',1),(408,'46','A3',1),(409,'46','B1',1),(410,'46','B2',1),(411,'46','B3',1),(412,'46','C1',1),(413,'46','C2',1),(414,'46','C3',1),(415,'47','A1',1),(416,'47','A2',1),(417,'47','A3',1),(418,'47','B1',1),(419,'47','B2',1),(420,'47','B3',1),(421,'47','C1',1),(422,'47','C2',1),(423,'47','C3',1),(424,'48','A1',1),(425,'48','A2',1),(426,'48','A3',1),(427,'48','B1',1),(428,'48','B2',1),(429,'48','B3',1),(430,'48','C1',1),(431,'48','C2',1),(432,'48','C3',1),(433,'49','A1',1),(434,'49','A2',1),(435,'49','A3',1),(436,'49','B1',1),(437,'49','B2',1),(438,'49','B3',1),(439,'49','C1',1),(440,'49','C2',1),(441,'49','C3',1),(442,'50','A1',1),(443,'50','A2',1),(444,'50','A3',1),(445,'50','B1',1),(446,'50','B2',1),(447,'50','B3',1),(448,'50','C1',1),(449,'50','C2',1),(450,'50','C3',1);
/*!40000 ALTER TABLE `rack_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rack_transfer_log`
--

DROP TABLE IF EXISTS `rack_transfer_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rack_transfer_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roll` varchar(100) NOT NULL,
  `from_rack` varchar(50) DEFAULT NULL,
  `to_rack` varchar(50) DEFAULT NULL,
  `transfer_by` varchar(100) DEFAULT NULL,
  `transfer_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rtl_roll` (`roll`),
  KEY `idx_rtl_date` (`transfer_date`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rack_transfer_log`
--

LOCK TABLES `rack_transfer_log` WRITE;
/*!40000 ALTER TABLE `rack_transfer_log` DISABLE KEYS */;
INSERT INTO `rack_transfer_log` VALUES (4,'3000000001','34A1','12C3','main user','2026-08-30 12:37:06');
/*!40000 ALTER TABLE `rack_transfer_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `USER_NAME` varchar(10) NOT NULL,
  `USER_ID` varchar(10) NOT NULL DEFAULT '',
  `email` varchar(50) NOT NULL DEFAULT 'sarwar.alam@purbanigroup.com',
  `password` varchar(100) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (5,'abuhena','U01','abuhena@purbanigroip.com','8a032c11a781fcb28673b94fc952411e','2026-08-18 02:51:23'),(9,'main user','admin','admin@rifat.com','7c657cd3d46d8ec8fddb174773d57bb4','2026-08-18 03:21:21'),(65,'PPQ30','PPQ30','sarwar.alam@purbanigroup.com','202cb962ac59075b964b07152d234b70','2026-10-03 11:01:54'),(70,'PPQ34','PPQ34','anis.rahman@purbanigroup.com','24d6e46a16a8fd89f3d44d92e917e9f2','2026-08-18 02:46:21'),(71,'tv','tv','tv@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-18 02:46:21'),(72,'PPQ28','PPQ28','mehedi.pad@purbanigroup.com','82d24206cbe647f19716a30a28c25765','2026-08-18 02:46:21'),(73,'PPQ70','PPQ70','rafiqul.rep@purbanigroup.com','31891c666eb2cceda062f6e07d388dad','2026-08-18 02:46:21'),(74,'F1','F1','F1@purbanigroup.com','898dc2c947cee718e4afd7dfcb2f1a09','2026-08-18 02:46:21'),(78,'PPQ71','PPQ71','ppq71@purbanigroup.com','f899139df5e1059396431415e770c6dd','2026-08-18 02:46:21'),(79,'PPQ57','PPQ57','ppq57@gmail.com','6320831839ab799ee20bdf86d4f19377','2026-08-18 02:46:21'),(80,'41','41','ktl@purbanigroup.com','202cb962ac59075b964b07152d234b70','2026-08-18 02:46:21'),(92,'rifat','rifat','rifat@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-18 02:46:21'),(142,'siam','siam','siambigshot@gmail.com','202cb962ac59075b964b07152d234b70','2026-08-25 04:14:31');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-04 14:56:24
