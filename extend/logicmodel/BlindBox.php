<?php


namespace logicmodel;


use app\admin\model\ChipUsers;
use app\admin\model\GoodsMangheConfig;
use app\admin\model\GoodsMangheNumber;
use app\admin\model\GoodsMangheUsers;
use app\admin\model\GoodsRank;
use app\admin\model\MangheAwardRecord;
use app\common\model\Config;
use comservice\GetRedis;
use comservice\Response;
use datamodel\Goods;
use datamodel\GoodsConfig;
use datamodel\GoodsTransfer;
use datamodel\GoodsUsers;
use datamodel\Orders;
use datamodel\Users;
use think\Db;

class BlindBox
{
    private $goodsData;
    private $ordersData;
    private $goodsConfigData;
    private $goodsConfigNumber;
    private $goodsTransfer;
    private $redis;
    /** @var AccountLogic @accountLogic */
    private $accountLogic;

    public function __construct()
    {
        $this->goodsData = new Goods();
        $this->ordersData = new Orders();
        $this->goodsConfigData = new GoodsConfig();
        $this->goodsConfigNumber = new GoodsMangheNumber();
        $this->goodsTransfer = new GoodsTransfer();
        $this->redis = GetRedis::getRedis();
        $this->config = new Config();
        $this->accountLogic = new AccountLogic();
    }

