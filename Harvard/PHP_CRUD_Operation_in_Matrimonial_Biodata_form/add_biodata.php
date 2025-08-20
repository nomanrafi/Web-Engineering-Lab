<?php
session_start();
require_once 'database_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = "";
$error_message = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic validation for required fields
    $required_fields = [
        'name', 'dob', 'age', 'gender', 'marital_status', 'contact_number', 'email',
        'permanent_address', 'present_address', 'father_name', 'mother_name',
        'degree_level', 'institute', 'education_result', 'year_of_passing'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }

    // Email validation
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Handle file upload
    $photo_path = "";
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . uniqid() . '_' . basename($_FILES["photo"]["name"]);
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo_path = $target_file;
        } else {
            $errors[] = "Sorry, there was an error uploading your file.";
        }
    }

    if (empty($errors)) {
        $sql = "INSERT INTO biodata (
            photo_path, name, dob, pob, age, gender, height, marital_status, religion, 
            nationality, blood_group, contact_number, email, permanent_address, 
            present_address, father_name, father_occupation, mother_name, 
            mother_occupation, siblings, degree_level, institute, education_result, 
            year_of_passing, additional_certifications, current_occupation, 
            annual_income, future_career_plan, complexion, body_type, diet, smoking, 
            drinking, hobbies_interests, partner_age_range, partner_height, 
            partner_education, partner_occupation, partner_religion, about_me, 
            languages_known, future_plans, health_issues, social_media_links, comments
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        
        // Create variables for all form values
        $p_name = $_POST['name'];
        $p_dob = $_POST['dob'];
        $p_pob = $_POST['pob'];
        $p_age = intval($_POST['age']);
        $p_gender = $_POST['gender'];
        $p_height = $_POST['height'];
        $p_marital_status = $_POST['marital_status'];
        $p_religion = $_POST['religion'];
        $p_nationality = $_POST['nationality'];
        $p_blood_group = $_POST['blood_group'];
        $p_contact_number = $_POST['contact_number'];
        $p_email = $_POST['email'];
        $p_permanent_address = $_POST['permanent_address'];
        $p_present_address = $_POST['present_address'];
        $p_father_name = $_POST['father_name'];
        $p_father_occupation = $_POST['father_occupation'];
        $p_mother_name = $_POST['mother_name'];
        $p_mother_occupation = $_POST['mother_occupation'];
        $p_siblings = $_POST['siblings'];
        $p_degree_level = $_POST['degree_level'];
        $p_institute = $_POST['institute'];
        $p_education_result = $_POST['education_result'];
        $p_year_of_passing = intval($_POST['year_of_passing']);
        $p_additional_certifications = $_POST['additional_certifications'];
        $p_current_occupation = $_POST['current_occupation'];
        $p_annual_income = $_POST['annual_income'];
        $p_future_career_plan = $_POST['future_career_plan'];
        $p_complexion = $_POST['complexion'];
        $p_body_type = $_POST['body_type'];
        $p_diet = $_POST['diet'];
        $p_smoking = $_POST['smoking'];
        $p_drinking = $_POST['drinking'];
        $p_hobbies_interests = $_POST['hobbies_interests'];
        $p_partner_age_range = $_POST['partner_age_range'];
        $p_partner_height = $_POST['partner_height'];
        $p_partner_education = $_POST['partner_education'];
        $p_partner_occupation = $_POST['partner_occupation'];
        $p_partner_religion = $_POST['partner_religion'];
        $p_about_me = $_POST['about_me'];
        $p_languages_known = $_POST['languages_known'];
        $p_future_plans = $_POST['future_plans'];
        $p_health_issues = $_POST['health_issues'];
        $p_social_media_links = $_POST['social_media_links'];
        $p_comments = $_POST['comments'];

        // Create the types string for bind_param
        $types = '';
        foreach(range(1, 45) as $i) {
            if ($i == 5 || $i == 24) { // age and year_of_passing are integers
                $types .= 'i';
            } else {
                $types .= 's';
            }
        }
        
        $stmt->bind_param($types,
            $photo_path, $p_name, $p_dob, $p_pob, $p_age,
            $p_gender, $p_height, $p_marital_status, $p_religion,
            $p_nationality, $p_blood_group, $p_contact_number,
            $p_email, $p_permanent_address, $p_present_address,
            $p_father_name, $p_father_occupation, $p_mother_name,
            $p_mother_occupation, $p_siblings, $p_degree_level,
            $p_institute, $p_education_result, $p_year_of_passing,
            $p_additional_certifications, $p_current_occupation,
            $p_annual_income, $p_future_career_plan, $p_complexion,
            $p_body_type, $p_diet, $p_smoking, $p_drinking,
            $p_hobbies_interests, $p_partner_age_range, $p_partner_height,
            $p_partner_education, $p_partner_occupation, $p_partner_religion,
            $p_about_me, $p_languages_known, $p_future_plans,
            $p_health_issues, $p_social_media_links, $p_comments);

        if ($stmt->execute()) {
            $success_message = "Biodata added successfully!";
        } else {
            $error_message = "Error adding biodata: " . $stmt->error;
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Biodata - Modern Form</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1519681393784-d120267933ba?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1770&q=80');
            background-size: cover;
            background-attachment: fixed;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            color: #667eea;
        }

        .form-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #667eea;
        }

        .section-title {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eee;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a5568;
            font-weight: 500;
        }
        
        .form-group label.required:after {
            content: " *";
            color: #e53e3e;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(118, 75, 162, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1rem;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h1><i class="fas fa-user-plus"></i> Add New Biodata</h1>
            <p>Fill in the details below to create a new biodata entry</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <!-- Personal Information Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-user-circle"></i> Personal Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="photo" class="required">Profile Photo</label>
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label for="name" class="required">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="dob" class="required">Date of Birth</label>
                        <input type="date" name="dob" id="dob" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="pob" class="required">Place of Birth</label>
                        <input type="text" name="pob" id="pob" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="age" class="required">Age</label>
                        <input type="number" name="age" id="age" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="gender" class="required">Gender</label>
                        <select name="gender" id="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="height" class="required">Height</label>
                        <input type="text" name="height" id="height" class="form-control" placeholder="e.g., 5' 8&quot;" required>
                    </div>
                    <div class="form-group">
                        <label for="marital_status" class="required">Marital Status</label>
                        <select name="marital_status" id="marital_status" class="form-control" required>
                            <option value="">Select Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="religion" class="required">Religion</label>
                        <input type="text" name="religion" id="religion" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="nationality" class="required">Nationality</label>
                        <input type="text" name="nationality" id="nationality" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="blood_group" class="required">Blood Group</label>
                        <select name="blood_group" id="blood_group" class="form-control" required>
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option><option value="A-">A-</option>
                            <option value="B+">B+</option><option value="B-">B-</option>
                            <option value="O+">O+</option><option value="O-">O-</option>
                            <option value="AB+">AB+</option><option value="AB-">AB-</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-address-book"></i> Contact Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="contact_number" class="required">Contact Number</label>
                        <input type="tel" name="contact_number" id="contact_number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="required">Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="permanent_address" class="required">Permanent Address</label>
                        <textarea name="permanent_address" id="permanent_address" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="present_address" class="required">Present Address</label>
                        <textarea name="present_address" id="present_address" class="form-control" required></textarea>
                    </div>
                </div>
            </div>

            <!-- Family Information Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-users"></i> Family Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="father_name" class="required">Father's Name</label>
                        <input type="text" name="father_name" id="father_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="father_occupation" class="required">Father's Occupation</label>
                        <input type="text" name="father_occupation" id="father_occupation" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="mother_name" class="required">Mother's Name</label>
                        <input type="text" name="mother_name" id="mother_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="mother_occupation" class="required">Mother's Occupation</label>
                        <input type="text" name="mother_occupation" id="mother_occupation" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="siblings" class="required">Siblings</label>
                        <textarea name="siblings" id="siblings" class="form-control" placeholder="e.g., 1 brother, 2 sisters" required></textarea>
                    </div>
                </div>
            </div>

            <!-- Educational Information Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-graduation-cap"></i> Educational Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="degree_level" class="required">Degree Level</label>
                        <input type="text" name="degree_level" id="degree_level" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="institute" class="required">Institute</label>
                        <input type="text" name="institute" id="institute" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="education_result" class="required">Result</label>
                        <input type="text" name="education_result" id="education_result" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="year_of_passing" class="required">Year of Passing</label>
                        <input type="number" name="year_of_passing" id="year_of_passing" class="form-control" min="1950" max="2099" required>
                    </div>
                    <div class="form-group">
                        <label for="additional_certifications" class="required">Additional Certifications</label>
                        <textarea name="additional_certifications" id="additional_certifications" class="form-control" required></textarea>
                    </div>
                </div>
            </div>

            <!-- Career Information Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-briefcase"></i> Career Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="current_occupation" class="required">Current Occupation</label>
                        <input type="text" name="current_occupation" id="current_occupation" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="annual_income" class="required">Annual Income</label>
                        <input type="text" name="annual_income" id="annual_income" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="future_career_plan" class="required">Future Career Plan</label>
                        <textarea name="future_career_plan" id="future_career_plan" class="form-control" required></textarea>
                    </div>
                </div>
            </div>

            <!-- Physical & Lifestyle Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-spa"></i> Physical & Lifestyle</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="complexion" class="required">Complexion</label>
                        <input type="text" name="complexion" id="complexion" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="body_type" class="required">Body Type</label>
                        <input type="text" name="body_type" id="body_type" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="diet" class="required">Diet</label>
                        <input type="text" name="diet" id="diet" class="form-control" placeholder="e.g., Vegetarian, Non-vegetarian" required>
                    </div>
                    <div class="form-group">
                        <label for="smoking" class="required">Smoking</label>
                        <select name="smoking" id="smoking" class="form-control" required>
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                            <option value="Occasionally">Occasionally</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="drinking" class="required">Drinking</label>
                        <select name="drinking" id="drinking" class="form-control" required>
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                            <option value="Occasionally">Occasionally</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-info-circle"></i> Additional Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="hobbies_interests" class="required">Hobbies & Interests</label>
                        <textarea name="hobbies_interests" id="hobbies_interests" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="about_me" class="required">About Me</label>
                        <textarea name="about_me" id="about_me" class="form-control" required></textarea>
                    </div>
                    <div class="form-group" >
                        <label for="languages_known" class="required">Languages Known</label>
                        <textarea name="languages_known" id="languages_known" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="future_plans" class="required">Future Plans (Personal)</label>
                        <textarea name="future_plans" id="future_plans" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="health_issues" class="required">Health Issues (if any)</label>
                        <textarea name="health_issues" id="health_issues" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="social_media_links" class="required">Social Media Links</label>
                        <textarea name="social_media_links" id="social_media_links" class="form-control" required></textarea>
                    </div>
                </div>
            </div>

            <!-- Partner Preferences Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-heart"></i> Partner Preferences</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="partner_age_range" class="required">Partner Age Range</label>
                        <input type="text" name="partner_age_range" id="partner_age_range" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="partner_height" class="required">Partner Height</label>
                        <input type="text" name="partner_height" id="partner_height" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="partner_education" class="required">Partner Education</label>
                        <input type="text" name="partner_education" id="partner_education" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="partner_occupation" class="required">Partner Occupation</label>
                        <input type="text" name="partner_occupation" id="partner_occupation" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="partner_religion" class="required">Partner Religion</label>
                        <input type="text" name="partner_religion" id="partner_religion" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <!-- Comments Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-comments"></i> Comments</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="comments" class="required">Any other comments</label>
                        <textarea name="comments" id="comments" class="form-control" required></textarea>
                    </div>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" class="btn btn-primary" a href="dashboard.php">
                    <i class="fas fa-save"></i> Save Biodata
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </form>
    </div>
</body>
</html>