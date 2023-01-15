<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/5/3 0003
 * Time: 22:11
 */

namespace logicmodel;


use app\admin\model\Currency;
use app\admin\model\CurrencyProtocol;
use app\admin\model\RechargeRecord;
use app\admin\model\User;
use comservice\Response;
use datamodel\ConfigPay;
use datamodel\Users;
use think\Db;

class RechargeLogic
{
    private $currencyData;
    private $rechargeRecordData;
    private $currencyProtocolData;
    public  function __construct()
    {
        $this->currencyData = new Currency();
        $this->rechargeRecordData = new RechargeRecord();
        $this->currencyProtocolData = new CurrencyProtocol();
    }

    /**
     * 汇款记录
     * @param $uid
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rechargeRecordList($uid, $page, $pagesize){
        $where['rr.uid'] = $uid;
        $count = $this->rechargeRecordData->alias('rr')
            ->join('currency c','c.id = rr.currency_id')
            ->join('currency_protocol cp','cp.id = rr.currency_protocol_id')
            ->where($where)
            ->count();
        if ($count <= 0) return Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
        $field = ['rr.id','rr.account','rr.status','rr.address','expiration','rr.refuse','rr.order_num','rr.create_time','cp.name currency_protocol_name','c.name currency_name'];
        $data =  $this->rechargeRecordData->alias('rr')
            ->join('currency c','c.id = rr.currency_id')
            ->join('currency_protocol cp','cp.id = rr.currency_protocol_id')
            ->where($where)
            ->order(['rr.id desc'])
            ->field($field)
            ->page($page, $pagesize)
            ->select();
      if($data) return Response::success('success',collection($data)->toArray());
      return   Response::success('暂无数据',[]);
    }

    /**
     * 列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function currencyList(){
       $data =  $this->currencyData->with(['currencyProtocol'])->where(['is_del'=>0,'status'=>1,'is_show'=>1])->select();
       if($data) {
            $data = collection($data)->toArray();
            $data = addWebSiteUrl($data,['image']);
            return Response::success('success',$data);
       }
       return Response::success('success');
    }


    /**
     * 充值
     * @param $uid
     * @param $config_pay_id
     * @param $account
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function  remittance($uid,$currency_protocol_id,$account,$screenshot){

       $config =  $this->currencyProtocolData->where(['is_open'=>1,'id'=>$currency_protocol_id])->find();
       if(empty($config)) return  Response::fail('未开启充值');
        $recharge_expiration = config('site.recharge_expiration');//时效
        //生成充值记录
        $order_num = uniqueNum();
        $data['uid'] = $uid;
        $data['currency_id'] = $config['currency_id'];
        $data['order_num'] = $order_num;
        $data['account'] = $account;
        $data['address'] = $config['address'];
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['currency_protocol_id'] = $currency_protocol_id;
        $data['screenshot'] = $screenshot;
        $data['expiration'] = time()+$recharge_expiration;
        Db::startTrans();
        $order_id = $this->rechargeRecordData->insertGetId($data);
        if(!$order_id){
            Db::rollback();
            return Response::fail('订单生成失败');
        }
        Db::commit();
        return Response::success('下单成功');
    }


    /**
     * 确认
     * @param $uid
     * @param $config_pay_id
     * @param $account
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function  confirm($uid,$id,$screenshot){
       $config =  $this->rechargeRecordData->where(['uid'=>$uid])->where(['id'=>$id])->find();
       if(empty($config)) return  Response::fail('没有这个充值订单');
       if($config['status']!=0) return  Response::fail('订单不可确认');
       if($config['expiration']<time()) return  Response::fail('订单不可确认');
        $config->status = 1;
        $config->screenshot = $screenshot;
        $res = $config->save();
        if(!$res){
            return Response::fail('操作失败');
        }
        return Response::success('操作成功');
    }

    public function  cancel($uid,$id){
       $config =  $this->rechargeRecordData->where(['uid'=>$uid])->where(['id'=>$id])->find();
       if(empty($config)) return  Response::fail('没有这个充值订单');
       if($config['status']!=0) return  Response::fail('订单不可取消');
        $config->status = 4;
        $res = $config->save();
        if(!$res){
            return Response::fail('操作失败');
        }
        return Response::success('操作成功');
    }
}
