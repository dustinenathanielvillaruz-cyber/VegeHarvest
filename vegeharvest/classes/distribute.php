<?php
class Distribution {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Handles calls using 4 or 6 parameters seamlessly
    public function addDistributionLog($harvest_id, $volunteer_id, $purok_id, $recipient_name = 'General Distribution', $quantity_kg = 0, $distribution_date = null) {
        // Handle scenario where 4 arguments are passed: ($harvest_id, $volunteer_id, $purok_id, $distribution_date)
        if (is_string($recipient_name) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $recipient_name)) {
            $distribution_date = $recipient_name;
            $recipient_name = 'General Distribution';
            $quantity_kg = 0;
        }

        if (empty($distribution_date)) {
            $distribution_date = date('Y-m-d');
        }

        return $this->logDistribution($harvest_id, $volunteer_id, $purok_id, $recipient_name, (float)$quantity_kg, $distribution_date);
    }

    public function logDistribution($harvest_id, $volunteer_id, $purok_id, $recipient_name, $quantity_kg, $distribution_date) {
        // TCL: INSERT distribution + decrement crop stock = ONE atomic transaction.
        // Uses throw + single commit/rollback like _fletw3pw1 demo (instead of
        // early rollBack+return), plus FOR UPDATE row lock like Bro Code/Ruchi videos teach.
        try {
            $harvest_id = (int)$harvest_id;
            $volunteer_id = (int)$volunteer_id;
            $purok_id = (int)$purok_id;
            $quantity_kg = (float)$quantity_kg;

            if ($harvest_id <= 0 || $volunteer_id <= 0 || $purok_id <= 0) {
                throw new Exception("Please fill in all required fields.");
            }

            $this->pdo->beginTransaction();

            $cropQuery = $this->pdo->prepare("
                SELECT c.crop_id, c.crop_name, c.total_stock_kg 
                FROM harvest_record h 
                JOIN crop c ON h.crop_id = c.crop_id 
                WHERE h.harvest_id = :harvest_id
                FOR UPDATE
            ");
            $cropQuery->execute([':harvest_id' => $harvest_id]);
            $cropData = $cropQuery->fetch();

            if (!$cropData) {
                throw new Exception("Invalid harvest record selected. Transaction rolled back.");
            }

            $crop_id = $cropData['crop_id'];
            $crop_name = $cropData['crop_name'];
            $current_stock = (float)$cropData['total_stock_kg'];

            if ($current_stock <= 0) {
                throw new Exception("Cannot distribute! <strong>{$crop_name}</strong> currently has 0.00 kg in stock. Transaction rolled back.");
            }

            if ($quantity_kg <= 0) {
                throw new Exception("Quantity must be greater than zero. Transaction rolled back.");
            }

            if ($quantity_kg > $current_stock) {
                throw new Exception("Insufficient stock! Only <strong>{$current_stock} kg</strong> of {$crop_name} available, but you requested <strong>{$quantity_kg} kg</strong>. Transaction rolled back.");
            }

            // Validate volunteer + purok exist inside the transaction
            $volCheck = $this->pdo->prepare("SELECT COUNT(*) FROM volunteer WHERE volunteer_id = :id");
            $volCheck->execute([':id' => $volunteer_id]);
            if ((int)$volCheck->fetchColumn() === 0) {
                throw new Exception("Selected volunteer does not exist. Transaction rolled back.");
            }

            $purokCheck = $this->pdo->prepare("SELECT COUNT(*) FROM purok WHERE purok_id = :id");
            $purokCheck->execute([':id' => $purok_id]);
            if ((int)$purokCheck->fetchColumn() === 0) {
                throw new Exception("Selected purok does not exist. Transaction rolled back.");
            }

            $sql = "INSERT INTO distribution_record (harvest_id, volunteer_id, purok_id, recipient_name, recipient_family, quantity_kg, distribution_date) 
                    VALUES (:harvest_id, :volunteer_id, :purok_id, :recipient_name, '', :quantity_kg, :distribution_date)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':harvest_id' => $harvest_id,
                ':volunteer_id' => $volunteer_id,
                ':purok_id' => $purok_id,
                ':recipient_name' => $recipient_name,
                ':quantity_kg' => $quantity_kg,
                ':distribution_date' => $distribution_date
            ]);

            $updateStock = "UPDATE crop SET total_stock_kg = total_stock_kg - :quantity_kg WHERE crop_id = :crop_id";
            $stockStmt = $this->pdo->prepare($updateStock);
            $stockStmt->execute([
                ':quantity_kg' => $quantity_kg,
                ':crop_id' => $crop_id
            ]);

            // Guard against negative stock from race conditions
            $negCheck = $this->pdo->prepare("SELECT total_stock_kg FROM crop WHERE crop_id = :id");
            $negCheck->execute([':id' => $crop_id]);
            if ((float)$negCheck->fetchColumn() < 0) {
                throw new Exception("Distribution would make stock negative. Transaction rolled back.");
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error logging distribution (rolled back): " . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function getDistributionLogs() {
        try {
            $sql = "SELECT d.*, c.crop_name, v.full_name AS volunteer_name, p.purok_name 
                    FROM distribution_record d 
                    JOIN harvest_record h ON d.harvest_id = h.harvest_id
                    JOIN crop c ON h.crop_id = c.crop_id
                    JOIN volunteer v ON d.volunteer_id = v.volunteer_id
                    JOIN purok p ON d.purok_id = p.purok_id
                    ORDER BY d.distribution_date DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching distribution logs: " . $e->getMessage());
            return [];
        }
    }
}
?>