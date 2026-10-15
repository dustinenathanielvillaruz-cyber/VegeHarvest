<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';
require_once 'classes/Analytics.php';

include_once 'includes/header.php';

$analyticsObj = new Analytics($pdo);
$purokData = $analyticsObj->getDistributionPerPurok();
$cropData = $analyticsObj->getHarvestYieldByCrop();
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-6 py-10">
  <div class="mb-8">
    <h1 class="text-3xl font-extrabold text-white tracking-tight">Real-Time Equity & Yield Analytics</h1>
    <p class="mt-1 text-sm text-slate-400">Track distribution fairness across sectors and harvest output trends.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Distribution Equity Card -->
    <div class="bg-[#141e38] border border-slate-800/80 rounded-xl p-6 shadow-xl">
      <h2 class="text-lg font-bold text-emerald-400 mb-4"><i class="fa-solid fa-chart-pie mr-2"></i>Distribution Equity by Purok</h2>
      <div class="space-y-4">
        <?php foreach ($purokData as $row): ?>
          <div>
            <div class="flex justify-between text-xs font-bold text-slate-300 mb-1">
              <span><?= htmlspecialchars($row['purok_name']); ?></span>
              <span class="text-emerald-400"><?= number_format($row['total_kg'], 2); ?> kg</span>
            </div>
            <div class="w-full bg-slate-800 h-3 rounded-full overflow-hidden">
              <div class="bg-emerald-500 h-full" style="width: <?= min(100, $row['total_kg'] * 5); ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Harvest Yield Breakdown -->
    <div class="bg-[#141e38] border border-slate-800/80 rounded-xl p-6 shadow-xl">
      <h2 class="text-lg font-bold text-emerald-400 mb-4"><i class="fa-solid fa-seedling mr-2"></i>Yield Output by Crop</h2>
      <div class="space-y-4">
        <?php foreach ($cropData as $row): ?>
          <div class="flex justify-between items-center border-b border-slate-800/80 pb-2">
            <div>
              <p class="text-sm font-bold text-white"><?= htmlspecialchars($row['crop_name']); ?></p>
              <p class="text-xs text-slate-400"><?= htmlspecialchars($row['category']); ?></p>
            </div>
            <span class="text-sm font-extrabold text-emerald-400">+<?= number_format($row['total_harvested'], 2); ?> kg</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</main>

<?php include_once 'includes/footer.php'; ?>