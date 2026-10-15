<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';
require_once 'classes/crop.php';
require_once 'classes/harvest.php';
require_once 'classes/distribute.php';
require_once 'classes/volunteer.php';
require_once 'classes/purok.php';

include_once 'includes/header.php';

$cropObj         = new Crop($pdo);
$harvestObj      = new Harvest($pdo);
$distributionObj = new Distribution($pdo);
$volunteerObj    = new Volunteer($pdo);
$purokObj        = new Purok($pdo);

// Fetch dynamic data for metrics
$cropsList        = $cropObj->getCrops();
$harvestLogs      = $harvestObj->getHarvestLogs();
$distributionLogs = $distributionObj->getDistributionLogs();

// Calculate total metrics
$totalCrops       = count($cropsList);
$totalStockKg     = array_sum(array_column($cropsList, 'total_stock_kg'));
$totalHarvests    = count($harvestLogs);
$totalDistributed = array_sum(array_column($distributionLogs, 'quantity_kg'));
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-6 py-10">
  <div class="mb-8">
    <h1 class="text-3xl font-extrabold text-white tracking-tight">Dashboard Overview</h1>
    <p class="mt-1 text-sm text-slate-400">System overview and harvest activity tracking for community gardens.</p>
  </div>

  <!-- Key Metrics Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Available Stock -->
    <div class="bg-[#141e38] border border-slate-800/80 rounded-xl p-5 shadow-xl">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Stock</span>
        <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-400">
          <i class="fa-solid fa-wheat-awn text-lg"></i>
        </div>
      </div>
      <div class="text-2xl font-black text-white"><?= number_format($totalStockKg, 2); ?> <span class="text-sm font-normal text-slate-400">kg</span></div>
      <p class="text-xs text-slate-400 mt-1">Available across all crops</p>
    </div>

    <!-- Active Crop Varieties -->
    <div class="bg-[#141e38] border border-slate-800/80 rounded-xl p-5 shadow-xl">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Crop Varieties</span>
        <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400">
          <i class="fa-solid fa-seedling text-lg"></i>
        </div>
      </div>
      <div class="text-2xl font-black text-white"><?= $totalCrops; ?></div>
      <p class="text-xs text-slate-400 mt-1">Managed varieties</p>
    </div>

    <!-- Total Harvest Logs -->
    <div class="bg-[#141e38] border border-slate-800/80 rounded-xl p-5 shadow-xl">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Harvest Records</span>
        <div class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-400">
          <i class="fa-solid fa-basket-shopping text-lg"></i>
        </div>
      </div>
      <div class="text-2xl font-black text-white"><?= $totalHarvests; ?></div>
      <p class="text-xs text-slate-400 mt-1">Log entries recorded</p>
    </div>

    <!-- Total Distributed -->
    <div class="bg-[#141e38] border border-slate-800/80 rounded-xl p-5 shadow-xl">
      <div class="flex items-center justify-between mb-3">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Distributed</span>
        <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center text-purple-400">
          <i class="fa-solid fa-truck-ramp-box text-lg"></i>
        </div>
      </div>
      <div class="text-2xl font-black text-white"><?= number_format($totalDistributed, 2); ?> <span class="text-sm font-normal text-slate-400">kg</span></div>
      <p class="text-xs text-slate-400 mt-1">Yields distributed</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Crop Inventory Table -->
    <div class="lg:col-span-7 bg-[#141e38] border border-slate-800/80 rounded-xl p-6 shadow-xl">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2 text-emerald-400 font-bold text-lg">
          <i class="fa-solid fa-list-check"></i>
          <span>Crop Inventory Overview</span>
        </div>
        <a href="crop.php" class="text-xs text-emerald-400 hover:underline font-semibold">Manage Crops &rarr;</a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-700/80 text-xs font-bold uppercase tracking-wider text-slate-400">
              <th class="py-3 px-4">ID</th>
              <th class="py-3 px-4">Crop Name</th>
              <th class="py-3 px-4">Category</th>
              <th class="py-3 px-4 text-right">Stock (KG)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <?php if (!empty($cropsList)): ?>
              <?php foreach ($cropsList as $crop): ?>
                <tr class="hover:bg-slate-800/40 transition-colors">
                  <td class="py-3.5 px-4 font-mono text-xs text-slate-400"><?= $crop['crop_id']; ?></td>
                  <td class="py-3.5 px-4 font-bold text-white"><?= htmlspecialchars($crop['crop_name']); ?></td>
                  <td class="py-3.5 px-4 text-slate-400"><?= htmlspecialchars($crop['category'] ?? 'General'); ?></td>
                  <td class="py-3.5 px-4 text-right font-bold text-emerald-400"><?= number_format($crop['total_stock_kg'], 2); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="py-6 px-4 text-center text-slate-500">No crop records available.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Harvest Activity -->
    <div class="lg:col-span-5 bg-[#141e38] border border-slate-800/80 rounded-xl p-6 shadow-xl">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2 text-emerald-400 font-bold text-lg">
          <i class="fa-solid fa-clock-rotate-left"></i>
          <span>Recent Harvests</span>
        </div>
        <a href="harvest.php" class="text-xs text-emerald-400 hover:underline font-semibold">View All &rarr;</a>
      </div>

      <div class="space-y-4">
        <?php 
          $recentHarvests = array_slice($harvestLogs, 0, 5); 
        ?>
        <?php if (!empty($recentHarvests)): ?>
          <?php foreach ($recentHarvests as $log): ?>
            <div class="flex items-center justify-between p-3.5 rounded-lg bg-[#0b1329] border border-slate-800">
              <div>
                <div class="font-bold text-white text-sm"><?= htmlspecialchars($log['crop_name']); ?></div>
                <div class="text-xs text-slate-400 font-mono mt-0.5"><?= htmlspecialchars($log['harvest_date']); ?></div>
              </div>
              <div class="text-right">
                <div class="text-emerald-400 font-extrabold text-sm">+<?= number_format($log['weight_kg'] ?? $log['quantity_kg'] ?? 0, 2); ?> kg</div>
                <div class="text-xs text-slate-400"><?= htmlspecialchars($log['full_name'] ?? $log['volunteer_name'] ?? 'System'); ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center py-8 text-slate-500 text-sm">No recent harvests logged.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php include_once 'includes/footer.php'; ?>