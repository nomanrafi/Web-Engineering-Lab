<?php
session_start();
require_once 'database_connection.php';
require __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$biodata_id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM biodata WHERE id = ?");
$stmt->bind_param("i", $biodata_id);
$stmt->execute();
$result = $stmt->get_result();
$biodata = $result->fetch_assoc();

if (!$biodata) {
    header("Location: dashboard.php");
    exit();
}

class BiodataPDF extends FPDF {
    public $name;
    public $firstPage = true;

    function setBiodataName($name) {
        $this->name = $name;
    }

    function Header() {
        if ($this->firstPage) {
            // Subtle gradient header
            for ($i = 0; $i < 25; $i++) {
                $this->SetFillColor(220 - $i * 2, 225 - $i * 2, 235 - $i);
                $this->Rect(0, $i, $this->GetPageWidth(), 1, 'F');
            }
            
            // Main title
            $this->SetFont('Helvetica', 'B', 22);
            $this->SetTextColor(20, 20, 60);
            $this->SetY(8);
            $this->Cell(0, 12, 'Biodata Profile', 0, 1, 'C');
            $this->SetDrawColor(180, 180, 180);
            $this->Line(20, $this->GetY(), $this->GetPageWidth() - 20, $this->GetY());
            $this->Ln(8);
        }
    }

    function SectionTitle($title) {
        // Section background
        $this->SetFillColor(240, 242, 245);
        $this->SetDrawColor(20, 20, 60);
        $this->Rect(15, $this->GetY(), $this->GetPageWidth() - 30, 8, 'F');
        
        // Section title
        $this->SetFont('Helvetica', 'B', 13);
        $this->SetTextColor(20, 20, 60);
        $this->Cell(0, 8, '  ' . $title, 0, 1, 'L', true);
        $this->SetTextColor(0);
        $this->Ln(4);
    }

    function InfoField($label, $value, $is_multiline = false) {
        if (!empty($value)) {
            // Label
            $this->SetFont('Helvetica', 'B', 10);
            $this->SetTextColor(50, 50, 50);
            $this->Cell(55, 7, $label . ':', 0, 0);
            // Value
            $this->SetFont('Helvetica', '', 10);
            $this->SetTextColor(0, 0, 0);
            if ($is_multiline) {
                $this->MultiCell(135, 7, $value, 0, 'L');
            } else {
                $this->Cell(135, 7, $value, 0, 1);
            }
            $this->Ln(2);
        }
    }

    function CenterPhotoAndName($photo_path, $name) {
        $this->Ln(15);
        
        if (!empty($photo_path) && file_exists($photo_path)) {
            $startY = $this->GetY();
            list($origWidth, $origHeight) = getimagesize($photo_path);
            
            $maxWidth = 80;
            $maxHeight = 100;
            $ratio = min($maxWidth/$origWidth, $maxHeight/$origHeight);
            $imageWidth = $origWidth * $ratio;
            $imageHeight = $origHeight * $ratio;
            
            $x = ($this->GetPageWidth() - $imageWidth) / 2;
            
            // Photo border
            $this->SetDrawColor(20, 20, 60);
            $this->Rect($x - 1, $startY - 1, $imageWidth + 2, $imageHeight + 2);
            
            $this->Image($photo_path, $x, $startY, $imageWidth, $imageHeight);
            $this->SetY($startY + $imageHeight + 8);
        }

        // Name
        $this->SetFont('Helvetica', 'B', 18);
        $this->SetTextColor(20, 20, 60);
        $this->Cell(0, 10, $name, 0, 1, 'C');
        $this->SetDrawColor(180, 180, 180);
        $this->Line(30, $this->GetY(), $this->GetPageWidth() - 30, $this->GetY());
        $this->Ln(8);
    }

    function AddPage($orientation = '', $size = '', $rotation = 0) {
        parent::AddPage($orientation, $size, $rotation);
        if (!$this->firstPage) {
            $this->SetY(15);
        }
        $this->firstPage = false;
    }
}

// Build PDF
$pdf = new BiodataPDF();
// Set margins to remove footer space (left, top, right, bottom)
$pdf->SetMargins(15, 15, 15, 0);
$pdf->SetAutoPageBreak(true, 0); // Set bottom margin to 0 for no footer space
$pdf->setBiodataName($biodata['name']);
$pdf->AliasNbPages();
$pdf->AddPage();

