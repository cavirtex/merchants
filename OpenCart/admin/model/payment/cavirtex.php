<?php


class ModelPaymentCavirtex extends Model {
		public function install() {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order ADD cavirtex_order_key VARCHAR(255)");
		}

		public function uninstall() {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "order DROP COLUMN cavirtex_order_key");
		}
}
