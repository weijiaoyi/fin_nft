<?php

namespace app\admin\model;

use think\Model;

class CurrencyProtocol extends Model
{
    // 追加属性
    protected $append = [

    ];

    public function getChainProtocolNameAttr()
    {
        return $this->chainProtocol->name ?? __('未知');
    }
    public function getCurrencyNameAttr()
    {
        return $this->currency->name ?? __('未知');
    }

    public function chainProtocol()
    {
        return $this->belongsTo('ChainProtocol','chain_protocol_id','id','LEFT')->setEagerlyType(0);
    }
    public function currency()
    {
        return $this->belongsTo('Currency','currency_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
