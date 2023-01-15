<?php

namespace app\admin\model;

use think\Model;


class RechargeRecord extends Model
{

    // 表名
    protected $name = 'recharge_record';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'currency_name',
    ];

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3'), '4' => __('Status 4'), '5' => __('Status 5')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCurrencyNameAttr()
    {
        return $this->currency->name ?? __('未知');
    }

    public function currencyProtocol()
    {
        return $this->belongsTo('CurrencyProtocol','currency_protocol_id','id','LEFT')->setEagerlyType(0);
    }
    public function currency()
    {
        return $this->belongsTo('Currency', 'currency_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function users()
    {
        return $this->belongsTo('Users', 'uid', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
