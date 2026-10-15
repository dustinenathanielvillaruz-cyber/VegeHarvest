<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';
require_once 'classes/distribute.php';
require_once 'classes/harvest.php';
require_once 'classes/volunteer.php';
require_once 'classes/purok.php';

include_once 'includes/header.php';

$distributionObj = new Distribution($pdo);
$harvestObj      = new Harvest($pdo);
$volunteerObj    = new Volunteer($pdo);
$purokObj        = new Purok($pdo);

$successMessage = '';
$errorMessage   = '';

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $harvest_id        = (int)($_POST['harvest_id'] ?? 0);
    $volunteer_id      = (int)($_POST['volunteer_id'] ?? 0);
    $purok_id          = (int)($_POST['purok_id'] ?? 0);
    $recipient_name    = trim($_POST['recipient_name'] ?? 'General Distribution');
    $quantity_kg       = (float)($_POST['quantity_kg'] ?? 0);
    $distribution_date = $_POST['distribution_date'] ?? date('Y-m-d');

    if ($harvest_id > 0 && $volunteer_id > 0 && $purok_id > 0) {
        $result = $distributionObj->addDistributionLog(
            $harvest_id,
            $volunteer_id,
            $purok_id,
            $recipient_name,
            $quantity_kg,
            $distribution_date
        );

        if ($result === true) {
            $successMessage = "Distribution logged successfully! (COMMIT)";
        } else {
            $errorMessage = $result;
        }
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}

$harvestsList     = $harvestObj->getHarvestRecords();
$volunteersList   = $volunteerObj->getVolunteers();
$puroksList       = $purokObj->getPuroks();
$distributionLogs = $distributionObj->getDistributionLogs();
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-6 py-10">
  <div class="mb-8">
    <h1 class="text-3xl font-extrabold text-white tracking-tight">Crop Distributions</h1>
    <p class="mt-1 text-sm text-slate-400">Log distribution events of harvested yields to registered puroks and beneficiaries.</p>
  </div>

  <?php if (!empty($successMessage)): ?>
    <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm font-semibold">
      <?= htmlspecialchars($successMessage); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errorMessage)): ?>
    <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm font-semibold">
      <?= $errorMessage; ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <!-- Distribution Logging Form -->
    <div class="lg:col-span-4 bg-[#141e38] border border-slate-800/80 rounded-xl p-6 shadow-xl">
      <div class="flex items-center gap-2 text-emerald-400 font-bold text-lg mb-6">
        <i class="fa-solid fa-truck-ramp-box"></i>
        <span>Log Distribution</span>
      </div>

      <form action="distribute.php" method="POST" class="space-y-4">
        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Select Harvest Yield</label>
          <select name="harvest_id" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors">
            <option value="" disabled selected>Select Harvest Batch</option>
            <?php foreach ($harvestsList as $harvest): ?>
              <?php 
                $weight = $harvest['weight_kg'] ?? $harvest['quantity_kg'] ?? 0;
              ?>
              <option value="<?= $harvest['harvest_id']; ?>">
                <?= htmlspecialchars($harvest['crop_name']); ?> (<?= htmlspecialchars($weight); ?> kg - <?= htmlspecialchars($harvest['harvest_date']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Assigned Volunteer</label>
          <select name="volunteer_id" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors">
            <option value="" disabled selected>Select Volunteer</option>
            <?php foreach ($volunteersList as $vol): ?>
              <option value="<?= $vol['volunteer_id']; ?>"><?= htmlspecialchars($vol['full_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Target Purok</label>
          <select name="purok_id" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors">
            <option value="" disabled selected>Select Purok</option>
            <?php foreach ($puroksList as $purok): ?>
              <option value="<?= $purok['purok_id']; ?>"><?= htmlspecialchars($purok['purok_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Recipient / Beneficiary</label>
          <input type="text" name="recipient_name" placeholder="e.g. Purok 3 Families / Juan Dela Cruz" class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Quantity Distributed (kg)</label>
          <input type="number" step="0.01" min="0.01" name="quantity_kg" placeholder="0.00" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Distribution Date</label>
          <input type="date" name="distribution_date" value="<?= date('Y-m-d'); ?>" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-sm py-3 rounded-lg transition-colors mt-2">
          Submit Distribution Log
        </button>
      </form>
    </div>

    <!-- Distribution Records Table -->
    <div class="lg:col-span-8 bg-[#141e38] border border-slate-800/80 rounded-xl p-6 shadow-xl">
      <div class="flex items-center gap-2 text-emerald-400 font-bold text-lg mb-6">
        <i class="fa-solid fa-list-check"></i>
        <span>Recent Distributions</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-700/80 text-xs font-bold uppercase tracking-wider text-slate-400">
              <th class="py-3 px-4">Date</th>
              <th class="py-3 px-4">Crop</th>
              <th class="py-3 px-4">Recipient / Purok</th>
              <th class="py-3 px-4">Volunteer</th>
              <th class="py-3 px-4 text-right">Quantity</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <?php if (!empty($distributionLogs)): ?>
              <?php foreach ($distributionLogs as $log): ?>
                <tr class="hover:bg-slate-800/40 transition-colors">
                  <td class="py-3.5 px-4 font-mono text-xs text-slate-400"><?= htmlspecialchars($log['distribution_date']); ?></td>
                  <td class="py-3.5 px-4 font-bold text-white"><?= htmlspecialchars($log['crop_name']); ?></td>
                  <td class="py-3.5 px-4">
                    <div class="text-white font-medium"><?= htmlspecialchars($log['recipient_name'] ?? 'N/A'); ?></div>
                    <div class="text-xs text-slate-400"><?= htmlspecialchars($log['purok_name']); ?></div>
                  </td>
                  <td class="py-3.5 px-4 text-slate-300"><?= htmlspecialchars($log['volunteer_name']); ?></td>
                  <td class="py-3.5 px-4 text-right font-bold text-emerald-400"><?= number_format($log['quantity_kg'], 2); ?> kg</td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="py-6 px-4 text-center text-slate-500">No distribution records found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php include_once 'includes/footer.php'; ?>