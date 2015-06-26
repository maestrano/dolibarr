ALTER TABLE `llx_bank_account` MODIFY `label` VARCHAR(255);

-- Recreate a non unique index
DROP INDEX `uk_c_tva_id` ON `llx_c_tva`;
CREATE INDEX `uk_c_tva_id` ON `llx_c_tva` (fk_pays, taux, recuperableonly);
