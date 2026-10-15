<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';
require_once 'classes/crop.php';
require_once 'classes/harvest.php';
require_once 'classes/volunteer.php';

include_once 'includes/header.php';

$cropObj      = new Crop($pdo);
$harvestObj   = new Harvest($pdo);
$volunteerObj = new Volunteer($pdo);

$successMessage = '';
$errorMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crop_id      = (int)($_POST['crop_id'] ?? 0);
    $volunteer_id = (int)($_POST['volunteer_id'] ?? 0);
    $weight_kg    = (float)($_POST['weight_kg'] ?? 0);
    $harvest_date = $_POST['harvest_date'] ?? date('Y-m-d');
    $notes        = trim($_POST['notes'] ?? '');

    if ($crop_id > 0 && $weight_kg > 0) {
        $result = $harvestObj->logHarvest($crop_id, $volunteer_id, $harvest_date, $weight_kg, $notes);

        if ($result === true) {
            $successMessage = "Harvest logged successfully! (COMMIT)";
        } elseif (is_string($result)) {
            // TCL ROLLBACK reason from Harvest::logHarvest, same as demo's error message.
            $errorMessage = $result;
        } else {
            $errorMessage = "Failed to log harvest record. Transaction rolled back.";
        }
    } else {
        $errorMessage = "Please select a valid crop and enter a weight greater than 0.";
    }
}

$cropsList      = $cropObj->getCrops();
$volunteersList = $volunteerObj->getVolunteers();
$harvestLogs    = $harvestObj->getHarvestLogs();
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-6 py-6 flex flex-col min-h-0 overflow-hidden">
  <div class="mb-4 shrink-0">
    <h1 class="text-3xl font-extrabold text-white tracking-tight">Harvest Logs</h1>
    <p class="mt-1 text-sm text-slate-400">Record harvested produce weights and track collection activity.</p>
  </div>

  <?php if (!empty($successMessage)): ?>
    <div class="mb-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold shrink-0">
      <?= htmlspecialchars($successMessage); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errorMessage)): ?>
    <div class="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm font-semibold shrink-0">
      <?= htmlspecialchars($errorMessage); ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start flex-1 min-h-0">
    <!-- Log Harvest Form -->
    <div class="lg:col-span-4 bg-[#141e38] border border-slate-800/80 rounded-xl p-5 shadow-xl">
      <div class="flex items-center gap-2 text-emerald-400 font-bold text-base mb-4">
        <i class="fa-solid fa-leaf"></i>
        <span>Log Harvest</span>
      </div>

      <form action="harvest.php" method="POST" class="space-y-3">
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Crop</label>
          <select name="crop_id" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors">
            <option value="" disabled selected>Select Crop</option>
            <?php foreach ($cropsList as $crop): ?>
              <option value="<?= $crop['crop_id']; ?>"><?= htmlspecialchars($crop['crop_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Volunteer</label>
          <select name="volunteer_id" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors">
            <option value="" disabled selected>Select Volunteer</option>
            <?php foreach ($volunteersList as $vol): ?>
              <option value="<?= $vol['volunteer_id']; ?>"><?= htmlspecialchars($vol['full_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Weight (KG)</label>
          <input type="number" step="0.01" name="weight_kg" placeholder="0.00" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Date</label>
          <input type="date" name="harvest_date" value="<?= date('Y-m-d'); ?>" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-sm py-2.5 rounded-lg transition-colors mt-2">
          Save Harvest Log
        </button>
      </form>
    </div>

    <!-- Recent Harvests Container (Holds internal scrollbar) -->
    <div class="lg:col-span-8 bg-[#141e38] border border-slate-800/80 rounded-xl p-5 shadow-xl flex flex-col max-h-[calc(100vh-220px)] overflow-hidden">
      <div class="flex items-center gap-2 text-emerald-400 font-bold text-base mb-4 shrink-0">
        <i class="fa-solid fa-list-check"></i>
        <span>Recent Harvests</span>
      </div>

      <!-- Dedicated Scrollable Table Area -->
      <div class="overflow-y-auto flex-1 pr-1">
        <table class="w-full text-left border-collapse text-sm">
          <thead class="sticky top-0 bg-[#141e38] z-10">
            <tr class="border-b border-slate-700/80 text-xs font-bold uppercase tracking-wider text-slate-400">
              <th class="py-2.5 px-3">Date</th>
              <th class="py-2.5 px-3">Crop</th>
              <th class="py-2.5 px-3">Weight</th>
              <th class="py-2.5 px-3">Logged By</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <?php if (!empty($harvestLogs)): ?>
              <?php foreach ($harvestLogs as $log): ?>
                <tr class="hover:bg-slate-800/40 transition-colors">
                  <td class="py-3 px-3 font-mono text-xs text-slate-400"><?= htmlspecialchars($log['harvest_date']); ?></td>
                  <td class="py-3 px-3 font-bold text-white"><?= htmlspecialchars($log['crop_name']); ?></td>
                  <td class="py-3 px-3 font-bold text-emerald-400">+<?= number_format($log['weight_kg'] ?? $log['quantity_kg'] ?? 0, 2); ?> kg</td>
                  <td class="py-3 px-3 text-slate-300"><?= htmlspecialchars($log['full_name'] ?? $log['volunteer_name'] ?? 'System'); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="py-6 px-3 text-center text-slate-500">No harvest logs found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php include_once 'includes/footer.php'; ?>