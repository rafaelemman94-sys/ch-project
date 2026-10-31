DROP TABLE IF EXISTS `ronin_transactions`;

ALTER TABLE `legislatives`
  DROP COLUMN IF EXISTS `blockchainHash`,
  DROP COLUMN IF EXISTS `previousHash`,
  DROP COLUMN IF EXISTS `lastHash`;