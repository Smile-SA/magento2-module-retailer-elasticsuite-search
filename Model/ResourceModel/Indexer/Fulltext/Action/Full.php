<?php

declare(strict_types=1);

namespace Smile\ElasticsuiteRetailer\Model\ResourceModel\Indexer\Fulltext\Action;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Model\ResourceModel\Indexer\AbstractIndexer;
use Smile\ElasticsuiteRetailer\Helper\Configuration;
use Smile\Seller\Api\AttributeRepositoryInterface;

/**
 * ElasticSearch retailer full indexer resource model.
 */
class Full extends AbstractIndexer
{
    /**
     * Full constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection  $resource            Resource Connection
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager        Store Manager
     * @param AttributeRepositoryInterface               $attributeRepository Seller attribute Repository
     * @param Configuration                              $helper              Configuration helper.
     */
    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        private AttributeRepositoryInterface $attributeRepository,
        private Configuration $helper
    ) {
        parent::__construct($resource, $storeManager);
    }

    /**
     * Load a bulk of retailer data.
     *
     * @param int[]|null $retailerIds Retailer ids filter.
     * @param int        $fromId      Load retailers with id greater than.
     * @param int        $limit       Number of retailers to get loaded.
     */
    public function getSearchableRetailer(?array $retailerIds = null, int $fromId = 0, int $limit = 100): array
    {
        $fieldToSelect = ['retailer_id'];

        $fieldCanBeIndexed = ['street', 'postcode', 'latitude', 'longitude'];
        foreach ($fieldCanBeIndexed as $field) {
            if ($this->helper->isIndexed($field)) {
                $fieldToSelect[] = $field;
            }
        }

        $select = $this->getConnection()->select()
            ->from(
                ['sra' => $this->getTable('smile_retailer_address')],
                $fieldToSelect
            );

        $select->join(
            ['sse' => $this->getTable('smile_seller_entity')],
            'sra.retailer_id= sse.entity_id',
            null
        );

        $attributeNameId = $this->attributeRepository->get('name')->getAttributeId();
        $select->join(
            ['ssev' => $this->getTable('smile_seller_entity_varchar')],
            "ssev.attribute_id = $attributeNameId AND sse.entity_id = ssev.entity_id",
            ['name' => 'ssev.value']
        );

        $attributeIsActiveId = $this->attributeRepository->get('is_active')->getAttributeId();
        $select->join(
            ['ssei' => $this->getTable('smile_seller_entity_int')],
            "ssei.attribute_id = $attributeIsActiveId AND sse.entity_id = ssei.entity_id AND ssei.value = 1",
            ['is_active' => 'ssei.value']
        );

        if ($this->helper->isIndexed('description')) {
            $attributeDescId = $this->attributeRepository->get('description')->getAttributeId();
            $select->join(
                ['sset' => $this->getTable('smile_seller_entity_text')],
                "sset.attribute_id = $attributeDescId AND sse.entity_id = sset.entity_id",
                ['description' => 'sset.value']
            );
        }

        if ($retailerIds !== null) {
            $select->where('sra.retailer_id IN (?)', $retailerIds);
        }

        $select->where('sra.retailer_id > ?', $fromId)
            ->limit($limit)
            ->order('sra.retailer_id');

        return $this->connection->fetchAll($select);
    }
}
