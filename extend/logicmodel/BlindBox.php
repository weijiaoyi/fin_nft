<?php


namespace logicmodel;


use app\common\model\Config;
use comservice\GetRedis;
use datamodel\Goods;
use datamodel\GoodsConfig;
use datamodel\GoodsTransfer;
use datamodel\Orders;

class BlindBox
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


}
