<?php


namespace app\api\controller;



use app\admin\model\Contact;
use app\admin\model\GoodsRank;
use think\Controller;

class Common extends Controller
{

    /**
     * 联系方式
     * @return \think\response\Json
     */
    public function contact()
    {
        return json(Contact::where('is_show','=',1)->where('is_del','=',0)->select());
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
        return json($rank);
    }


}
