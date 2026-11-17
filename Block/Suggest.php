<?php

declare(strict_types=1);

namespace Smile\ElasticsuiteRetailer\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteRetailer\Helper\Configuration;
use Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext\Collection;
use Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext\CollectionFactory as RetailerCollectionFactory;
use Smile\Retailer\Api\Data\RetailerInterface;
use Smile\StoreLocator\Helper\Data;

/**
 * Custom implementation of the suggest block to apply on products page.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Suggest extends Template
{
    /**
     * Name of field to get max results.
     */
    public const MAX_RESULT = 'max_result';

    /**
     * Name of field to get if enable block.
     */
    public const ENABLE_BLOCK = 'enabled';

    private Collection $retailerCollection;

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
        private QueryFactory $queryFactory,
        RetailerCollectionFactory $retailerCollectionFactory,
        private Configuration $helper,
        protected Data $storeLocatorHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->retailerCollection = $this->initRetailerCollection($retailerCollectionFactory);
    }

    /**
     * Returns if block can be display.
     */
    public function canShowBlock(): bool
    {
        return $this->getResultCount() > 0 && (bool) $this->helper->getConfigValue(self::ENABLE_BLOCK);
    }

    /**
     * Returns retailer collection.
     */
    public function getRetailerCollection(): Collection
    {
        return $this->retailerCollection;
    }

    /**
     * Returns number of results.
     */
    public function getNumberOfResults(): int
    {
        return (int) $this->helper->getConfigValue(self::MAX_RESULT);
    }

    /**
     * Returns collection size.
     */
    public function getResultCount(): ?int
    {
        return $this->getRetailerCollection()->getSize();
    }

    /**
     * Returns query.
     */
    public function getQuery(): Query
    {
        /** @var Query $query */
        $query = $this->queryFactory->get();

        return $query;
    }

    /**
     * Returns query text.
     */
    public function getQueryText(): string
    {
        return $this->getQuery()->getQueryText();
    }

    /**
     * Returns all results url page.
     */
    public function getShowAllUrl(): string
    {
        return $this->getUrl('elasticsuite_retailer/result', ['q' => $this->getQueryText()]);
    }

    /**
     * Returns retailer url.
     *
     * @param RetailerInterface $retailer Retailer
     */
    public function getRetailerUrl(RetailerInterface $retailer): string
    {
        return $this->storeLocatorHelper->getRetailerUrl($retailer);
    }

    /**
     * Init retailer collection.
     *
     * @param RetailerCollectionFactory $collectionFactory Retailer collection.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function initRetailerCollection(RetailerCollectionFactory $collectionFactory): Collection
    {
        $retailerCollection = $collectionFactory->create();

        $retailerCollection->addAttributeToSelect(['name', 'url_key']);

        $retailerCollection->setPageSize($this->getNumberOfResults());

        $queryText = $this->getQueryText();
        $retailerCollection->addSearchFilter($queryText);

        return $retailerCollection;
    }
}
