<?php


namespace app\api\controller;



use app\admin\model\Contact;
use app\admin\model\GoodsRank;
use comservice\Response;
use think\Controller;

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

}
