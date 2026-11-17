<?php

declare(strict_types=1);

namespace Smile\ElasticsuiteRetailer\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;
use Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext\Collection;
use Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext\CollectionFactory as RetailerCollectionFactory;
use Smile\Retailer\Api\Data\RetailerInterface;
use Smile\StoreLocator\Helper\Data;

/**
 * Custom implementation of the result block to apply on products page.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Result extends Template
{
    private Collection $retailerCollection;

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
        private QueryFactory $queryFactory,
        RetailerCollectionFactory $retailerCollectionFactory,
        protected Data $storeLocatorHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->retailerCollection = $this->initRetailerCollection($retailerCollectionFactory);
    }

    /**
     * Returns retailer collection.
     */
    public function getRetailerCollection(): Collection
    {
        return $this->retailerCollection;
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

        $queryText = $this->getQueryText();
        $retailerCollection->addSearchFilter($queryText);

        return $retailerCollection;
    }
}
