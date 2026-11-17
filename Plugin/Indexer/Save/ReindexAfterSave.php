<?php

declare(strict_types=1);

namespace Smile\ElasticsuiteRetailer\Plugin\Indexer\Save;

use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Indexer\IndexerRegistry;
use Smile\ElasticsuiteRetailer\Model\Indexer\Fulltext;
use Smile\Retailer\Api\Data\RetailerInterface;

/**
 * Plugin that proceed retailer reindex in ES after save
 */
class ReindexAfterSave
{
    /**
     * ReindexAfterSave constructor.
     *
     * @param IndexerRegistry $indexerRegistry The indexer registry
     */
    public function __construct(private IndexerRegistry $indexerRegistry)
    {
    }

    /**
     * Reindex retailer's data into search engine after saving
     *
     * @param EntityManager $subject The entity manager
     * @param object        $result  The saved entity
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(EntityManager $subject, object $result): object
    {
        if ($result instanceof RetailerInterface) {
            if ($result->getIsActive()) {
                $retailerIndexer = $this->indexerRegistry->get(Fulltext::INDEXER_ID);
                $retailerIndexer->reindexRow($result->getId());
            }
        }

        return $result;
    }
}
