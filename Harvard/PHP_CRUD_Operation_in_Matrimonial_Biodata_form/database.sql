-- Create the database
CREATE DATABASE IF NOT EXISTS biodata_management;
USE biodata_management;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create biodata table
CREATE TABLE IF NOT EXISTS biodata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    photo_path VARCHAR(255),
    name VARCHAR(255) NOT NULL,
    dob DATE,
    pob VARCHAR(255),
    age INT,
    gender VARCHAR(50),
    height VARCHAR(50),
    marital_status VARCHAR(50),
    religion VARCHAR(100),
    nationality VARCHAR(100),
    blood_group VARCHAR(10),
    contact_number VARCHAR(50),
    email VARCHAR(255),
    permanent_address TEXT,
    present_address TEXT,
    father_name VARCHAR(255),
    father_occupation VARCHAR(255),
    mother_name VARCHAR(255),
    mother_occupation VARCHAR(255),
    siblings VARCHAR(255),
    degree_level VARCHAR(255),
    institute VARCHAR(255),
    education_result VARCHAR(50),
    year_of_passing YEAR,
    additional_certifications TEXT,
    current_occupation VARCHAR(255),
    annual_income VARCHAR(255),
    future_career_plan TEXT,
    complexion VARCHAR(100),
    body_type VARCHAR(100),
    diet VARCHAR(100),
    smoking VARCHAR(50),
    drinking VARCHAR(50),
    hobbies_interests TEXT,
    partner_age_range VARCHAR(100),
    partner_height VARCHAR(100),
    partner_education VARCHAR(255),
    partner_occupation VARCHAR(255),
    partner_religion VARCHAR(100),
    about_me TEXT,
    languages_known TEXT,
    future_plans TEXT,
    health_issues TEXT,
    social_media_links TEXT,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
