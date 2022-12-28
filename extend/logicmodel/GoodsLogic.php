<?php


namespace logicmodel;


use app\admin\model\GoodsMangheUsers;
use comservice\GetRedis;
use comservice\Response;
use datamodel\Coupon;
use datamodel\Goods;
use datamodel\GoodsCategory;
use datamodel\GoodsConfig;
use datamodel\GoodsTransfer;
use datamodel\GoodsUsers;
use datamodel\Orders;
use datamodel\Users;
use datamodel\UsersCoupon;
use think\Db;

class GoodsLogic
{
    private $goodsData;
    private $ordersData;
    private $goodsConfigData;
    private $goodsTransfer;
    private $redis;
    /** @var AccountLogic @accountLogic */
    private $accountLogic;

    public function __construct()
    {
        $this->goodsData = new Goods();
        $this->ordersData = new Orders();
        $this->goodsConfigData = new GoodsConfig();
        $this->goodsTransfer = new GoodsTransfer();
        $this->redis = GetRedis::getRedis();
        $this->accountLogic = new AccountLogic();
    }

    /**
     * 商品分类列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodsCategoryList()
    {
        $data = (new GoodsCategory())->where(['is_del' => 0, 'is_show' => 1])->order(['order asc'])->select();
        if ($data) return Response::success('success', collection($data)->toArray());
        return Response::success('暂无数据', []);
    }

    /**
     * 平台新品列表
     * @param $search
     * @param $goods_category_id
     * @param $page
     * @param $pagesize
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodsList($search,$is_chip,$page, $pagesize)
    {
        if($is_chip!=-1){
            $where['g.is_chip'] = $is_chip;
        }
        $where['g.is_del'] = 0;
        $where['g.is_show'] = 1;
        $where['g.is_manghe'] = 0; //非盲盒
        $where['g.is_can_buy'] = 1; //可以参与购买
        if (!empty($search)) $where['g.name|g.label'] = ['like', '%' . $search . '%'];
        $count = $this->goodsData->alias('g')->where($where)->count();
        if ($count <= 0) return Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
        $field = 'g.id,g.name,g.level,g.part,g.price,g.start_time,g.end_time,g.is_chip,gr.image,g.image as image_chip';
        $data = $this->goodsData->alias('g')
            ->join('goods_rank gr', 'g.level = gr.id','LEFT')
            ->where($where)
            ->field($field)
            ->order(['g.order asc', 'g.start_time asc'])
            ->page($page, $pagesize)
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image','image_chip']);
            $time = date('Y-m-d H:i:s');
            foreach ($data as &$v) {
                if($v['is_chip']==1){
                    $v['image'] = $v['image_chip'];
                }
                if ($time >= $v['start_time'] && $time <= $v['end_time']) {
                    $v['status'] = 1;
                } elseif ($v['start_time'] >= $time) {
                    $v['status'] = 2;
                } else {
                    $v['status'] = 3;
                }
                $start_time = date('Y-m-d H:i', strtotime($v['start_time']));
                $end_time = date('Y-m-d H:i', strtotime($v['end_time']));
                $v['start_time'] = $start_time;
                $v['end_time'] = $end_time;
            }
            return Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]);
        }
        return Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
    }

    /**
     * 商品详情
     * @param $id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodsDetail($id)
    {
        $where['g.id'] = $id;
        $where['g.is_del'] = 0;
        $field = 'g.id,g.name,g.level,g.part,g.price,g.start_time,g.end_time,g.is_chip,gr.image,g.image as image_chip,g.sell_type,g.duration,specify_uid  ';
        $data = $this->goodsData->alias('g')
            ->join('goods_rank gr', 'g.level = gr.id','LEFT')
            ->where($where)
            ->field($field)
            ->find();
        if ($data) {
            $data = $data->toArray();
            $data = addWebSiteUrl($data, ['image','image_chip']);
            if($data['is_chip']==1){
                $data['image'] = $data['image_chip'];
            }
            $start_time = date('Y-m-d H:i', strtotime($data['start_time']));
            $end_time = date('Y-m-d H:i', strtotime($data['end_time']));
            $data['start_time'] = $start_time;
            $data['end_time'] = $end_time;
            return Response::success('success', $data);
        }
        return Response::fail('商品信息错误');
    }

    /**
     * 购物下单
     * @param $uid
     * @param $id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function apply($userInfo, $id,$pay_password)
    {
        $uid = $userInfo['id'];
        //$count = $this->ordersData->where(['buy_uid' => $uid, 'status' => 1])->count();
        //if ($count >= 20) return Response::fail('您的待付款订单已达上限');
        if($id==0 || empty($pay_password)){
            return Response::fail('参数错误');
        }
        $password = md5(md5($pay_password) . $userInfo['pay_salt']);
        if(empty($userInfo['pay_password'])){
            return Response::fail('请先设置支付密码');
        }
        if($password!=$userInfo['pay_password']){
            return Response::fail('支付密码错误');
        }
        $goodsInfo = $this->goodsData->where(['is_del' => 0, 'is_show' => 1, 'id' => $id])->find();
        if (empty($goodsInfo)) return Response::fail('商品信息错误');
        //单品购买  盲盒不用管是否售罄
        if ($goodsInfo['is_manghe'] == 0 && $goodsInfo['surplus'] <= 0) return Response::fail('当前产品已售罄');

        if($goodsInfo['sell_type']==2){//竞价
            return Response::fail('竞价产品，购买入口错误');
        }else if($goodsInfo['sell_type']==3 && $uid!=$goodsInfo['specify_uid']){//指定人购买
            return Response::fail('您不是指定人，无法购买');
        }
        $goodsUsersData = new GoodsUsers();
        $goodsUser = $goodsUsersData::find($goodsInfo['goods_user_id']);
        $sale_uid = $goodsUser ? $goodsUser['uid'] : 0;
        $goods_id = $goodsInfo['id'];
        $price = $goodsInfo['price'];
        $goods_config_id = 0;
        $time = date('Y-m-d H:i:s');

        $goods_number = uniqueNum();
        $goods_user_number = $goodsUsersData->where(['goods_id' => $id])->whereNotNull('number')->order('id', 'desc')->value('number');
        if ($goods_user_number) {
            $goods_user_number = str_pad($goods_user_number + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $goods_user_number = '000001';
        }
        $goods['goods_number'] = $goods_number;
        $usersGoods['uid'] = $uid;
        $usersGoods['goods_id'] = $id;
        $usersGoods['price'] = $price;
        $usersGoods['create_time'] = $time;
        $usersGoods['number'] = $goods_user_number;
        $usersGoods['part'] = $goodsInfo['part'];
        $usersGoods['level'] = $goodsInfo['level'];

        Db::startTrans();
        $goods_users_id = $goodsUsersData->insertGetId($usersGoods);
        if (!$goods_users_id) {
            Db::rollback();
            return Response::fail('拍品信息错误');
        }
        $goods_manghe_users_id = "";
        //生成拍品信息，生成订单
        $order_num = uniqueNum();
        $order['goods_users_id'] = $goods_users_id;
        $order['goods_manghe_users_id'] = $goods_manghe_users_id;
        $order['order_num'] = $order_num;
        $order['goods_num'] = $goods_number;
        $order['goods_id'] = $goods_id;
        $order['sale_uid'] = $sale_uid;
        $order['buy_uid'] = $uid;
        $order['price'] = $price;
        $order['status'] = 2;
        $order['pay_type'] = 1;
        $order['create_time'] = $time;
        $order['pay_end_time'] = date('Y-m-d H:i:s', strtotime("+10 minutes"));
        $order['goods_config_id'] = $goods_config_id;
        $order['buy_goods_id'] = $id;

        $order_id = $this->ordersData->insertGetId($order);
        if (!$order_id) {
            Db::rollback();
            return Response::fail('订单生成失败');
        }
        $this->goodsData->where(['id' => $id])->setDec('surplus', 1);
        $this->goodsData->where(['id' => $id])->setInc('sales', 1);
        $accountLogic = new AccountLogic();
        $result = $accountLogic->subAccount($uid, 1, $price, 5,'市场购买');
        if (!$result) {
            Db::rollback();
            return Response::fail('余额不足');
        }
        if($sale_uid!=0) {
            $result = $accountLogic->addAccount($sale_uid, 1, $price, 6, '市场卖出');
            if (!$result) {
                Db::rollback();
                return Response::fail('订单支付失败');
            }
            $result = $goodsUsersData->where(['id' => $goodsInfo['goods_users_id']])->update(['status' => 3]);//已出售
            if (!$result) {
                Db::rollback();
                return Response::fail('订单支付失败');
            }
        }
        Db::commit();
        return Response::success('支付成功');
    }

    /**
     * 竞价
     * @param $uid
     * @param $id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bidding($uid, $id,$price)
    {
        if($id==0 || $price==0){//竞价
            return Response::invalidParam();
        }
        $goodsInfo = $this->goodsData->where(['is_del' => 0, 'is_show' => 1, 'id' => $id])->find();
        if (empty($goodsInfo)) return Response::fail('商品信息错误');
        if ($goodsInfo['status'] !=1) return Response::fail('当前藏品已售罄');
        if($goodsInfo['sell_type']!=2){//竞价
            return Response::fail('商品信息错误');
        }
        $goods_id = $goodsInfo['id'];
        $bidding_step = config('site.bidding_step');//最低加价数
        Db::startTrans();
        $gBidding = $this->ordersData->where('goods_id',$goods_id)->order('price','desc')->find();
        if($gBidding){
            if($price<$gBidding->price+$bidding_step){
                return Response::fail('竞价价格低于当前价');
            }
        }
        $accountLogic = new AccountLogic();
        $result = $accountLogic->subAccount($uid, 1, $price, 10, '竞价');
        if (!$result) {
            Db::rollback();
            return Response::fail('余额不足');
        }

        //改为竞拍失败
        $this->ordersData->where(['goods_id' => $goods_id])->where(['order_type' => 4])->setField('status',4);
        $goods_manghe_users_id = "";
        //生成拍品信息，生成订单
        $order_num = uniqueNum();
        $order['goods_users_id'] = 0;
        $order['goods_manghe_users_id'] = $goods_manghe_users_id;
        $order['order_num'] = $order_num;
        $order['goods_num'] =  uniqueNum();
        $order['goods_id'] = $goods_id;
        $order['sale_uid'] = 1;
        $order['buy_uid'] = $uid;
        $order['price'] = $price;
        $order['pay_type'] = 1;
        $order['status'] = 3;
        $order['create_time'] = date('Y-m-d H:i:s');
        $order['pay_end_time'] = $order['create_time'];
        $order['goods_config_id'] = 0;
        $order['buy_goods_id'] = $id;
        $order['order_type'] = 4;
        $order_id = $this->ordersData->insertGetId($order);
        if (!$order_id) {
            Db::rollback();
            return Response::fail('订单生成失败');
        }
        Db::commit();
        $this->goodsData->where(['id' => $id])->setInc('sales', 1);
        return Response::success('竞价成功');
    }


    /**
     * 付款
     * @param $userInfo
     * @param $order_id
     * @param $pay_type
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function pay($userInfo, $order_id, $pay_type)
    {
        $uid = $userInfo['id'];
        $info = $this->ordersData->where(['buy_uid' => $uid, 'id' => $order_id, 'status' => 1])->find();
        if (empty($info)) return Response::fail('订单信息错误');
        $price = $info['price'];
        $time = date('Y-m-d H:i:s');
        Db::startTrans();
        $accountLogic = new AccountLogic();

        $goodsUsersData = new GoodsUsers();

        $result = $accountLogic->subAccount($uid, 1, $price, 5,'市场购买');
        if (!$result) {
            Db::rollback();
            return Response::fail('余额不足');
        }
        if($info['goods_users_id']!=0) {
            $result = $accountLogic->addAccount($info['sale_uid'], 1, $price, 6, '市场卖出');
            if (!$result) {
                Db::rollback();
                return Response::fail('订单支付失败');
            }
            $result = $goodsUsersData->where(['id' => $info['goods_users_id']])->update(['status' => 4]);
            if (!$result) {
                Db::rollback();
                return Response::fail('订单支付失败');
            }
        }
        $result = $this->ordersData->updateByWhere(['id' => $order_id], ['pay_time' => $time, 'status' => 2, 'pay_type' => $pay_type]);
        if (!$result) {
            Db::rollback();
            return Response::fail('订单支付失败');
        }
        $goods_user_number = $goodsUsersData->where(['goods_id' => $info['goods_id']])->whereNotNull('number')->order('id', 'desc')->value('number');
        if ($goods_user_number) {
            $goods_user_number = str_pad($goods_user_number + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $goods_user_number = '000001';
        }
        $goods = Goods::find($info['goods_id']);
        $usersGoods['uid'] = $uid;
        $usersGoods['goods_id'] = $info['goods_id'];
        $usersGoods['goods_number'] = $info['goods_num'];
        $usersGoods['price'] = $price;
        $usersGoods['create_time'] = $time;
        $usersGoods['number'] = $goods_user_number;
        $usersGoods['part'] = $goods['part'];
        $usersGoods['level'] = $goods['level'];
        $result = $goodsUsersData->insertGetId($usersGoods);
        if ($result) {
            //$this->sub_commission($uid, $price);
            Db::commit();
            return Response::success('支付成功');
        }
        Db::rollback();
        return Response::fail('订单支付失败');
    }


    /**
     * 订单列表
     * @param $uid
     * @param $status
     * @param $page
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderList($uid, $status, $page, $pagesize)
    {
        $where['o.buy_uid'] = $uid;
        if ($status > 0) $where['o.status'] = $status;
        $count = $this->ordersData->alias('o')
            ->join('goods g', 'g.id = o.goods_id')
            ->join('goods_category gc', 'gc.id = g.goods_category_id')
            ->where($where)
            ->count();
        if ($count <= 0) return Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
        $data = $this->ordersData->alias('o')
            ->join('goods g', 'g.id = o.goods_id')
            ->join('goods bg', 'bg.id = o.buy_goods_id')
            ->join('goods_category gc', 'gc.id = g.goods_category_id')
            ->where($where)
            ->order(['o.id desc'])
            ->page($page, $pagesize)
            ->field(['o.*', 'g.name goods_name', 'g.image goods_image', 'bg.image buy_goods_image', 'bg.name buy_goods_name', 'gc.name goods_category_name'])
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            foreach ($data as &$v) {
                if ($v['goods_config_id'] > 0) {
                    $v['goods_image'] = $v['buy_goods_image'];
                    $v['goods_name'] = $v['buy_goods_name'];
                }
            }
            $data = addWebSiteUrl($data, ['goods_image']);
            return Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]);
        }
        return Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
    }

    /**
     * 订单详情
     * @param $uid
     * @param $id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderDetail($uid, $id)
    {
        $where['o.buy_uid'] = $uid;
        $where['o.id'] = $id;
        $data = $this->ordersData->alias('o')
            ->join('goods g', 'g.id = o.goods_id')
            ->join('goods bg', 'bg.id = o.buy_goods_id')
            ->join('goods_category gc', 'gc.id = g.goods_category_id')
            ->where($where)
            ->field(['o.*', 'g.name goods_name', 'g.image goods_image', 'bg.image buy_goods_image', 'bg.name buy_goods_name', 'gc.name goods_category_name'])
            ->find();
        if ($data) {
            $data = $data->toArray();
            if ($data['goods_config_id'] > 0) {
                $data['goods_image'] = $data['buy_goods_image'];
                $data['goods_name'] = $data['buy_goods_name'];
            }
            $data = addWebSiteUrl($data, ['goods_image']);
            return Response::success('success', $data);
        }
        return Response::fail('订单信息错误');
    }

    /**
     * 会员拍品列表
     * @param $search
     * @param $goods_category_id
     * @param $uid
     * @param $page
     * @param $pagesize
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function memberGoodsList($uid, $search, $goods_category_id, $page, $pagesize)
    {
        $is_trade = config('site.is_trade');
        if ($is_trade == 0) return Response::success('success', ['count' => 0, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
        $where['gu.status'] = 2;
        $where['gu.is_del'] = 0;
        $where['gu.is_show'] = 1;
        $where['u.id'] = ['<>', 1];
        if (!empty($uid)) $where['gu.uid'] = ['<>', $uid];
        if (!empty($goods_category_id)) $where['g.goods_category_id'] = $goods_category_id;
        if (!empty($search)) $where['g.name|g.label'] = ['like', '%' . $search . '%'];
        $goodsUsersData = new GoodsUsers();
        $count = $goodsUsersData->alias('gu')
            ->join('goods g', 'g.id = gu.goods_id')
            ->join('goods_category gc', 'gc.id = g.goods_category_id')
            ->join('users u', 'u.id = gu.uid')
            ->where($where)
            ->count();
        $data = $goodsUsersData->alias('gu')
            ->join('goods g', 'g.id = gu.goods_id')
            ->join('goods_category gc', 'gc.id = g.goods_category_id')
            ->join('users u', 'u.id = gu.uid')
            ->where($where)
            ->order(['gu.order asc'])
            ->page($page, $pagesize)
            ->field(['g.name', 'g.image', 'gu.price', 'gu.id', 'gc.name goods_category_name', 'g.creator'])
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image']);
        }
        return Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]);
    }

    /**
     * @param $uid
     * @param $id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function memberGoodsDetail($uid, $id)
    {
        $goodsUsersData = new GoodsUsers();
        $info = $goodsUsersData->alias('gu')
            ->join('goods g', 'g.id = gu.goods_id')
            ->join('users u', 'u.id = gu.uid')
            ->join('goods_category gc', 'gc.id = g.goods_category_id')
            ->where(['gu.status' => 2, 'gu.is_del' => 0, 'gu.is_show' => 1, 'u.id' => ['<>', 1], 'gu.id' => $id])
            ->order(['gu.order asc'])
            ->field(['g.*', 'gu.price', 'gu.id goods_users_id', 'u.ht_wallet_address owner', 'gc.name goods_category_name', 'gu.goods_number', 'gu.uid', 'gu.number'])
            ->find();
        if (empty($info)) return Response::fail('藏品信息错误');
        $info = $info->toArray();
        $info = addWebSiteUrl($info, ['image', 'images']);
//        $info['content'] = content($info['content']);
        return Response::success('success', $info);
    }

    /**
     * 会员拍品下单
     * @param $uid
     * @param $id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function memberApply($uid, $id)
    {
        $info = $this->ordersData->where(['buy_uid' => $uid, 'status' => 1])->find();
        if ($info) return Response::fail('您有待付款的订单,请先支付订单');
        $goodsUsersData = new GoodsUsers();
        $goodsInfo = $goodsUsersData->alias('gu')
            ->join('goods g', 'g.id = gu.goods_id')
            ->join('users u', 'u.id = gu.uid')
            ->where(['gu.status' => 2, 'gu.is_del' => 0, 'gu.is_show' => 1, 'u.id' => ['<>', 1], 'gu.id' => $id])
            ->order(['gu.order asc'])
            ->field(['gu.price', 'gu.id goods_users_id', 'u.ht_wallet_address owner', 'gu.goods_number', 'gu.goods_id', 'gu.uid'])
            ->find();
        if (empty($goodsInfo)) return Response::fail('商品信息错误');
        if ($goodsInfo['uid'] == $uid) return Response::fail('您不能购买自己的作品');
        $time = date('Y-m-d H:i:s');
        //生成拍品信息，生成订单
        $order_num = uniqueNum();
        $order['goods_users_id'] = $id;
        $order['order_num'] = $order_num;
        $order['goods_num'] = $goodsInfo['goods_number'];
        $order['goods_id'] = $goodsInfo['goods_id'];
        $order['sale_uid'] = $goodsInfo['uid'];
        $order['buy_uid'] = $uid;
        $order['price'] = $goodsInfo['price'];
        $order['status'] = 1;
        $order['create_time'] = $time;
        $order['pay_end_time'] = date('Y-m-d H:i:s', strtotime("+5 minutes"));
        $order['goods_config_id'] = 0;
        $order['order_type'] = 2;
        $order_id = $this->ordersData->insertGetId($order);
        if (!$order_id) {
            Db::rollback();
            return Response::fail('订单生成失败');
        }
        $result = $goodsUsersData->updateByWhere(['id' => $id], ['status' => 3]);
        if (!$result) {
            Db::rollback();
            return Response::fail('订单生成失败');
        }
        Db::commit();
        return Response::success('购买成功', ['order_id' => $order_id, 'order_num' => $order_num, 'time' => 300]);
    }

    /**
     * 收藏品列表
     * @param $uid
     * @param $status
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function collectionList($uid, $status)
    {
        $where['gu.uid'] = $uid;
        $where['gu.is_del'] = 0;
        if ($status == 1) {
            $where['gu.status'] = $status;
        } elseif ($status == 2) {
            $where['gu.status'] = ['in', [2, 3]];
        } else {
            return Response::fail('产品状态错误');
        }
        $data = (new GoodsUsers())->alias('gu')
            ->join('goods g', 'g.id = gu.goods_id')
            ->where($where)
            ->field(['gu.id', 'g.name goods_name', 'g.image goods_image', 'gu.status', 'gu.price'])
            ->order(['gu.id desc'])
            ->select();
        if (!empty($data)) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['goods_image']);
            return Response::success('success', $data);
        }
        return Response::success('暂无数据', []);
    }

    /**
     * 藏品详情
     * @param $id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function collectionDetail($uid, $id)
    {
        $goodsUsersData = new GoodsUsers();
        $info = $goodsUsersData->alias('gu')
            ->join('goods g', 'g.id = gu.goods_id')
            ->join('users u', 'u.id = gu.uid')
            ->where(['gu.id' => $id])
            ->order(['gu.order asc'])
            ->field(['g.*', 'gu.price', 'gu.id goods_users_id', 'u.ht_wallet_address owner', 'gu.status', 'gu.is_show', 'gu.goods_number', 'gu.uid', 'gu.number'])
            ->find();
        if (empty($info)) return Response::fail('藏品信息错误');
        $info = $info->toArray();
        if ($info['uid'] != $uid) {
            $info['member'] = '';
        }
        $info = addWebSiteUrl($info, ['image', 'images']);
        $info['content'] = content($info['content']);
        return Response::success('success', $info);
    }

    /**
     * 市场卖出
     * @param $uid
     * @param $id
     * @param $price
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sales($uid, $id, $price)
    {
        $goodsUsersData = new GoodsUsers();
        $info = $goodsUsersData->where(['status' => 1, 'uid' => $uid, 'id' => $id])->find();
        if (empty($info)) return Response::fail('藏品信息错误');
        $trade_day = config('site.trade_day');
        $end_time = date('Y-m-d H:i:s', strtotime("{$trade_day} days"));
        if ($end_time < $info['create_time']) {
            return Response::fail('未到出售时间');
        }
        if ($price < 0.01) return Response::fail('价格输入错误');
        $result = $goodsUsersData->where(['id' => $id])->update(['price' => $price, 'status' => 2]);
        if ($result) return Response::success('出售成功');
        return Response::fail('出售失败');
    }

    /**
     * 藏品转赠
     * @param $uid
     * @param $id
     * @param $target_phone
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function transfer($uid, $id, $target_phone)
    {
        $goodsUsersData = new GoodsUsers();
        $goods = $goodsUsersData->where(['id' => $id, 'status' => ['in', [1, 2]], 'uid' => $uid])->find();
        if (empty($goods)) return Response::fail('藏品信息错误');
        $trade_day = config('site.trade_day');
        $end_time = date('Y-m-d H:i:s', strtotime("{$trade_day} days"));
        if ($end_time < $goods['create_time']) {
            return Response::fail('未到转赠时间');
        }
        $info = (new Users())->where(['phone' => $target_phone, 'is_del' => 0, 'id' => ['<>', $uid], 'status' => 1])->find();
        if (empty($info)) return Response::fail('接收会员手机号错误');
        $transfer['uid'] = $uid;
        $transfer['target_uid'] = $info['id'];
        $transfer['goods_id'] = $goods['goods_id'];
        $transfer['goods_users_id'] = $id;
        $transfer['price'] = $goods['price'];
        $transfer['create_time'] = date('Y-m-d H:i:s');
        Db::startTrans();
        $result = (new GoodsTransfer())->insertGetId($transfer);
        if (!$result) {
            Db::rollback();
            return Response::fail('转赠失败');
        }
        $result = $goodsUsersData->updateByWhere(['id' => $id], ['uid' => $info['id']]);
        if ($result) {
            Db::commit();
            (new SendLogic())->transfer($target_phone);

            // clrTODO 区块链转移
            $haixiaLogic = new HaixiaLogic();
            $haixiaLogic->transactionGood_2($transfer['uid'],$transfer['target_uid'],$transfer['goods_users_id']);

            return Response::success('转赠成功');
        }
        Db::rollback();
        return Response::success('转赠失败');
    }

    /**
     * 更新拍品状态
     * @param $uid
     * @param $id
     * @param $is_show
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkShow($uid, $id, $is_show)
    {
        $goodsUsersData = new GoodsUsers();
        $info = $goodsUsersData->where(['status' => 2, 'uid' => $uid, 'id' => $id])->find();
        if (empty($info)) return Response::fail('藏品信息错误');
        $result = $goodsUsersData->where(['id' => $id])->update(['is_show' => $is_show]);
        if ($result) return Response::success('操作成功');
        return Response::fail('操作失败');
    }

    /**
     * 修改价格
     * @param $uid
     * @param $id
     * @param $price
     * @return array
     */
    public function checkPrice($uid, $id, $price)
    {
        $goodsUsersData = new GoodsUsers();
        $result = $goodsUsersData->where(['uid' => $uid, 'id' => $id])->update(['price' => $price]);
        if ($result) return Response::success('修改成功');
        return Response::fail('修改失败');
    }

