<?php

namespace App\Search\SearchRule\Product;

use App\Classes\Helpers\StringHelper;
use App\Search\SearchRule\AbstractRule;

class SearchProductFullMatchRule extends AbstractRule
{
    protected $name = 'FullMatch';

    public function buildQueryPayload()
    {
        $original_query = $this->builder->query;
        $normalized_query = StringHelper::normalizeSearchString($original_query);

        $should =
            [
                [
                    'match' => [
                        'article' => [
                            'query' => $normalized_query,
                            'boost' => $this->boosts['article']
                        ]
                    ]
                ],
                [
                    'match' => [
                        'oem' => [
                            'query' => $normalized_query,
                            'boost' => $this->boosts['oem']
                        ]
                    ]
                ],
            ];

        $must = [
            'bool' => [
                'should' => [
                    [
                        'match' => [
                            'oem' => [
                                'query' => $normalized_query
                            ]
                        ]
                    ],
                    [
                        'match' => [
                            'article_text_clean' => [
                                'query' => $normalized_query
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $bool_query = [
            'should' => $should,
            'must' => $must
        ];

        $this->logQuery($bool_query);

        return $bool_query;

    }
}
