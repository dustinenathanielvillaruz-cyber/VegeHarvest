<?php
session_start();
require_once 'config/connection.php';

$message = '';$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verification Credentials
    $verifierUsername = trim($_POST['verifier_username'] ?? '');
    $verifierPassword = trim($_POST['verifier_password'] ?? '');

    // New Admin Details
    $fullName      = trim($_POST['full_name'] ?? '');
    $username      = trim($_POST['username'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $password      = trim($_POST['password'] ?? '');
    $confirmPass   = trim($_POST['confirm_password'] ?? '');

    // 1. Verify Existing Admin Credentials
    $stmt =$pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$verifierUsername]);
    $existingAdmin =$stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingAdmin || !password_verify($verifierPassword, $existingAdmin['password_hash'])) {$message = "Admin authorization failed! Invalid existing admin credentials.";
        $messageType = "error";
    } elseif ($password !== $confirmPass) {$message = "New account passwords do not match!";
        $messageType = "error";
    } elseif (empty($fullName) || empty($username) || empty($password)) {$message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        // 2. Check if new username already exists
        $checkStmt =$pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        if ($checkStmt->fetch()) {$message = "Username is already taken. Please choose another.";
            $messageType = "error";
        } else {
            try {
                $pdo->beginTransaction();

                // 3. Register New Admin into `users` table
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);$insertUserSql = "INSERT INTO users (username, password_hash, role, full_name, contact_number) VALUES (?, ?, 'admin', ?, ?)";
                $insertUserStmt =$pdo->prepare($insertUserSql);$insertUserStmt->execute([$username,$hashedPassword, $fullName,$contactNumber]);

                // Get newly created user_id
                $newUserId =$pdo->lastInsertId();

                // 4. Link/Insert into `volunteer` table so it appears in Members list
                // Fetch default purok_id if available, fallback to 1
                $purokStmt =$pdo->query("SELECT purok_id FROM purok LIMIT 1");
                $defaultPurok =$purokStmt->fetchColumn() ?: 1;

                $insertVolSql = "INSERT INTO volunteer (user_id, purok_id, full_name, contact_number, status, role) VALUES (?, ?, ?, ?, 'active', 'Admin')";
                $insertVolStmt =$pdo->prepare($insertVolSql);$insertVolStmt->execute([$newUserId,$defaultPurok, $fullName,$contactNumber]);

                $pdo->commit();

                $message = "Admin account created successfully! You can now log in.";
                $messageType = "success";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {$pdo->rollBack();
                }
                $message = "An error occurred while creating the account: " . $e->getMessage();$messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VegeHarvest - Register Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-cover bg-center bg-no-repeat flex items-center justify-center p-4 relative" style="background-image: url('https://images.unsplash.com/photo-1595855759920-86582396756a?q=80&w=1920&auto=format&fit=crop');">
  <!-- Overlay -->
  <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

  <!-- Main Registration Card -->
  <div class="relative z-10 w-full max-w-md bg-[#141e38]/85 border border-slate-700/60 backdrop-blur-md rounded-2xl p-8 shadow-2xl text-white">
    
    <div class="text-center mb-6">
      <div class="flex items-center justify-center gap-2 text-emerald-400 text-2xl font-black tracking-wide">
        <i class="fa-solid fa-leaf"></i>
        <span>VegeHarvest</span>
      </div>
      <p class="text-xs uppercase tracking-widest text-slate-400 font-bold mt-1">Register Administrator</p>
    </div>

    <?php if (!empty($message)): ?>
      <div class="mb-5 p-3.5 rounded-lg text-xs font-semibold border <?= $messageType === 'success' ? 'bg-emerald-500/20 border-emerald-500/40 text-emerald-300' : 'bg-rose-500/20 border-rose-500/40 text-rose-300'; ?>">
        <?= htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form id="adminRegisterForm" action="register.php" method="POST" class="space-y-4">
      <input type="hidden" name="verifier_username" id="hiddenVerifierUsername">
      <input type="hidden" name="verifier_password" id="hiddenVerifierPassword">

      <div>
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">Full Name</label>
        <input type="text" name="full_name" required placeholder="e.g. María Santos" class="w-full bg-[#0b1329]/80 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
      </div>

      <div>
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">Username</label>
        <input type="text" name="username" required placeholder="Choose a username" class="w-full bg-[#0b1329]/80 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
      </div>

      <div>
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">Contact Number <span class="text-slate-500 lowercase">(optional)</span></label>
        <input type="text" name="contact_number" placeholder="e.g. 09123456789" class="w-full bg-[#0b1329]/80 border border-slate-700 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
      </div>

      <div>
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">Password</label>
        <div class="relative">
          <input type="password" name="password" id="regPassword" required placeholder="Create password" class="w-full bg-[#0b1329]/80 border border-slate-700 rounded-lg pl-4 pr-10 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
          <button type="button" onclick="toggleVisibility('regPassword', 'regPassIcon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-emerald-400">
            <i id="regPassIcon" class="fa-solid fa-eye-slash"></i>
          </button>
        </div>
      </div>

      <div>
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1.5">Confirm Password</label>
        <div class="relative">
          <input type="password" name="confirm_password" id="regConfirmPassword" required placeholder="Confirm password" class="w-full bg-[#0b1329]/80 border border-slate-700 rounded-lg pl-4 pr-10 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
          <button type="button" onclick="toggleVisibility('regConfirmPassword', 'regConfirmIcon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-emerald-400">
            <i id="regConfirmIcon" class="fa-solid fa-eye-slash"></i>
          </button>
        </div>
      </div>

      <button type="button" onclick="openAuthModal()" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-sm py-3 rounded-lg transition-colors mt-2">
        Create Admin Account
      </button>

      <p class="text-center text-xs text-slate-400 mt-4">
        Already have an account? <a href="login.php" class="text-emerald-400 hover:underline font-semibold">Log in here</a>
      </p>
    </form>
  </div>

  <!-- ADMIN AUTHORIZATION MODAL -->
  <div id="authModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md hidden">
    <div class="w-full max-w-sm bg-[#141e38] border border-amber-500/40 rounded-2xl p-6 shadow-2xl text-white">
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-500/10 text-amber-400 mb-3 border border-amber-500/20">
          <i class="fa-solid fa-user-shield text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-white">Admin Authorization Required</h3>
        <p class="text-xs text-slate-400 mt-1">Please enter an existing admin account credentials to authorize this action.</p>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-1">Existing Admin Username</label>
          <input type="text" id="modalAdminUsername" placeholder="Admin username" class="w-full bg-[#0b1329] border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors">
        </div>

        <div>
          <label class="block text-[11px] font-bold uppercase tracking-wider text-amber-400 mb-1">Existing Admin Password</label>
          <div class="relative">
            <input type="password" id="modalAdminPassword" placeholder="••••••••" class="w-full bg-[#0b1329] border border-slate-700 rounded-lg pl-3.5 pr-10 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors">
            <button type="button" onclick="toggleVisibility('modalAdminPassword', 'modalPassIcon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-amber-400">
              <i id="modalPassIcon" class="fa-solid fa-eye-slash"></i>
            </button>
          </div>
        </div>

        <div id="modalError" class="text-xs text-rose-400 font-semibold hidden"></div>

        <div class="flex items-center gap-3 pt-2">
          <button type="button" onclick="closeAuthModal()" class="w-1/2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs py-2.5 rounded-lg transition-colors">
            Cancel
          </button>
          <button type="button" onclick="submitAuthorizedRegistration()" class="w-1/2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-xs py-2.5 rounded-lg transition-colors">
            Authorize & Create
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
  function toggleVisibility(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
      } else {
          input.type = 'password';
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
      }
  }

  function openAuthModal() {
      const form = document.getElementById('adminRegisterForm');
      if (!form.checkValidity()) {
          form.reportValidity();
          return;
      }

      const pass = document.getElementById('regPassword').value;
      const confirmPass = document.getElementById('regConfirmPassword').value;

      if (pass !== confirmPass) {
          alert('Passwords do not match!');
          return;
      }

      document.getElementById('modalError').classList.add('hidden');
      document.getElementById('authModal').classList.remove('hidden');
  }

  function closeAuthModal() {
      document.getElementById('authModal').classList.add('hidden');
  }

  function submitAuthorizedRegistration() {
      const verifierUser = document.getElementById('modalAdminUsername').value.trim();
      const verifierPass = document.getElementById('modalAdminPassword').value.trim();
      const modalError = document.getElementById('modalError');

      if (!verifierUser || !verifierPass) {
          modalError.textContent = 'Please enter existing admin credentials.';
          modalError.classList.remove('hidden');
          return;
      }

      document.getElementById('hiddenVerifierUsername').value = verifierUser;
      document.getElementById('hiddenVerifierPassword').value = verifierPass;

      document.getElementById('adminRegisterForm').submit();
  }
  </script>
</body>
</html>