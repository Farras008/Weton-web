-- Run this once only if the previous payments schema has already been imported.
ALTER TABLE payments ADD COLUMN email_sending_at DATETIME NULL AFTER email_sent_at;
