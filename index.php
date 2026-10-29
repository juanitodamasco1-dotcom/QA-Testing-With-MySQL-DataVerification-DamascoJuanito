<?php
// ---------------------------------------------------------------
// Database connection (local MariaDB via mysqli)
// ---------------------------------------------------------------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";
$port = 3306;

mysqli_report(MYSQLI_REPORT_OFF); // handle errors manually below
$conn = new mysqli($servername, $username, $password, $dbname, $port);

$successMessage = "";
$errorMessage   = "";

if ($conn->connect_error) {
    $errorMessage = "Error: Could not connect to the database.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $errorMessage === "") {

    // Capture all four variables from the form
    $FirstName   = trim($_POST["FirstName"]   ?? "");
    $LastName    = trim($_POST["LastName"]    ?? "");
    $GovPosition = trim($_POST["GovPosition"] ?? "");
    $Location    = trim($_POST["Location"]    ?? "");

    // =============================================================
    // INTENTIONAL FLAW (for the activity):
    // $Location is captured above and shown in the confirmation
    // modal, but it is deliberately LEFT OUT of both the SQL string
    // and bind_param(). The INSERT therefore saves only FirstName,
    // LastName and GovPosition, so the Location value is silently
    // lost even though the user sees a success message.
    // =============================================================
    $sql  = "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Fallback error message if the query fails to prepare
        $errorMessage = "Error: The query could not be prepared. Please try again later.";
    } else {
        // Only three "s" types and three variables - Location is omitted
        $stmt->bind_param("sss", $FirstName, $LastName, $GovPosition);

        if ($stmt->execute()) {
            $successMessage = "Success! The wish note has been sent.";
        } else {
            $errorMessage = "Error: The wish note could not be saved.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kara David's Wish Note</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: #eceff1;
            font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #263238;
        }

        .card {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 10px;
            padding: 32px 28px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
        }

        h2 {
            margin: 0 0 22px;
            font-size: 1.5rem;
            color: #1976d2;
            text-align: center;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 6px;
            margin-bottom: 18px;
            font-size: 0.95rem;
        }
        .alert.success { background: #e3f2fd; color: #0d47a1; border-left: 4px solid #1976d2; }
        .alert.error   { background: #ffebee; color: #b71c1c; border-left: 4px solid #d32f2f; }

        label {
            display: block;
            margin: 14px 0 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .opt { font-weight: 400; color: #78909c; }

        input[type="text"] {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cfd8dc;
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #1976d2;
            box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.2);
        }

        button {
            font-family: inherit;
            font-size: 1rem;
            border: none;
            border-radius: 6px;
            padding: 11px 18px;
            cursor: pointer;
            transition: background 0.15s;
        }
        button:focus-visible { outline: 3px solid rgba(25, 118, 210, 0.45); outline-offset: 2px; }

        .btn-primary { background: #1976d2; color: #fff; }
        .btn-primary:hover { background: #1565c0; }
        .btn-secondary { background: #eceff1; color: #37474f; }
        .btn-secondary:hover { background: #cfd8dc; }

        #submitBtn { width: 100%; margin-top: 24px; }

        /* Modal */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(38, 50, 56, 0.55);
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 100;
        }
        .overlay.open { display: flex; }

        .modal {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 10px;
            padding: 26px 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }
        .modal h3 { margin: 0 0 16px; color: #1976d2; }

        .summary { margin: 0 0 22px; padding: 0; list-style: none; }
        .summary li {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid #eceff1;
            font-size: 0.95rem;
        }
        .summary li span:first-child { color: #607d8b; }
        .summary li span:last-child { font-weight: 600; text-align: right; word-break: break-word; }

        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }

        @media (max-width: 420px) {
            .card { padding: 24px 18px; }
            .modal-actions { flex-direction: column-reverse; }
            .modal-actions button { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Kara David's Wish Note</h2>

        <?php if ($successMessage): ?>
            <div class="alert success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="alert error"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <form id="wishForm" method="POST" action="">
            <label for="FirstName">Target's First Name</label>
            <input type="text" id="FirstName" name="FirstName" maxlength="30" required>

            <label for="LastName">Target's Last Name</label>
            <input type="text" id="LastName" name="LastName" maxlength="30" required>

            <label for="GovPosition">Target's Government Position</label>
            <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>

            <label for="Location">Target's Location <span class="opt">(optional)</span></label>
            <input type="text" id="Location" name="Location" maxlength="40">

            <button type="submit" id="submitBtn" class="btn-primary">Send Wish Note</button>
        </form>
    </div>

    <!-- Verification modal -->
    <div class="overlay" id="modalOverlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal">
            <h3 id="modalTitle">Verify Target Data</h3>
            <ul class="summary">
                <li><span>First Name</span><span id="vFirstName"></span></li>
                <li><span>Last Name</span><span id="vLastName"></span></li>
                <li><span>Government Position</span><span id="vGovPosition"></span></li>
                <li><span>Location</span><span id="vLocation"></span></li>
            </ul>
            <div class="modal-actions">
                <button type="button" id="cancelBtn" class="btn-secondary">Cancel</button>
                <button type="button" id="confirmBtn" class="btn-primary">Confirm &amp; Send</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var form    = document.getElementById("wishForm");
            var overlay = document.getElementById("modalOverlay");

            function closeModal() { overlay.classList.remove("open"); }

            // Intercept the submission and show the verification modal
            form.addEventListener("submit", function (e) {
                e.preventDefault();

                document.getElementById("vFirstName").textContent   = form.FirstName.value.trim();
                document.getElementById("vLastName").textContent    = form.LastName.value.trim();
                document.getElementById("vGovPosition").textContent = form.GovPosition.value.trim();
                document.getElementById("vLocation").textContent    = form.Location.value.trim() || "(not provided)";

                overlay.classList.add("open");
                document.getElementById("confirmBtn").focus();
            });

            document.getElementById("cancelBtn").addEventListener("click", closeModal);

            // form.submit() bypasses the submit event, so no interception loop
            document.getElementById("confirmBtn").addEventListener("click", function () {
                form.submit();
            });

            overlay.addEventListener("click", function (e) {
                if (e.target === overlay) closeModal();
            });
            document.addEventListener("keydown", function (e) {
                if (e.key === "Escape") closeModal();
            });
        })();
    </script>
</body>
</html>
