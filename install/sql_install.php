<?php

$CI = &get_instance();

// Create master tables first
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "country` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone_code` VARCHAR(10) NOT NULL,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "bank` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "school_name` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "university_name` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "university_program` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "product` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255),
    `description` TEXT,
    `price` FLOAT,
    `type` VARCHAR(50),
    `status` VARCHAR(20),
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Create Sponsor Records Table
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "sponsor_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entity_type` ENUM('sponsor') NOT NULL DEFAULT 'sponsor',
    `name` VARCHAR(255),
    `sponsor_type` VARCHAR(20),
    `sponsor_occupation` VARCHAR(255),
    `contact_no` VARCHAR(20),
    `email` VARCHAR(255) UNIQUE,
    `address` TEXT,
    `city` VARCHAR(100),
    `state_id` INT,
    `zip` VARCHAR(20),
    `country_id` INT,
    `bank_id` INT,
    `sponsor_bank_branch_info` VARCHAR(255),
    `sponsor_bank_branch_number` VARCHAR(100),
    `sponsor_bank_account_no` VARCHAR(100) UNIQUE,
    `sponsor_frequency` VARCHAR(20),
    `state` VARCHAR(20),
    `product_id` INT,
    `membership_start_date` DATE,
    `membership_end_date` DATE,
    `school_internal_ids` JSON,
    `university_internal_ids` JSON,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sponsor_email` (`email`),
    KEY `idx_sponsor_bank_account` (`sponsor_bank_account_no`),
    KEY `idx_sponsor_country` (`country_id`),
    KEY `idx_sponsor_bank` (`bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Create School Students Table
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "school_students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entity_type` ENUM('school') NOT NULL DEFAULT 'school',
    `name` VARCHAR(255),
    `profile_photo` VARCHAR(255),
    `contact_no` VARCHAR(20),
    `email` VARCHAR(255) UNIQUE,
    `address` TEXT,
    `city` VARCHAR(100),
    `zip` VARCHAR(20),
    `country_id` INT,
    `school_internal_id` VARCHAR(100) UNIQUE,
    `school_id` VARCHAR(100),
    `school_type` VARCHAR(20),
    `school_name_id` INT,
    `school_grade_year` INT,
    `school_grade` VARCHAR(20),
    `grade_mismatch_reason` TEXT,
    `school_student_dob` DATE,
    `school_age` INT,
    `bank_id` INT,
    `school_bank_branch_number` VARCHAR(100),
    `school_bank_branch_info` VARCHAR(255),
    `school_bank_account_no` VARCHAR(100) UNIQUE,
    `school_sponsorship_start_date` DATE,
    `school_sponsorship_end_date` DATE,
    `school_introducedby` VARCHAR(255),
    `school_introducedph` VARCHAR(50),
    `school_father_name` VARCHAR(255),
    `school_mother_name` VARCHAR(255),
    `school_father_income` FLOAT,
    `school_mother_income` FLOAT,
    `school_guardian_name` VARCHAR(255),
    `school_guardian_income` FLOAT,
    `sponsor_id` INT,
    `background_info` TEXT,
    `internal_comment` TEXT,
    `external_comment` TEXT,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_school_email` (`email`),
    KEY `idx_school_internal_id` (`school_internal_id`),
    KEY `idx_school_bank_account` (`school_bank_account_no`),
    KEY `idx_school_country` (`country_id`),
    KEY `idx_school_bank` (`bank_id`),
    KEY `idx_school_sponsor` (`sponsor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Create University Students Table
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "university_students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `entity_type` ENUM('university') NOT NULL DEFAULT 'university',
    `name` VARCHAR(255),
    `profile_photo` VARCHAR(255),
    `contact_no` VARCHAR(20),
    `email` VARCHAR(255) UNIQUE,
    `address` TEXT,
    `city` VARCHAR(100),
    `zip` VARCHAR(20),
    `country_id` INT,
    `university_internal_id` VARCHAR(100) UNIQUE,
    `university_id` VARCHAR(100),
    `university_name_id` INT,
    `university_program_id` INT,
    `university_year_of_study` ENUM('1Y1S','1Y2S','2Y1S','2Y2S','3Y1S','3Y2S','4Y1S','4Y2S','5Y1S','5Y2S'),
    `university_student_dob` DATE,
    `university_age` INT,
    `bank_id` INT,
    `university_bank_branch_info` VARCHAR(255),
    `university_bank_branch_number` VARCHAR(100),
    `university_bank_account_no` VARCHAR(100) UNIQUE,
    `university_sponsorship_start_date` DATE,
    `university_sponsorship_end_date` DATE,
    `university_introducedby` VARCHAR(255),
    `university_introducedph` VARCHAR(50),
    `university_father_name` VARCHAR(255),
    `university_mother_name` VARCHAR(255),
    `university_father_income` FLOAT,
    `university_mother_income` FLOAT,
    `university_guardian_name` VARCHAR(255),
    `university_guardian_income` FLOAT,
    `sponsor_id` INT,
    `background_info` TEXT,
    `internal_comment` TEXT,
    `external_comment` TEXT,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_university_email` (`email`),
    KEY `idx_university_internal_id` (`university_internal_id`),
    KEY `idx_university_bank_account` (`university_bank_account_no`),
    KEY `idx_university_country` (`country_id`),
    KEY `idx_university_bank` (`bank_id`),
    KEY `idx_university_sponsor` (`sponsor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Create Payment Table
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "sponsor_payment` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sponsor_id` INT NOT NULL,
    `external_reference_id` VARCHAR(100),
    `student_school_id` INT,
    `student_university_id` INT,
    `total_amount` DECIMAL(12,2) NOT NULL,
    `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `currency_code` CHAR(3) NOT NULL DEFAULT 'USD',
    `payment_date` DATE NOT NULL,
    `payment_type` ENUM('one_time', 'installment', 'recurring', 'other') NOT NULL,
    `last_payment_date` DATE,
    `next_payment_date` DATE,
    `payment_status` ENUM('pending', 'partial', 'completed', 'overdue', 'cancelled') NOT NULL DEFAULT 'pending',
    `email_subject` VARCHAR(255),
    `email_body` TEXT,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_on` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_payment_sponsor` (`sponsor_id`),
    KEY `idx_payment_school` (`student_school_id`),
    KEY `idx_payment_university` (`student_university_id`),
    KEY `idx_payment_dates` (`payment_date`, `next_payment_date`),
    KEY `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Create Payment History Table
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "payment_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sponsor_payment_id` INT,
    `amount` FLOAT,
    `currency_paid` VARCHAR(5),
    `date` DATE,
    `cumulative_paid` FLOAT,
    `balance_after` FLOAT,
    `note` TEXT,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_payment_history_payment` (`sponsor_payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Create Sponsor Reminder Table
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "sponsor_reminder` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sponsor_id` INT NOT NULL,
    `student_school_id` INT NULL,
    `student_university_id` INT NULL,
    `sponsorship_start_date` DATE,
    `sponsorship_end_date` DATE,
    `reminder_status` ENUM('not_sent','sent') DEFAULT 'not_sent',
    `reminder_days_before` INT,
    `reminder_date` DATE,
    `renewal_reminder_status` ENUM('not_sent','sent') DEFAULT 'not_sent',
    `renewal_reminder_days_before` INT,
    `renewal_reminder_date` DATE,
    `notes` TEXT,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_reminder_sponsor` (`sponsor_id`),
    KEY `idx_reminder_school` (`student_school_id`),
    KEY `idx_reminder_university` (`student_university_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Create School Report Card Table
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "school_report_card` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_school_id` INT NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `term` ENUM('Term1','Term2','Term3') NOT NULL,
    `upload_date` DATE NOT NULL,
    `report_card_file` VARCHAR(255) NOT NULL,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_report_card_school` (`student_school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Create University Report Card Table
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "university_report_card` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_university_id` INT NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `upload_date` DATE NOT NULL,
    `report_card_term` ENUM('1Y1S','1Y2S','2Y1S','2Y2S','3Y1S','3Y2S','4Y1S','4Y2S','5Y1S','5Y2S') NOT NULL,
    `current_term` ENUM('1Y1S','1Y2S','2Y1S','2Y2S','3Y1S','3Y2S','4Y1S','4Y2S','5Y1S','5Y2S') NOT NULL,
    `semester_end_month` TINYINT UNSIGNED NOT NULL,
    `semester_end_year` YEAR NOT NULL,
    `report_card_file` VARCHAR(255) NOT NULL,
    `created_on` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_report_card_university` (`student_university_id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Add foreign key constraints after all tables are created
