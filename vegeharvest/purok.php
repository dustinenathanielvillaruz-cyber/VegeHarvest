<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';
require_once 'classes/purok.php';

include_once 'includes/header.php';

$purokObj = new Purok($pdo);
$message = '';
$editPurok = null;

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    if ($purokObj->deletePurok($deleteId)) {
        $message = "Purok deleted successfully!";
    } else {
        $message = "Error deleting Purok. Ensure no members or records are linked to it.";
    }
}

// Handle Form Submissions (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purokName = trim($_POST['purok_name'] ?? '');
    $barangay  = trim($_POST['barangay'] ?? '');

    if (isset($_POST['add_purok']) && !empty($purokName) && !empty($barangay)) {
        if ($purokObj->addPurok($purokName, $barangay)) {
            $message = "Purok added successfully!";
        } else {
            $message = "Error adding Purok.";
        }
    } elseif (isset($_POST['update_purok']) && !empty($purokName) && !empty($barangay)) {
        $purokId = (int)$_POST['purok_id'];
        if ($purokObj->updatePurok($purokId, $purokName, $barangay)) {
            $message = "Purok updated successfully!";
        } else {
            $message = "Error updating Purok.";
        }
    }
}

// Fetch record for Editing if triggered
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editPurok = $purokObj->getPurokById((int)$_GET['id']);
}

$puroksList = $purokObj->getPuroks();
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-6 py-10">
  <div class="mb-8">
    <h1 class="text-3xl font-extrabold text-white tracking-tight">Purok Management</h1>
    <p class="mt-1 text-sm text-slate-400">Register and manage community sectors and distribution zones.</p>
  </div>

  <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold">
      <?= htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <!-- Dynamic Form (Add / Edit) -->
    <div class="lg:col-span-4 bg-[#141e38] border border-slate-800/80 rounded-xl p-6 shadow-xl">
      <div class="flex items-center gap-2 text-emerald-400 font-bold text-lg mb-6">
        <i class="fa-solid <?= $editPurok ? 'fa-pen-to-square' : 'fa-location-dot'; ?>"></i>
        <span><?= $editPurok ? 'Edit Purok' : 'Add New Purok'; ?></span>
      </div>

      <form action="purok.php" method="POST" class="space-y-5">
        <?php if ($editPurok): ?>
          <input type="hidden" name="purok_id" value="<?= htmlspecialchars($editPurok['purok_id']); ?>">
        <?php endif; ?>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Purok Name</label>
          <input type="text" name="purok_name" value="<?= htmlspecialchars($editPurok['purok_name'] ?? ''); ?>" placeholder="e.g. Purok Mangga" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Barangay</label>
          <input type="text" name="barangay" value="<?= htmlspecialchars($editPurok['barangay'] ?? ''); ?>" placeholder="e.g. Dakit" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <button type="submit" name="<?= $editPurok ? 'update_purok' : 'add_purok'; ?>" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-sm py-3 rounded-lg transition-colors mt-2">
          <?= $editPurok ? 'Update Purok' : 'Save Purok'; ?>
        </button>

        <?php if ($editPurok): ?>
          <a href="purok.php" class="block text-center text-xs font-bold text-slate-400 hover:text-slate-200 mt-2">
            Cancel Editing
          </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Registered Puroks List Table -->
    <div class="lg:col-span-8 bg-[#141e38] border border-slate-800/80 rounded-xl p-6 shadow-xl">
      <div class="flex items-center gap-2 text-emerald-400 font-bold text-lg mb-6">
        <i class="fa-solid fa-list-ul"></i>
        <span>Registered Puroks</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-700/80 text-xs font-bold uppercase tracking-wider text-slate-400">
              <th class="py-3 px-4">ID</th>
              <th class="py-3 px-4">Purok Name</th>
              <th class="py-3 px-4">Barangay</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <?php if (!empty($puroksList)): ?>
              <?php foreach ($puroksList as $purok): ?>
                <tr class="hover:bg-slate-800/40 transition-colors">
                  <td class="py-3.5 px-4 font-mono text-xs text-slate-400"><?= htmlspecialchars($purok['purok_id']); ?></td>
                  <td class="py-3.5 px-4 font-bold text-white"><?= htmlspecialchars($purok['purok_name']); ?></td>
                  <td class="py-3.5 px-4 text-slate-300"><?= htmlspecialchars($purok['barangay'] ?? 'N/A'); ?></td>
                  <td class="py-3.5 px-4 text-right space-x-3 text-xs font-bold">
                    <a href="purok.php?action=edit&id=<?= $purok['purok_id']; ?>" class="text-sky-400 hover:text-sky-300">Edit</a>
                    <a href="purok.php?action=delete&id=<?= $purok['purok_id']; ?>" onclick="return confirm('Are you sure you want to delete this purok?');" class="text-rose-400 hover:text-rose-300">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="py-6 px-4 text-center text-slate-500">No puroks registered yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php include_once 'includes/footer.php'; ?>