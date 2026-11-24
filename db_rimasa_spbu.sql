/*
SQLyog Ultimate v13.1.1 (64 bit)
MySQL - 8.0.30 : Database - rimasa_spbu
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


/*Table structure for table `branches` */

DROP TABLE IF EXISTS `branches`;

CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `margin_rate` decimal(6,4) NOT NULL DEFAULT '0.0300',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `branches` */

insert  into `branches`(`id`,`code`,`name`,`margin_rate`,`is_active`,`created_at`,`updated_at`) values 
(1,'TS','Tanjung Sari',0.0300,1,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(2,'KKP','Kakap',0.0300,1,'2026-11-23 08:20:14','2026-11-23 08:20:14');

/*Table structure for table `cash_entries` */

DROP TABLE IF EXISTS `cash_entries`;

CREATE TABLE `cash_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `account_type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_date` date NOT NULL,
  `description` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'shift_omzet',
  `debit` decimal(16,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(16,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_entries_branch_id_foreign` (`branch_id`),
  KEY `cash_entries_created_by_foreign` (`created_by`),
  KEY `cash_entries_account_type_index` (`account_type`),
  KEY `cash_entries_entry_date_index` (`entry_date`),
  CONSTRAINT `cash_entries_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_entries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cash_entries` */

insert  into `cash_entries`(`id`,`branch_id`,`account_type`,`entry_date`,`description`,`category`,`debit`,`credit`,`created_by`,`created_at`,`updated_at`) values 
(1,1,'rek1','2026-11-02','Tunai ex 31/10 Shift I','shift_omzet',56405100.00,0.00,2,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(2,1,'rek1','2026-11-02','Shift II','shift_omzet',51385500.00,0.00,2,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(3,1,'rek1','2026-11-02','Shift III','shift_omzet',15077300.00,0.00,2,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(4,1,'rek1','2026-11-02','Tunai ex 1/11 Shift I','shift_omzet',36526700.00,0.00,2,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(5,1,'rek1','2026-11-02','Shift II','shift_omzet',31652900.00,0.00,2,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(6,1,'rek1','2026-11-02','Shift III','shift_omzet',16404700.00,0.00,2,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(7,1,'rek1','2026-11-02','Stor BCA ex TSPS Tukar','tsps_tukar',0.00,25000000.00,2,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(8,1,'rek1','2026-11-03','Tunai ex 2/11 Shift I','shift_omzet',65834300.00,0.00,2,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(9,1,'rek1','2026-11-03','Shift II','shift_omzet',56971000.00,0.00,2,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(10,1,'rek1','2026-11-03','Shift III','shift_omzet',23995500.00,0.00,2,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(11,1,'rek1','2026-11-03','Stor BCA ex TSPS Tukar','tsps_tukar',0.00,15000000.00,2,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(12,1,'rek2','2026-11-02','Tunai ex O. LPG M 31/10','non_bbm_income',357500.00,0.00,2,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(13,1,'rek2','2026-11-02','U/ Kas SPBU','operational_expense',0.00,100000.00,2,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(14,1,'rek2','2026-11-02','Tunai ex O. LPG M 1/11','non_bbm_income',570000.00,0.00,2,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(15,1,'rek2','2026-11-02','U/ Kas SPBU','operational_expense',0.00,4200000.00,2,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(16,1,'rek2','2026-11-03','Tunai ex O. LPG M 2/11','non_bbm_income',411500.00,0.00,2,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(17,1,'rek2','2026-11-03','U/ Kas SPBU','operational_expense',0.00,200000.00,2,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(18,2,'rek1','2026-11-02','Tunai ex 31/10 Shift I','shift_omzet',96695000.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(19,2,'rek1','2026-11-02','Shift II','shift_omzet',90310500.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(20,2,'rek1','2026-11-02','Shift III','shift_omzet',15774300.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(21,2,'rek1','2026-11-02','Tunai ex 1/10 Shift I','shift_omzet',60158100.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(22,2,'rek1','2026-11-02','Shift II','shift_omzet',45453000.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(23,2,'rek1','2026-11-02','Shift III','shift_omzet',41551400.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(24,2,'rek1','2026-11-03','Tunai ex 2/11 Shift I','shift_omzet',119296700.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(25,2,'rek1','2026-11-03','Shift II','shift_omzet',79528600.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(26,2,'rek1','2026-11-03','Shift III','shift_omzet',34439700.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(27,2,'rek1','2026-11-03','Stor BCA ex TSPS Tukar','tsps_tukar',0.00,15000000.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(28,2,'rek2','2026-11-02','Tunai ex O. LPG M 31/10','non_bbm_income',357500.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(29,2,'rek2','2026-11-02','U/ Kas SPBU','operational_expense',0.00,400000.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(30,2,'rek2','2026-11-02','Tunai ex O. LPG M 01/11','non_bbm_income',570000.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(31,2,'rek2','2026-11-02','U/ Kas SPBU','operational_expense',0.00,4200000.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(32,2,'rek2','2026-11-03','Tunai ex O. LPG M','non_bbm_income',411500.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(33,2,'rek2','2026-11-03','U/ Kas SPBU','operational_expense',0.00,200000.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(34,2,'rek2','2026-11-03','Kotak toilet','non_bbm_income',609000.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(35,2,'rek2','2026-11-03','Bensin + Parkir','operational_expense',0.00,60000.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(36,2,'rek2','2026-11-03','Pulsa','operational_expense',0.00,25000.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(37,2,'rek2','2026-11-03','Kulakan LPG','operational_expense',0.00,1000000.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(38,2,'rek2','2026-11-30','Tunai ex O. LPG M 31/10','non_bbm_income',357500.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(39,2,'rek2','2026-11-30','U/ Kas SPBU','operational_expense',0.00,400000.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(40,2,'rek2','2026-11-30','Tunai ex O. LPG M 01/11','non_bbm_income',570000.00,0.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15'),
(41,2,'rek2','2026-11-30','U/ Kas SPBU','operational_expense',0.00,4200000.00,3,'2026-11-23 08:20:15','2026-11-23 08:20:15');

/*Table structure for table `cash_projection_settings` */

DROP TABLE IF EXISTS `cash_projection_settings`;

CREATE TABLE `cash_projection_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint unsigned NOT NULL,
  `month` tinyint unsigned NOT NULL,
  `opening_balance_rek1` decimal(16,2) NOT NULL DEFAULT '0.00',
  `opening_balance_rek2` decimal(16,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cash_projection_settings_year_month_unique` (`year`,`month`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cash_projection_settings` */

insert  into `cash_projection_settings`(`id`,`year`,`month`,`opening_balance_rek1`,`opening_balance_rek2`,`created_at`,`updated_at`) values 
(1,2026,11,200000000.00,0.00,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(2,2026,2,0.00,0.00,'2026-11-23 08:48:44','2026-11-23 08:48:44'),
(3,2026,12,0.00,0.00,'2026-11-23 08:48:52','2026-11-23 08:48:52');

/*Table structure for table `credit_sales` */

DROP TABLE IF EXISTS `credit_sales`;

CREATE TABLE `credit_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `sale_date` date NOT NULL,
  `client_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_non_bbm` tinyint(1) NOT NULL DEFAULT '0',
  `volume_liters` decimal(14,2) NOT NULL DEFAULT '0.00',
  `price_per_liter` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(16,2) NOT NULL DEFAULT '0.00',
  `credit_term_days` smallint unsigned NOT NULL DEFAULT '7',
  `due_date` date DEFAULT NULL,
  `payment_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Belum Lunas',
  `paid_date` date DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_sales_branch_id_foreign` (`branch_id`),
  KEY `credit_sales_created_by_foreign` (`created_by`),
  KEY `credit_sales_sale_date_index` (`sale_date`),
  KEY `credit_sales_client_name_index` (`client_name`),
  KEY `credit_sales_due_date_index` (`due_date`),
  KEY `credit_sales_payment_status_index` (`payment_status`),
  KEY `credit_sales_paid_date_index` (`paid_date`),
  CONSTRAINT `credit_sales_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_sales_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `credit_sales` */

insert  into `credit_sales`(`id`,`branch_id`,`sale_date`,`client_name`,`product_name`,`is_non_bbm`,`volume_liters`,`price_per_liter`,`total_amount`,`credit_term_days`,`due_date`,`payment_status`,`paid_date`,`notes`,`created_by`,`created_at`,`updated_at`) values 
(1,1,'2026-11-01','Zhenglu','Solar',0,5000.00,11200.00,56000000.00,14,'2026-11-15','Lunas','2026-11-01',NULL,2,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(2,2,'2026-11-01','Rajawali','Pertalite',0,4000.00,10700.00,42800000.00,7,'2026-11-08','Lunas','2026-11-01',NULL,3,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(3,1,'2026-11-02','RPT','Pertamax',0,3000.00,13300.00,39900000.00,7,'2026-11-09','Belum Lunas',NULL,NULL,2,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(4,2,'2026-11-02','Wanto','Solar',0,5000.00,11300.00,56500000.00,14,'2026-11-16','Belum Lunas',NULL,NULL,3,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(5,1,'2026-11-03','Rajawali','Pertalite',0,4000.00,10700.00,42800000.00,7,'2026-11-10','Belum Lunas',NULL,NULL,2,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(6,1,'2026-11-04','RPT','Pertamax',0,3000.00,13300.00,39900000.00,7,'2026-11-11','Belum Lunas',NULL,NULL,2,'2026-11-23 08:20:14','2026-11-23 08:20:14');

/*Table structure for table `fuel_redemptions` */

DROP TABLE IF EXISTS `fuel_redemptions`;

CREATE TABLE `fuel_redemptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `do_number` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_non_bbm` tinyint(1) NOT NULL DEFAULT '0',
  `volume_liters` decimal(14,2) NOT NULL DEFAULT '0.00',
  `price_per_liter` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(16,2) NOT NULL DEFAULT '0.00',
  `payment_status` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Lunas (Tunai)',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fuel_redemptions_branch_id_foreign` (`branch_id`),
  KEY `fuel_redemptions_created_by_foreign` (`created_by`),
  KEY `fuel_redemptions_transaction_date_index` (`transaction_date`),
  CONSTRAINT `fuel_redemptions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fuel_redemptions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `fuel_redemptions` */

insert  into `fuel_redemptions`(`id`,`branch_id`,`transaction_date`,`do_number`,`product_name`,`is_non_bbm`,`volume_liters`,`price_per_liter`,`total_amount`,`payment_status`,`notes`,`created_by`,`created_at`,`updated_at`) values 
(1,1,'2026-11-01','DO-261101-01','Solar',0,8000.00,6800.00,54400000.00,'Lunas (Tunai)',NULL,2,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(2,2,'2026-11-01','DO-261101-02','Pertalite',0,8000.00,6800.00,54400000.00,'Lunas (Tunai)',NULL,3,'2026-11-23 08:20:14','2026-11-23 08:23:57'),
(3,1,'2026-11-02','DO-261102-01','Pertalite',0,8000.00,10000.00,80000000.00,'Lunas (Tunai)',NULL,2,'2026-11-23 08:20:14','2026-11-23 08:25:11'),
(4,1,'2026-11-02','DO-261102-02','Solar',0,8000.00,6800.00,54400000.00,'Lunas (Tunai)',NULL,2,'2026-11-23 08:20:14','2026-11-23 08:20:14'),
(5,2,'2026-11-03','DO-261103-01','Solar',0,8000.00,10000.00,80000000.00,'Lunas (Tunai)',NULL,3,'2026-11-23 08:20:14','2026-11-23 08:23:09'),
(6,1,'2026-11-04','DO-261104-01','Pertamax',0,8000.00,12500.00,100000000.00,'Lunas (Tunai)',NULL,2,'2026-11-23 08:20:14','2026-11-23 08:20:14');

/*Table structure for table `migrations` */

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `migrations` */

insert  into `migrations`(`id`,`migration`,`batch`) values 
(1,'0001_01_01_000000_create_users_table',1),
(2,'2026_11_23_000001_create_spbu_finance_tables',1);

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin_cabang',
  `branch_id` bigint unsigned DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_branch_id_foreign` (`branch_id`),
  CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`name`,`username`,`role`,`branch_id`,`email`,`email_verified_at`,`password`,`remember_token`,`created_at`,`updated_at`) values 
(1,'Owner Rimasa SPBU','owner','owner',NULL,NULL,NULL,'$2y$12$xT2m3ahUZCa1KzG3uxlTfuK.9y/dNp/wcJCPfQh51LV3wrye3KgKy','XcCttfbjNcYkJSk9J10LEUTkZjP3jK2GPNq6QLfOgSn3UdPWnfDTfMl3LIlM','2026-11-23 08:20:14','2026-11-24 02:52:29'),
(2,'Admin Tanjung Sari','admin_ts','admin_cabang',1,NULL,NULL,'$2y$12$/bHuUh8yZr1Fv8u9/EZCXuYU1EWQbIuRncRl1mVzfmeRTMnq/T.xm','XxIe584CYgTdBwrwb6vsKH1jq4CAiumjnSiDeE545ZUtbd474KOPUJUIx2OF','2026-11-23 08:20:14','2026-11-24 02:53:25'),
(3,'Admin Kakap','admin_kakap','admin_cabang',2,NULL,NULL,'$2y$12$URqy5c1BOsMhUdUKR9Z4qe76CismTzC8QSDEgZSwdEwi6o9rRYhYm','9OqAYtUJ6MUBPv9A7cGTPPoZDlXT0viK2jwyRDGwyoQkNG47v0ftCNLw3yEa','2026-11-23 08:20:14','2026-11-24 02:54:04');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