    /**
     * 概率算法
     * @param $proArr
     * @return int|string
     */
    function getRand($proArr) {
        $result = 0;
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            }else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }

    public function open($num){
        $config = $this->config->where('group','blind_box')->select();
        $randArr = [];
        $randGoodsArr = [];
        foreach($config as $vo){
           switch ($vo['name']){
              case 'blind_box_level_1':
                   $randGoodsArr[1] = $vo['value'];
                   break;
              case 'blind_box_level_2':
                   $randGoodsArr[2] = $vo['value'];
                   break;
              case 'blind_box_level_3':
                   $randGoodsArr[3] = $vo['value'];
                   break;
              case 'blind_box_level_4':
                   $randGoodsArr[4] = $vo['value'];
                   break;
              case 'blind_box_level_5':
                   $randGoodsArr[5] = $vo['value'];
                   break;
              case 'blind_box_level_6':
                   $randGoodsArr[6] = $vo['value'];
                   break;
              case 'blind_box_level_7':
                   $randGoodsArr[7] = $vo['value'];
                   break;
              case 'blind_box_level_1_probability':
                   $randArr[1] = $vo['value']*100;
                   break;
              case 'blind_box_level_2_part_1_probability':
                   $randArr[21] = $vo['value']*100;
                   break;
              case 'blind_box_level_2_part_2_probability':
                   $randArr[22] = $vo['value']*100;
                   break;
              case 'blind_box_level_2_part_3_probability':
                   $randArr[23] = $vo['value']*100;
                   break;
              case 'blind_box_level_2_part_4_probability':
                   $randArr[24] = $vo['value']*100;
                   break;
              case 'blind_box_level_3_part_1_probability':
                   $randArr[31] = $vo['value']*100;
                   break;
              case 'blind_box_level_3_part_2_probability':
                   $randArr[32] = $vo['value']*100;
                   break;
              case 'blind_box_level_3_part_3_probability':
                   $randArr[33] = $vo['value']*100;
                   break;
              case 'blind_box_level_3_part_4_probability':
                   $randArr[34] = $vo['value']*100;
                   break;
              case 'blind_box_level_4_part_1_probability':
                   $randArr[41] = $vo['value']*100;
                   break;
              case 'blind_box_level_4_part_2_probability':
                   $randArr[42] = $vo['value']*100;
                   break;
              case 'blind_box_level_4_part_3_probability':
                   $randArr[43] = $vo['value']*100;
                   break;
              case 'blind_box_level_4_part_4_probability':
                   $randArr[44] = $vo['value']*100;
                   break;
              case 'blind_box_level_5_part_1_probability':
                   $randArr[51] = $vo['value']*100;
                   break;
              case 'blind_box_level_5_part_2_probability':
                   $randArr[52] = $vo['value']*100;
                   break;
              case 'blind_box_level_5_part_3_probability':
                   $randArr[53] = $vo['value']*100;
                   break;
              case 'blind_box_level_5_part_4_probability':
                   $randArr[54] = $vo['value']*100;
                   break;
              case 'blind_box_level_6_part_1_probability':
                   $randArr[61] = $vo['value']*100;
                   break;
              case 'blind_box_level_6_part_2_probability':
                   $randArr[62] = $vo['value']*100;
                   break;
              case 'blind_box_level_6_part_3_probability':
                   $randArr[63] = $vo['value']*100;
                   break;
              case 'blind_box_level_6_part_4_probability':
                   $randArr[64] = $vo['value']*100;
                   break;
              case 'blind_box_level_7_part_1_probability':
                   $randArr[71] = $vo['value']*100;
                   break;
              case 'blind_box_level_7_part_2_probability':
                   $randArr[72] = $vo['value']*100;
                   break;
              case 'blind_box_level_7_part_3_probability':
                   $randArr[73] = $vo['value']*100;
                   break;
              case 'blind_box_level_7_part_4_probability':
                   $randArr[74] = $vo['value']*100;
                   break;
           }
        }
        $arr = [];
        for($i=0;$i<$num;$i++) {
           $arr[]= $this->getGoodsPart($randArr, $randGoodsArr);
        }

        return $arr;
    }


    /**
     * 获取随机商品及碎片
     * @param $randArr
     * @return array
     */
    public function getGoodsPart($randArr,$randGoodsArr){
        $rand = $this->getRand($randArr);
        if($rand) {
            $repeatArr = $this->getRepeatArr($randArr);
            if (count($repeatArr) > 0 && in_array($randArr[$rand], $repeatArr)) {
                $randCountArr = array_count_values($randArr);
                //出现的次数
                $num = isset($randCountArr[$randArr[$rand]]) ? $randCountArr[$randArr[$rand]] : 1;
                if($num>1) {
                    $arr = [];
                    foreach($randArr as $k=>$v){
                        if($randArr[$rand]==$v){
                            $arr[]=$k;
                        }
                    }
                    $i = mt_rand(0,$num-1);
                    $rand =  isset($arr[$i]) ? $arr[$i] : $arr[0];
                }
            }
        }
        $level = 1;
        $part = 0;
        if($rand>1){
            $level = floor($rand /10);
            $part = $rand %10;
        }
        $goodsId = isset($randGoodsArr[$level]) ? $randGoodsArr[$level] : $randGoodsArr[7];
        return ['goods_id'=>$goodsId,'part'=>$part];
    }

    /**
     * 获取重复概率
     * @param $array
     * @return array
     */
    function getRepeatArr($array) {
        // 获取去掉重复数据的数组
        $unique_arr = array_unique ( $array );
        // 获取重复数据的数组
        $repeat_arr = array_diff_assoc ( $array, $unique_arr );
        return $repeat_arr;
    }

    /**
     * 列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function blindBoxList()
    {
        $time = date('Y-m-d H:i:s');
        $where['g.is_del'] = 0;
        $where['g.is_show'] = 1;
        $where['g.is_manghe'] = 1; //非盲盒
        $where['g.is_can_buy'] = 1; //可以参与购买
        $field = 'g.id,g.name,g.level,g.part,g.price,g.start_time,g.end_time,g.image';
        $data = $this->goodsData->alias('g')
            ->where($where)
            ->field($field)
            ->order(['g.order asc', 'g.start_time asc'])
            ->select();
        if ($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data, ['image']);
            return Response::success('success', $data);
        }
        return Response::success('success', $data);
    }

    /**
     * 详情
     * @param $id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function details($id)
    {
        $time = date('Y-m-d H:i:s');
        $where['is_del'] = $id;
        $where['is_del'] = 0;
        $where['is_show'] = 1;
        $where['is_manghe'] = 1; //非盲盒
        $where['is_can_buy'] = 1; //可以参与购买
        $where['start_time'] = ['lt',$time];
        $where['end_time'] = ['gt',$time];
        $field = 'id,name,price,image';
        $data = $this->goodsData->alias('g')
            ->where($where)
            ->field($field)
            ->order(['order asc', 'start_time asc'])
            ->find();
        if ($data) {
            $data = $data->toArray();
            $data = addWebSiteUrl($data, ['image']);
            $data['number'] = GoodsMangheNumber::where('goods_id',$id)->select();
            return Response::success('success', $data);
        }
        return Response::fail('盲盒不存在');
    }


    public function openBlindBox($goods_id,$number,$userInfo,$pay_password)
    {
        if($goods_id==0 || $number==0 || empty($pay_password)){
            return Response::fail('盲盒不存在');
        }
        $info = $this->goodsData->find($goods_id);
        if(!$info){
            return Response::fail('盲盒不存在');
        }
        $n_info = $this->goodsConfigNumber->where(['number'=>$number,'goods_id'=>$goods_id])->find();
        if(!$n_info){
            return Response::fail('盲盒抽取次数有误');
        }
        $password = md5(md5($pay_password) . $userInfo['pay_salt']);
        if(empty($userInfo['pay_password'])){
            return Response::fail('请先设置支付密码');
        }
        if($password!=$userInfo['pay_password']){
            return Response::fail('支付密码错误');
        }
        $uid = $userInfo['id'];
        $price = $n_info['amount'];
        $number = $n_info['number'];
        $gift_times = $n_info['gift_times'];//赠送次数
        $number = $gift_times ? $number+$gift_times : $number;
        $time = date('Y-m-d H:i:s');
        Db::startTrans();
        $accountLogic = new AccountLogic();
        $result = $accountLogic->subAccount($uid, 1, $price, 4, '抽取NFT盲盒'.$n_info['number'].'次');
        if (!$result) {
            Db::rollback();
            return Response::fail('余额不足');
        }
        //生成拍品信息，生成订单
        $order_num = uniqueNum();
        $order['goods_users_id'] = $uid;
        $order['goods_manghe_users_id'] = $uid;
        $order['order_num'] = $order_num;
        $order['goods_num'] = $number;
        $order['goods_id'] = $goods_id;
        $order['sale_uid'] = 1;
        $order['buy_uid'] = $uid;
        $order['price'] = $price;
        $order['status'] = 1;
        $order['create_time'] = $time;
        $order['pay_end_time'] = $time;
        $order['pay_time'] = $time;
        $order['goods_config_id'] = 0;
        $order['status'] = 2;
        $order['pay_type'] = 1;
        $order['buy_goods_id'] = $goods_id;
        $order['order_type'] = 3;
        $result = $this->ordersData->insertGetId($order);
        if ($result) {
            $goodsMangheConfigModel = new GoodsMangheConfig();
            $goodsUsersData = new GoodsUsers();
            $usersGoodsArr = [];
            $winInfoArr = [];
            for($i=0;$i<$number;$i++) {
                $goodsMangheList = $goodsMangheConfigModel->alias('c')
                    ->join('goods g', 'g.id = c.combination_goods_id')
                    ->field(['c.*', 'g.name goods_name', 'g.image goods_image', 'g.price','g.level','g.part','g.is_chip'])
                    ->where(['goods_id' => $goods_id])
                    ->select();
                if ($goodsMangheList) {
                    $goodsMangheList = collection($goodsMangheList)->toArray();
                    $arrJiangxiang = array_column($goodsMangheList, 'win_rate', 'combination_goods_id');
                    $win_id = getWinRand($arrJiangxiang);
                    $winInfo = [];
                    foreach ($goodsMangheList as $item) {
                        if ($item['combination_goods_id'] == $win_id) {
                            $winInfo = $item;
                            $winInfoArr[]=$winInfo;
                            break;
                        }
                    }
                    if($winInfo) {
                        $goods_user_number = $goodsUsersData->where(['goods_id' => $winInfo['combination_goods_id']])->whereNotNull('number')->order('id', 'desc')->value('number');
                        if ($goods_user_number) {
                            $goods_user_number = str_pad($goods_user_number + 1, 6, '0', STR_PAD_LEFT);
                        } else {
                            $goods_user_number = '000001';
                        }
                        $goods_number = uniqueNum();
                        $usersGoods = [];
                        $usersGoods['uid'] = $uid;
                        $usersGoods['goods_id'] = $winInfo['combination_goods_id'];
                        $usersGoods['goods_number'] = $goods_number;
                        $usersGoods['price'] = $winInfo['price'];
                        $usersGoods['create_time'] = $time;
                        $usersGoods['status'] = 1; //待出售
                        $usersGoods['part'] = $winInfo['part'];
                        $usersGoods['level'] = $winInfo['level'];
                        $usersGoods['number'] = $goods_user_number;
                        $usersGoods['source'] = 1;
                        $usersGoodsArr[] = $usersGoods;
                    }
                }
            }
            if($usersGoodsArr) {
                $goodsUsersData->insertAll($usersGoodsArr);
            }
            //佣金
            $this->sub_commission($uid, $price);
            Db::commit();
            // clrTODO 区块链转移
          //  $haixiaLogic = new HaixiaLogic();
           // $haixiaLogic->transactionGood($info['id'], $result);

            $winInfoArr = addWebSiteUrl($winInfoArr, ['goods_image']);
            return Response::success('success', $winInfoArr);
        }
        Db::rollback();
        return Response::fail('支付失败');
    }

    public function nftRank()
    {
        $rank = GoodsRank::order('id','asc')->select();
        if($rank){
            $rank = collection($rank)->toArray();
            $rank = addWebSiteUrl($rank, ['image']);
        }
        foreach ($rank as &$vo){
            $log = GoodsUsers::alias('r')
                ->join('users u', 'r.uid = u.id','LEFT')
                ->field('u.nick_name,r.level,r.part,\'/uploads/base/headicon.png\' as head_img')
                ->where('r.source',1)
                ->where('r.level',$vo['id'])
                ->limit(10)
                ->order('r.id','desc')
                ->select();
            if(count($log)==0){
                $level = $vo['id'];
                $log = GoodsUsers::alias('r')
                    ->join('users u', 'r.uid = u.id','LEFT')
                    ->field("u.nick_name, $level as level,r.part,'/uploads/base/headicon.png' as head_img")
                    ->where('r.source',1)
                    ->orderRaw('rand()')
                    ->limit(10)
                    ->select();
            }
            $log = collection($log)->toArray();
            $log = addWebSiteUrl($log, ['head_img']);
            $vo['scroll'] = $log;
        }
        return Response::success('success', $rank);
    }


    //分佣
    public function sub_commission($uid, $price)
    {
        $user = (new Users())->get($uid);
        if ($user) {
            $commission_level_1 = config('site.commission_level_1') / 100;
            $price_1 = $price * $commission_level_1;
            $this->accountLogic->addAccount($user->pid, 1, $price_1, 8, '一级分佣');

            $commission_level_2 = config('site.commission_level_2') / 100;
            $price_2 = $price * $commission_level_2;
            $this->accountLogic->addAccount($user->pid_2, 1, $price_2, 9, '二级分佣');
        }
    }

}
