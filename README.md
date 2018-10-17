## ElasticSuite Seller Search

This module is a plugin for [ElasticSuite](https://github.com/Smile-SA/elasticsuite).

It allows to index retailers into the search engine and display them into the autocomplete results, and also on the search result page.

### Requirements

The module requires :

- [ElasticSuite](https://github.com/Smile-SA/elasticsuite) > 2.1.*

### How to use

1. Install the module via Composer :

``` composer require smile/module-retailer-elasticsuite-search ```

2. Enable it

``` bin/magento module:enable Smile_ElasticsuiteRetailer ```

3. Install the module and rebuild the DI cache

``` bin/magento setup:upgrade ```

4. Process a full reindex of the Retailer search index

``` bin/magento index:reindex elasticsuite_seller_fulltext ```

### How to configure

> Stores > Configuration > Elasticsuite > Retailer search settings > Settings
* Max result : Maximum number of results to display in result block.
* Enabled suggest bloc on result page : Yes/No

> Stores > Configuration > Elasticsuite > Retailer search settings > Fields indexed
* Index street : Yes/No
* Index postcode : Yes/No
* Index latitude : Yes/No
* Index longitude : Yes/No
* Index description : Yes/No

> Stores > Configuration > Elasticsuite > Autocomplete > Retailer Autocomplete
* Max size : Maximum number of retailers to display in autocomplete results.

### Fields indexed

Field       | Type    | Choice to index
------------|---------|------------------
retailer_id | Integer | No
street      | String  | Yes
postcode    | String  | Yes
latitude    | String  | Yes
longitude   | String  | Yes
name        | String  | No
is_active   | Boolean | No
description | Text    | Yes

Index example :
```
{
   "_index": "magento2_default_retailer_20181015_124322",
   "_type": "retailer",
   "_id": "1",
   "_score": 1,
   "_source": {
     "retailer_id": "1",
     "street": " 7 Boulevard Louis XIV",
     "postcode": "59800",
     "latitude": "50.629113",
     "longitude": "3.071649",
     "name": "Smile Lille",
     "is_active": "1",
     "description": "shop de Lille"
   }
 }
```

