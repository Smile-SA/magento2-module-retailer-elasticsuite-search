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
use Smile\ElasticsuiteRetailer\Helper\Configuration;
use Smile\Retailer\Api\Data\RetailerInterface;

/**
 * Custom implementation of the suggest block to apply on products page.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @category Smile
 * @package  Smile\ElasticsuiteRetailer
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class Suggest extends \Magento\Framework\View\Element\Template
{
    /**
     * Name of field to get max results.
     *
     * @var string
     */
    const MAX_RESULT = 'max_result';

    /**
     * Name of field to get if enable block.
     *
     * @var bool
     */
    const ENABLE_BLOCK = 'enabled';

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var Configuration
     */
    private $helper;

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
     * @param Configuration                   $helper                    Configuration helper.
     * @param \Smile\StoreLocator\Helper\Data $storeLocatorHelper        StoreLocator helper.
     * @param array                           $data                      Data.
     */
    public function __construct(
        TemplateContext $context,
        QueryFactory $queryFactory,
        RetailerCollectionFactory $retailerCollectionFactory,
        Configuration $helper,
        \Smile\StoreLocator\Helper\Data $storeLocatorHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->queryFactory       = $queryFactory;
        $this->helper             = $helper;
        $this->retailerCollection = $this->initRetailerCollection($retailerCollectionFactory);
        $this->storeLocatorHelper = $storeLocatorHelper;
    }

    /**
     * Returns if block can be display.
     *
     * @return bool
     */
    public function canShowBlock()
    {
        return $this->getResultCount() > 0 && $this->helper->getConfigValue(self::ENABLE_BLOCK);
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
     * Returns number of results.
     *
     * @return int
     */
    public function getNumberOfResults()
    {
        return $this->helper->getConfigValue(self::MAX_RESULT);
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
     * Returns all results url page.
     *
     * @return string
     */
    public function getShowAllUrl()
    {
        return $this->getUrl('elasticsuite_retailer/result', ['q' => $this->getQueryText()]);
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

        $retailerCollection->setPageSize($this->getNumberOfResults());

        $queryText = $this->getQueryText();
        $retailerCollection->addSearchFilter($queryText);

        return $retailerCollection;
    }
}
