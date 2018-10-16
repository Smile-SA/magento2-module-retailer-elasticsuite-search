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
namespace Smile\ElasticsuiteRetailer\Block;

use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext\CollectionFactory as RetailerCollectionFactory;
use Smile\Retailer\Api\Data\RetailerInterface;

/**
 * Custom implementation of the result block to apply on products page.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteRetailer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class Result extends \Magento\Framework\View\Element\Template
{
    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext\Collection
     */
    private $retailerCollection;

    /**
     * @var \Smile\StoreLocator\Helper\Data
     */
    protected $storeLocatorHelper;

    /**
     * Suggest constructor.
     *
     * @param TemplateContext                 $context                   Template contexte.
     * @param QueryFactory                    $queryFactory              Query factory.
     * @param RetailerCollectionFactory       $retailerCollectionFactory Retailer collection factory.
     * @param \Smile\StoreLocator\Helper\Data $storeLocatorHelper        StoreLocator helper.
     * @param array                           $data                      Data.
     */
    public function __construct(
        TemplateContext $context,
        QueryFactory $queryFactory,
        RetailerCollectionFactory $retailerCollectionFactory,
        \Smile\StoreLocator\Helper\Data $storeLocatorHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->queryFactory       = $queryFactory;
        $this->retailerCollection = $this->initRetailerCollection($retailerCollectionFactory);
        $this->storeLocatorHelper = $storeLocatorHelper;
    }

    /**
     * Returns retailer collection.
     *
     * @return \Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext\Collection
     */
    public function getRetailerCollection()
    {
        return $this->retailerCollection;
    }

    /**
     * Returns collection size.
     *
     * @return int|null
     */
    public function getResultCount()
    {
        return $this->getRetailerCollection()->getSize();
    }

    /**
     * Returns query.
     *
     * @return \Magento\Search\Model\Query
     */
    public function getQuery()
    {
        return $this->queryFactory->get();
    }

    /**
     * Returns query text.
     *
     * @return string
     */
    public function getQueryText()
    {
        return $this->getQuery()->getQueryText();
    }

    /**
     * Returns retailer url.
     *
     * @param RetailerInterface $retailer Retailer
     *
     * @return string
     */
    public function getRetailerUrl(RetailerInterface $retailer)
    {
        return $this->storeLocatorHelper->getRetailerUrl($retailer);
    }

    /**
     * Init retailer collection.
     *
     * @param RetailerCollectionFactory $collectionFactory Retailer collection.
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function initRetailerCollection($collectionFactory)
    {
        $retailerCollection = $collectionFactory->create();

        $retailerCollection->addAttributeToSelect(['name', 'url_key']);

        $queryText = $this->getQueryText();
        $retailerCollection->addSearchFilter($queryText);

        return $retailerCollection;
    }
}
