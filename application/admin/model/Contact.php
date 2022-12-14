<?php

namespace app\admin\model;

use think\Model;


class Contact extends Model
{



    // 表名
    protected $name = 'contact';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_show_text'
    ];



    public function getIsShowList()
    {
        return ['0' => '隐藏', '1' => '显示'];
    }


    public function getIsShowTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_show']) ? $data['is_show'] : '');
        $list = $this->getIsShowList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}
