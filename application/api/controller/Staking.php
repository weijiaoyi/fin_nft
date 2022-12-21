<?php


namespace app\api\controller;


use app\admin\model\MiningPool;
use app\admin\model\MiningPoolLevel;
use app\admin\model\Staking as StakingModel;
use app\admin\model\GoodsRank;
use app\admin\model\GoodsUsers;
use comservice\Response;
use think\Db;
use think\Request;

class Staking extends BaseController
{

    public function staking($mining_pool_id=0,$number=1,$level=0)
    {
        if($mining_pool_id==0 || $number==0 ||  $level==0){
            return json( Response::fail('参数错误'));
        }
        $stak =  new Staking();
        $goodsUser =  new GoodsUsers();
        $user_number = $goodsUser->alias('gu')
            ->join('goods g', 'gu.goods_id = g.id','LEFT')
            ->where('g.level',$level)
            ->where('gu.uid',$this->uid)
            ->where('gu.status',1)
            ->count();
        if($number>$user_number){
            return json( Response::fail('质押数量超出背包数量'));
        }
        $mpl = MiningPoolLevel::where('level',$level)->where('mining_pool_id',$mining_pool_id)->find();
        $efficiency = mt_rand($mpl['efficiency_start'],$mpl['efficiency_end']);
        Db::startTrans();
        $add=[];
        $add['mining_pool_id'] = $mining_pool_id;
        $add['level'] = $level;
        $add['user_id'] = $this->uid;
        $add['number'] = $number;
        $add['daily_interest_rate'] = $efficiency;
        $add['daily_income'] = $efficiency;
        $add['status'] = 1;
        $add['create_time'] = date('Y-m-d H:i:s');
        $add['start_at'] = date("Y-m-d",strtotime("+1 day"));
        $stak->insert($add);
        $goodsUser->where('uid',$this->uid)->where('status',1)->order('id','asc')->limit($number)->save(['status'=>5]);
        Db::commit();
        return json(Response::success('质押成功'));
    }


    public function log($page=1,$pagesize=10){
        $miningPool =  new StakingModel();
        $count = $miningPool->where('user_id',$this->uid)->count();
        if ($count <= 0) return json(Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]));
        $data = $miningPool->alias('s')
            ->join('goods_rank gr', 's.level = gr.id','LEFT')
            ->where('user_id',$this->uid)
            ->field('s.*,gr.image')
            ->order(['s.id desc'])
            ->page($page, $pagesize)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image']);
        }
        return json( Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]));
    }

    public function miningPool($page=1,$pagesize=10){
        $miningPool =  new MiningPool();
        $count = $miningPool->count();
        if ($count <= 0) return json(Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]));
        $data = $miningPool
            ->order(['id asc'])
            ->page($page, $pagesize)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image']);
            foreach($data as &$vo){
                $vo['income'] = 0;
            }
        }
        return json(Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]));
    }

    public function miningPoolDetails($id){
        $miningPool =  new MiningPool();
        $data = $miningPool->find($id);
        if ($data) {
            $data = $data->toArray();
            $data = addWebSiteUrl($data, ['image']);
            $data['level'] = MiningPoolLevel::where('mining_pool_id',$data['id'])->select();
        }
        return json(Response::success('success', $data));
    }

}
