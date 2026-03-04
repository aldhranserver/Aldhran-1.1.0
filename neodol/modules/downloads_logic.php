<?php
if (!defined('IN_CMS')) { exit; }

$action = $_GET['action'] ?? '';

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentUserId <= 0) { die("Login required."); }

    $title = strip_tags($_POST['title'] ?? 'Untitled');
    $category = strip_tags($_POST['category'] ?? 'tools');
    $description = strip_tags($_POST['description'] ?? '');
    $install_info = strip_tags($_POST['how_to_install'] ?? ''); // Neu hinzugefügt

    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['resource_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['zip', 'cs', 'sql', 'txt', 'rar', '7z'];

        if (in_array($ext, $allowed)) {
            $newFileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $fileName);
            $dir = './uploads/downloads/';
            if (!is_dir($dir)) { mkdir($dir, 0755, true); }

            if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $dir . $newFileName)) {
                // Hier fügen wir how_to_install in das Query ein
                $stmt = $db->prepare("INSERT INTO neodol_downloads (category, title, author_id, what_it_does, how_to_install, file_path, status) VALUES (?, ?, ?, ?, ?, ?, 'approved')");
                $stmt->execute([$category, $title, $currentUserId, $description, $install_info, $newFileName]);
                
                header("Location: index.php?p=downloads&msg=success");
                exit;
            }
        }
    }
    header("Location: index.php?p=downloads&msg=error");
    exit;
}