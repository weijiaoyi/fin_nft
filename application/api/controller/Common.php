<?php


namespace app\api\controller;



use app\admin\model\Contact;
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


}
