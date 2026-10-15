<?php
class Volunteer {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getVolunteers() {
        $sql = "SELECT v.*, p.purok_name, u.username 
                FROM volunteer v 
                LEFT JOIN purok p ON v.purok_id = p.purok_id 
                LEFT JOIN users u ON v.user_id = u.user_id 
                ORDER BY v.volunteer_id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVolunteerById($id) {
        $sql = "SELECT v.*, u.username 
                FROM volunteer v 
                LEFT JOIN users u ON v.user_id = u.user_id 
                WHERE v.volunteer_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addVolunteer($fullName, $contactNumber, $purokId, $role = 'Volunteer', $username = null, $password = null) {
        // TCL: users + volunteer must succeed together (COMMIT) or both fail (ROLLBACK).
        // Mirrors _fletw3pw1_transaction_demo transferMoney(): beginTransaction -> validates ->
        // writes -> commit, catch -> rollBack. See Bro Code AUTOCOMMIT/COMMIT/ROLLBACK video.
        try {
            $this->pdo->beginTransaction();

            $userId = null;

            // If registering as Admin, create record in users table first
            if ($role === 'Admin' && !empty($username) && !empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $userSql = "INSERT INTO users (username, password_hash, role, full_name, contact_number) VALUES (?, ?, ?, ?, ?)";
                $userStmt = $this->pdo->prepare($userSql);
                $userStmt->execute([$username, $hashedPassword, 'admin', $fullName, $contactNumber]);
                $userId = $this->pdo->lastInsertId();
            }

            // Insert into volunteer table with linked user_id
            $sql = "INSERT INTO volunteer (user_id, purok_id, full_name, contact_number, status, role) VALUES (?, ?, ?, ?, 'active', ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $purokId, $fullName, $contactNumber, $role]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error adding volunteer (rolled back): " . $e->getMessage());
            return false;
        }
    }

    public function updateVolunteer($id, $fullName, $contactNumber, $purokId, $role = 'Volunteer', $username = null, $password = null) {
        // TCL: keep users + volunteer in sync atomically.
        try {
            $this->pdo->beginTransaction();

            $member = $this->getVolunteerById($id);
            if (!$member) {
                throw new Exception("Volunteer #$id does not exist.");
            }
            $userId = $member['user_id'];

            if ($role === 'Admin') {
                if ($userId) {
                    // Update existing user credentials
                    if (!empty($password)) {
                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                        $userSql = "UPDATE users SET username = ?, password_hash = ?, full_name = ?, contact_number = ? WHERE user_id = ?";
                        $userStmt = $this->pdo->prepare($userSql);
                        $userStmt->execute([$username, $hashedPassword, $fullName, $contactNumber, $userId]);
                    } else {
                        $userSql = "UPDATE users SET username = ?, full_name = ?, contact_number = ? WHERE user_id = ?";
                        $userStmt = $this->pdo->prepare($userSql);
                        $userStmt->execute([$username, $fullName, $contactNumber, $userId]);
                    }
                } else if (!empty($username) && !empty($password)) {
                    // Promoted from Volunteer to Admin: create new user account
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $userSql = "INSERT INTO users (username, password_hash, role, full_name, contact_number) VALUES (?, ?, ?, ?, ?)";
                    $userStmt = $this->pdo->prepare($userSql);
                    $userStmt->execute([$username, $hashedPassword, 'admin', $fullName, $contactNumber]);
                    $userId = $this->pdo->lastInsertId();
                }
            } else {
                // Demoted back to Volunteer: remove associated user account
                if ($userId) {
                    $delUser = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $delUser->execute([$userId]);
                    $userId = null;
                }
            }

            // Update volunteer record
            $sql = "UPDATE volunteer SET user_id = ?, full_name = ?, contact_number = ?, purok_id = ?, role = ? WHERE volunteer_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $fullName, $contactNumber, $purokId, $role, $id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error updating volunteer (rolled back): " . $e->getMessage());
            return false;
        }
    }

    public function deleteVolunteer($id) {
        // TCL: deleting volunteer + linked user must be atomic.
        try {
            $this->pdo->beginTransaction();

            $member = $this->getVolunteerById($id);
            if (!$member) {
                throw new Exception("Volunteer #$id does not exist.");
            }
            if (!empty($member['user_id'])) {
                $delUser = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $delUser->execute([$member['user_id']]);
            }
            $sql = "DELETE FROM volunteer WHERE volunteer_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error deleting volunteer (rolled back): " . $e->getMessage());
            return false;
        }
    }
}