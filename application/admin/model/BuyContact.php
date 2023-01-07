<?php

namespace app\admin\model;


use think\Model;


class BuyContact extends Model
{

    // 表名
    protected $name = 'buy_connect';

    public function buy()
    {
        return $this->belongsTo('Buy', 'buy_id')->setEagerlyType(0);
    }

    public function contact()
    {
        return $this->belongsTo('Contact', 'contact_id')->setEagerlyType(0);
    }
}
