<?php

namespace app\admin\model;

use think\Model;


class MiningPoolLevel extends Model
{





    // 表名
    protected $name = 'mining_pool_level';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function miningpool()
    {
        return $this->belongsTo('MiningPool', 'mining_pool_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function goodsrank()
    {
        return $this->belongsTo('GoodsRank', 'level', 'id', [], 'LEFT')->setEagerlyType(0);
    }

}
