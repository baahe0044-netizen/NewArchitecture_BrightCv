<?php
header('Content-Type: application/json');

function getPdo(): PDO
{
    return new PDO(
        "mysql:host=localhost;dbname=lunettistar_db;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
}

$pdo = getPdo();

function json_ok($data = [])
{
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function json_err($msg)
{
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function getJsonInput()
{
    return json_decode(file_get_contents("php://input"), true) ?? [];
}

/* =========================
   DASHBOARD DATA (NEW)
========================= */
function handle_dashboard_data(PDO $pdo)
{
    $userId = 1; // DEV MODE (replace with session later)

    // CV COUNT
    $st = $pdo->prepare("SELECT COUNT(*) total FROM resumes WHERE user_id=?");
    $st->execute([$userId]);
    $cvCount = $st->fetch()['total'];

    // DOWNLOADS
    $st = $pdo->prepare("
        SELECT COUNT(*) total
        FROM resume_generations rg
        JOIN resumes r ON r.id = rg.resume_id
        WHERE r.user_id=?
    ");
    $st->execute([$userId]);
    $downloads = $st->fetch()['total'];

    // ATS SCORE
    $st = $pdo->prepare("
        SELECT AVG(score) avg_score
        FROM resume_ats_scores rs
        JOIN resumes r ON r.id = rs.resume_id
        WHERE r.user_id=?
    ");
    $st->execute([$userId]);
    $ats = round($st->fetch()['avg_score'] ?? 0);

    // RECENT CVS
    $st = $pdo->prepare("
        SELECT id, name, updated_at
        FROM resumes
        WHERE user_id=?
        ORDER BY updated_at DESC
        LIMIT 5
    ");
    $st->execute([$userId]);
    $recent = $st->fetchAll();

    json_ok([
        "total_cvs" => $cvCount,
        "total_downloads" => $downloads,
        "ats_score" => $ats,
        "recent_cvs" => $recent
    ]);
}

/* =========================
   GET RESUMES
========================= */
function handle_get_resumes(PDO $pdo)
{
    $userId = 1;

    $st = $pdo->prepare("
        SELECT id, name, updated_at
        FROM resumes
        WHERE user_id=?
        ORDER BY updated_at DESC
    ");
    $st->execute([$userId]);

    json_ok($st->fetchAll());
}

/* =========================
   CREATE RESUME
========================= */
function handle_create_resume(PDO $pdo)
{
    $data = getJsonInput();
    $title = $data['title'] ?? 'My Resume';

    $st = $pdo->prepare("INSERT INTO resumes (user_id, name) VALUES (1, ?)");
    $st->execute([$title]);

    json_ok(["resume_id" => $pdo->lastInsertId()]);
}

/* =========================
   ROUTER
========================= */
$action = $_GET['action'] ?? '';

switch ($action) {

    case "dashboard_data":
        handle_dashboard_data($pdo);
        break;

    case "get_resumes":
        handle_get_resumes($pdo);
        break;

    case "create_resume":
        handle_create_resume($pdo);
        break;

    default:
        json_err("Invalid action");
}
