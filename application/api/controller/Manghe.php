<?php
/**
 * 盲盒管理
 */

namespace app\api\controller;


use app\admin\model\GoodsMangheConfig;
use app\admin\model\GoodsMangheUsers;
use app\admin\model\MangheAwardRecord;
use app\admin\model\MangheBanner;
use comservice\Response;
use datamodel\GoodsUsers;
use datamodel\Goods;
use think\Db;
use think\Request;

class Manghe extends BaseController
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }


    public function bannerList()
    {
        $data = (new MangheBanner())->where(['is_show' => 1])->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image']);
            return json(Response::success('success', $data));
        }
        return json(Response::success('暂无数据', []));
    }

    /**
     * 获取盲盒
     * @param int $page
     * @param int $pagesize
     * @return array
     */
    public function goodsMangheList($page = 1, $pagesize = 10)
    {
        $goodsModel = new \app\admin\model\Goods();
        $where['g.is_del'] = 0;
        $where['g.is_show'] = 1;
        $where['g.is_manghe'] = 1; //盲盒
        $data = $goodsModel->alias('g')
            ->join('goods_category gc', 'gc.id = g.goods_category_id')
            ->where($where)
            ->field("g.id, g.name, g.content,g.price,g.order,g.image,g.is_manghe,g.start_time,g.end_time,gc.name as goods_category_name")
            ->order(['g.order asc'])
            ->page($page, $pagesize)
            ->select();
        $count = $goodsModel->alias('g')
            ->join('goods_category gc', 'gc.id = g.goods_category_id')
            ->where($where)
            ->field("g.id, g.name, g.content,g.price,g.order,g.is_manghe,g.start_time,g.end_time")
            ->order(['g.order asc'])
            ->count();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image']);
            $time = date('Y-m-d H:i:s');
            foreach ($data as $k => $v) {
                if ($time >= $v['start_time'] && $time <= $v['end_time']) {
                    $data[$k]['status'] = 1;
                } elseif ($v['start_time'] >= $time) {
                    $data[$k]['status'] = 2;
                } else {
                    $data[$k]['status'] = 3;
                }
                $start_time = date('Y-m-d H:i', strtotime($v['start_time']));
                $end_time = date('Y-m-d H:i', strtotime($v['end_time']));
                $data[$k]['start_time'] = $start_time;
                $data[$k]['end_time'] = $end_time;
            }
            $responsData = Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]);
        } else {
            $responsData = Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
        }
        return json($responsData);
    }

    /**
     * 获取盲盒详情
     * @param $id
     * @return array
     */
    public function goodsMangheDetail($id)
    {
        $goodsModel = new \app\admin\model\Goods();
        $where['g.id'] = $id;
        $where['g.is_del'] = 0;
        $data = $goodsModel->alias('g')
            ->join('goods_category gc', 'gc.id = g.goods_category_id')
            ->where($where)
            ->field(['g.*', 'gc.name goods_category_name'])
            ->find();
        if ($data) {
            $data = $data->toArray();
            $data = addWebSiteUrl($data, ['image', 'images', 'company_image']);
//            $data['content'] = content($data['content']);

            $time = date('Y-m-d H:i:s');
            if ($time >= $data['start_time'] && $time <= $data['end_time']) {
                $data['status'] = 1; //正常
            } elseif ($data['start_time'] >= $time) {
                $data['status'] = 2; //未开始
            } else {
                $data['status'] = 3; //已过期
            }
            $start_time = date('Y-m-d H:i', strtotime($data['start_time']));
            $end_time = date('Y-m-d H:i', strtotime($data['end_time']));
            $data['start_time'] = $start_time;
            $data['end_time'] = $end_time;

            //获取当前盲盒 未开启 和总数
            $goodsMangheUsersModel = new GoodsMangheUsers();
            $weikaiTotal = $goodsMangheUsersModel->where(['user_id' => $this->uid, 'status' => 2])->count();
            $allTotal = $goodsMangheUsersModel->where(['user_id' => $this->uid, 'status' => ['in', [2, 3]]])->count();
            $data['no_open'] = $weikaiTotal;
            $data['total'] = $allTotal;
            $responsData = Response::success('success', $data);
        } else {
            $responsData = Response::fail('商品信息错误');
        }
        return json($responsData);
    }

    /**
     * 获取已经购买的盲盒
     */
    public function mangheList($page, $pagesize)
    {
        $goodsMangheUsersModel = new GoodsMangheUsers();

        $where['m.user_id'] = $this->uid;
        $where['m.status'] = ['in', [2, 3]];
        $count = $goodsMangheUsersModel->alias('m')
            ->join('goods g', 'g.id = m.goods_id')
            ->where($where)
            ->group('m.goods_id')
            ->count();
        if ($count <= 0) {
            $responsData = Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
        }
        $data = $goodsMangheUsersModel->alias('m')
            ->join('goods g', 'g.id = m.goods_id')
            ->field(['m.goods_id', 'm.user_id', 'count(*) total', 'g.name goods_name', 'g.image goods_image'])
            ->where($where)
            ->group('m.goods_id')
            ->order(['m.id desc'])
            ->page($page, $pagesize)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            foreach ($data as &$v) {
                //区分统计 已开盲盒 和未开盲盒的数据
                $weikaiTotal = $goodsMangheUsersModel->where(['user_id' => $this->uid, 'status' => 2])->count();
                $v['no_open'] = $weikaiTotal;
            }
            $data = addWebSiteUrl($data, ['goods_image']);
            $responsData = Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]);
        } else {
            $responsData = Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
        }
        return json($responsData);
    }

    /**
     * 开盲盒
     */
    public function openManghe()
    {
        $goodsId = $this->request->post('goodsId', '');
        if (!$goodsId) {
            $responsData = Response::fail('盲盒信息错误');
            return json($responsData);
        }
        //查询当前是否还有没有开启的盲盒
        $goodsMangheUsersModel = new GoodsMangheUsers();
        $no_openData = $goodsMangheUsersModel->where(['user_id' => $this->uid, 'status' => 2])->count();
        if ($no_openData <= 0) {
            $responsData = Response::fail('盲盒已全部开启,请选择其他盲盒!');
            return json($responsData);
        }
        //查询此盲盒对应的商品列表
        $goodsMangheConfigModel = new GoodsMangheConfig();

        $goodsMangheList = $goodsMangheConfigModel->alias('c')
            ->join('goods g', 'g.id = c.combination_goods_id')
            ->field(['c.*', 'g.name goods_name', 'g.image goods_image', 'g.price'])
            ->where(['goods_id' => $goodsId])
            ->select();


        if ($goodsMangheList) {
            $goodsMangheList = collection($goodsMangheList)->toArray();
            $arrJiangxiang = array_column($goodsMangheList, 'win_rate', 'combination_goods_id');
            $win_id = getWinRand($arrJiangxiang);
            $winInfo = [];
            foreach ($goodsMangheList as $item) {
                if ($item['combination_goods_id'] == $win_id) {
                    $winInfo = $item;
                    break;
                }
            }
            Db::startTrans();
            //添加记录到
            $mangheAwardRecord = new MangheAwardRecord();
            $winRecordData = [];
            $winRecordData['user_id'] = $this->uid;
            $winRecordData['goods_id'] = $winInfo['combination_goods_id'];
            $winRecordData['status'] = $winInfo['is_win'] ? 1 : 0;
            $winRecordData['createtime'] = time();
            $mangheAwardRecord->insert($winRecordData);
            $manghe_award_record_id = $mangheAwardRecord->insertGetId($winRecordData);
            if (!$manghe_award_record_id) {
                Db::rollback();
                $responsData = Response::fail('盲盒抽取失败!');
                return json($responsData);
            }
            //从用户盲盒中随机抽一个 更新状态
            $goods_manghe_users_info = $goodsMangheUsersModel->where(['user_id' => $this->uid, 'status' => 2])->order('createtime')->find();
            if (!$goods_manghe_users_info) {
                $responsData = Response::fail('盲盒已全部开启,请选择其他盲盒!');
                return json($responsData);
            }
            //添加盲盒中奖记录
            $result = $goodsMangheUsersModel->where(['id' => $goods_manghe_users_info['id']])->update(['status' => 3]);
            if (!$result) {
                Db::rollback();
                $responsData = Response::fail('盲盒抽取失败!');
                return json($responsData);
            }
            //如果抽到的是NFT 则做添加处理
            if ($winInfo['is_win']) {
                $goodsUsersData = new GoodsUsers();
                //添加到我的NFT中
                $goods_user_number = $goodsUsersData->where(['goods_id' => $winInfo['combination_goods_id']])->whereNotNull('number')->order('id', 'desc')->value('number');
                if ($goods_user_number) {
                    $goods_user_number = str_pad($goods_user_number + 1, 6, '0', STR_PAD_LEFT);
                } else {
                    $goods_user_number = '000001';
                }
                $time = date('Y-m-d H:i:s');
                $goods_number = uniqueNum();
                $usersGoods['uid'] = $this->uid;
                $usersGoods['goods_id'] = $winInfo['combination_goods_id'];
                $usersGoods['goods_number'] = $goods_number;
                $usersGoods['price'] = $winInfo['price'];
                $usersGoods['create_time'] = $time;
                $usersGoods['status'] = 1; //待出售
                $usersGoods['number'] = $goods_user_number;
                $result = $goodsUsersData->insertGetId($usersGoods);
                if (!$result) {
                    Db::rollback();
                    $responsData = Response::fail('盲盒抽取失败!');
                    return json($responsData);
                }
                //减少 NFT份额
                $goodsModel = new Goods();
                $goodsModel->where(['id' => $winInfo['combination_goods_id']])->setDec('surplus', 1);
                $goodsModel->where(['id' => $winInfo['combination_goods_id']])->setInc('sales', 1);
            }

            Db::commit();
            $winInfo = addWebSiteUrl($winInfo, ['goods_image']);
            $responsData = Response::success('success', $winInfo);
            return json($responsData);
        } else {
            $responsData = Response::fail('盲盒出问题了，请联系客服!');
            return json($responsData);
        }
    }
}
