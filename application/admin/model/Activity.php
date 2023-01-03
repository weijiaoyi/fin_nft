<?php

namespace app\admin\model;

use comservice\Response;
use logicmodel\AccountLogic;
use think\Db;
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
        $field = 'a.*,gr.image as rank_image,gr.name goods_rank,g.part,g.image as image_chip,g.is_chip';
        $data = self::alias('a')
            ->join('goods g', 'a.goods_id = g.id','LEFT')
            ->join('goods_rank gr', 'g.level = gr.id','LEFT')
            ->where($where)
            ->field($field)
            ->order(['a.start_time asc'])
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['rank_image','image_chip']);

        }
        foreach ($data as &$vo){
            if($vo['is_chip']==1){
                $vo['rank_image'] = $vo['image_chip'];
            }
            $vo['remaining_time'] = strtotime($vo['end_time']) - time();
            $vo['remaining_time'] = $vo['remaining_time']>0 ? $vo['remaining_time'] : 0;
        }
        return Response::success('success', $data);
    }

    public static function details(int $id)
    {
        $where['a.id'] = $id;
        $field = 'a.*,gr.image as rank_image,gr.name goods_rank,g.part,g.image as image_chip,g.is_chip';
        $data = self::alias('a')
            ->join('goods g', 'a.goods_id = g.id','LEFT')
            ->join('goods_rank gr', 'g.level = gr.id','LEFT')
            ->where($where)
            ->field($field)
            ->find();
        if ($data) {
            $data = $data->toArray();
            $data = addWebSiteUrl($data, ['rank_image','image_chip']);
            if($data['is_chip']==1){
                $data['rank_image'] = $data['image_chip'];
            }
        }
        //查询此盲盒对应的商品列表
        $goodsMangheConfigModel = new GoodsMangheConfig();
        $goodsMangheList = $goodsMangheConfigModel->alias('c')
            ->join('goods g', 'g.id = c.combination_goods_id')
            ->field(['g.image', 'g.price','g.level','g.part','c.*'])
            ->where(['c.goods_id' => $data['goods_id']])
            ->select();
        $data['special'] = [];
        if($goodsMangheList) {
            $goodsMangheList = collection($goodsMangheList)->toArray();
            $goodsMangheList = addWebSiteUrl($goodsMangheList, ['image']);
            foreach ($goodsMangheList as $vo) {
                if ($vo['is_special']==1){
                    $data['special'] = $vo;
                    break;
                }
            }
        }
        $data['manghe_list'] = $goodsMangheList;
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


    public static function lottery()
    {
        $time = date('Y-m-d H:i:s');
        $where['status'] = ['=',1];
        $where['end_time'] = ['<',$time];
        $list = self::where($where)->order('id','asc')->limit(10)->column('id');
        $num = 0;
        $t = time();
        foreach($list as $id){
            Db::startTrans();
            $activity = self::lock(true)->find($id);
            if($activity['status']!=1){
                Db::rollback();
            }
            //设定的中奖人
            if($activity->winne!=0 || $activity->winne_actual!=0){
                $winne = $activity->winne ? $activity->winne : $activity->winne_actual;
                $accountLogic = new AccountLogic();
                $accountLogic->addAccount($winne, 1,  $activity->bonus, '参加活动获得特定奖品', '参加活动获得特定奖品');
            }
            /*   $winRecordDataAll=[];
               if($activity->winne_actual!=0){
                   $part = Goods::where('id',$activity->special_goods_id)->value('part');
                   $winRecordData = [];
                   $winRecordData['user_id'] = $activity->winne_actual;
                   $winRecordData['goods_id'] = $activity->special_goods_id;
                   $winRecordData['part'] = $part;
                   $winRecordData['createtime'] = $t;
                   $winRecordDataAll[]=$winRecordData;
            }
            /* $alist = ActivityUser::where('activity_id',$id)->where('winning_status',1)->where('goods_id','<>',$activity->special_goods_id)->select();
             foreach ($alist as $vo){
                 $part = Goods::where('id',$vo->goods_id)->value('part');
                 $winRecordData = [];
                 $winRecordData['user_id'] = $vo->user_id;
                 $winRecordData['goods_id'] = $vo->goods_id;
                 $winRecordData['part'] = $part;
                 $winRecordData['createtime'] = $t;
                 $winRecordDataAll[]=$winRecordData;
             }
             $chipUsers = new ChipUsers();
             $chipUsers->insertAll($winRecordDataAll);
            */
            $num++;
            $activity->status = 2;
            $activity->save();
            Db::commit();
        }
        return '开奖:'.$num.' 次';
    }

}
