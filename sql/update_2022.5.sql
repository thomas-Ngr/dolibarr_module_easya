-- Easya 2022.5

-- Dictionaries - add possibility to manage countries in EEC #20261
UPDATE llx_c_country SET eec = 0 WHERE code IN ('GB', 'UK', 'IM');
UPDATE llx_c_country SET eec=0 WHERE eec IS NULL;
ALTER TABLE llx_c_country MODIFY COLUMN eec tinyint DEFAULT 0 NOT NULL;

-- Add option for SEPA formatting
ALTER TABLE llx_bank_account ADD COLUMN pti_in_ctti integer DEFAULT 0 AFTER domiciliation;

-- keep the last msg sent to display warnings on ticket list
ALTER TABLE llx_ticket ADD COLUMN date_last_msg_sent datetime AFTER date_read;