    /**
     * 藏品数量
     * @param $uid
     * @return array
     * @throws \think\Exception
     */
    public function collectionCount($uid)
    {
        $where['gu.uid'] = $uid;
        $where['gu.is_del'] = 0;
        $where['gu.status'] = 1;
        $goodsUsersData = new GoodsUsers();
        $buy_count = $goodsUsersData->alias('gu')
            ->join('goods g', 'g.id = gu.goods_id')
            ->where($where)->count();

        $where['gu.status'] = ['in', [2, 3]];
        $sale_count = $goodsUsersData->alias('gu')
            ->join('goods g', 'g.id = gu.goods_id')
            ->where($where)->count();
        return Response::success('success', ['buy_count' => $buy_count, 'sale_count' => $sale_count]);
    }

    /**
     * 取消
     * @param $uid
     * @param $id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelOrder($uid, $id)
    {

        $info = $this->ordersData->where(['buy_uid' => $uid, 'id' => $id, 'status' => 1])->find();
        if (empty($info)) return Response::fail('订单状态错误，不能取消');
        $result = $this->ordersData->where(['id' => $id])->update(['status' => 3]);
        if ($result) {
            (new GoodsUsers())->where('id', $info->goods_users_id)->update(['status' => 2]);
            $goods_config_id = $info['goods_config_id'];
            if ($goods_config_id > 0) {
                $goodsConfigData = new GoodsConfig();
                $goodsConfigData->where(['id' => $goods_config_id])->setInc('surplus', 1);
                $goodsConfigData->where(['id' => $goods_config_id])->setDec('sales', 1);
            }

            $this->goodsData->where(['id' => $info['buy_goods_id']])->setInc('surplus', 1);
            $this->goodsData->where(['id' => $info['buy_goods_id']])->setDec('sales', 1);

            return Response::success('取消成功');
        }
        return Response::fail('取消失败');
    }

    /**
     * 赠送优惠券
     * @param $uid
     * @param $goods_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendCoupon($uid, $goods_id)
    {
        $goodsInfo = $this->goodsData->where(['id' => $goods_id])->find();
        if ($goodsInfo['coupon_id'] > 0) {
            $day = (new Coupon())->where(['id' => $goodsInfo['coupon_id']])->value('day');
            $coupon['uid'] = $uid;
            $coupon['order_num'] = uniqueNum();
            $coupon['coupon_id'] = $goodsInfo['coupon_id'];
            $coupon['create_time'] = date('Y-m-d H:i:s');
            $coupon['end_time'] = date('Y-m-d H:i:s', strtotime("+{$day} days"));
            (new UsersCoupon())->insertGetId($coupon);
        }
    }

    /**
     * 平台新品列表
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function indexGoods()
    {
        $where['g.is_del'] = 0;
        $where['g.is_show'] = 1;
        $field = ['g.*', 'gc.name goods_category_name'];
        $data = $this->goodsData->alias('g')
            ->join('goods_category gc', 'gc.id = g.goods_category_id')
            ->where($where)
            ->field($field)
            ->order(['g.order asc', 'g.start_time asc'])
            ->find();
        if ($data) {
            $data = $data->toArray();
            $data = addWebSiteUrl($data, ['image', 'company_image']);
            $time = date('Y-m-d H:i:s');
            if ($time >= $data['start_time'] && $time <= $data['end_time']) {
                $data['status'] = 1;
            } elseif ($data['start_time'] >= $time) {
                $data['status'] = 2;
            } else {
                $data['status'] = 3;
            }
            $start_time = date('Y-m-d H:i', strtotime($data['start_time']));
            $end_time = date('Y-m-d H:i', strtotime($data['end_time']));
            $data['start_time'] = $start_time;
            $data['end_time'] = $end_time;
            return Response::success('success', $data);
        }
        return Response::success('success', []);
    }

    /**
     * 二手市场
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function indexMemberGoods()
    {
        $where['gu.status'] = 2;
        $where['gu.is_del'] = 0;
        $where['gu.is_show'] = 1;
        $where['u.id'] = ['<>', 1];
        $goodsUsersData = new GoodsUsers();

        $data = $goodsUsersData->alias('gu')
            ->join('goods g', 'g.id = gu.goods_id')
            ->join('users u', 'u.id = gu.uid')
            ->where($where)
            ->order(['gu.order asc'])
            ->field(['g.name', 'g.image', 'gu.price', 'gu.id'])
            ->find();
        if ($data) {
            $data = $data->toArray();
            $data = addWebSiteUrl($data, ['image']);
            return Response::success('success', $data);
        }
        return Response::success('success', []);
    }

    /**
     * 交易记录
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodsRecord($id)
    {
        $where['o.sale_uid'] = 1;
        $where['o.status'] = 2;
        $where['o.goods_id'] = $id;
        //购买记录
        $data = collection($this->ordersData->alias('o')
            ->join('goods g', 'g.id = o.goods_id')
            ->where($where)
            ->field(["IFNULL(g.extend, '购买人：') label", 'g.owner', 'o.pay_time'])
            ->limit(10)
            ->select())->toArray();
        //铸造记录
        $goods[] = $this->goodsData->where('id', $id)->field("IFNULL(extend, '铸造人：') label, casting_name owner, casting_time pay_time")->find()->toArray();
        //转赠记录
        $transfer_info = collection($this->goodsTransfer->alias('t')
            ->join('users u', 'u.id = t.uid')
            ->join('users us', 'us.id = t.target_uid')
            ->where('goods_id', $id)
            ->field('u.nick_name, us.nick_name as target_nick_name, t.create_time')
            ->select())->toArray();

        if ($transfer_info) {
            foreach ($transfer_info as $key => $value) {
                $transfer[$key]['label'] = '';
                $transfer[$key]['owner'] = '"' . $value['nick_name'] . '"' . ' 藏品转赠 ' . '"' . $value['target_nick_name'] . '"';
                $transfer[$key]['pay_time'] = $value['create_time'];
            }
            $transfer_info = $transfer;
        }

        $result = array_merge($goods, $data, $transfer_info);

        $pay_time = array_column($result, 'pay_time');
        array_multisort($pay_time, SORT_ASC, $result);

        return Response::success('success', $result);
    }

    /**
     * 交易纪录
     * @param $goods_number
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function memberGoodsRecord($goods_number)
    {
        $where['gu.goods_number'] = $goods_number;
        $where['gu.is_del'] = 0;
        $where['gu.status'] = ['in', [2, 3, 4]];
        $where['gu.uid'] = ['>', 1];
        $data = (new GoodsUsers())->alias('gu')
            ->join('users u', 'u.id = gu.uid')
            ->join('goods g', 'g.id = gu.goods_id')
            ->where($where)
            ->field(["IFNULL(extend, '购买人：') label", 'u.nick_name owner', 'gu.create_time pay_time'])
            ->limit(10)
            ->select();
        // if($data){
        //     $data = collection($data)->toArray();
        //     return Response::success('success',$data);
        // }
        // return Response::success('success',[]);

        $id = (new GoodsUsers())->where('goods_number', $goods_number)->value('goods_id');
        //铸造记录
        $goods[] = $this->goodsData->where('id', $id)->field("IFNULL(extend, '铸造人：') label, casting_name owner, casting_time pay_time")->find()->toArray();
        //转赠记录
        $transfer_info = collection($this->goodsTransfer->alias('t')
            ->join('users u', 'u.id = t.uid')
            ->join('users us', 'us.id = t.target_uid')
            ->where('goods_id', $id)
            ->field('u.nick_name, us.nick_name as target_nick_name, t.create_time')
            ->select())->toArray();

        if ($transfer_info) {
            foreach ($transfer_info as $key => $value) {
                $transfer[$key]['label'] = '';
                $transfer[$key]['owner'] = '"' . $value['nick_name'] . '"' . ' 藏品转赠 ' . '"' . $value['target_nick_name'] . '"';
                $transfer[$key]['pay_time'] = $value['create_time'];
            }
            $transfer_info = $transfer;
        }

        $result = array_merge($goods, $data, $transfer_info);

        $pay_time = array_column($result, 'pay_time');
        array_multisort($pay_time, SORT_ASC, $result);

        return Response::success('success', $result);
    }


    //分佣
    public function sub_commission($uid, $price)
    {
        $user = (new Users())->get($uid);
        if ($user) {
            $total_rate = config('site.sub_commission') / 100;
            $price = $price * $total_rate;
            $result = $this->accountLogic->addAccount($user->pid, 1, $price, '分佣', '分佣');
        }

    }

    /**
     * 竞价历史
     * @param $id
     * @param int $page
     * @param int $pagesize
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function biddingList($id, int $page, int $pagesize)
    {
        $where['o.buy_goods_id'] = $id;
        $count = $this->ordersData
            ->alias('o')
            //->join('users u', 'o.buy_uid = u.id')
            ->where($where)
            ->count();
        $data = $this->ordersData->alias('o')
            ->join('users u', 'o.buy_uid = u.id')
            ->where($where)
            ->order(['o.id desc'])
            ->page($page, $pagesize)
            ->field(['o.price', 'u.nick_name'])
            ->select();
        return Response::success('success', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]);
    }

}
