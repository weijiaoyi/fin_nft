<?php


namespace app\api\controller;

use app\admin\model\GoodsBidding;
use app\admin\model\GoodsUsers;
use app\admin\model\Orders;
use comservice\Response;
use think\Request;
use app\admin\model\BuyModel;


class Buy extends BaseController
{

    /**
     * 发布收购
     * @param Request $request
     * @return \think\response\Json
     */
    public function  publish(Request $request){
        $describe = $request->post('describe','');
        $contact = $request->post('contact','');
        if(!empty($contact)){
            $contact = json_decode($contact,true);
        }
        $level = $request->post('level',0);
        $part = $request->post('part',0);
        if(empty($describe) || $level==0 ){
            return json(Response::invalidParam());
        }
        return json(BuyModel::publish($describe,$level,$part,$contact,$this->uid));
    }

    /**
     * 收购列表
     * @param int $page
     * @param int $pagesize
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function buyList($status=1,$page=1,$pagesize=10){
        return json(BuyModel::buyList($status,$page,$pagesize));
    }

    /**
     * 我的收购列表
     * @param int $page
     * @param int $pagesize
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myBuyList($page=1,$pagesize=10){
        return json(BuyModel::myBuyList($this->uid,$page,$pagesize));
    }

    /**
     * 收购详情
     * @param int $id
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function details($id=0){
        return json(BuyModel::details($id,$this->uid));
    }

    /**
     * 删除
     * @param $id
     * @return \think\response\Json
     */
    public function del($id){
        return json(BuyModel::del($id,$this->uid));
    }

    /**
     * 竞价
     * @param $id
     * @return \think\response\Json
     */
    public function bidding($id,$price){
        return json(GoodsBidding::add($id,$price,$this->uid));
    }

    /**
     * nft竞价历史
     * @param $id
     * @param int $page
     * @param int $pagesize
     * @return \think\response\Json
     */
    public function biddingList($id,$page=1,$pagesize=10){
        return json(GoodsBidding::biddingList($id,$page=1,$pagesize=10));
    }

    /**
     * 下架
     * @param $id
     * @return \think\response\Json
     */
    public function offShelve($id){
        return json(GoodsUsers::offShelve($id,$this->uid));
    }

    /**
     * 我的 出售列表
     * @param int $level
     * @param int $is_chip
     * @param int $page
     * @param int $pagesize
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function mySale($status=-1,$page=1,$pagesize=10){
        $goodsUser =  new GoodsUsers();
        $where=[];
        if($status!=-1){
            $where['gu.status'] = $status==1 ? 2 : 3;
        }
        $where['gu.uid'] = $this->uid;
        $count = $goodsUser->alias('gu')
            ->where($where)
            ->count();
        if ($count <= 0)  return json( Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]));
        $field = 'gu.id,gu.is_chip,gu.level,gu.part,g.price,gr.image,g.image as chip_image,gu.status';
        $data = $goodsUser->alias('gu')
            ->join('goods g', 'gu.id = g.goods_user_id','LEFT')
            ->join('goods_rank gr', 'g.level = gr.id','LEFT')
            ->where($where)
            ->field($field)
            ->order(['gu.id desc'])
            ->page($page, $pagesize)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image','chip_image']);
            foreach ($data as &$vo){
                $vo['image'] = $vo['is_chip']==1 ?  $vo['chip_image'] :  $vo['image'];
                unset( $vo['chip_image']);
            }
        }
        return json( Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]));
    }
    /**
     * 详情
     * @param $id
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saleDetails($id){
        $goodsUser =  new GoodsUsers();
        $where=[];
        $where['gu.id'] = $id;
        $where['gu.uid'] = $this->uid;
        $field = 'gu.id,gu.is_chip,gu.part,gu.level,g.price,g.sell_type,g.specify_uid,gr.image,g.image as chip_image,gu.status,g.duration,g.sell_type,g.specify_uid,g.id as goods_id';
        $data = $goodsUser->alias('gu')
            ->join('goods g', 'gu.id = g.goods_user_id','LEFT')
            ->join('goods_rank gr', 'g.level = gr.id','LEFT')
            ->where($where)
            ->field($field)
            ->order(['gu.id desc'])
            ->find();
        if ($data) {
            $data = $data->toArray();
            $data = addWebSiteUrl($data, ['image','chip_image']);
        }
        $data['bidding_price'] = 0;
        if($data['sell_type']==2){
           // $biddingPrice = Orders::where('goods_id',$data['goods_id'])->order('price','desc')->find();
           // if($biddingPrice){
           //     $data['bidding_price'] = $biddingPrice['price'];
          //  }
        }
        return json( Response::success('success',$data));
    }


}
