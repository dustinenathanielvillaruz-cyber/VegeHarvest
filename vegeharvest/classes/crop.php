<?php
class Crop {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function addCrop($crop_name, $category, $total_stock_kg = 0.00) {
        try {
            $sql = "INSERT INTO crop (crop_name, category, total_stock_kg) VALUES (:crop_name, :category, :total_stock_kg)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':crop_name' => $crop_name,
                ':category' => $category,
                ':total_stock_kg' => $total_stock_kg
            ]);
        } catch (PDOException $e) {
            error_log("Error adding crop: " . $e->getMessage());
            return false;
        }
    }

    public function getCrops() {
        try {
            $sql = "SELECT * FROM crop ORDER BY crop_id DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching crops: " . $e->getMessage());
            return [];
        }
    }

    public function getCropById($id) {
        try {
            $sql = "SELECT * FROM crop WHERE crop_id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching crop by ID: " . $e->getMessage());
            return false;
        }
    }

    public function updateCrop($id, $crop_name, $category, $total_stock_kg) {
        try {
            $sql = "UPDATE crop SET crop_name = :crop_name, category = :category, total_stock_kg = :total_stock_kg WHERE crop_id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':crop_name' => $crop_name,
                ':category' => $category,
                ':total_stock_kg' => $total_stock_kg
            ]);
        } catch (PDOException $e) {
            error_log("Error updating crop: " . $e->getMessage());
            return false;
        }
    }

    public function deleteCrop($id) {
        try {
            $sql = "DELETE FROM crop WHERE crop_id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting crop: " . $e->getMessage());
            return false;
        }
    }

    public function searchCrops($keyword) {
        try {
            $sql = "SELECT * FROM crop 
                    WHERE crop_name LIKE :keyword 
                       OR category LIKE :keyword 
                       OR crop_id LIKE :keyword 
                    ORDER BY crop_id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':keyword' => "%" . $keyword . "%"]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error searching crops: " . $e->getMessage());
            return [];
        }
    }
}
?>