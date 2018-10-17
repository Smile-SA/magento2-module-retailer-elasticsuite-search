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

namespace Smile\ElasticsuiteRetailer\Model\ResourceModel\Indexer\Fulltext\Action;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Model\ResourceModel\Indexer\AbstractIndexer;
use Smile\Seller\Api\AttributeRepositoryInterface;
use Smile\ElasticsuiteRetailer\Helper\Configuration;

/**
 * ElasticSearch retailer full indexer resource model.
 *
 * @category  Smile
 * @package   Smile\ElasticsuiteRetailer
 * @author    Fanny DECLERCK <fadec@smile.fr>
 */
class Full extends AbstractIndexer
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var Configuration
     */
    private $helper;

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
        AttributeRepositoryInterface $attributeRepository,
        Configuration $helper
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->helper              = $helper;
        parent::__construct($resource, $storeManager);
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
    public function getSearchableRetailer($retailerIds = null, $fromId = 0, $limit = 100)
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
