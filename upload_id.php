<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
require_once __DIR__ . '/backend/includes/db.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT valid_id_path, id_type, id_verification_status, id_uploaded_at FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$idInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$status = $idInfo['id_verification_status'] ?? 'not_submitted';

function idStatusInfo($status) {
    switch ($status) {
        case 'verified': return ['✓ Verified', '#e6f2e0', '#3c6b41'];
        case 'pending':  return ['⏳ Pending Review', '#fdf3d9', '#8a6d1a'];
        case 'rejected': return ['✕ Rejected — Mag-upload ulit', '#fbe4e1', '#c0392b'];
        default:          return ['Wala pang na-upload', '#f0ece0', '#6b6350'];
    }
}
[$statusLabel, $statusBg, $statusFg] = idStatusInfo($status);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload ID — Pahingahan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f7f0d8;
            color: #2f2a20;
            margin: 0;
            padding: 40px 20px;
        }
        .page {
            max-width: 480px;
            margin: 0 auto;
        }
        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            margin: 0;
            color: #3c6b41;
        }
        .btn-back {
            padding: 10px 18px;
            border-radius: 10px;
            background: #fff;
            color: #3c6b41;
            text-decoration: none;
            font-weight: 600;
            font-size: .85rem;
            border: 1px solid #e2ddc9;
        }
        .id-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: .85rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .current-id-label {
            font-size: .85rem;
            color: #8a8266;
            margin: 0 0 8px;
            font-weight: 600;
        }
        .current-id-preview {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e2ddc9;
        }
        .form-row {
            margin-bottom: 16px;
        }
        .form-row label {
            display: block;
            font-weight: 600;
            font-size: .9rem;
            margin-bottom: 6px;
        }
        .form-row select {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e2ddc9;
            border-radius: 10px;
            font-family: inherit;
            font-size: .95rem;
            background: #fff;
        }
        .form-row select:focus {
            outline: none;
            border-color: #5c8a3a;
        }
        .upload-area {
            border: 2px dashed #e2ddc9;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.15s ease;
        }
        .upload-area:hover {
            border-color: #5c8a3a;
        }
        .upload-area.has-file {
            border-color: #5c8a3a;
            background: #f7faf2;
        }
        .upload-icon { font-size: 2.5rem; margin-bottom: 10px; }
        .upload-text { color: #8a8266; font-size: .9rem; }
        input[type="file"] { display: none; }
        button[type="submit"] {
            width: 100%;
            margin-top: 20px;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #5c8a3a;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
        }
        button[type="submit"]:hover { background: #4a7130; }
        button[type="submit"]:disabled { background: #cfc9b0; cursor: not-allowed; }
        .info-note {
            font-size: .8rem;
            color: #8a8266;
            margin-top: 12px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="page">
    <div class="page-head">
        <h1>🪪 Upload ID</h1>
        <a href="homepage.php#top" class="btn-back">← Back</a>
    </div>

    <div class="id-box">
        <span class="status-badge" style="background:<?php echo $statusBg; ?>; color:<?php echo $statusFg; ?>;">
            <?php echo $statusLabel; ?>
        </span>

        <?php if (!empty($idInfo['valid_id_path'])): ?>
            <p class="current-id-label">Kasalukuyang na-upload: <?php echo htmlspecialchars($idInfo['id_type'] ?? 'ID'); ?></p>
            <img src="<?php echo htmlspecialchars($idInfo['valid_id_path']); ?>" class="current-id-preview" alt="Current ID">
        <?php endif; ?>

        <form id="idUploadForm" enctype="multipart/form-data">
            <div class="form-row">
                <label>Klase ng Valid ID</label>
                <select id="idTypeSelect" required>
                    <option value="">-- Pumili ng ID type --</option>
                    <option value="Philippine Passport">Philippine Passport</option>
                    <option value="Driver's License">Driver's License</option>
                    <option value="UMID">UMID</option>
                    <option value="PhilSys National ID">PhilSys National ID</option>
                    <option value="Postal ID">Postal ID</option>
                    <option value="Voter's ID/Certificate">Voter's ID/Certificate</option>
                    <option value="PRC ID">PRC ID</option>
                    <option value="SSS ID">SSS ID</option>
                    <option value="GSIS ID">GSIS ID</option>
                </select>
            </div>

            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📄</div>
                <div class="upload-text" id="uploadText">I-click para pumili ng valid ID (JPG/PNG, max 5MB)</div>
                <input type="file" id="idPhotoInput" name="id_photo" accept="image/jpeg,image/png">
            </div>
            <button type="submit" id="submitBtn" disabled>Upload ID</button>
        </form>

        <p class="info-note">Tinatanggap na valid IDs: Passport, Driver's License, National ID, UMID, o Postal ID.</p>
    </div>
</div>

<script>
    const uploadArea = document.getElementById('uploadArea');
    const idPhotoInput = document.getElementById('idPhotoInput');
    const uploadText = document.getElementById('uploadText');
    const submitBtn = document.getElementById('submitBtn');

    uploadArea.addEventListener('click', () => idPhotoInput.click());

    idPhotoInput.addEventListener('change', () => {
        if (idPhotoInput.files.length > 0) {
            uploadText.textContent = idPhotoInput.files[0].name;
            uploadArea.classList.add('has-file');
            submitBtn.disabled = false;
        }
    });

    document.getElementById('idUploadForm').addEventListener('submit', (e) => {
        e.preventDefault();

        const idType = document.getElementById('idTypeSelect').value;
        if (!idType) {
            alert('Pumili muna ng klase ng ID.');
            return;
        }
        if (!idPhotoInput.files[0]) {
            alert('Pumili muna ng ID photo.');
            return;
        }

        const formData = new FormData();
        formData.append('id_photo', idPhotoInput.files[0]);
        formData.append('id_type', idType);

        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading...';

        fetch('api/upload_id.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.error || 'Something went wrong.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Upload ID';
            }
        })
        .catch(() => {
            alert('Something went wrong. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Upload ID';
        });
    });
</script>

</body>
</html>