$CI->db->query("ALTER TABLE `" . db_prefix() . "sponsor_records` 
    ADD FOREIGN KEY (`bank_id`) REFERENCES `" . db_prefix() . "bank`(`id`),
    ADD FOREIGN KEY (`country_id`) REFERENCES `" . db_prefix() . "country`(`id`),
    ADD FOREIGN KEY (`product_id`) REFERENCES `" . db_prefix() . "product`(`id`)");

$CI->db->query("ALTER TABLE `" . db_prefix() . "school_students` 
    ADD FOREIGN KEY (`school_name_id`) REFERENCES `" . db_prefix() . "school_name`(`id`),
    ADD FOREIGN KEY (`bank_id`) REFERENCES `" . db_prefix() . "bank`(`id`),
    ADD FOREIGN KEY (`country_id`) REFERENCES `" . db_prefix() . "country`(`id`),
    ADD FOREIGN KEY (`sponsor_id`) REFERENCES `" . db_prefix() . "sponsor_records`(`id`) ON DELETE SET NULL");

$CI->db->query("ALTER TABLE `" . db_prefix() . "university_students` 
    ADD FOREIGN KEY (`university_name_id`) REFERENCES `" . db_prefix() . "university_name`(`id`),
    ADD FOREIGN KEY (`university_program_id`) REFERENCES `" . db_prefix() . "university_program`(`id`),
    ADD FOREIGN KEY (`bank_id`) REFERENCES `" . db_prefix() . "bank`(`id`),
    ADD FOREIGN KEY (`country_id`) REFERENCES `" . db_prefix() . "country`(`id`),
    ADD FOREIGN KEY (`sponsor_id`) REFERENCES `" . db_prefix() . "sponsor_records`(`id`) ON DELETE SET NULL");

$CI->db->query("ALTER TABLE `" . db_prefix() . "sponsor_payment` 
    ADD FOREIGN KEY (`sponsor_id`) REFERENCES `" . db_prefix() . "sponsor_records`(`id`) ON DELETE CASCADE,
    ADD FOREIGN KEY (`student_school_id`) REFERENCES `" . db_prefix() . "school_students`(`id`) ON DELETE SET NULL,
    ADD FOREIGN KEY (`student_university_id`) REFERENCES `" . db_prefix() . "university_students`(`id`) ON DELETE SET NULL");

$CI->db->query("ALTER TABLE `" . db_prefix() . "payment_history` 
    ADD FOREIGN KEY (`sponsor_payment_id`) REFERENCES `" . db_prefix() . "sponsor_payment`(`id`)");

$CI->db->query("ALTER TABLE `" . db_prefix() . "sponsor_reminder` 
    ADD FOREIGN KEY (`sponsor_id`) REFERENCES `" . db_prefix() . "sponsor_records`(`id`) ON DELETE CASCADE,
    ADD FOREIGN KEY (`student_school_id`) REFERENCES `" . db_prefix() . "school_students`(`id`) ON DELETE SET NULL,
    ADD FOREIGN KEY (`student_university_id`) REFERENCES `" . db_prefix() . "university_students`(`id`) ON DELETE SET NULL");

$CI->db->query("ALTER TABLE `" . db_prefix() . "school_report_card` 
    ADD FOREIGN KEY (`student_school_id`) REFERENCES `" . db_prefix() . "school_students`(`id`) ON DELETE CASCADE");

$CI->db->query("ALTER TABLE `" . db_prefix() . "university_report_card` 
    ADD FOREIGN KEY (`student_university_id`) REFERENCES `" . db_prefix() . "university_students`(`id`) ON DELETE CASCADE");

// Add balance_amount as generated column (added separately for compatibility)
$CI->db->query("ALTER TABLE `" . db_prefix() . "sponsor_payment` 
    ADD `balance_amount` DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - amount_paid) STORED");

// Insert sample data for master tables
$CI->db->query("INSERT IGNORE INTO `" . db_prefix() . "country` (`name`, `phone_code`) VALUES 
    ('Sri Lanka', '+94'),
    ('United States', '+1'),
    ('United Kingdom', '+44')");

$CI->db->query("INSERT IGNORE INTO `" . db_prefix() . "bank` (`name`) VALUES 
    ('Bank of Ceylon'),
    ('People''s Bank'),
    ('Commercial Bank'),
    ('Hatton National Bank'),
    ('Sampath Bank')");

echo "Database schema installed successfully!";
$CI->db->query("
CREATE TABLE IF NOT EXISTS `tblcustomer_user_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$CI->db->query("
CREATE TABLE IF NOT EXISTS `tblcustomer_user_types_in` (
  `customer_id` INT UNSIGNED NOT NULL,
  `type_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`customer_id`,`type_id`),
  KEY `type_id` (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

/*** Username as Customer custom field ***/
$exists = $CI->db->where('fieldto','customers')->where('slug','username')->get('tblcustomfields')->row();
if (!$exists) {
    $CI->db->insert('tblcustomfields', [
        'fieldto'       => 'customers',
        'name'          => 'Username',
        'slug'          => 'username',
        'type'          => 'input',
        'active'        => 1,
        'show_on_table' => 1,
        'required'      => 0,
    ]);
}

?>