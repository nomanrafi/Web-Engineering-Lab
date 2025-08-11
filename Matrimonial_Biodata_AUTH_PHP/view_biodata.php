<?php
session_start();
require_once 'database_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$biodata = null;
$biodata_id = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $biodata_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM biodata WHERE id = ?");
    $stmt->bind_param("i", $biodata_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $biodata = $result->fetch_assoc();

    if (!$biodata) {
        $_SESSION['error'] = "Biodata not found.";
        header("Location: dashboard.php");
        exit();
    }
} else {
    $_SESSION['error'] = "No biodata ID provided.";
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Biodata - CV Format</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            border-radius: 15px 15px 0 0;
            margin-bottom: 0;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid rgba(255,255,255,0.3);
            margin-bottom: 1rem;
            object-fit: cover;
        }
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: white;
        }
        .contact-info {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
            flex-wrap: wrap; /* Allow items to wrap on smaller screens */
        }
        .contact-info span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
        }
        .main-content {
            padding: 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Responsive grid */
            gap: 2rem;
            background: white;
            border-radius: 0 0 15px 15px;
        }
        .section-title {
            font-size: 1.2rem;
            color: #667eea;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .info-group {
            margin-bottom: 1rem;
            display: flex; /* Use flex for label-value alignment */
            flex-wrap: wrap; /* Allow wrapping */
        }
        .info-label {
            font-weight: 600;
            color: #555;
            flex: 0 0 150px; /* Fixed width for labels */
            padding-right: 10px; /* Space between label and value */
        }
        .info-value {
            flex: 1; /* Take remaining space */
            word-break: break-word; /* Break long words */
        }
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            .contact-info {
                flex-direction: column;
                gap: 1rem;
            }
            .info-label {
                flex: none;
                width: 100%;
                margin-bottom: 0.25rem;
            }
            .info-value {
                flex: none;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php if ($biodata): ?>
    <div class="container fade-in">
        <div class="page-header">
            <?php if (!empty($biodata['photo_path']) && file_exists($biodata['photo_path'])): ?>
                <img src="<?php echo htmlspecialchars($biodata['photo_path']); ?>" alt="Profile Photo" class="profile-img">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($biodata['name']); ?></h1>
            <?php if (!empty($biodata['current_occupation'])): ?>
                <p><?php echo htmlspecialchars($biodata['current_occupation']); ?></p>
            <?php endif; ?>
            <div class="contact-info">
                <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($biodata['contact_number']); ?></span>
                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($biodata['email']); ?></span>
                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($biodata['present_address']); ?></span>
            </div>
        </div>

        <div class="main-content card">
            <!-- Left Column -->
            <div class="left-column">
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-user"></i> Personal Information</h2>
                    <div class="info-group">
                        <span class="info-label">Date of Birth:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['dob']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Place of Birth:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['pob']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Age:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['age']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Gender:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['gender']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Height:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['height']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Marital Status:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['marital_status']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Religion:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['religion']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Nationality:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['nationality']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Blood Group:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['blood_group']); ?></span>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title"><i class="fas fa-users"></i> Family Information</h2>
                    <div class="info-group">
                        <span class="info-label">Father's Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['father_name']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Father's Occupation:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['father_occupation']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Mother's Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['mother_name']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Mother's Occupation:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['mother_occupation']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Siblings:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['siblings']); ?></span>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title"><i class="fas fa-search-plus"></i> Partner Preferences</h2>
                    <div class="info-group">
                        <span class="info-label">Age Range:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['partner_age_range']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Height:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['partner_height']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Education:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['partner_education']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Occupation:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['partner_occupation']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Religion:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['partner_religion']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="right-column">
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-info-circle"></i> About Me</h2>
                    <div class="info-group">
                        <span class="info-label">Description:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['about_me']); ?></span>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title"><i class="fas fa-graduation-cap"></i> Education</h2>
                    <div class="info-group">
                        <span class="info-label">Degree Level:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['degree_level']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Institute:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['institute']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Result:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['education_result']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Year of Passing:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['year_of_passing']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Certifications:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['additional_certifications']); ?></span>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title"><i class="fas fa-briefcase"></i> Career Information</h2>
                    <div class="info-group">
                        <span class="info-label">Current Occupation:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['current_occupation']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Annual Income:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['annual_income']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Future Career Plan:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['future_career_plan']); ?></span>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-heartbeat"></i> Physical & Lifestyle</h2>
                    <div class="info-group">
                        <span class="info-label">Complexion:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['complexion']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Body Type:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['body_type']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Diet:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['diet']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Smoking:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['smoking']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Drinking:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['drinking']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Hobbies & Interests:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['hobbies_interests']); ?></span>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-globe"></i> Languages & Social</h2>
                    <div class="info-group">
                        <span class="info-label">Languages Known:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['languages_known']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Social Media:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['social_media_links']); ?></span>
                    </div>
                </div>

                <div class="section">
                    <h2 class="section-title"><i class="fas fa-notes-medical"></i> Health & Comments</h2>
                    <div class="info-group">
                        <span class="info-label">Health Issues:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['health_issues']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Future Plans:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['future_plans']); ?></span>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Comments:</span>
                        <span class="info-value"><?php echo htmlspecialchars($biodata['comments']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="buttons container-fluid">
            <a href="edit_biodata.php?id=<?php echo htmlspecialchars($biodata['id']); ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="delete_biodata.php?id=<?php echo htmlspecialchars($biodata['id']); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this biodata?');">
                <i class="fas fa-trash"></i> Delete
            </a>
            <a href="generate_pdf.php?id=<?php echo htmlspecialchars($biodata['id']); ?>" class="btn btn-primary">
                <i class="fas fa-download"></i> Download PDF
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="container card">
        <div class="page-header">
            <h1>Error</h1>
        </div>
        <div style="padding: 2rem; text-align: center;">
            <div class="alert alert-error">
                <?php 
                if (isset($_SESSION['error'])) {
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                } else {
                    echo "Biodata not found or no ID provided.";
                }
                ?>
            </div>
        </div>
        <div class="buttons">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>