<?php


namespace logicmodel;


use comservice\Response;
use datamodel\ConfigDraw;
use datamodel\Currency;
use datamodel\DrawRecord;
use function fast\e;
use think\Db;

class DrawLogic
{
    private $configDrawData;
    private $drawRecordData;
    private $currencyData;
    public function __construct()
    {
        $this->configDrawData = new ConfigDraw();
        $this->drawRecordData = new DrawRecord();
        $this->currencyData = new Currency();
    }

    /**
     * 配置信息
     * @param $currency_id
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function configDraw($currency_id){
        $data = $this->configDrawData->where(['is_del'=>0,'is_show'=>1,'currency_id'=>$currency_id])->find();
       if($data) return  Response::success('success',$data->toArray());
       return  Response::fail('暂无开启提现');
    }

    /**
     * 提现
     * @param $userInfo
     * @param $account
     * @param $type
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
       public function  draw($userInfo,$account,$type,$currency='',$address='',$pay_password){
           if($account==0 || empty($type) || empty($currency) || empty($pay_password)){
               return Response::invalidParam();
           }
           $password = md5(md5($pay_password) . $userInfo['pay_salt']);
           if(empty($userInfo['pay_password'])){
               return Response::fail('请先设置支付密码');
           }
           if($password!=$userInfo['pay_password']){
               return Response::fail('支付密码错误');
           }
         $field = ['cd.*','c.name'];
         $currency_id  = 1;
         $config = $this->configDrawData->alias('cd')
             ->join('currency c','c.id = cd.currency_id')
             ->where(['cd.currency_id'=>$currency_id,'cd.is_del'=>0,'cd.is_show'=>1])
             ->field($field)
             ->find();
         if(empty($config)) return Response::fail('暂不支持提现');
         if($account > $config['max']) return Response::fail('超出最大提现金额');
         if($account < $config['min']) return Response::fail('低于最小提现金额');

         $uid = $userInfo['id'];
         Db::startTrans();
         if($currency=='usdt') {
             $result = (new AccountLogic())->subAccount($uid, $currency_id, $account, 2, '提现');
         }else{
             $currency_id = 2;
             $result = (new AccountLogic())->subFtc($userInfo, $account, 3, '提现');
         }
         if($result == false){
             Db::rollback();
             return Response::fail('账户余额不足');
         }
         $reality_account = bcdiv($account*(100-$config['rate']),100,2);
         $data['order_num'] = uniqueNum();
         $data['currency_id'] = $currency_id;
         $data['uid'] = $uid;
         $data['account'] = $account;
         $data['reality_account'] = $reality_account;
         $data['type'] = $type;
         $data['currency'] = $currency;
         $data['address'] = $address;
         $data['create_time'] = date('Y-m-d H:i:s');
         $result = $this->drawRecordData->saveEntityAndGetId($data);
         if($result){
             Db::commit();
             return Response::success('申请成功');
         }
         Db::rollback();
         return Response::fail('申请失败');
    }

    /**
     * 提现记录
     * @param $uid
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function drawRecordList($uid, int $page, int $pagesize){
        $where ['dr.uid'] = $uid;
        $count = $this->drawRecordData->alias('dr')
            ->where($where)
            ->count();
        if ($count <= 0) return Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
        $field = ['dr.*','c.name currency_name'];
        $data = $this->drawRecordData->alias('dr')
            ->join('currency c','c.id = dr.currency_id')
            ->where($where)
            ->field($field)
            ->order(['dr.id desc'])
            ->page($page, $pagesize)
            ->select();
        if($data) return Response::success('success',collection($data)->toArray());
        return Response::success('暂无数据',[]);
     }
}
