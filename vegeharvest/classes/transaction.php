<?php
// classes/transaction.php — TCL code technique (NOT a system feature).
// Refactored from _fletw3pw1_transaction_demo Transaction::transferMoney()
// + YouTube lessons:
//   - Bro Code "MySQL: AUTOCOMMIT, COMMIT, ROLLBACK"
//     (AUTOCOMMIT=1 by default; beginTransaction() groups statements)
//   - RuchiCodes "SQL Transactions Explained | COMMIT, ROLLBACK & SAVEPOINT"
//     (SAVEPOINT = checkpoint for partial ROLLBACK TO SAVEPOINT)
// No nav entry — Harvest / Distribution / Volunteer use the same pattern inline.
class Transaction {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Atomic transfer: deduct + add + log COMMIT together, or ROLLBACK all.
    // Returns ["status" => "success"|"error", "message" => ...] like the demo.
    public function transferMoney($sender_id, $receiver_id, $amount, $simulateError = false) {
        $sender_id   = (int)$sender_id;
        $receiver_id = (int)$receiver_id;
        $amount      = (float)$amount;

        if ($sender_id <= 0 || $receiver_id <= 0) {
            return ["status" => "error", "message" => "Invalid sender or receiver."];
        }
        if ($sender_id === $receiver_id) {
            return ["status" => "error", "message" => "Sender and receiver must differ."];
        }
        if ($amount <= 0) {
            return ["status" => "error", "message" => "Amount must be greater than zero."];
        }

        try {
            // START TRANSACTION (AUTOCOMMIT off until commit/rollback)
            $this->pdo->beginTransaction();

            // Lock sender row + verify exists (FOR UPDATE like balance check)
            $senderQuery = $this->pdo->prepare("SELECT balance FROM fund_account WHERE account_id = :id FOR UPDATE");
            $senderQuery->execute([':id' => $sender_id]);
            $senderBalance = $senderQuery->fetchColumn();
            if ($senderBalance === false) {
                throw new Exception("Sender account #$sender_id does not exist. Transaction rolled back.");
            }

            // Verify receiver exists
            $receiverQuery = $this->pdo->prepare("SELECT COUNT(*) FROM fund_account WHERE account_id = :id");
            $receiverQuery->execute([':id' => $receiver_id]);
            if ((int)$receiverQuery->fetchColumn() === 0) {
                throw new Exception("Receiver account #$receiver_id does not exist. Transaction rolled back.");
            }

            // Check funds before moving
            if ((float)$senderBalance < $amount) {
                throw new Exception("Insufficient funds for account #$sender_id. Transaction rolled back.");
            }

            // Deduct
            $deduct = $this->pdo->prepare("UPDATE fund_account SET balance = balance - :amount WHERE account_id = :id");
            $deduct->execute([':amount' => $amount, ':id' => $sender_id]);

            // SAVEPOINT after deduction (partial rollback point)
            $this->pdo->exec("SAVEPOINT after_deduct");

            // Classroom demo: force failure to prove ROLLBACK restores money
            if ($simulateError) {
                $this->pdo->exec("ROLLBACK TO SAVEPOINT after_deduct");
                throw new Exception("Simulated error after deduction. Transaction rolled back.");
            }

            // Add to receiver
            $add = $this->pdo->prepare("UPDATE fund_account SET balance = balance + :amount WHERE account_id = :id");
            $add->execute([':amount' => $amount, ':id' => $receiver_id]);

            // Log inside same transaction
            $log = $this->pdo->prepare("INSERT INTO fund_transfer (sender_id, receiver_id, amount, status, remarks) VALUES (:s, :r, :a, 'Committed', 'Transfer OK')");
            $log->execute([':s' => $sender_id, ':r' => $receiver_id, ':a' => $amount]);

            $this->pdo->exec("RELEASE SAVEPOINT after_deduct");

            // COMMIT — all permanent together
            $this->pdo->commit();
            return ["status" => "success", "message" => "₱$amount transferred successfully."];
        } catch (Exception $e) {
            // ROLLBACK — undo everything
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ["status" => "error", "message" => $e->getMessage()];
        }
    }
}
