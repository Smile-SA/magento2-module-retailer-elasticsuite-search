<?php

declare(strict_types=1);

namespace Smile\ElasticsuiteRetailer\Model\Autocomplete;

use Magento\Search\Model\Autocomplete\DataProviderInterface;
use Magento\Search\Model\Autocomplete\ItemFactory;
use Magento\Search\Model\Autocomplete\ItemInterface;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ElasticsuiteCore\Helper\Autocomplete as ConfigurationHelper;
use Smile\ElasticsuiteCore\Model\Autocomplete\Terms\DataProvider as TermDataProvider;
use Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext\Collection;
use Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext\CollectionFactory as RetailerCollectionFactory;
use Smile\StoreLocator\Helper\Data;

/**
 * Retailer autocomplete data provider.
 */
class DataProvider implements DataProviderInterface
{
    /**
     * Autocomplete type
     */
    public const AUTOCOMPLETE_TYPE = "retailer";

    /**
     * Autocomplete result item factory
     */
    protected ItemFactory $itemFactory;

    /**
     * Query factory
     */
    protected QueryFactory $queryFactory;

    /**
     * Store manager
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var string Autocomplete result type
     */
    private string $type;

    /**
     * Constructor.
     *
     * @param ItemFactory                     $itemFactory               Suggest item factory.
     * @param QueryFactory                    $queryFactory              Search query factory.
     * @param TermDataProvider                $termDataProvider          Search terms suggester.
     * @param RetailerCollectionFactory       $retailerCollectionFactory Retailer collection factory.
     * @param ConfigurationHelper             $configurationHelper       Autocomplete configuration helper.
     * @param StoreManagerInterface           $storeManager              Store manager.
     * @param \Smile\StoreLocator\Helper\Data $storeLocatorHelper        StoreLocator Helper.
     * @param string                          $type                      Autocomplete provider type.
     */
    public function __construct(
        ItemFactory $itemFactory,
        QueryFactory $queryFactory,
        protected TermDataProvider $termDataProvider,
        protected RetailerCollectionFactory $retailerCollectionFactory,
        protected ConfigurationHelper $configurationHelper,
        StoreManagerInterface $storeManager,
        protected Data $storeLocatorHelper,
        string $type = self::AUTOCOMPLETE_TYPE
    ) {
        $this->itemFactory          = $itemFactory;
        $this->queryFactory         = $queryFactory;
        $this->type                 = $type;
        $this->storeManager         = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function getItems()
    {
        $result = [];
        foreach ($this->getRetailerCollection() as $retailer) {
            $result[] = $this->itemFactory->create(
                [
                    'title' => $retailer->getName(),
                    'url'   => $this->storeLocatorHelper->getRetailerUrl($retailer),
                    'type'  => $this->getType(),
                ]
            );
        }

        return $result;
    }

    /**
     * List of search terms suggested by the search terms data daprovider.
     */
    private function getSuggestedTerms(): array
    {
        $terms = array_map(
            function (ItemInterface $termItem) {
                return $termItem->getTitle();
            },
            $this->termDataProvider->getItems()
        );

        return $terms;
    }

    /**
     * Suggested retailer collection.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getRetailerCollection(): Collection
    {
        $suggestedTerms = $this->getSuggestedTerms();
        $terms          = [$this->queryFactory->get()->getQueryText()];

        if (!empty($suggestedTerms)) {
            $terms = array_merge($terms, $suggestedTerms);
        }

        $retailerCollection = $this->retailerCollectionFactory->create();
        $retailerCollection->addAttributeToSelect(['name', 'url_key']);
        $retailerCollection->addSearchFilter($terms);
        $retailerCollection->setPageSize($this->getResultsPageSize());

        return $retailerCollection;
    }

    /**
     * Retrieve number of retailers to display in autocomplete results
     */
    private function getResultsPageSize(): int
    {
        return $this->configurationHelper->getMaxSize($this->getType());
    }
}
