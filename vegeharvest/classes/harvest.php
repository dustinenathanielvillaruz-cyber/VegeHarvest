<?php
class Harvest {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function logHarvest($crop_id, $volunteer_id, $harvest_date, $weight_kg, $notes = '') {
        // TCL pattern from _fletw3pw1_transaction_demo + YouTube videos:
        // MySQL AUTOCOMMIT=1 by default (Bro Code video), so beginTransaction()
        // groups INSERT + stock UPDATE into ONE atomic unit.
        // Either both COMMIT, or any failure triggers ROLLBACK.
        try {
            $crop_id = (int)$crop_id;
            $volunteer_id = (int)$volunteer_id;
            $weight_kg = (float)$weight_kg;

            if ($crop_id <= 0) {
                throw new Exception("Please select a valid crop.");
            }
            if ($volunteer_id <= 0) {
                throw new Exception("Please select a volunteer.");
            }
            if ($weight_kg <= 0) {
                throw new Exception("Weight must be greater than zero.");
            }

            $this->pdo->beginTransaction();

            // Lock + validate crop row (same FOR UPDATE technique as demo's balance check)
            $cropCheck = $this->pdo->prepare("SELECT crop_id FROM crop WHERE crop_id = :crop_id FOR UPDATE");
            $cropCheck->execute([':crop_id' => $crop_id]);
            if ($cropCheck->fetchColumn() === false) {
                throw new Exception("Selected crop does not exist. Transaction rolled back.");
            }

            // Validate volunteer exists
            $volCheck = $this->pdo->prepare("SELECT COUNT(*) FROM volunteer WHERE volunteer_id = :vid");
            $volCheck->execute([':vid' => $volunteer_id]);
            if ((int)$volCheck->fetchColumn() === 0) {
                throw new Exception("Selected volunteer does not exist. Transaction rolled back.");
            }

            $sql = "INSERT INTO harvest_record (crop_id, volunteer_id, harvest_date, weight_kg, notes) 
                    VALUES (:crop_id, :volunteer_id, :harvest_date, :weight_kg, :notes)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':crop_id' => $crop_id,
                ':volunteer_id' => $volunteer_id,
                ':harvest_date' => $harvest_date,
                ':weight_kg' => $weight_kg,
                ':notes' => $notes
            ]);

            $updateStock = "UPDATE crop SET total_stock_kg = total_stock_kg + :weight_kg WHERE crop_id = :crop_id";
            $stockStmt = $this->pdo->prepare($updateStock);
            $stockStmt->execute([
                ':weight_kg' => $weight_kg,
                ':crop_id' => $crop_id
            ]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error logging harvest (rolled back): " . $e->getMessage());
            // Return message string so harvest.php can display WHY it rolled back,
            // same as demo's ["status" => "error", "message" => ...] pattern.
            return $e->getMessage();
        }
    }

    // Wrapper in case form passes parameters without volunteer_id or notes
    public function addHarvest($crop_id, $weight_kg, $harvest_date, $volunteer_id = null, $notes = '') {
        return $this->logHarvest($crop_id, $volunteer_id, $harvest_date, $weight_kg, $notes);
    }

    public function getHarvestLogs() {
        try {
            $sql = "SELECT h.*, c.crop_name, v.full_name AS volunteer_name 
                    FROM harvest_record h 
                    JOIN crop c ON h.crop_id = c.crop_id
                    LEFT JOIN volunteer v ON h.volunteer_id = v.volunteer_id
                    ORDER BY h.harvest_date DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching harvest logs: " . $e->getMessage());
            return [];
        }
    }

    // Aliases to ensure distribute.php and other pages run smoothly
    public function getHarvestRecords() {
        return $this->getHarvestLogs();
    }

    public function getHarvests() {
        return $this->getHarvestLogs();
    }
}
?>