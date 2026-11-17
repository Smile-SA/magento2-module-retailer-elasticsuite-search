<?php

declare(strict_types=1);

namespace Smile\ElasticsuiteRetailer\Model\ResourceModel\Fulltext;

use LogicException;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Helper;
use Magento\Framework\Api\Search\Document;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Validator\UniversalFactory;
use Magento\Search\Model\SearchEngine;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Smile\ElasticsuiteCore\Search\Adapter\Elasticsuite\Response\QueryResponse;
use Smile\ElasticsuiteCore\Search\Request\Builder;
use Smile\ElasticsuiteCore\Search\RequestInterface;
use Smile\Retailer\Model\Retailer;
use Smile\Seller\Model\ResourceModel\Seller as SellerResource;

/**
 * Search engine product collection.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Smile\Retailer\Model\ResourceModel\Retailer\Collection
{
    private QueryResponse $queryResponse;

    /** @var string|string[]|null */
    private string|array|null $queryText = null;

    private array $filters = [];

    private array $facets = [];

    /**
     * Collection constructor.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList) Parent construct already has 11 arguments.
     * @param \Magento\Framework\Data\Collection\EntityFactory             $entityFactory     Entity Factory
     * @param \Psr\Log\LoggerInterface                                     $logger            Logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy     Fetch Strategy
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager      Event Manager
     * @param \Magento\Eav\Model\Config                                    $eavConfig         EAV Config
     * @param \Magento\Framework\App\ResourceConnection                    $resource          Resource Connection
     * @param \Magento\Eav\Model\EntityFactory                             $eavEntityFactory  EAV Entity Factory
     * @param \Magento\Eav\Model\ResourceModel\Helper                      $resourceHelper    Resource Helper
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager      The Store Manager
     * @param \Magento\Framework\Validator\UniversalFactory                $universalFactory  Universal Factory
     * @param \Smile\ElasticsuiteCore\Search\Request\Builder               $requestBuilder    Search request
     *                                                                                        builder.
     * @param \Magento\Search\Model\SearchEngine                           $searchEngine      Search engine
     * @param string                                                       $searchRequestName Search request
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null          $connection        Database Connection
     */
    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Config $eavConfig,
        ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        Helper $resourceHelper,
        StoreManagerInterface $storeManager,
        UniversalFactory $universalFactory,
        private Builder $requestBuilder,
        private SearchEngine $searchEngine,
        private string $searchRequestName = 'retailer_search_container',
        ?AdapterInterface $connection = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $storeManager,
            $universalFactory,
            $connection
        );
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        if ($this->_totalRecords === null) {
            $this->loadRetailersCounts();
        }

        return $this->_totalRecords;
    }

    /**
     * @inheritdoc
     */
    public function setOrder($attribute, $dir = self::SORT_ORDER_DESC)
    {
        throw new LogicException("Sorting on multiple stores is not allowed in search engine collections.");
    }

    /**
     * Add filter by store
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) Method is inherited
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) Method is inherited
     * @param int|string|array|Store $store     Store
     * @param bool                   $withAdmin With admin
     * @return $this
     */
    public function addStoreFilter(mixed $store, bool $withAdmin = true): self
    {
        if (is_object($store)) {
            $store = $store->getId();
        }

        if (is_array($store)) {
            throw new LogicException("Filtering on multiple stores is not allowed in search engine collections.");
        }

        $this->setStoreId($store);

        return $this;
    }

    /**
     * Add search query filter
     *
     * @param string|string[] $query Search query text (or list of terms).
     * @return $this
     */
    public function addSearchFilter(string|array $query): self
    {
        $this->queryText = $query;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        $this->filters[$field] = $condition;

        return $this;
    }

    /**
     * Redefined to delete filter attribute_set_id
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Retailer::class, SellerResource::class);
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _renderFiltersBefore()
    {
        $searchRequest = $this->prepareRequest();

        /** @var QueryResponse $queryResponse */
        $queryResponse = $this->searchEngine->search($searchRequest);
        $this->queryResponse = $queryResponse;

        // Update the product count.
        $this->_totalRecords = $this->queryResponse->count();

        // Filter search results. The pagination has to be resetted since it is managed by the engine itself.
        $docIds = array_map(
            function (Document $doc) {
                return (int) $doc->getId();
            },
            iterator_to_array($this->queryResponse->getIterator())
        );

        if (empty($docIds)) {
            $docIds[] = 0;
        }

        $this->getSelect()->where('retailer_id IN (?)', ['in' => $docIds]);
        $this->_pageSize = false;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _afterLoad()
    {
        // Resort items according the search response.
        $orginalItems = $this->_items;
        $this->_items = [];

        foreach ($this->queryResponse->getIterator() as $document) {
            $documentId = $document->getId();
            if (isset($orginalItems[$documentId])) {
                $this->_items[$documentId] = $orginalItems[$documentId];
                $this->_items[$documentId]->setStoreId($this->getStoreId());
            }
        }

        return parent::_afterLoad();
    }

    /**
     * Prepare the search request before it will be executed.
     */
    private function prepareRequest(): RequestInterface
    {
        // Store id and request name.
        $storeId           = $this->getStoreId();
        $searchRequestName = $this->searchRequestName;

        // Pagination params.
        $size = $this->_pageSize ? $this->_pageSize : 20;
        $from = $size * (max(1, $this->_curPage) - 1);

        // Query text.
        $queryText = $this->queryText;

        // Setup sort orders.
        $sortOrders = $this->prepareSortOrders();

        $searchRequest = $this->requestBuilder->create(
            $storeId,
            $searchRequestName,
            $from,
            $size,
            $queryText,
            $sortOrders,
            $this->filters,
            $this->facets
        );

        return $searchRequest;
    }

    /**
     * Prepare sort orders for the request builder.
     */
    private function prepareSortOrders(): array
    {
        $sortOrders = [];

        foreach ($this->_orders as $attribute => $direction) {
            $sortParams = ['direction' => $direction];
            $sortField = $this->mapFieldName($attribute);
            $sortOrders[$sortField] = $sortParams;
        }

        return $sortOrders;
    }

    /**
     * Convert standard field name to ES fieldname.
     * (eg. category_ids => category.category_id).
     *
     * @param string $fieldName Field name to be mapped.
     */
    private function mapFieldName(string $fieldName): string
    {
        if (isset($this->fieldNameMapping[$fieldName])) {
            $fieldName = $this->fieldNameMapping[$fieldName];
        }

        return $fieldName;
    }

    /**
     * Load retailer collection size.
     */
    private function loadRetailersCounts(): void
    {
        $storeId     = $this->getStoreId();
        $requestName = $this->searchRequestName;

        // Query text.
        $queryText = $this->queryText;

        $searchRequest = $this->requestBuilder->create($storeId, $requestName, 0, 0, $queryText, [], $this->filters);

        $searchResponse = $this->searchEngine->search($searchRequest);

        $this->_totalRecords = $searchResponse->count();
    }
}
