<?php

namespace app\admin\model;

use comservice\Response;
use think\Db;
use think\Model;


class ActivityUser extends Model
{

    // 表名
    protected $name = 'activity_user';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $append = [
        'status_text',
    ];


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

    public function activity()
    {
        return $this->belongsTo('Activity', 'activity_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    /**
     * 参与活动
     * @param $id
     * @param $uid
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function joinIn($id, $uid)
    {
        Db::startTrans();
        $isJoin = self::where('activity_id',$id)->where('user_id',$uid)->find();
        if($isJoin){
            Db::rollback();
            return Response::fail('已参加该活动');
        }
        $activity = Activity::where('id',$id)->find();
        if(!$activity){
            Db::rollback();
            return Response::fail('活动不存在');
        }
        $t = date('Y-m-d H:i:s');
        if($activity['status']!=1 || $activity['start_time']>$t || $activity['end_time'] < $t){
            Db::rollback();
            return Response::fail('活动异常');
        }
        $activity->participants++;
        $winning_time = 0;
        $winning_status = 0;
        $winne_actual = 0;
        $goods_id = 0;
        $winInfo = self::openBlindBox($activity['goods_id']);
        if(!empty($winInfo) && $winInfo['is_win']==1){
            if($winInfo['is_special']==1){
                $activity->end_time = date('Y-m-d H:i:s',strtotime($activity['end_time'])+10);
            }
            $winning_time = time();
            $winning_status = 1;
            $goods_id = $winInfo['combination_goods_id'];
            $winne_actual = $uid;
        }
        $activity->winne_actual = $winne_actual;
        $activity->save();
        $create_time = date('Y-m-d H:i:s');
        self::insert([
            'user_id'=>$uid,
            'activity_id'=>$id,
            'goods_id'=>$goods_id,
            'create_time'=>$create_time,
            'winning_time'=>$winning_time,
            'winning_status'=>$winning_status,
        ]);
        Db::commit();
        return Response::success('参与成功');
    }


    /**
     * 开盲盒
     * @param $goodsId
     * @param $user_id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function openBlindBox($blindId)
    {
        //查询此盲盒对应的商品列表
        $goodsMangheConfigModel = new GoodsMangheConfig();
        $goodsMangheList = $goodsMangheConfigModel->alias('c')
            ->join('goods g', 'g.id = c.combination_goods_id')
            ->field(['c.*', 'g.name goods_name', 'g.image goods_image', 'g.price'])
            ->where(['goods_id' => $blindId])
            ->select();
        $winInfo = [];
        if ($goodsMangheList) {
            $goodsMangheList = collection($goodsMangheList)->toArray();
            $arrJiangxiang = array_column($goodsMangheList, 'win_rate', 'combination_goods_id');
            $win_id = getWinRand($arrJiangxiang);
            foreach ($goodsMangheList as $item) {
                if ($item['combination_goods_id'] == $win_id) {
                    $winInfo = $item;
                    break;
                }
            }
        }
        return $winInfo;
    }

}
