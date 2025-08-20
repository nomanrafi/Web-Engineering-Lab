<?php
session_start();
require_once 'database_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = "";
$error_message = "";
$biodata = null;

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM biodata WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $biodata = $result->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];

    // Handle file upload
    $photo_path = $_POST['existing_photo'];
    if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . uniqid() . '_' . basename($_FILES["photo"]["name"]);
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            if (!empty($_POST['existing_photo']) && file_exists($_POST['existing_photo'])) {
                unlink($_POST['existing_photo']);
            }
            $photo_path = $target_file;
        } else {
            $error_message = "Sorry, there was an error uploading your new photo.";
        }
    }

    // Process all form fields
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

    $sql = "UPDATE biodata SET
        photo_path = ?, name = ?, dob = ?, pob = ?, age = ?, gender = ?,
        height = ?, marital_status = ?, religion = ?, nationality = ?,
        blood_group = ?, contact_number = ?, email = ?, permanent_address = ?,
        present_address = ?, father_name = ?, father_occupation = ?,
        mother_name = ?, mother_occupation = ?, siblings = ?, degree_level = ?,
        institute = ?, education_result = ?, year_of_passing = ?,
        additional_certifications = ?, current_occupation = ?, annual_income = ?,
        future_career_plan = ?, complexion = ?, body_type = ?, diet = ?,
        smoking = ?, drinking = ?, hobbies_interests = ?, partner_age_range = ?,
        partner_height = ?, partner_education = ?, partner_occupation = ?,
        partner_religion = ?, about_me = ?, languages_known = ?, future_plans = ?,
        health_issues = ?, social_media_links = ?, comments = ?
        WHERE id = ?";

    // Build the types string for bind_param
    $types = '';
    foreach(range(1, 46) as $i) {
        if ($i == 5 || $i == 24 || $i == 46) { // age, year_of_passing, and id are integers
            $types .= 'i';
        } else {
            $types .= 's';
        }
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types,
        $photo_path, $p_name, $p_dob, $p_pob, $p_age,
        $p_gender, $p_height, $p_marital_status, $p_religion, $p_nationality,
        $p_blood_group, $p_contact_number, $p_email, $p_permanent_address,
        $p_present_address, $p_father_name, $p_father_occupation,
        $p_mother_name, $p_mother_occupation, $p_siblings, $p_degree_level,
        $p_institute, $p_education_result, $p_year_of_passing,
        $p_additional_certifications, $p_current_occupation, $p_annual_income,
        $p_future_career_plan, $p_complexion, $p_body_type, $p_diet,
        $p_smoking, $p_drinking, $p_hobbies_interests, $p_partner_age_range,
        $p_partner_height, $p_partner_education, $p_partner_occupation,
        $p_partner_religion, $p_about_me, $p_languages_known, $p_future_plans,
        $p_health_issues, $p_social_media_links, $p_comments, $id
    );

    if ($stmt->execute()) {
        $success_message = "Biodata updated successfully!";
        // Re-fetch biodata to show updated values immediately
        $stmt = $conn->prepare("SELECT * FROM biodata WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $biodata = $result->fetch_assoc();
    } else {
        $error_message = "Error updating biodata: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Biodata - Biodata Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        /* Specific styles for edit_biodata.php that are not in styles.css */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .form-section {
            background: #f8faff;
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
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .photo-preview {
            max-width: 200px;
            margin: 1rem 0;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($biodata): ?>
            <div class="page-header">
                <h1><i class="fas fa-edit"></i> Edit Biodata</h1>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($biodata['id']); ?>">
                <input type="hidden" name="existing_photo" value="<?php echo htmlspecialchars($biodata['photo_path']); ?>">

                <div class="form-section">
                    <h2 class="section-title"><i class="fas fa-user-circle"></i> Personal Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="photo">Profile Photo</label>
                            <?php if (!empty($biodata['photo_path']) && file_exists($biodata['photo_path'])): ?>
                                <img src="<?php echo htmlspecialchars($biodata['photo_path']); ?>" alt="Current Photo" class="photo-preview">
                            <?php endif; ?>
                            <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="name" class="required-field">Full Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($biodata['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="dob" class="required-field">Date of Birth</label>
                            <input type="date" name="dob" id="dob" class="form-control" value="<?php echo htmlspecialchars($biodata['dob']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="pob" class="required-field">Place of Birth</label>
                            <input type="text" name="pob" id="pob" class="form-control" value="<?php echo htmlspecialchars($biodata['pob']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="age" class="required-field">Age</label>
                            <input type="number" name="age" id="age" class="form-control" value="<?php echo htmlspecialchars($biodata['age']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="gender" class="required-field">Gender</label>
                            <select name="gender" id="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($biodata['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($biodata['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($biodata['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="height" class="required-field">Height</label>
                            <input type="text" name="height" id="height" class="form-control" value="<?php echo htmlspecialchars($biodata['height']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="marital_status" class="required-field">Marital Status</label>
                            <select name="marital_status" id="marital_status" class="form-control" required>
                                <option value="">Select Status</option>
                                <option value="Single" <?php echo ($biodata['marital_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                                <option value="Married" <?php echo ($biodata['marital_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                                <option value="Divorced" <?php echo ($biodata['marital_status'] == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                                <option value="Widowed" <?php echo ($biodata['marital_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="religion" class="required-field">Religion</label>
                            <input type="text" name="religion" id="religion" class="form-control" value="<?php echo htmlspecialchars($biodata['religion']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="nationality" class="required-field">Nationality</label>
                            <input type="text" name="nationality" id="nationality" class="form-control" value="<?php echo htmlspecialchars($biodata['nationality']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="blood_group" class="required-field">Blood Group</label>
                            <select name="blood_group" id="blood_group" class="form-control" required>
                                <option value="">Select Blood Group</option>
                                <option value="A+" <?php echo ($biodata['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo ($biodata['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo ($biodata['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo ($biodata['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                <option value="O+" <?php echo ($biodata['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo ($biodata['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                <option value="AB+" <?php echo ($biodata['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo ($biodata['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title"><i class="fas fa-address-book"></i> Contact Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="contact_number" class="required-field">Contact Number</label>
                            <input type="tel" name="contact_number" id="contact_number" class="form-control" value="<?php echo htmlspecialchars($biodata['contact_number']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email" class="required-field">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($biodata['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="permanent_address" class="required-field">Permanent Address</label>
                            <textarea name="permanent_address" id="permanent_address" class="form-control" required><?php echo htmlspecialchars($biodata['permanent_address']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="present_address" class="required-field">Present Address</label>
                            <textarea name="present_address" id="present_address" class="form-control" required><?php echo htmlspecialchars($biodata['present_address']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title"><i class="fas fa-users"></i> Family Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="father_name" class="required-field">Father's Name</label>
                            <input type="text" name="father_name" id="father_name" class="form-control" value="<?php echo htmlspecialchars($biodata['father_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="father_occupation">Father's Occupation</label>
                            <input type="text" name="father_occupation" id="father_occupation" class="form-control" value="<?php echo htmlspecialchars($biodata['father_occupation']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="mother_name" class="required-field">Mother's Name</label>
                            <input type="text" name="mother_name" id="mother_name" class="form-control" value="<?php echo htmlspecialchars($biodata['mother_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mother_occupation">Mother's Occupation</label>
                            <input type="text" name="mother_occupation" id="mother_occupation" class="form-control" value="<?php echo htmlspecialchars($biodata['mother_occupation']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="siblings">Siblings</label>
                            <textarea name="siblings" id="siblings" class="form-control"><?php echo htmlspecialchars($biodata['siblings']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title"><i class="fas fa-graduation-cap"></i> Educational Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="degree_level" class="required-field">Degree Level</label>
                            <input type="text" name="degree_level" id="degree_level" class="form-control" value="<?php echo htmlspecialchars($biodata['degree_level']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="institute" class="required-field">Institute</label>
                            <input type="text" name="institute" id="institute" class="form-control" value="<?php echo htmlspecialchars($biodata['institute']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="education_result" class="required-field">Result</label>
                            <input type="text" name="education_result" id="education_result" class="form-control" value="<?php echo htmlspecialchars($biodata['education_result']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="year_of_passing" class="required-field">Year of Passing</label>
                            <input type="number" name="year_of_passing" id="year_of_passing" class="form-control" min="1950" max="2099" value="<?php echo htmlspecialchars($biodata['year_of_passing']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="additional_certifications">Additional Certifications</label>
                            <textarea name="additional_certifications" id="additional_certifications" class="form-control"><?php echo htmlspecialchars($biodata['additional_certifications']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title"><i class="fas fa-briefcase"></i> Career Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="current_occupation">Current Occupation</label>
                            <input type="text" name="current_occupation" id="current_occupation" class="form-control" value="<?php echo htmlspecialchars($biodata['current_occupation']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="annual_income">Annual Income</label>
                            <input type="text" name="annual_income" id="annual_income" class="form-control" value="<?php echo htmlspecialchars($biodata['annual_income']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="future_career_plan">Future Career Plan</label>
                            <textarea name="future_career_plan" id="future_career_plan" class="form-control"><?php echo htmlspecialchars($biodata['future_career_plan']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title"><i class="fas fa-spa"></i> Physical & Lifestyle</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="complexion">Complexion</label>
                            <input type="text" name="complexion" id="complexion" class="form-control" value="<?php echo htmlspecialchars($biodata['complexion']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="body_type">Body Type</label>
                            <input type="text" name="body_type" id="body_type" class="form-control" value="<?php echo htmlspecialchars($biodata['body_type']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="diet">Diet</label>
                            <input type="text" name="diet" id="diet" class="form-control" value="<?php echo htmlspecialchars($biodata['diet']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="smoking">Smoking</label>
                            <select name="smoking" id="smoking" class="form-control">
                                <option value="">Select</option>
                                <option value="Yes" <?php echo ($biodata['smoking'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                <option value="No" <?php echo ($biodata['smoking'] == 'No') ? 'selected' : ''; ?>>No</option>
                                <option value="Occasionally" <?php echo ($biodata['smoking'] == 'Occasionally') ? 'selected' : ''; ?>>Occasionally</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="drinking">Drinking</label>
                            <select name="drinking" id="drinking" class="form-control">
                                <option value="">Select</option>
                                <option value="Yes" <?php echo ($biodata['drinking'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                <option value="No" <?php echo ($biodata['drinking'] == 'No') ? 'selected' : ''; ?>>No</option>
                                <option value="Occasionally" <?php echo ($biodata['drinking'] == 'Occasionally') ? 'selected' : ''; ?>>Occasionally</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="hobbies_interests">Hobbies & Interests</label>
                            <textarea name="hobbies_interests" id="hobbies_interests" class="form-control"><?php echo htmlspecialchars($biodata['hobbies_interests']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title"><i class="fas fa-heart"></i> Partner Preferences</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="partner_age_range">Partner Age Range</label>
                            <input type="text" name="partner_age_range" id="partner_age_range" class="form-control" value="<?php echo htmlspecialchars($biodata['partner_age_range']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="partner_height">Partner Height</label>
                            <input type="text" name="partner_height" id="partner_height" class="form-control" value="<?php echo htmlspecialchars($biodata['partner_height']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="partner_education">Partner Education</label>
                            <input type="text" name="partner_education" id="partner_education" class="form-control" value="<?php echo htmlspecialchars($biodata['partner_education']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="partner_occupation">Partner Occupation</label>
                            <input type="text" name="partner_occupation" id="partner_occupation" class="form-control" value="<?php echo htmlspecialchars($biodata['partner_occupation']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="partner_religion">Partner Religion</label>
                            <input type="text" name="partner_religion" id="partner_religion" class="form-control" value="<?php echo htmlspecialchars($biodata['partner_religion']); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="section-title"><i class="fas fa-info-circle"></i> Additional Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="about_me">About Me</label>
                            <textarea name="about_me" id="about_me" class="form-control"><?php echo htmlspecialchars($biodata['about_me']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="languages_known">Languages Known</label>
                            <textarea name="languages_known" id="languages_known" class="form-control"><?php echo htmlspecialchars($biodata['languages_known']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="future_plans">Future Plans (Personal)</label>
                            <textarea name="future_plans" id="future_plans" class="form-control"><?php echo htmlspecialchars($biodata['future_plans']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="health_issues">Health Issues (if any)</label>
                            <textarea name="health_issues" id="health_issues" class="form-control"><?php echo htmlspecialchars($biodata['health_issues']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="social_media_links">Social Media Links</label>
                            <textarea name="social_media_links" id="social_media_links" class="form-control"><?php echo htmlspecialchars($biodata['social_media_links']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="comments">Any other comments</label>
                            <textarea name="comments" id="comments" class="form-control"><?php echo htmlspecialchars($biodata['comments']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Biodata
                    </button>
                    <a href="dashboard.php?id=<?php echo htmlspecialchars($biodata['id']); ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to View
                    </a>
                </div>
            </form>
        <?php else: ?>
            <div class="card">
                <div class="page-header">
                    <h1><i class="fas fa-exclamation-circle"></i> Biodata Not Found</h1>
                    <p>The requested biodata could not be found.</p>
                </div>
                <div class="buttons">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>