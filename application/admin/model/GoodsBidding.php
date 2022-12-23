<?php

namespace app\admin\model;


use comservice\Response;
use logicmodel\AccountLogic;
use think\Db;
use think\Model;


class GoodsBidding extends Model
{

    // 表名
    protected $name = 'goods_bidding';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;


    public function user()
    {
        return $this->belongsTo('Users', 'user_id')->setEagerlyType(0);
    }

    public function goods()
    {
        return $this->belongsTo('Goods', 'goods_id')->setEagerlyType(0);
    }


    public static function add($id,$price,$userInfo)
    {
        Db::startTrans();
        $goods = Goods::find($id);
        if(!$goods){
            return Response::fail('NFT不存在');
        }
        if($goods->sell_type!=2){
            return Response::fail('当前NFT不是竞价产品');
        }
        if($goods->price>$price){
            return Response::fail('竞价价格低于当前价');
        }
        $gBidding = self::where('goods_id',$id)->order('price','desc')->find();
        if($gBidding){
            if($price<$gBidding->price+10){
                return Response::fail('竞价价格低于当前价');
            }
        }
        $accountLogic = new AccountLogic();
        $result = $accountLogic->subAccount($userInfo['id'], 1, $price, 10, '竞价');
        if (!$result) {
            Db::rollback();
            return Response::fail('余额不足');
        }
        self::where('goods_id',$id)->save(['status'=>2]);
        $data = [];
        $data['price'] = $price;
        $data['user_id'] = $userInfo['id'];
        $data['nick_name'] = $userInfo['nick_name'];
        self::create($data);
        Db::commit();
        return Response::success('竞价成功');
    }


    public static function biddingList($id, int $page, int $pagesize)
    {
        $where['goods_id'] = $id;
        $count = self::where($where)->count();
        if ($count <= 0){
            return Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
        }
        $data = self::where($where)
            ->order(['create_time desc'])
            ->page($page, $pagesize)
            ->select();
        return Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]);
    }


}
