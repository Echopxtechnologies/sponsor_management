<!-- install/sql_install.php -->
<?php

$CI = &get_instance();

// Ensure minimal school_students table exists (in case it's a fresh install)
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "school_students` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150),
    `phone` VARCHAR(50),
    `dob` DATE,
    `grade` VARCHAR(50),
    `school_id` VARCHAR(50),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// 🛠️ Add additional fields only if they don't exist
$fields_to_add = [
    'internal_id' => "VARCHAR(50) UNIQUE",
    'profile_picture' => "VARCHAR(255)",
    'age' => "INT(3)",
    'school_type' => "ENUM('Type 1AB', 'Type 1C', 'Type 2', 'Type 3')",
    'school_name' => "VARCHAR(200)",
    'graduation_exam_date' => "DATE",
    'warning_message' => "TEXT",
    'grade_age_mismatch_reason' => "TEXT",
    'full_phone_number' => "VARCHAR(25)",
    'address' => "TEXT",
    'district' => "ENUM('Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale', 'Nuwara Eliya', 'Galle', 'Matara', 'Hambantota', 'Jaffna', 'Kilinochchi', 'Mannar', 'Vavuniya', 'Mullaitivu', 'Batticaloa', 'Ampara', 'Trincomalee', 'Kurunegala', 'Puttalam', 'Anuradhapura', 'Polonnaruwa', 'Badulla', 'Moneragala', 'Ratnapura', 'Kegalle')",
    'postal_code' => "VARCHAR(10)",
    'country' => "VARCHAR(100) DEFAULT 'Sri Lanka'",
    'father_name' => "VARCHAR(150)",
    'mother_name' => "VARCHAR(150)",
    'father_income' => "DECIMAL(12,2)",
    'mother_income' => "DECIMAL(12,2)",
    'guardian_name' => "VARCHAR(150)",
    'guardian_income' => "DECIMAL(12,2)",
    'sponsorship_start' => "DATE",
    'sponsorship_end' => "DATE",
    'sponsors' => "TEXT",
    'introduced_by' => "VARCHAR(150)",
    'introduced_phone' => "VARCHAR(20)",
    'bank_name' => "VARCHAR(100)",
    'bank_branch_number' => "VARCHAR(50)",
    'bank_branch_info' => "TEXT",
    'bank_account_number' => "VARCHAR(50)",
    'background_information' => "TEXT",
    'internal_comment' => "TEXT",
    'external_comment' => "TEXT",
    'academic_year'       => "VARCHAR(20)",
    'term'                => "ENUM('1st_term','2nd_term','3rd_term','annual')",
    'overall_grade'       => "VARCHAR(10)",
    'percentage'          => "DECIMAL(5,2)",
    'class_rank'          => "INT(5)",
    'attendance'          => "DECIMAL(5,2)",
    'teacher_comments'    => "TEXT",
    'subjects_performance'=> "TEXT",
    'created_at'          => "DATETIME DEFAULT CURRENT_TIMESTAMP",
    'updated_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

$existing_columns = array_map('strtolower', array_column($CI->db->list_fields(db_prefix() . 'school_students'), 0));

foreach ($fields_to_add as $field => $definition) {
    if (!in_array(strtolower($field), $existing_columns)) {
        $CI->db->query("ALTER TABLE `" . db_prefix() . "school_students` ADD `$field` $definition;");
    }
}

// Create indexes if needed
$CI->db->query("CREATE INDEX IF NOT EXISTS `idx_internal_id` ON `" . db_prefix() . "school_students` (`internal_id`);");
$CI->db->query("CREATE INDEX IF NOT EXISTS `idx_student_name` ON `" . db_prefix() . "school_students` (`name`);");
$CI->db->query("CREATE INDEX IF NOT EXISTS `idx_district` ON `" . db_prefix() . "school_students` (`district`);");
$CI->db->query("CREATE INDEX IF NOT EXISTS `idx_grade` ON `" . db_prefix() . "school_students` (`grade`);");

// Create Report Cards Table
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "student_report_cards` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `internal_id` VARCHAR(50),
    `term_date` DATE,
    `file_name` VARCHAR(255),
    `file_path` VARCHAR(500),
    `file_size` INT(11),
    `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`student_id`) REFERENCES `" . db_prefix() . "school_students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Create University Students Table (unchanged)
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "university_students` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150),
    `phone` VARCHAR(50),
    `dob` DATE,
    `program` VARCHAR(100),
    `year_of_study` VARCHAR(50),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Create Sponsor Records Table (unchanged)
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "sponsor_records` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150),
  `phone` VARCHAR(50),
  `sponsor_type` VARCHAR(50),               -- keep VARCHAR to avoid breaking existing data
  `sponsor_occupation` VARCHAR(150),
  `country_id` VARCHAR(5),
  `phone_code` VARCHAR(10),
  `contact_no` VARCHAR(30),
  `address` TEXT,
  `city` VARCHAR(100),
  `state_id` VARCHAR(50),
  `zip` VARCHAR(20),
  `sponsor_name_of_the_bank_id` INT(11),
  `sponsor_bank_branch_info` VARCHAR(150),
  `sponsor_bank_branch_number` VARCHAR(50),
  `sponsor_bank_account_no` VARCHAR(50),
  `sponsor_sponsorship_start_date` DATE,
  `sponsor_sponsorship_reneval_date` DATE,
  `sponsor_frequency` ENUM('one_time','monthly','half_yearly','yearly'),
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sponsor_name` (`name`),
  KEY `idx_sponsor_type` (`sponsor_type`),
  KEY `idx_country` (`country_id`),
  KEY `idx_state` (`state_id`),
  KEY `idx_frequency` (`sponsor_frequency`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

// Student sequence table (for generating internal_id)
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "student_sequence` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `last_number` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

$CI->db->query("INSERT IGNORE INTO `" . db_prefix() . "student_sequence` (`id`, `last_number`) VALUES (1, 0);");
