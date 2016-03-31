<?php
use Migrations\AbstractMigration;

class <%= $className %> extends AbstractMigration
{
	/**
	 * Change Method.
	 *
	 * More information on this method is available here:
	 * http://docs.phinx.org/en/latest/migrations.html#the-change-method
	 * @return void
	 */
	public function change()
	{
		$isUuid = <%= $isUuid ? 'true' : 'false'; %>;
		$baseTable = $this->table('<%= $baseTableName %>');
		$baseTable->addColumn('stripe_customer_id', 'string', [
			'default' => null,
			'limit' => 	255,
			'null' => true,
		]);
		$baseTable->addColumn('card_brand', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true,
		]);
		$baseTable->addColumn('card_last_four', 'string', [
			'default' => null,
			'limit' => 255,
			'null' => true
		]);

		$baseTable->update();

		$subscriptionTable = $this->table('<%= $subscriptionTableName %>', ['id' => false, 'primary_key' => ['id']]);
		if($isUuid) {
			$subscriptionTable->addColumn('id', 'uuid', [
					'null' => false,
			]);
			$subscriptionTable->addColumn('<%= $joiningFieldName %>', 'uuid', [
					'default' => false,
					'null' => false,
			]);
		} else {
			$subscriptionTable->addColumn('id', 'integer', [
					'null' => false,
					'autoIncrement'=>true
			]);

			$subscriptionTable->addColumn('<%= $joiningFieldName %>', 'integer', [
					'default' => false,
					'null' => false,
			]);
		}

		$subscriptionTable->addColumn('stripe_subscription_id', 'string', [
			'default' => false,
			'null' => false
		]);

		$subscriptionTable->addColumn('stripe_plan_id', 'string', [
			'default' => false,
			'null' => false
		]);

		$subscriptionTable->addColumn('quantity', 'integer', [
			'default' => 1,
			'null' => true
		]);

		$subscriptionTable->addColumn('expiry', 'datetime', [
			'default' => null,
			'null' => true
		]);

		$subscriptionTable->addColumn('trial_expiry', 'datetime', [
				'default' => null,
				'null' => true
		]);

		$subscriptionTable->addColumn('created', 'datetime', [
				'default' => null,
				'null' => false,
		]);

		$subscriptionTable->addColumn('modified', 'datetime', [
				'default' => null,
				'null' => false,
		]);

		$subscriptionTable->create();
	}
}