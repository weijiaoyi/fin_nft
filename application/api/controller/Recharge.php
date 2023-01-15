<?php


namespace app\api\controller;


use logicmodel\RechargeLogic;
use think\Request;

class Recharge extends BaseController
{
    private $rechargeLogic;
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->rechargeLogic = new RechargeLogic();
    }

    /**
     * 币种列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function currencyList(){
        return json($this->rechargeLogic->currencyList());
    }


    /**
     * 充值
     * @param $config_pay_id
     * @param $account
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function  remittance($currency_protocol_id,$account,$screenshot=''){
        return json($this->rechargeLogic->remittance($this->uid,$currency_protocol_id,$account,$screenshot));
    }

    /**
     * 确认
     * @param $id
     * @param string $screenshot
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function  confirm($id,$screenshot=''){
        return json($this->rechargeLogic->confirm($this->uid,$id,$screenshot));
    }

    /**
     * 取消
     * @param $id
     * @return \think\response\Json
     */
    public function  cancel($id){
        return json($this->rechargeLogic->cancel($this->uid,$id));
    }

    /**
     * 充值记录
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recordList($page=1,$pagesize=10){
        return json($this->rechargeLogic->rechargeRecordList($this->uid,$page,$pagesize));
    }

   public function rechargeRecordDetails($id=0){
        return json($this->rechargeLogic->rechargeRecordDetails($this->uid,$id));
    }

}
