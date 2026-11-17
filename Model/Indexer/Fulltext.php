<?php

declare(strict_types=1);

namespace Smile\ElasticsuiteRetailer\Model\Indexer;

use ArrayIterator;
use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Framework\Search\Request\DimensionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteRetailer\Model\Indexer\Fulltext\Action\Full;

/**
 * Retailers fulltext indexer
 */
class Fulltext implements ActionInterface, MviewActionInterface
{
    public const INDEXER_ID = 'elasticsuite_retailer_fulltext';

    /**
     * @param Full                  $fullAction       The full index action
     * @param IndexerInterface      $indexerHandler   The index handler
     * @param StoreManagerInterface $storeManager     The Store Manager
     * @param DimensionFactory      $dimensionFactory The dimension factory
     */
    public function __construct(
        private Full $fullAction,
        private IndexerInterface $indexerHandler,
        private StoreManagerInterface $storeManager,
        private DimensionFactory $dimensionFactory
    ) {
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids The ids
     */
    public function execute($ids): void // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint
    {
        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            $dimension = $this->dimensionFactory->create(['name' => 'scope', 'value' => $storeId]);
            $this->indexerHandler->deleteIndex([$dimension], new ArrayIterator($ids));
            $this->indexerHandler->saveIndex([$dimension], $this->fullAction->rebuildStoreIndex($ids));
        }
    }

    /**
     * Execute full indexation
     */
    public function executeFull(): void
    {
        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            $dimension = $this->dimensionFactory->create(['name' => 'scope', 'value' => $storeId]);
            $this->indexerHandler->cleanIndex([$dimension]);
            $this->indexerHandler->saveIndex([$dimension], $this->fullAction->rebuildStoreIndex());
        }
    }

    /**
     * @inheritdoc
     */
    public function executeList(array $retailerIds)
    {
        $this->execute($retailerIds);
    }

    /**
     * @inheritdoc
     */
    public function executeRow($retailerId)
    {
        $this->execute([$retailerId]);
    }
}
