<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';
require_once 'classes/volunteer.php';
require_once 'classes/purok.php';

include_once 'includes/header.php';

$volunteerObj = new Volunteer($pdo);
$purokObj = new Purok($pdo);

$message = '';
$editMember = null;

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    if ($volunteerObj->deleteVolunteer($deleteId)) {
        $message = "Member deleted successfully!";
    } else {
        $message = "Error deleting member.";
    }
}

// Handle Form Submissions (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName      = trim($_POST['full_name'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $purokId       = (int)($_POST['purok_id'] ?? 0);
    $role          = trim($_POST['role'] ?? 'Volunteer');
    $username      = trim($_POST['username'] ?? '');
    $password      = trim($_POST['password'] ?? '');

    if (isset($_POST['add_member']) && !empty($fullName) && $purokId > 0) {
        if ($volunteerObj->addVolunteer($fullName, $contactNumber, $purokId, $role, $username, $password)) {
            $message = "Member registered successfully!";
        } else {
            $message = "Error registering member.";
        }
    } elseif (isset($_POST['update_member']) && !empty($fullName) && $purokId > 0) {
        $volunteerId = (int)$_POST['volunteer_id'];
        if ($volunteerObj->updateVolunteer($volunteerId, $fullName, $contactNumber, $purokId, $role, $username, $password)) {
            $message = "Member updated successfully!";
        } else {
            $message = "Error updating member.";
        }
    }
}

// Fetch record for Editing if triggered
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editMember = $volunteerObj->getVolunteerById((int)$_GET['id']);
}

