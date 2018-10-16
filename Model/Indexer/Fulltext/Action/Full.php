<?php
/**
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteRetailer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2018 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace Smile\ElasticsuiteRetailer\Model\Indexer\Fulltext\Action;

use Smile\ElasticsuiteRetailer\Model\ResourceModel\Indexer\Fulltext\Action\Full as ResourceModel;

/**
 * ElasticSearch retailers full indexer
 *
 * @category Smile
 * @package  Smile\ElasticsuiteRetailer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class Full
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * Constructor.
     *
     * @param ResourceModel $resourceModel Indexer resource model.
     */
    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    /**
     * Get data for a list of retailer in a store id.
     *
     * @param array|null $retailerIds List of retailer ids.
     *
     * @return \Traversable
     */
    public function rebuildStoreIndex($retailerIds = null)
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
     * @param string  $retailerIds Retailer ids filter.
     * @param integer $fromId      Load product with id greater than.
     * @param integer $limit       Number of product to get loaded.
     *
     * @return array
     */
    private function getSearchableRetailer($retailerIds = null, $fromId = 0, $limit = 100)
    {
        return $this->resourceModel->getSearchableRetailer($retailerIds, $fromId, $limit);
    }
}
