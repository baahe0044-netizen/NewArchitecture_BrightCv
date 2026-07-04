<?php
session_start();

/*if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized access.");
}*/

require_once 'connect.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;

$resume_id = isset($_GET['resume_id']) ? intval($_GET['resume_id']) : 0;
$user_id = $_SESSION['user_id'];

// Fetch resume ownership
$stmt = $pdo->prepare("SELECT * FROM resumes WHERE id = ? AND user_id = ?");
$stmt->execute([$resume_id, $user_id]);
$resume = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resume) {
    http_response_code(403);
    die("Resume not found or access denied.");
}

// Fetch personal info
$stmt = $pdo->prepare("SELECT * FROM resume_personal WHERE resume_id = ?");
$stmt->execute([$resume_id]);
$personal = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch experiences
$stmt = $pdo->prepare("SELECT * FROM resume_experience WHERE resume_id = ? ORDER BY sort_order");
$stmt->execute([$resume_id]);
$experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch education
$stmt = $pdo->prepare("SELECT * FROM resume_education WHERE resume_id = ? ORDER BY sort_order");
$stmt->execute([$resume_id]);
$education = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch skills
$stmt = $pdo->prepare("SELECT skill_name FROM resume_skills WHERE resume_id = ? ORDER BY sort_order");
$stmt->execute([$resume_id]);
$skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

function generateResumeHTML($personal, $experiences, $education, $skills)
{
    $html = '<html><head><style>
        body { font-family: Arial, sans-serif; }
        h1 { font-size: 24px; margin-bottom: 5px; }
        h2 { font-size: 18px; margin-top: 20px; }
        p, li { font-size: 14px; margin: 3px 0; }
        ul { padding-left: 20px; }
        .section { margin-bottom: 20px; }
        .label { font-weight: bold; }
    </style></head><body>';

    if ($personal) {
        $html .= "<h1>" . htmlspecialchars($personal['full_name']) . "</h1>";
        $html .= "<p><span class='label'>Email:</span> " . htmlspecialchars($personal['email']) . "<br>";
        $html .= "<span class='label'>Phone:</span> " . htmlspecialchars($personal['phone']) . "<br>";
        $html .= "<span class='label'>Location:</span> " . htmlspecialchars($personal['location']) . "<br>";
        $html .= "<span class='label'>LinkedIn:</span> " . htmlspecialchars($personal['linkedin']) . "</p>";
        $html .= "<div class='section'><h2>Summary</h2><p>" . nl2br(htmlspecialchars($personal['summary'])) . "</p></div>";
    }

    if ($experiences) {
        $html .= "<div class='section'><h2>Experience</h2>";
        foreach ($experiences as $exp) {
            $html .= "<p><strong>" . htmlspecialchars($exp['job_title']) . "</strong> at " . htmlspecialchars($exp['company']) . "<br>";
            $html .= htmlspecialchars($exp['start_date']) . " - " . htmlspecialchars($exp['end_date']) . "<br>";
            $html .= nl2br(htmlspecialchars($exp['description'])) . "</p>";
        }
        $html .= "</div>";
    }

    if ($education) {
        $html .= "<div class='section'><h2>Education</h2>";
        foreach ($education as $edu) {
            $html .= "<p><strong>" . htmlspecialchars($edu['degree']) . ", " . htmlspecialchars($edu['field_of_study']) . "</strong><br>";
            $html .= htmlspecialchars($edu['institution']) . " - " . htmlspecialchars($edu['graduation_year']) . "</p>";
        }
        $html .= "</div>";
    }

    if ($skills) {
        $html .= "<div class='section'><h2>Skills</h2><ul>";
        foreach ($skills as $skill) {
            $html .= "<li>" . htmlspecialchars($skill) . "</li>";
        }
        $html .= "</ul></div>";
    }

    $html .= "</body></html>";
    return $html;
}

$html = generateResumeHTML($personal, $experiences, $education, $skills);

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'resume_' . $resume_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
$outputPath = __DIR__ . '/generated_resumes/' . $filename;
file_put_contents($outputPath, $dompdf->output());

$stmt = $pdo->prepare("INSERT INTO resume_generations (resume_id, generation_type, file_path) VALUES (?, 'PDF', ?)");
$stmt->execute([$resume_id, $filename]);

$dompdf->stream($filename, ["Attachment" => 1]);
exit();
?>
