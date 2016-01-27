<?php
/**
 * 葡萄科技.
 * @copyright Copyright (c) 2015 Putao Inc.
 * @license http://www.putao.com/
 * @author Jeff Zhang <zhangjf@putao.com>
 * @Date: 16/1/27 18:50
 *
 * $Id $
 */

namespace Kerisy\Search;


use Elasticsearch\ClientBuilder;

class Elasticsearch
{
    public $hosts;

    /**
     * Elasticsearch Client
     * @return \Elasticsearch\Client
     */
    public function getClient()
    {
        return ClientBuilder::create()->setHosts($this->hosts)->build();
    }
}