// Centered Photo and Name
$pdf->CenterPhotoAndName($biodata['photo_path'], $biodata['name']);

// Contact Information
$pdf->SectionTitle('Contact Information');
$pdf->InfoField('Phone Number', $biodata['contact_number']);
$pdf->InfoField('Email Address', $biodata['email']);
$pdf->InfoField('Present Address', $biodata['present_address'], true);
$pdf->InfoField('Permanent Address', $biodata['permanent_address'], true);
$pdf->InfoField('Social Media', $biodata['social_media_links'], true);

// Personal Details
$pdf->SectionTitle('Personal Details');
$pdf->InfoField('Name', $biodata['name']);
$pdf->InfoField('Date of Birth', $biodata['dob']);
$pdf->InfoField('Place of Birth', $biodata['pob']);
$pdf->InfoField('Age', $biodata['age']);
$pdf->InfoField('Gender', $biodata['gender']);
$pdf->InfoField('Height', $biodata['height']);
$pdf->InfoField('Marital Status', $biodata['marital_status']);
$pdf->InfoField('Religion', $biodata['religion']);
$pdf->InfoField('Nationality', $biodata['nationality']);
$pdf->InfoField('Blood Group', $biodata['blood_group']);
$pdf->InfoField('Languages Known', $biodata['languages_known'], true);

// Family Information
$pdf->SectionTitle('Family Information');
$pdf->InfoField('Father\'s Name', $biodata['father_name']);
$pdf->InfoField('Father\'s Occupation', $biodata['father_occupation']);
$pdf->InfoField('Mother\'s Name', $biodata['mother_name']);
$pdf->InfoField('Mother\'s Occupation', $biodata['mother_occupation']);
$pdf->InfoField('Siblings', $biodata['siblings']);

// About Me & Interests
$pdf->SectionTitle('About Me & Interests');
$pdf->InfoField('About Me', $biodata['about_me'], true);
$pdf->InfoField('Hobbies & Interests', $biodata['hobbies_interests'], true);

// Educational Background
$pdf->SectionTitle('Educational Background');
$pdf->InfoField('Degree Level', $biodata['degree_level']);
$pdf->InfoField('Institution', $biodata['institute']);
$pdf->InfoField('Result/CGPA', $biodata['education_result']);
$pdf->InfoField('Passing Year', $biodata['year_of_passing']);
$pdf->InfoField('Additional Certifications', $biodata['additional_certifications'], true);

// Career Information
$pdf->SectionTitle('Career Information');
$pdf->InfoField('Current Occupation', $biodata['current_occupation']);
$pdf->InfoField('Annual Income', $biodata['annual_income']);
$pdf->InfoField('Future Career Plan', $biodata['future_career_plan'], true);

// Future Plans
$pdf->SectionTitle('Future Plans & Goals');
$pdf->InfoField('Future Plans', $biodata['future_plans'], true);

// Physical Attributes & Lifestyle
$pdf->SectionTitle('Physical Attributes & Lifestyle');
$pdf->InfoField('Complexion', $biodata['complexion']);
$pdf->InfoField('Body Type', $biodata['body_type']);
$pdf->InfoField('Height', $biodata['height']);
$pdf->InfoField('Diet Preferences', $biodata['diet']);
$pdf->InfoField('Smoking Habits', $biodata['smoking']);
$pdf->InfoField('Drinking Habits', $biodata['drinking']);

// Health Information
$pdf->SectionTitle('Health Information');
$pdf->InfoField('Blood Group', $biodata['blood_group']);
$pdf->InfoField('Health Issues/Conditions', $biodata['health_issues'], true);

// Partner Preferences
$pdf->SectionTitle('Partner Preferences');
$pdf->InfoField('Preferred Age Range', $biodata['partner_age_range']);
$pdf->InfoField('Preferred Height', $biodata['partner_height']);
$pdf->InfoField('Preferred Education', $biodata['partner_education']);
$pdf->InfoField('Preferred Occupation', $biodata['partner_occupation']);
$pdf->InfoField('Preferred Religion', $biodata['partner_religion']);

// Additional Information
$pdf->SectionTitle('Additional Information');
$pdf->InfoField('Comments', $biodata['comments'], true);
$pdf->Ln(5);

// Output PDF
$filename = 'Biodata_' . str_replace(' ', '_', $biodata['name']) . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename);
?>