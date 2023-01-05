<?php


namespace app\api\controller;


use logicmodel\DrawLogic;
use think\Request;

class Draw extends BaseController
{
    private $drawLogic;
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->drawLogic = new DrawLogic();
    }

    /**
     * 提现配置
     * @param int $currency_id
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function configDraw($currency_id=1){
        return json($this->drawLogic->configDraw($currency_id));
    }

    /**
     * 提现
     * @param $account
     * @param int $type
     * @param int $pay_password
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function  draw($account=0,$type='bsc',$currency='usdt',$address='',$pay_password=''){
        return json($this->drawLogic->draw($this->userInfo,$account,$type,$currency,$address,$pay_password));
    }

    /**
     * 合约提现
     * @param int $account
     * @param string $type
     * @param string $currency
     * @param string $address
     * @param string $pay_password
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function  drawContract($id,$pay_password=''){
        return json($this->drawLogic->drawcContract($this->userInfo,$id,$pay_password));
    }

    /**
     * 提现记录
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function drawRecordList($page=1,$pagesize=10){
        return json($this->drawLogic->drawRecordList($this->uid,$page,$pagesize));
    }
}
