<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/connection.php';
require_once 'classes/crop.php';

include_once 'includes/header.php';

$cropObj = new Crop($pdo);
$message = '';
$editCrop = null;

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    if ($cropObj->deleteCrop($deleteId)) {
        $message = "Crop deleted successfully!";
    } else {
        $message = "Error deleting crop. Ensure no harvest or distribution logs are linked to it.";
    }
}

// Handle Form Submissions (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cropName = trim($_POST['crop_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $stock = (float)($_POST['total_stock_kg'] ?? 0.00);

    if (isset($_POST['add_crop']) && !empty($cropName)) {
        if ($cropObj->addCrop($cropName, $category, $stock)) {
            $message = "Crop added successfully!";
        } else {
            $message = "Error adding crop.";
        }
    } elseif (isset($_POST['update_crop']) && !empty($cropName)) {
        $cropId = (int)$_POST['crop_id'];
        if ($cropObj->updateCrop($cropId, $cropName, $category, $stock)) {
            $message = "Crop updated successfully!";
        } else {
            $message = "Error updating crop.";
        }
    }
}

// Fetch record for Editing if triggered
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editCrop = $cropObj->getCropById((int)$_GET['id']);
}

// Handle Search vs List View
$searchKeyword = trim($_GET['search'] ?? '');
if (!empty($searchKeyword)) {
    $cropsList = $cropObj->searchCrops($searchKeyword);
} else {
    $cropsList = $cropObj->getCrops();
}
?>

<main class="flex-1 max-w-7xl w-full mx-auto px-6 py-10">
  <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
      <h1 class="text-3xl font-extrabold text-white tracking-tight">Crop Inventory</h1>
      <p class="mt-1 text-sm text-slate-400">Manage crop produce, categories, and inventory stock levels.</p>
    </div>

    <!-- Search Form -->
    <form action="crop.php" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
      <input type="text" name="search" value="<?= htmlspecialchars($searchKeyword); ?>" placeholder="Search crop or category..." class="bg-[#141e38] border border-slate-700/80 rounded-lg px-4 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
      <button type="submit" class="bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-bold px-4 py-2 rounded-lg text-sm transition-colors">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
      <?php if (!empty($searchKeyword)): ?>
        <a href="crop.php" class="text-xs text-slate-400 hover:text-slate-200 underline">Clear</a>
      <?php endif; ?>
    </form>
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
        <i class="fa-solid <?= $editCrop ? 'fa-pen-to-square' : 'fa-seedling'; ?>"></i>
        <span><?= $editCrop ? 'Edit Crop' : 'Add New Crop'; ?></span>
      </div>

      <form action="crop.php" method="POST" class="space-y-5">
        <?php if ($editCrop): ?>
          <input type="hidden" name="crop_id" value="<?= htmlspecialchars($editCrop['crop_id']); ?>">
        <?php endif; ?>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Crop Name</label>
          <input type="text" name="crop_name" value="<?= htmlspecialchars($editCrop['crop_name'] ?? ''); ?>" placeholder="e.g. Tomato" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Category</label>
          <input type="text" name="category" value="<?= htmlspecialchars($editCrop['category'] ?? ''); ?>" placeholder="e.g. Fruit Vegetable" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Initial / Current Stock (kg)</label>
          <input type="number" step="0.01" name="total_stock_kg" value="<?= htmlspecialchars($editCrop['total_stock_kg'] ?? '0.00'); ?>" required class="w-full bg-[#0b1329] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors">
        </div>

        <button type="submit" name="<?= $editCrop ? 'update_crop' : 'add_crop'; ?>" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-sm py-3 rounded-lg transition-colors mt-2">
          <?= $editCrop ? 'Update Crop' : 'Save Crop'; ?>
        </button>

        <?php if ($editCrop): ?>
          <a href="crop.php" class="block text-center text-xs font-bold text-slate-400 hover:text-slate-200 mt-2">
            Cancel Editing
          </a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Registered Crops List Table -->
    <div class="lg:col-span-8 bg-[#141e38] border border-slate-800/80 rounded-xl p-6 shadow-xl">
      <div class="flex items-center gap-2 text-emerald-400 font-bold text-lg mb-6">
        <i class="fa-solid fa-list-ul"></i>
        <span>Registered Crops & Stock</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="border-b border-slate-700/80 text-xs font-bold uppercase tracking-wider text-slate-400">
              <th class="py-3 px-4">ID</th>
              <th class="py-3 px-4">Crop Name</th>
              <th class="py-3 px-4">Category</th>
              <th class="py-3 px-4 text-right">Stock (kg)</th>
              <th class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 text-slate-300">
            <?php if (!empty($cropsList)): ?>
              <?php foreach ($cropsList as $crop): ?>
                <tr class="hover:bg-slate-800/40 transition-colors">
                  <td class="py-3.5 px-4 font-mono text-xs text-slate-400"><?= htmlspecialchars($crop['crop_id']); ?></td>
                  <td class="py-3.5 px-4 font-bold text-white"><?= htmlspecialchars($crop['crop_name']); ?></td>
                  <td class="py-3.5 px-4 text-slate-300"><?= htmlspecialchars($crop['category']); ?></td>
                  <td class="py-3.5 px-4 text-right font-extrabold text-emerald-400"><?= number_format($crop['total_stock_kg'], 2); ?></td>
                  <td class="py-3.5 px-4 text-right space-x-3 text-xs font-bold">
                    <a href="crop.php?action=edit&id=<?= $crop['crop_id']; ?>" class="text-sky-400 hover:text-sky-300">Edit</a>
                    <a href="crop.php?action=delete&id=<?= $crop['crop_id']; ?>" onclick="return confirm('Are you sure you want to delete this crop?');" class="text-rose-400 hover:text-rose-300">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="py-6 px-4 text-center text-slate-500">No crops found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php include_once 'includes/footer.php'; ?>