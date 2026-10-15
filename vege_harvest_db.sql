-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: vege_harvest_db
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
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` enum('CREATE','UPDATE','DELETE') NOT NULL,
  `table_affected` varchar(100) NOT NULL,
  `record_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_audit_timestamp` (`timestamp`),
  KEY `FK_users_TO_audit_log` (`user_id`),
  CONSTRAINT `FK_users_TO_audit_log` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crop`
--

DROP TABLE IF EXISTS `crop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crop` (
  `crop_id` int(11) NOT NULL AUTO_INCREMENT,
  `crop_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `total_stock_kg` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(20) NOT NULL DEFAULT 'kg',
  PRIMARY KEY (`crop_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crop`
--

LOCK TABLES `crop` WRITE;
/*!40000 ALTER TABLE `crop` DISABLE KEYS */;
INSERT INTO `crop` VALUES (1,'Tomato','Fruit Vegetable',4.00,'kg'),(2,'Pechay','Vegetable',7.00,'kg');
/*!40000 ALTER TABLE `crop` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `distribution_record`
--

DROP TABLE IF EXISTS `distribution_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `distribution_record` (
  `distribution_id` int(11) NOT NULL AUTO_INCREMENT,
  `harvest_id` int(11) NOT NULL,
  `volunteer_id` int(11) NOT NULL,
  `purok_id` int(11) NOT NULL,
  `recipient_name` varchar(150) DEFAULT NULL,
  `recipient_family` varchar(150) NOT NULL,
  `quantity_kg` decimal(8,2) NOT NULL,
  `distribution_date` date NOT NULL,
  `status` enum('Pending','Distributed','Received') DEFAULT 'Distributed',
  `feedback_notes` text DEFAULT NULL,
  PRIMARY KEY (`distribution_id`),
  KEY `idx_distribution_date` (`distribution_date`),
  KEY `idx_distribution_purok` (`purok_id`),
  KEY `FK_harvest_record_TO_distribution_record` (`harvest_id`),
  KEY `FK_volunteer_TO_distribution_record` (`volunteer_id`),
  CONSTRAINT `FK_harvest_record_TO_distribution_record` FOREIGN KEY (`harvest_id`) REFERENCES `harvest_record` (`harvest_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_purok_TO_distribution_record` FOREIGN KEY (`purok_id`) REFERENCES `purok` (`purok_id`),
  CONSTRAINT `FK_volunteer_TO_distribution_record` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteer` (`volunteer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `distribution_record`
--

LOCK TABLES `distribution_record` WRITE;
/*!40000 ALTER TABLE `distribution_record` DISABLE KEYS */;
INSERT INTO `distribution_record` VALUES (1,3,2,1,'Edsiel L. Ursal','',5.00,'2026-08-27','Distributed',NULL),(2,3,2,1,'Edsiel L. Ursal','',5.00,'2026-08-27','Distributed',NULL),(3,2,1,5,'Dustine Nathaniel Villaruz','',1.00,'2026-09-16','Distributed',NULL);
/*!40000 ALTER TABLE `distribution_record` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `harvest_record`
--

DROP TABLE IF EXISTS `harvest_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `harvest_record` (
  `harvest_id` int(11) NOT NULL AUTO_INCREMENT,
  `crop_id` int(11) NOT NULL,
  `volunteer_id` int(11) NOT NULL,
  `harvest_date` date NOT NULL,
  `weight_kg` decimal(8,2) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`harvest_id`),
  KEY `idx_harvest_date` (`harvest_date`),
  KEY `idx_harvest_crop` (`crop_id`),
  KEY `FK_volunteer_TO_harvest_record` (`volunteer_id`),
  CONSTRAINT `FK_crop_TO_harvest_record` FOREIGN KEY (`crop_id`) REFERENCES `crop` (`crop_id`),
  CONSTRAINT `FK_volunteer_TO_harvest_record` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteer` (`volunteer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `harvest_record`
--

LOCK TABLES `harvest_record` WRITE;
/*!40000 ALTER TABLE `harvest_record` DISABLE KEYS */;
INSERT INTO `harvest_record` VALUES (1,1,1,'2026-08-25',5.00,'Ok'),(2,1,1,'2026-08-27',5.00,'Good Harvest'),(3,1,2,'2026-08-27',5.00,''),(4,2,2,'2026-08-27',5.00,''),(5,1,2,'2026-08-27',10.00,''),(6,2,5,'2026-09-16',1.00,''),(7,2,5,'2026-09-16',1.00,'');
/*!40000 ALTER TABLE `harvest_record` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purok`
--

DROP TABLE IF EXISTS `purok`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purok` (
  `purok_id` int(11) NOT NULL AUTO_INCREMENT,
  `purok_name` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  PRIMARY KEY (`purok_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purok`
--

LOCK TABLES `purok` WRITE;
/*!40000 ALTER TABLE `purok` DISABLE KEYS */;
INSERT INTO `purok` VALUES (1,'Aquilina','Dakit'),(2,'red','Dakit'),(5,'Rosal','Gairan');
/*!40000 ALTER TABLE `purok` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sensor_logs`
--

DROP TABLE IF EXISTS `sensor_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sensor_logs` (
  `sensor_id` int(11) NOT NULL AUTO_INCREMENT,
  `soil_moisture` float DEFAULT NULL,
  `temperature` float DEFAULT NULL,
  `humidity` float DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`sensor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor_logs`
--

LOCK TABLES `sensor_logs` WRITE;
/*!40000 ALTER TABLE `sensor_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sensor_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `surplus_alert`
--

DROP TABLE IF EXISTS `surplus_alert`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `surplus_alert` (
  `alert_id` int(11) NOT NULL AUTO_INCREMENT,
  `crop_id` int(11) NOT NULL,
  `purok_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `message` varchar(500) NOT NULL,
  `broadcast_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`alert_id`),
  KEY `FK_crop_TO_surplus_alert` (`crop_id`),
  KEY `FK_purok_TO_surplus_alert` (`purok_id`),
  KEY `FK_users_TO_surplus_alert` (`created_by`),
  CONSTRAINT `FK_crop_TO_surplus_alert` FOREIGN KEY (`crop_id`) REFERENCES `crop` (`crop_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_purok_TO_surplus_alert` FOREIGN KEY (`purok_id`) REFERENCES `purok` (`purok_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_users_TO_surplus_alert` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `surplus_alert`
--

LOCK TABLES `surplus_alert` WRITE;
/*!40000 ALTER TABLE `surplus_alert` DISABLE KEYS */;
/*!40000 ALTER TABLE `surplus_alert` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `full_name` varchar(150) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `UQ_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin123','admin','Edsiel L. Ursal','09123456789','2026-08-25 19:21:56'),(2,'Monica123','$2y$10$NhPQ72TsF739l/qP9.BKb.k60vxA5561592pYMiAR.MWFeWERYvzS','admin','Dustine Nathaniel Villaruz','09651366519','2026-09-23 07:57:30'),(3,'JamesD123','$2y$10$M04p2w1/cT/G.KUw8Y4FJ.JD41MHz4plHqsuUEIvVfNmOMVafSKee','admin','James Dave Manguilimotan','09651366589','2026-09-23 08:09:45');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `volunteer`
--

DROP TABLE IF EXISTS `volunteer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `volunteer` (
  `volunteer_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `purok_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `role` enum('Volunteer','Admin') DEFAULT 'Volunteer',
  PRIMARY KEY (`volunteer_id`),
  UNIQUE KEY `UQ_user_id` (`user_id`),
  KEY `idx_volunteer_purok` (`purok_id`),
  CONSTRAINT `FK_purok_TO_volunteer` FOREIGN KEY (`purok_id`) REFERENCES `purok` (`purok_id`) ON DELETE CASCADE,
  CONSTRAINT `FK_users_TO_volunteer` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `volunteer`
--

LOCK TABLES `volunteer` WRITE;
/*!40000 ALTER TABLE `volunteer` DISABLE KEYS */;
INSERT INTO `volunteer` VALUES (1,NULL,2,'Edsiel L. Ursal','09451366589','active','Admin'),(2,2,1,'Dustine Nathaniel Villaruz','09651366519','active','Admin'),(3,NULL,1,'Malinao','564164574','active','Volunteer'),(4,NULL,2,'john mica','12345665522','active','Volunteer'),(5,NULL,5,'Chrisler Malinao','09657851420','active','Volunteer'),(6,3,1,'James Dave Manguilimotan','09651366589','active','Admin');
/*!40000 ALTER TABLE `volunteer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `volunteer_shift`
--

DROP TABLE IF EXISTS `volunteer_shift`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `volunteer_shift` (
  `shift_id` int(11) NOT NULL AUTO_INCREMENT,
  `volunteer_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  PRIMARY KEY (`shift_id`),
  KEY `FK_volunteer_TO_volunteer_shift` (`volunteer_id`),
  CONSTRAINT `FK_volunteer_TO_volunteer_shift` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteer` (`volunteer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `volunteer_shift`
--

LOCK TABLES `volunteer_shift` WRITE;
/*!40000 ALTER TABLE `volunteer_shift` DISABLE KEYS */;
/*!40000 ALTER TABLE `volunteer_shift` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-13 18:53:21
