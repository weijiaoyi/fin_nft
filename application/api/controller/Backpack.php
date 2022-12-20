<?php


namespace app\api\controller;


use app\admin\model\ChipUsers;
use app\admin\model\GoodsRank;
use app\admin\model\GoodsUsers;
use comservice\Response;
use think\Request;

class Backpack extends BaseController
{

    public function levelNum()
    {
        $rank = GoodsRank::order('id','asc')->select();
        $goodsUser =  new GoodsUsers();
        foreach($rank as &$vo){
            $vo->number = $goodsUser->alias('gu')
                ->join('goods g', 'gu.goods_id = g.id','LEFT')
                ->where('g.level',$vo['id'])
                ->where('gu.uid',$this->uid)
                ->where('gu.status','lt',3)
                ->count();
        }
        $data = collection($rank)->toArray();
        $data = addWebSiteUrl($data, ['image']);
        return  json( Response::success('获取成功', $data));
    }

    /**
     * 成品
     * @param string $search
     * @param int $page
     * @param int $pagesize
     * @return array
     * @throws \think\Exception
     */
    public function finishedProduct($search='',$page=1,$pagesize=10){
        $goodsUser =  new GoodsUsers();
        $where['gu.uid'] = $this->uid;
        $where['gu.status'] = ['lt',3];
        if (!empty($search)) $where['g.name|g.label'] = ['like', '%' . $search . '%'];
        $count = $goodsUser->alias('gu')
            ->join('goods g', 'gu.goods_id = g.id','LEFT')
            ->where($where)
            ->count();
        if ($count <= 0)  return json( Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]));
        $field = 'gu.id,g.id as goods_id,g.name,g.level,g.price,gr.image,gu.status';
        $data = $goodsUser->alias('gu')
            ->join('goods g', 'gu.goods_id = g.id','LEFT')
            ->join('goods_rank gr', 'g.level = gr.id','LEFT')
            ->where($where)
            ->field($field)
            ->order(['gu.id desc'])
            ->page($page, $pagesize)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image']);
        }
        return json( Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]));
    }

    /**
     * 碎片
     * @param string $search
     * @param int $page
     * @param int $pagesize
     * @return array
     * @throws \think\Exception
     */
    public function chip($search='',$page=1,$pagesize=10){
        $goodsUser =  new ChipUsers();
        $where['gu.user_id'] = $this->uid;
        if (!empty($search)) $where['g.name|g.label'] = ['like', '%' . $search . '%'];
        $count = $goodsUser->alias('gu')
            ->join('goods g', 'gu.goods_id = g.id','LEFT')
            ->where($where)
            ->count();
        if ($count <= 0)  return json( Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]));
        $field = 'gu.id,g.id as goods_id,g.name,g.level,g.price,gu.part,g.image,gu.total as number';
        $data = $goodsUser->alias('gu')
            ->join('goods g', 'gu.goods_id = g.id','LEFT')
            ->where($where)
            ->field($field)
            ->order(['gu.id desc'])
            ->page($page, $pagesize)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image']);
        }
        return json( Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]));
    }

    public function synthesis($level,$number){
        $goodsUser =  new ChipUsers();
        $where['gu.user_id'] = $this->uid;
        $field = 'gu.id,g.id as goods_id,g.name,g.level,g.price,gu.part,g.image,gu.total as number';
        $data = $goodsUser->alias('gu')
            ->join('goods g', 'gu.goods_id = g.id','LEFT')
            ->where($where)
            ->field($field)
            ->order(['gu.id desc'])
            ->select();
    }
}
