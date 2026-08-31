-- Run once for existing installations after migration 001.
ALTER TABLE payments
  MODIFY status ENUM('PENDING','SUCCESS','PAID','FAILED','EXPIRED') NOT NULL DEFAULT 'PENDING';

UPDATE payments SET status = 'PAID' WHERE status = 'SUCCESS';

ALTER TABLE payments
  DROP INDEX idx_payments_duitku_reference,
  DROP COLUMN duitku_reference,
  MODIFY status ENUM('PENDING','PAID','FAILED','EXPIRED') NOT NULL DEFAULT 'PENDING',
  ADD COLUMN doku_transaction_id VARCHAR(100) NULL AFTER reference,
  ADD KEY idx_payments_doku_transaction_id (doku_transaction_id);
