<?php
require_once __DIR__ . '/backend/includes/auth_guard.php';
require_once __DIR__ . '/backend/includes/db.php';

if ($_SESSION['role'] !== 'guest') {
    header('Location: homepage.php');
    exit();
}

$userId = $_SESSION['user_id'];
$checkStmt = $pdo->prepare("SELECT status FROM host_applications WHERE user_id = :user_id ORDER BY submitted_at DESC LIMIT 1");
$checkStmt->execute([':user_id' => $userId]);
$existingApplication = $checkStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Host — Pahingahan</title>
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
        .page { max-width: 520px; margin: 0 auto; }
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
        .form-box {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
        }
        .status-notice {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: .9rem;
            font-weight: 600;
        }
        .status-pending { background: #fdf3d9; color: #8a6d1a; }
        .status-approved { background: #e6f2e0; color: #3c6b41; }
        .status-declined { background: #fbe4e1; color: #c0392b; }
        .form-row { margin-bottom: 18px; }
        label {
            display: block;
            font-weight: 600;
            font-size: .9rem;
            margin-bottom: 6px;
        }
        input[type="text"], input[type="tel"], textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e2ddc9;
            border-radius: 10px;
            font-family: inherit;
            font-size: .95rem;
        }
        textarea { min-height: 90px; resize: vertical; }
        input:focus, textarea:focus { outline: none; border-color: #5c8a3a; }
        .upload-area {
            border: 2px dashed #e2ddc9;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
        }
        .upload-area:hover { border-color: #5c8a3a; }
        .upload-area.has-file { border-color: #5c8a3a; background: #f7faf2; }
        .upload-icon { font-size: 2rem; margin-bottom: 8px; }
        .upload-text { color: #8a8266; font-size: .85rem; }
        input[type="file"] { display: none; }
        button[type="submit"] {
            width: 100%;
            margin-top: 8px;
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
    </style>
</head>
<body>

<div class="page">
    <div class="page-head">
        <h1>🏠 Become a Host</h1>
        <a href="homepage.php#top" class="btn-back">← Back</a>
    </div>

    <div class="form-box">
        <?php if ($existingApplication && $existingApplication['status'] === 'pending'): ?>
            <div class="status-notice status-pending">⏳ Your application is still awaiting review. We'll get back to you soon.</div>
        <?php elseif ($existingApplication && $existingApplication['status'] === 'declined'): ?>
            <div class="status-notice status-declined">✕ Your last application was declined. You can apply again below.</div>
        <?php endif; ?>

        <?php if (!$existingApplication || $existingApplication['status'] !== 'pending'): ?>
            <form id="hostAppForm" enctype="multipart/form-data">
                <div class="form-row">
                    <label>Business Name *</label>
                    <input type="text" name="business_name" required>
                </div>
                <div class="form-row">
                    <label>Contact Number *</label>
                    <input type="tel" name="contact_number" required placeholder="09XXXXXXXXX">
                </div>
                <div class="form-row">
                    <label>Unit Address *</label>
                    <input type="text" name="unit_address" required>
                </div>
                <div class="form-row">
                    <label>Unit Description</label>
                    <textarea name="unit_description" placeholder="Describe your unit (optional)"></textarea>
                </div>
                <div class="form-row">
                    <label>Valid ID *</label>
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon">📄</div>
                        <div class="upload-text" id="uploadText">Click to upload a valid ID (JPG/PNG, max 5MB)</div>
                        <input type="file" id="idInput" name="valid_id" accept="image/jpeg,image/png" required>
                    </div>
                </div>
                <button type="submit" id="submitBtn">Submit Application</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    const uploadArea = document.getElementById('uploadArea');
    const idInput = document.getElementById('idInput');
    const uploadText = document.getElementById('uploadText');

    if (uploadArea) {
        uploadArea.addEventListener('click', () => idInput.click());
        idInput.addEventListener('change', () => {
            if (idInput.files.length > 0) {
                uploadText.textContent = idInput.files[0].name;
                uploadArea.classList.add('has-file');
            }
        });

        document.getElementById('hostAppForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            const formData = new FormData(e.target);

            fetch('api/submit_host_application.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = 'homepage.php';
                } else {
                    alert(data.error || 'Something went wrong.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Application';
                }
            })
            .catch(() => {
                alert('Something went wrong. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Application';
            });
        });
    }
</script>

</body>
</html>