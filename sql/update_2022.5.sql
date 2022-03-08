-- Easya 2022.5

-- Dictionaries - add possibility to manage countries in EEC #20261
UPDATE llx_c_country SET eec = 0 WHERE code IN ('GB', 'UK', 'IM');
UPDATE llx_c_country SET eec=0 WHERE eec IS NULL;
ALTER TABLE llx_c_country MODIFY COLUMN eec tinyint DEFAULT 0 NOT NULL;

-- Add option for SEPA formatting
ALTER TABLE llx_bank_account ADD COLUMN pti_in_ctti integer DEFAULT 0 AFTER domiciliation;

