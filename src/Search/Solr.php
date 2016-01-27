<?php
/**
 * @Created by 上哪学.
 * @author jeff zhang jeff.zhang@51xuetang.com
 * @Date: 15/5/13
 *
 * $Id: $
 */

namespace Kerisy\Search;


use Kerisy\Core\Object;

class Solr extends Object
{
    public $options = [];

    private $solr;

    public function connect()
    {
        if (!$this->solr) {
            $this->solr = new \SolrClient($this->options);
            $this->solr->ping();
        }
        return $this->solr;
    }

} 