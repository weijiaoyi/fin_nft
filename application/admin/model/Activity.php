<?php

namespace app\admin\model;

use comservice\Response;
use think\Model;


class Activity extends Model
{

    // 表名
    protected $name = 'activity';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;
    protected $append = [
        'status_text',
    ];

    public static function activityList()
    {
        $where['a.is_del'] = 0;
        //$where['a.end_time'] = ['gt',date('Y-m-d H:i:s')];
        $field = 'a.*,gr.image as rank_image,gr.name goods_rank,g.part';
        $data = self::alias('a')
            ->join('goods g', 'a.goods_id = g.id','LEFT')
            ->join('goods_rank gr', 'g.level = gr.id','LEFT')
            ->where($where)
            ->field($field)
            ->order(['a.start_time asc'])
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['rank_image']);
        }
        foreach ($data as &$vo){
            $vo['remaining_time'] = strtotime($vo['end_time']) - time();
            $vo['remaining_time'] = $vo['remaining_time']>0 ? $vo['remaining_time'] : 0;
        }
        return Response::success('success', $data);
    }

    public static function details(int $id)
    {
        $where['a.id'] = $id;
        $field = 'a.*,gr.image as rank_image,gr.name goods_rank,g.part';
        $data = self::alias('a')
            ->join('goods g', 'a.goods_id = g.id','LEFT')
            ->join('goods_rank gr', 'g.level = gr.id','LEFT')
            ->where($where)
            ->field($field)
            ->find()->toArray();
        if ($data) {
           // $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['rank_image']);
        }
        $data['remaining_time'] = strtotime($data['end_time']) - time();
        $data['remaining_time'] = $data['remaining_time']>0 ? $data['remaining_time'] : 0;
        return Response::success('success', $data);
    }

    public function getStatusList()
    {
        return ['未上架','进行中','已结束'];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

}
