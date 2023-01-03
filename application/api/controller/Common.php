<?php


namespace app\api\controller;



use app\admin\model\Contact;
use app\admin\model\GoodsRank;
use comservice\Response;
use logicmodel\Web3Logic;
use think\Controller;
use Web3\Web3;

class Common extends Controller
{

    /**
     * 联系方式
     * @return \think\response\Json
     */
    public function contact()
    {
        $list = Contact::where('is_show','=',1)->where('is_del','=',0)->select();
        if($list){
            $list = collection($list)->toArray();
            $list = addWebSiteUrl($list, ['image']);
        }
        return json(Response::success('success',$list));
    }

    /**
     * NFT等级
     * @return \think\response\Json
     */
    public function nftRank()
    {
        $rank = GoodsRank::all();
        if($rank){
            $rank = collection($rank)->toArray();
            $rank = addWebSiteUrl($rank, ['image']);
        }
        return json(Response::success('success',$rank));
    }

    /**
     * 竞价规则
     * @return \think\response\Json
     */
    public function bidding()
    {
        $bidding_rules = config('site.bidding_rules');
        return json(Response::success('success',['rules'=>$bidding_rules]));
    }

    /**
     * 用户协议
     * @return \think\response\Json
     */
    public function userAgreement()
    {
        $users_content = config('site.users_content');
        return json(Response::success('success',['user_agreement'=>$users_content]));
    }


    public function biddingTiming()
    {
        $users_content = config('site.users_content');
        return json(Response::success('success',['user_agreement'=>$users_content]));
    }

    public function testWeb3()
    {
        $web3 = new Web3Logic();
        return json($web3->withdraw('0x051a9adc157cd44070c3731a77ed94a8f95a0916',1));
    }

    //通信
    public function requestclient($method,$param=[],$chanId=1,$url='https://bsc-dataseed.binance.org')
    {
        $opts = array(
            'http'=>array(
                'ignore_errors' => true, //忽略错误
                'method'=>"POST",
                'header' => "content-type:application/json",
                'timeout'=>10,
                'content' =>json_encode(array('jsonrpc' => '2.0',  'method' => $method,'params'=>$param,'id'=>$chanId)),
            )
        );
        $context = stream_context_create($opts);
        $res =file_get_contents($url, false, $context);
        return $res;
    }

}
