<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once 'includes/admin_functions.php';

// Vérifier si l'utilisateur est connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Gérer l'upload de fichiers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    try {
        // Spécifier le dossier d'upload pour la médiathèque
        $upload_folder = 'uploads/media';

        // Définir les types de fichiers autorisés (tous les types)
        $allowed_types = [
            // Images
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            // Documents
            'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            // Archives
            'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
            // Audio
            'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/midi',
            // Vidéo
            'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime',
            // Autres
            'text/plain', 'text/csv', 'text/html', 'application/json'
        ];

        // Taille maximale de 100MB
        $max_size = 104857600; // 100MB en octets

        $file_path = uploadFile($_FILES['media_file'], $upload_folder, $allowed_types, $max_size);

        if ($file_path) {
            // Optionnel: Enregistrer les informations du fichier dans la base de données si nécessaire
            // Par exemple, si vous avez une table 'media' avec 'filename', 'filepath', 'uploaded_by', etc.
            // $stmt = $conn->prepare("INSERT INTO media (filename, filepath, uploaded_by) VALUES (?, ?, ?)");
            // $stmt->execute([$_FILES['media_file']['name'], $file_path, $_SESSION['admin_id']]);

            $success = 'Fichier téléversé avec succès : ' . htmlspecialchars($file_path);
             logAdminAction($conn, $_SESSION['admin_id'], 'upload_media', 'Fichier téléversé : ' . $file_path);

        } else {
            $error = 'Une erreur est survenue lors du téléversement du fichier. Vérifiez la taille (max 100MB) ou le type de fichier.';
        }
    } catch (Exception $e) {
        $error = 'Une erreur est survenue : ' . $e->getMessage();
         logAdminAction($conn, $_SESSION['admin_id'], 'upload_media_error', 'Erreur: ' . $e->getMessage());
    }
}

// Fonction pour lister les fichiers dans un répertoire (simple, sans base de données)
function listFilesInDirectory($directory) {
    $files = [];
    // Assurez-vous que le chemin est correct par rapport au script PHP
    $scan_directory = __DIR__ . '/../' . $directory;

    if (is_dir($scan_directory)) {
        $items = scandir($scan_directory);
        if ($items !== false) {
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..') {
                    $files[] = $directory . '/' . $item;
                }
            }
        }
    }
    return $files;
}

// Lister les fichiers de la médiathèque (en supposant qu'ils sont tous dans 'uploads/media')
// Lister les fichiers de plusieurs répertoires d'upload
$upload_directories = ['uploads/media', 'uploads/articles', 'uploads/avatars'];
$media_files = [];

foreach ($upload_directories as $directory) {
    $files_in_dir = listFilesInDirectory($directory);
    $media_files = array_merge($media_files, $files_in_dir);
}

// Optionnel: Trier les fichiers par nom ou date si souhaité
// usort($media_files, function($a, $b) { return basename($a) <=> basename($b); });

include 'includes/admin_header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Médiathèque</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Médiathèque</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Téléverser un nouveau fichier</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?= $success ?></div>
                            <?php endif; ?>
                            <form action="media.php" method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="media_file">Choisir un fichier :</label>
                                    <input type="file" class="form-control" id="media_file" name="media_file" required>
                                    <small class="form-text text-muted">
                                        Types de fichiers acceptés : Images (JPG, PNG, GIF, WebP, SVG), Documents (PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX), 
                                        Archives (ZIP, RAR, 7Z), Audio (MP3, WAV, OGG, MIDI), Vidéo (MP4, WebM, OGG, MOV), 
                                        Texte (TXT, CSV, HTML, JSON). Taille maximale : 100MB.
                                    </small>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Téléverser</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                     <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Fichiers de la médiathèque</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($media_files)): ?>
                                <div class="alert alert-info">Aucun fichier dans la médiathèque.</div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($media_files as $file): ?>
                                        <div class="col-md-3 col-sm-4 col-6 mb-4">
                                            <div class="card h-100">
                                                <?php
                                                $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                                                $isVideo = in_array($fileExtension, ['mp4', 'webm', 'ogg', 'mov']);
                                                $isAudio = in_array($fileExtension, ['mp3', 'wav', 'ogg', 'midi']);
                                                $isDocument = in_array($fileExtension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
                                                $isArchive = in_array($fileExtension, ['zip', 'rar', '7z']);
                                                $isText = in_array($fileExtension, ['txt', 'csv', 'html', 'json']);
                                                ?>
                                                <div class="card-body text-center">
                                                    <?php if ($isImage): ?>
                                                        <img src="../<?= htmlspecialchars($file) ?>" class="img-fluid mb-2" alt="<?= htmlspecialchars(basename($file)) ?>">
                                                    <?php elseif ($isVideo): ?>
                                                        <i class="bi bi-camera-video" style="font-size: 4rem;"></i>
                                                    <?php elseif ($isAudio): ?>
                                                        <i class="bi bi-music-note-beamed" style="font-size: 4rem;"></i>
                                                    <?php elseif ($isDocument): ?>
                                                        <i class="bi bi-file-earmark-text" style="font-size: 4rem;"></i>
                                                    <?php elseif ($isArchive): ?>
                                                        <i class="bi bi-file-earmark-zip" style="font-size: 4rem;"></i>
                                                    <?php elseif ($isText): ?>
                                                        <i class="bi bi-file-earmark-text" style="font-size: 4rem;"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-file-earmark" style="font-size: 4rem;"></i>
                                                    <?php endif; ?>
                                                    <p class="mb-1 text-truncate" title="<?= htmlspecialchars(basename($file)) ?>">
                                                        <?= htmlspecialchars(basename($file)) ?>
                                                    </p>
                                                    <small class="text-muted d-block mb-2">
                                                        <?= number_format(filesize('../' . $file) / 1024, 2) ?> KB
                                                    </small>
                                                    <div class="btn-group">
                                                        <a href="../<?= htmlspecialchars($file) ?>" class="btn btn-sm btn-info" target="_blank" title="Voir">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-danger" onclick="if(confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')) { window.location.href = 'media.php?action=delete&file=' + encodeURIComponent('<?= $file ?>'); } return false;" title="Supprimer">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
             <?php
                // Gérer la suppression de fichier si l'action est demandée
                if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file'])) {
                    $file_to_delete = sanitizeInput(urldecode($_GET['file']));
                    $full_path_to_delete = __DIR__ . '/../' . $file_to_delete;

                    // Sécurité : Assurez-vous que le fichier est bien dans l'un des dossiers d'upload
                    $allowed_upload_base = realpath(__DIR__ . '/../uploads');
                    $real_path_to_delete = realpath($full_path_to_delete);

                    if ($real_path_to_delete !== false && strpos($real_path_to_delete, $allowed_upload_base) === 0) {
                        if (file_exists($real_path_to_delete)) {
                            if (unlink($real_path_to_delete)) {
                                 logAdminAction($conn, $_SESSION['admin_id'], 'delete_media', 'Fichier média supprimé : ' . $file_to_delete);
                                header('Location: media.php?success=' . urlencode('Fichier supprimé avec succès.'));
                                exit;
                            } else {
                                $error = 'Erreur lors de la suppression du fichier.';
                            }
                        } else {
                             $error = 'Fichier non trouvé.';
                        }
                    } else {
                        $error = 'Action non autorisée ou chemin de fichier invalide.';
                    }
                     // Rediriger même en cas d'erreur pour nettoyer l'URL
                    header('Location: media.php?error=' . urlencode($error));
                    exit;
                }
            ?>
        </div>
    </section>
</div>

<?php include 'includes/admin_footer.php'; ?> 