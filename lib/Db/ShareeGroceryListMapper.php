<?php

namespace OCA\GroceryList\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

/**
 * @template-implements QBMapper<Sharee>
 */
class ShareeGroceryListMapper extends QBMapper {
	private $userId;

	public function __construct(IDBConnection $db, $userId) {
		parent::__construct($db, 'grocerylist_sharee', Sharee::class);

		$this->userId = $userId;
	}

	public function find(int $id) : array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('list', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));

		return $this->findEntities($qb);
	}

	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($this->userId)));

		return $this->findEntities($qb);
	}
}
