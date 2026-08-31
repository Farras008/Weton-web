-- Run once on an existing installation before deploying the DOKU Checkout code.
-- This is safe after migration 002 and also repairs installations where that
-- migration was applied to an older payments table that did not have reference.

SET @payments_schema := DATABASE();

SELECT COUNT(*) INTO @has_reference
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @payments_schema AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'reference';
SET @sql := IF(@has_reference = 0,
    'ALTER TABLE payments ADD COLUMN reference VARCHAR(100) NULL AFTER id',
    'SELECT 1');
PREPARE payments_schema_statement FROM @sql;
EXECUTE payments_schema_statement;
DEALLOCATE PREPARE payments_schema_statement;

SELECT COUNT(*) INTO @has_doku_transaction_id
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @payments_schema AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'doku_transaction_id';
SET @sql := IF(@has_doku_transaction_id = 0,
    'ALTER TABLE payments ADD COLUMN doku_transaction_id VARCHAR(100) NULL AFTER reference',
    'SELECT 1');
PREPARE payments_schema_statement FROM @sql;
EXECUTE payments_schema_statement;
DEALLOCATE PREPARE payments_schema_statement;

SELECT COUNT(*) INTO @has_payment_message
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @payments_schema AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'payment_message';
SET @sql := IF(@has_payment_message = 0,
    'ALTER TABLE payments ADD COLUMN payment_message VARCHAR(255) NULL AFTER payment_method',
    'SELECT 1');
PREPARE payments_schema_statement FROM @sql;
EXECUTE payments_schema_statement;
DEALLOCATE PREPARE payments_schema_statement;

SELECT COUNT(*) INTO @has_doku_transaction_index
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @payments_schema AND TABLE_NAME = 'payments' AND INDEX_NAME = 'idx_payments_doku_transaction_id';
SET @sql := IF(@has_doku_transaction_index = 0,
    'ALTER TABLE payments ADD KEY idx_payments_doku_transaction_id (doku_transaction_id)',
    'SELECT 1');
PREPARE payments_schema_statement FROM @sql;
EXECUTE payments_schema_statement;
DEALLOCATE PREPARE payments_schema_statement;
