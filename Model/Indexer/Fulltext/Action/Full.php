<?php

declare(strict_types=1);

namespace Smile\ElasticsuiteRetailer\Model\Indexer\Fulltext\Action;

use Smile\ElasticsuiteRetailer\Model\ResourceModel\Indexer\Fulltext\Action\Full as ResourceModel;
use Traversable;

/**
 * ElasticSearch retailers full indexer
 */
class Full
{
    /**
     * Constructor.
     *
     * @param ResourceModel $resourceModel Indexer resource model.
     */
    public function __construct(private ResourceModel $resourceModel)
    {
    }

    /**
     * Get data for a list of retailer in a store id.
     *
     * @param array|null $retailerIds List of retailer ids.
     */
    public function rebuildStoreIndex(?array $retailerIds = null): Traversable
    {
        $lastRetailerId = 0;

        do {
            $retailers = $this->getSearchableRetailer($retailerIds, $lastRetailerId);
            foreach ($retailers as $retailer) {
                $lastRetailerId = (int) $retailer['retailer_id'];
                yield $lastRetailerId => $retailer;
            }
        } while (!empty($retailers));
    }

    /**
     * Load a bulk of retailer data.
     *
     * @param int[]|null $retailerIds Retailer ids filter.
     * @param int        $fromId      Load retailers with id greater than.
     * @param int        $limit       Number of retailers to get loaded.
     */
    private function getSearchableRetailer(?array $retailerIds = null, int $fromId = 0, int $limit = 100): array
    {
        return $this->resourceModel->getSearchableRetailer($retailerIds, $fromId, $limit);
    }
}
