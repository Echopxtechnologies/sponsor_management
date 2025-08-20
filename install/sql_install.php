<?php

$CI = &get_instance();

// Create School Students Table
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

// Create University Students Table
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

// Create Sponsor Records Table
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "sponsor_records` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150),
    `phone` VARCHAR(50),
    `sponsor_type` VARCHAR(50),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");