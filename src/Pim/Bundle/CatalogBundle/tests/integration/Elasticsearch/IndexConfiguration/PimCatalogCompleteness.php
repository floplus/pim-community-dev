<?php

namespace Pim\Bundle\CatalogBundle\tests\integration\Elasticsearch\IndexConfiguration;

class PimCatalogCompletenessIntegration extends AbstractPimCatalogTestCase
{
    public function testISelectTheCompleteProductModelEcommerceEnUS()
    {
        $query = [
            "aggs" => [
                "group_by_id" => [
                    "terms" => [
                        "field" => "at_least_complete.ecommerce.en_US",
                        "min_doc_count" => 10,
                    ],
                ],
            ]
        ];
        
//        $query = [
//            'query'=> [
//                'bool' => [
//                    'must' => [
//                        'script' => [
//                            'script' => [
//                                'source'=> 'doc["at_least_complete.ecommerce.en_US"].value > 0',
//                             ]
//                        ]
//                    ]
//                ]
//            ]
//        ];

        $productsFound = $this->getSearchQueryResults($query);

        $this->assertProducts($productsFound, ['product_1']);
    }

    public function testISelectTheCompleteProductModelMobileFrFr()
    {
        $query = [
            'query'=> [
                'bool' => [
                    'must' => [
                        'script' => [
                            'script' => [
                                'source'=> 'doc["at_least_complete.mobile.fr_FR"].value > 0',
                             ]
                        ]
                    ]
                ]
            ]
        ];

        $productsFound = $this->getSearchQueryResults($query);

        $this->assertProducts($productsFound, ['product_1', 'product_2']);
    }

    public function testISelectTheincompleteProductModelOnMobileAnEnUS()
    {
        $query = [
            'query'=> [
                'bool' => [
                    'must' => [
                        'script' => [
                            'script' => [
                                'source'=> 'doc["at_least_incomplete.mobile.en_US"].value > 0',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $productsFound = $this->getSearchQueryResults($query);

        $this->assertProducts($productsFound, ['product_2']);
    }

    public function testISelectTheincompleteProductModelOnEcommerceAnEnUS()
    {
        $query = [
            'query'=> [
                'bool' => [
                    'must' => [
                        'script' => [
                            'script' => [
                                'source'=> 'doc["at_least_incomplete.ecommerce.en_US"].value > 0',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $productsFound = $this->getSearchQueryResults($query);

        $this->assertProducts($productsFound, ['product_1']);
    }

    /**
     * {@inheritdoc}
     */
    protected function addProducts()
    {
        $products = [
            [
                'at_least_complete' => [
                    'ecommerce' => [
                        'en_US' => [
                            40,
                        ],
                        'fr_FR' => [
                            40,
                            33,
                        ],
                    ],
                ],
                'at_least_incomplete' => [
                    'ecommerce' => [
                        'en_US' => [
                            64,
                        ],
                    ],
                ],
                'identifier' => 'product_1',
                'values' => [],
            ],
            [
                'at_least_complete' => [
                    'mobile' => [
                        'en_US' => [
                            40,
                        ],
                        'fr_FR' => [
                            40,
                            33,
                        ],
                    ],
                ],
                'at_least_incomplete' => [
                    'mobile' => [
                        'en_US' => [
                            64,
                        ],
                    ],
                ],
                'identifier' => 'product_2',
                'values' => [],
            ],
        ];

        /**
         * Est ce qu'on doit avoir une structure fixe?
         * Pourquoi les id pour avoir une data à sélectionner?
         *
         *
         */
        $this->indexProductDocuments($products);
    }
}
