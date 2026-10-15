<?php
class Purok {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function addPurok($purok_name, $barangay) {
        try {
            $sql = "INSERT INTO purok (purok_name, barangay) VALUES (:purok_name, :barangay)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':purok_name' => $purok_name,
                ':barangay'   => $barangay
            ]);
        } catch (PDOException $e) {
            error_log("Error adding purok: " . $e->getMessage());
            return false;
        }
    }

    public function getPuroks() {
        try {
            $sql = "SELECT * FROM purok ORDER BY barangay ASC, purok_name ASC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching puroks: " . $e->getMessage());
            return [];
        }
    }

    public function getPurokById($id) {
        try {
            $sql = "SELECT * FROM purok WHERE purok_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching purok by ID: " . $e->getMessage());
            return false;
        }
    }

    public function updatePurok($id, $purok_name, $barangay) {
        try {
            $sql = "UPDATE purok SET purok_name = :purok_name, barangay = :barangay WHERE purok_id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id'         => $id,
                ':purok_name' => $purok_name,
                ':barangay'   => $barangay
            ]);
        } catch (PDOException $e) {
            error_log("Error updating purok: " . $e->getMessage());
            return false;
        }
    }

    public function deletePurok($id) {
        try {
            $sql = "DELETE FROM purok WHERE purok_id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting purok: " . $e->getMessage());
            return false;
        }
    }
}
?>