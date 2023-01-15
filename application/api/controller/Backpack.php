<?php


namespace app\api\controller;


use app\admin\model\ChipUsers;
use app\admin\model\Goods;
use app\admin\model\GoodsRank;
use app\admin\model\GoodsUsers;
use comservice\Response;
use think\Db;
use think\Request;

class Backpack extends BaseController
{

    /**
     * 背包
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
    public function index($level = -1, $is_chip = -1,$status = -1, $page = 1, $pagesize = 10)
    {
        $goodsUser = new GoodsUsers();
        $where = [];
        if ($is_chip != -1) {
            $where['gu.part'] = $is_chip==0  ? 0 : ['gt',0];
        }
        if ($level != -1) {
            $where['gu.level'] = $level;
        }
        $where['gu.uid'] = $this->uid;
        if($status==-1){
            $where['gu.status'] = 1;// array('lt',4);
        }else{
            $where['gu.status'] = $status;// array('lt',4);
        }
        $count = $goodsUser->alias('gu')
            ->where($where)
            ->count();
        if ($count <= 0) return json(Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]));
        $field = 'gu.id,gu.is_chip,gu.level,gu.part,gu.price,gr.image,g.image as chip_image,gu.status';
        $data = $goodsUser->alias('gu')
            ->join('goods g', 'gu.goods_id = g.id', 'LEFT')
            ->join('goods_rank gr', 'gu.level = gr.id', 'LEFT')
            ->where($where)
            ->field($field)
            ->order(['gu.id desc'])
            ->page($page, $pagesize)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image', 'chip_image']);
            foreach ($data as &$vo) {
                $vo['image'] = $vo['part'] !=0 ? $vo['chip_image'] : $vo['image'];
                $vo['is_chip'] = $vo['part'] !=0 ? 1 : 0;
                unset($vo['chip_image']);
            }
        }
        return json(Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]));
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
    public function detail($id)
    {
        $goodsUser = new GoodsUsers();
        $where = [];
        $where['gu.id'] = $id;
        $where['gu.uid'] = $this->uid;
        $field = 'gu.id,gu.is_chip,gu.part,gu.level,gu.price,gr.image,g.image as chip_image,gu.status,g.duration,g.sell_type,g.specify_uid,gu.create_time';
        $data = $goodsUser->alias('gu')
            ->join('goods g', 'gu.id = g.goods_user_id', 'LEFT')
            ->join('goods_rank gr', 'gu.level = gr.id', 'LEFT')
            ->where($where)
            ->field($field)
            ->order(['gu.id desc'])
            ->find();
        if ($data) {
            $data = $data->toArray();
            $data = addWebSiteUrl($data, ['image', 'chip_image']);
        }
        $data['is_chip'] = $data['part'] !=0 ? 1 : 0;
        return json(Response::success('success', $data));
    }

    /**
     * 出售
     * @param $id
     * @param int $sell_type
     * @param $price
     * @param int $specify_ui
     * @param int $time
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sell($id = 0, $sell_type = 1, $price = 0, $specify_uid = 0, $time = 24)
    {
        if ($id == 0 || $price <= 0) {
            Db::rollback();
            return json(Response::invalidParam());
        }
        Db::startTrans();
        $goodsUser = GoodsUsers::where(['uid' => $this->uid, 'id' => $id])->find();
        if (!$goodsUser) {
            Db::rollback();
            return json(Response::fail('权限不足，不可出售'));
        }
        if ($goodsUser->status != 1) {
            Db::rollback();
            return json(Response::fail('当前状态不可出售'));
        }
        if ($goodsUser['part'] != 0) {
            $image = Goods::where(['level' => $goodsUser['level'], 'part' => $goodsUser['part']])->value('image');
        } else {
            $image = GoodsRank::where(['id' => $goodsUser['level']])->value('image');
        }
        $add = [];
        $add['goods_user_id'] = $id;
        $add['image'] = $image;
        $add['sell_type'] = $sell_type;
        $add['specify_uid'] = $sell_type == 3 ? $specify_uid : 0;
        $add['duration'] = $time;
        $add['price'] = $price;
        $add['is_chip'] = $goodsUser->is_chip;
        $add['part'] = $goodsUser->part;
        $add['is_del'] = 0;
        $add['is_show'] = 1;
        $add['stock'] = 1;
        $add['surplus'] = 1;
        $add['sales'] = 1;
        $add['is_manghe'] = 0; //非盲盒
        $add['is_can_buy'] = 1; //可以参与购买
        $add['start_time'] = date('Y-m-d H:i:s');
        $add['end_time'] = $time == 24 ? date('Y-m-d H:i:s', strtotime('+1 day')) : date('Y-m-d H:i:s', strtotime('+2 day'));
        Goods::create($add);
        $goodsUser['status'] = 2;
        $goodsUser->save();
        Db::commit();
        $data = $goodsUser->toArray();
        $data['image'] = $image;
        $data = addWebSiteUrl($data, ['image']);
        return json(Response::success('出售成功',$data));
    }

    /**
     * 碎片分组数
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function levelNum()
    {
        $rank = GoodsRank::order('id', 'asc')->select();
        foreach ($rank as &$vo) {
            $vo->number = GoodsUsers::alias('gu')
                ->join('goods g', 'gu.goods_id = g.id', 'LEFT')
                ->where('g.level', $vo['id'])
                ->where('gu.uid', $this->uid)
                ->where('gu.status', 1)
                ->count();
        }
        $data = collection($rank)->toArray();
        $data = addWebSiteUrl($data, ['image']);
        return json(Response::success('获取成功', $data));
    }

    public function chipNum()
    {
        $rank = GoodsRank::order('id', 'asc')->select();
        $goodsUser = new GoodsUsers();
        $data = collection($rank)->toArray();
        foreach ($data as &$vo) {
            $arr = [];
            $vo['part'] = [];
            for ($i = 1; $i < 5; $i++) {
                $num = $goodsUser->alias('gu')
                    ->join('goods g', 'gu.goods_id = g.id', 'LEFT')
                    ->where('gu.level', $vo['id'])
                    ->where('gu.uid', $this->uid)
                    //->where('gu.is_chip', 1)
                    ->where('gu.part', $i)
                    ->where('gu.status', 1)
                    ->count();
                $arr[] = $num;
                $chip = [];
                $chip['num'] = $num;
                $chip['image'] = Goods::where('part', $i)->where('level', $vo['id'])->value('image');

                $chip = addWebSiteUrl($chip, ['image']);
                $vo['part'][] = $chip;
            }
            $vo['max_num'] = min($arr);
        }
        $data = addWebSiteUrl($data, ['image']);
        return json(Response::success('获取成功', $data));
    }

    /**
     * 合成
     * @param $level
     * @param $number
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function synthesis($level, $number)
    {
        $goodsUser = new GoodsUsers();
        $arr = [];
        for ($i = 1; $i < 5; $i++) {
            $num = $goodsUser->alias('gu')
                //->join('goods g', 'gu.goods_id = g.id', 'LEFT')
                ->where('gu.level', $level)
                ->where('gu.uid', $this->uid)
                //->where('gu.is_chip', 1)
                ->where('gu.part', $i)
                ->where('gu.status', 1)
                ->count();
            $arr[] = $num;
        }
        $goods = Goods::where('level', $level)->where('part', 0)->where('goods_user_id', 0)->find();
        if ($number > min($arr)) {
            return json(Response::fail('碎片不足'.min($arr)));
        }
        Db::startTrans();
        for ($i = 1; $i < 5; $i++) {
            $goodsUser->where('status', 1)->where('uid', $this->uid)->where('level', $level)->where('part', $i)->limit($number)->update(['status' => 4]);
        }
        $adds = [];
        for ($i = 0; $i < $number; $i++) {
            $add = [];
            $goods_user_number = $goodsUser->where(['level' => $level])->whereNotNull('number')->order('id', 'desc')->value('number');
            if ($goods_user_number) {
                $goods_user_number = str_pad($goods_user_number + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $goods_user_number = '000001';
            }
            $goods_number = uniqueNum();
            $usersGoods = [];
            $usersGoods['uid'] = $this->uid;
            $usersGoods['goods_id'] = $goods['id'];
            $usersGoods['goods_number'] = $goods_number;
            $usersGoods['price'] = $goods['price'];
            $usersGoods['create_time'] = date('Y-m-d H:i:s');
            $usersGoods['status'] = 1; //待出售
            $usersGoods['part'] = 0;
            $usersGoods['level'] = $level;
            $usersGoods['number'] = $goods_user_number;
            $usersGoods['source'] = 2;
            $adds[] = $usersGoods;
        }
        $goodsUser->insertAll($adds);
        Db::commit();
        $goods = collection($goods->toArray());
        $goods = addWebSiteUrl($goods, ['image']);
        return json(Response::success('合成成功',['data'=>$goods]));
    }

    /**
     * 成品
     * @param string $search
     * @param int $page
     * @param int $pagesize
     * @return array
     * @throws \think\Exception
     */
    public function finishedProduct($search = '', $page = 1, $pagesize = 10)
    {
        $goodsUser = new GoodsUsers();
        $where['gu.uid'] = $this->uid;
        $where['gu.status'] = ['lt', 3];
        if (!empty($search)) $where['g.name|g.label'] = ['like', '%' . $search . '%'];
        $count = $goodsUser->alias('gu')
            ->join('goods g', 'gu.goods_id = g.id', 'LEFT')
            ->where($where)
            ->count();
        if ($count <= 0) return json(Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]));
        $field = 'gu.id,g.id as goods_id,g.name,g.level,g.price,gr.image,gu.status';
        $data = $goodsUser->alias('gu')
            ->join('goods g', 'gu.goods_id = g.id', 'LEFT')
            ->join('goods_rank gr', 'g.level = gr.id', 'LEFT')
            ->where($where)
            ->field($field)
            ->order(['gu.id desc'])
            ->page($page, $pagesize)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image']);
        }
        return json(Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]));
    }

    /**
     * 碎片
     * @param string $search
     * @param int $page
     * @param int $pagesize
     * @return array
     * @throws \think\Exception
     */
    public function chip($search = '', $page = 1, $pagesize = 10)
    {
        $goodsUser = new ChipUsers();
        $where['gu.user_id'] = $this->uid;
        if (!empty($search)) $where['g.name|g.label'] = ['like', '%' . $search . '%'];
        $count = $goodsUser->alias('gu')
            ->join('goods g', 'gu.goods_id = g.id', 'LEFT')
            ->where($where)
            ->count();
        if ($count <= 0) return json(Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]));
        $field = 'gu.id,g.id as goods_id,g.name,g.level,g.price,gu.part,g.image,gu.total as number';
        $data = $goodsUser->alias('gu')
            ->join('goods g', 'gu.goods_id = g.id', 'LEFT')
            ->where($where)
            ->field($field)
            ->order(['gu.id desc'])
            ->page($page, $pagesize)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image']);
        }
        return json(Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]));
    }


}
