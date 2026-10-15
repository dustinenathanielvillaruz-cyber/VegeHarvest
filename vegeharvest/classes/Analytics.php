<?php
class Analytics {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Equity check: Total kg distributed per Purok
    public function getDistributionPerPurok() {
        $sql = "SELECT p.purok_name, COALESCE(SUM(d.quantity_kg), 0) AS total_kg
                FROM purok p
                LEFT JOIN distribution_record d ON p.purok_id = d.purok_id
                GROUP BY p.purok_id, p.purok_name";
        return $this->pdo->query($sql)->fetchAll();
    }

    // Yield metrics per Crop Category
    public function getHarvestYieldByCrop() {
        $sql = "SELECT c.crop_name, c.category, COALESCE(SUM(h.weight_kg), 0) AS total_harvested
                FROM crop c
                LEFT JOIN harvest_record h ON c.crop_id = h.crop_id
                GROUP BY c.crop_id, c.crop_name, c.category";
        return $this->pdo->query($sql)->fetchAll();
    }
}