$membersList = $volunteerObj->getVolunteers();
$puroksList  = $purokObj->getPuroks();
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-6 py-10">
  <div class="mb-8">
    <h1 class="text-3xl font-extrabold text-white tracking-tight">Community Members</h1>
    <p class="mt-1 text-sm text-slate-400">Register and manage community volunteers and beneficiary details.</p>
  </div>

  <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold">
      <?= htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <!-- Form Section -->
    <div class="lg:col-span-4 bg-[#141e38] border border-slate-800/80 rounded-xl p-6 shadow-xl">
      <div class="flex items-center gap-2 text-emerald-400 font-bold text-lg mb-6">
        <i class="fa-solid <?= $editMember ? 'fa-user-pen' : 'fa-user-plus'; ?>"></i>
        <span><?= $editMember ? 'Edit Member' : 'Register New Member'; ?></span>
      </div>

      <form action="volunteer.php" method="POST" class="space-y-5">
        <?php if ($editMember): ?>
          <input type="hidden" name="volunteer_id" value="<?= htmlspecialchars($editMember['volunteer_id']); ?>">
        <?php endif; ?>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Full Name</label>
          <input type="text" name="full_name" value="<?= htmlspecialchars($editMember['full_name'] ?? ''); ?>" placeholder="e.g. Juan Dela Cruz" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Contact Number</label>
          <input type="text" name="contact_number" value="<?= htmlspecialchars($editMember['contact_number'] ?? ''); ?>" placeholder="e.g. 09123456789" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Assigned Purok</label>
          <select name="purok_id" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors">
            <option value="" disabled <?= !$editMember ? 'selected' : ''; ?>>Select a Purok</option>
            <?php foreach ($puroksList as $purok): ?>
              <option value="<?= $purok['purok_id']; ?>" <?= ($editMember && $editMember['purok_id'] == $purok['purok_id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($purok['purok_name']); ?> <?= !empty($purok['barangay']) ? '(' . htmlspecialchars($purok['barangay']) . ')' : ''; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Role</label>
          <select name="role" id="roleSelect" onchange="toggleAdminCredentials()" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors">
            <option value="Volunteer" <?= (($editMember['role'] ?? 'Volunteer') === 'Volunteer') ? 'selected' : ''; ?>>Volunteer</option>
            <option value="Admin" <?= (($editMember['role'] ?? '') === 'Admin') ? 'selected' : ''; ?>>Admin</option>
          </select>
        </div>

        <!-- Admin Credentials Section (Appears when Role is set to Admin) -->
        <div id="adminCredentials" class="space-y-4 pt-2 border-t border-slate-800/80 <?= (($editMember['role'] ?? '') === 'Admin') ? '' : 'hidden'; ?>">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-amber-400 mb-2">Admin Username</label>
            <input type="text" name="username" id="usernameInput" value="<?= htmlspecialchars($editMember['username'] ?? ''); ?>" placeholder="Enter username" class="w-full bg-[#0b1329] border border-amber-500/50 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors">
          </div>

          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-amber-400 mb-2">
              Admin Password <?= $editMember ? '<span class="text-[10px] text-slate-400 lowercase">(leave blank to keep unchanged)</span>' : ''; ?>
            </label>
            <div class="relative">
              <input type="password" name="password" id="passwordInput" placeholder="••••••••" class="w-full bg-[#0b1329] border border-amber-500/50 rounded-lg pl-4 pr-11 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition-colors">
              <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-amber-400 focus:outline-none transition-colors">
                <i id="togglePasswordIcon" class="fa-solid fa-eye-slash"></i>
              </button>
            </div>
          </div>
        </div>

        <button type="submit" name="<?= $editMember ? 'update_member' : 'add_member'; ?>" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-sm py-3 rounded-lg transition-colors mt-2">
          <?= $editMember ? 'Update Member' : 'Save Member'; ?>
        </button>

        <?php if ($editMember): ?>
          <a href="volunteer.php" class="block text-center text-xs font-bold text-slate-400 hover:text-slate-200 mt-2">
            Cancel Editing
          </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Registered Members Table -->
    <div class="lg:col-span-8 bg-[#141e38] border border-slate-800/80 rounded-xl p-6 shadow-xl">
      <div class="flex items-center gap-2 text-emerald-400 font-bold text-lg mb-6">
        <i class="fa-solid fa-users"></i>
        <span>Registered Members</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-700/80 text-xs font-bold uppercase tracking-wider text-slate-400">
              <th class="py-3 px-4">ID</th>
              <th class="py-3 px-4">Full Name</th>
              <th class="py-3 px-4">Contact Number</th>
              <th class="py-3 px-4">Purok</th>
              <th class="py-3 px-4">Role</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <?php if (!empty($membersList)): ?>
              <?php foreach ($membersList as $member): ?>
                <tr class="hover:bg-slate-800/40 transition-colors">
                  <td class="py-3.5 px-4 font-mono text-xs text-slate-400"><?= htmlspecialchars($member['volunteer_id']); ?></td>
                  <td class="py-3.5 px-4 font-bold text-white"><?= htmlspecialchars($member['full_name']); ?></td>
                  <td class="py-3.5 px-4 text-slate-300"><?= htmlspecialchars($member['contact_number']); ?></td>
                  <td class="py-3.5 px-4 text-emerald-400 font-medium"><?= htmlspecialchars($member['purok_name']); ?></td>
                  <td class="py-3.5 px-4 font-medium text-slate-300">
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= ($member['role'] ?? 'Volunteer') === 'Admin' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-slate-700/50 text-slate-300'; ?>">
                      <?= htmlspecialchars($member['role'] ?? 'Volunteer'); ?>
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-right space-x-3 text-xs font-bold">
                    <a href="volunteer.php?action=edit&id=<?= $member['volunteer_id']; ?>" class="text-sky-400 hover:text-sky-300">Edit</a>
                    <a href="volunteer.php?action=delete&id=<?= $member['volunteer_id']; ?>" onclick="return confirm('Are you sure you want to delete this member?');" class="text-rose-400 hover:text-rose-300">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="py-6 px-4 text-center text-slate-500">No members registered yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script>
function toggleAdminCredentials() {
    const roleSelect = document.getElementById('roleSelect');
    const credentialsDiv = document.getElementById('adminCredentials');
    const usernameInput = document.getElementById('usernameInput');
    const passwordInput = document.getElementById('passwordInput');

    if (roleSelect.value === 'Admin') {
        credentialsDiv.classList.remove('hidden');
        usernameInput.setAttribute('required', 'required');
        <?php if (!$editMember || empty($editMember['user_id'])): ?>
          passwordInput.setAttribute('required', 'required');
        <?php endif; ?>
    } else {
        credentialsDiv.classList.add('hidden');
        usernameInput.removeAttribute('required');
        passwordInput.removeAttribute('required');
    }
}

function togglePasswordVisibility() {
    const passwordInput = document.getElementById('passwordInput');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    }
}

// Run script on initial page load in case form opens in Edit Mode with 'Admin' selected
document.addEventListener("DOMContentLoaded", function() {
    toggleAdminCredentials();
});
</script>

<?php include_once 'includes/footer.php'; ?>