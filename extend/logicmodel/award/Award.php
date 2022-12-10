<?php
namespace logicmodel\award;
use datamodel\AwardRecord;
use datamodel\GoodsUsers;
use datamodel\Users;
use logicmodel\AccountLogic;
use think\Db;

class Award
{

    protected $awardData;
    protected $awardRecordData;
    protected $accountLogic;
    protected $usersData;
    public function __construct()
    {
        $this->awardData = new \datamodel\Award();
        $this->awardRecordData = new AwardRecord();
        $this->accountLogic = new AccountLogic();
        $this->usersData = new Users();
    }
    /**
     * 奖项是否开启
     * @param $award_id
     * @return array|bool|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function awardIsOpen($award_id){
        $where ['id'] = ['=',$award_id];
        $where ['status'] = ['=',1];
        $result = $this->awardData
            ->where($where)
            ->find();
        if ($result != null) return $result;
        return false;
    }

    /**
     * 生成记录
     * @param $uid
     * @param $currency_id
     * @param $money
     * @param $from_uid
     * @param $award_id
     * @param $award_name
     * @param $remark
     * @param $field
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function record($uid,$award_id,$goods_id){
        $goodsInfo = Db::name('goods')->where(['id'=>$goods_id])->find();
        $price = $goodsInfo['price'];
        $data['uid'] = $uid;
        $data['price'] = $price;
        $data['goods_id'] = $goods_id;
        $data['award_id'] = $award_id;
        $data['create_time'] = date('Y-m-d H:i:s');
       $this->awardRecordData->saveEntityAndGetId($data);
        $user = ['goods_id'=>$goods_id,'uid'=>$uid,'price'=>$price,'create_time'=>date('Y-m-d H:i:s')];
        (new GoodsUsers())->insertGetId($user);

    }
}