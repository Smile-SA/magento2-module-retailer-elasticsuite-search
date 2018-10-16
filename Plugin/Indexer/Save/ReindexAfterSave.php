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
namespace Smile\ElasticsuiteRetailer\Plugin\Indexer\Save;

use Magento\Framework\Indexer\IndexerRegistry;
use Smile\ElasticsuiteRetailer\Model\Indexer\Fulltext;
use Magento\Framework\EntityManager\EntityManager;

/**
 * Plugin that proceed retailer reindex in ES after save
 *
 * @category Smile
 * @package  Smile\ElasticsuiteRetailer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class ReindexAfterSave
{
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * ReindexAfterSave constructor.
     *
     * @param IndexerRegistry $indexerRegistry The indexer registry
     */
    public function __construct(IndexerRegistry $indexerRegistry)
    {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Reindex retailer's data into search engine after saving
     *
     * @param EntityManager $subject The retailer being reindexed
     * @param EntityManager $result  The parent function we are plugged on
     *
     * @return EntityManager
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        EntityManager $subject,
        $result
    ) {
        if ($result instanceof \Smile\Retailer\Api\Data\RetailerInterface) {
            if ($result->getIsActive()) {
                $retailerIndexer = $this->indexerRegistry->get(Fulltext::INDEXER_ID);
                $retailerIndexer->reindexRow($result->getId());
            }
        }

        return $result;
    }
}
