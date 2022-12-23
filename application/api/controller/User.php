<?php


namespace app\api\controller;


use logicmodel\UserLogic;
use logicmodel\WxLogic;
use think\Request;
use validate\AuthValidate;

class User extends BaseController
{
    private $userLogic;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->userLogic = new UserLogic();
    }

    /**
     * 个人信息
     * @return \think\response\Json
     */
    public function userInfo()
    {
        return json($this->userLogic->userInfo($this->userInfo));
    }

    /**
     * 编辑个人信息
     * @param $nick_name
     * @param $head_image
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editUserInfo($nick_name, $head_image)
    {
        return json($this->userLogic->editUserInfo($this->uid, $nick_name, $head_image));
    }

    /**
     * 切换手机号
     * @param $phone
     * @param $code
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function checkPhone($phone, $code)
    {
        return json($this->userLogic->checkPhone($this->userInfo, $phone, $code));
    }

    /**
     * 邀请分享
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function share()
    {
        return json($this->userLogic->share($this->userInfo));
    }

    /**
     * 修改登录密码
     * @param $password
     * @param $password_re
     * @param $code
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updatePassword($password, $password_re, $code)
    {
        return json($this->userLogic->updatePassword($this->userInfo, $password, $password_re, $code));
    }

    /**
     * 修改支付密码
     * @param $pay_password
     * @param $pay_password_re
     * @param $code
     * @param $type
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updatePayPassword($pay_password, $pay_password_re,$old_password)
    {
        return json($this->userLogic->updatePayPassword($this->userInfo, $pay_password, $pay_password_re, $old_password));
    }

    /**
     * 设置支付密码
     * @param $pay_password
     * @param $pay_password_re
     * @return \think\response\Json
     */
    public function setPayPassword($pay_password, $pay_password_re)
    {
        return json($this->userLogic->setPayPassword($this->userInfo, $pay_password, $pay_password_re));
    }

    /**
     * 团队信息
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function team($level=1,$page=1,$pagesize=10)
    {
        return json($this->userLogic->team($level,$page,$pagesize,$this->userInfo));
    }

    /**
     * 收款信息
     * @return \think\response\Json
     */
    public function collection()
    {
        return json($this->userLogic->collection($this->userInfo));
    }

    /**
     * 编辑收款信息
     * @param $bank_name
     * @param $bank_number
     * @param $bank_owner
     * @param $bank_branch
     * @param $ali_name
     * @param $ali_image
     * @param $wx_name
     * @param $wx_image
     * @param $code
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function collectMoney($bank_name, $bank_number, $bank_owner, $bank_branch, $ali_name, $ali_image, $wx_name, $wx_image, $code)
    {
        return json($this->userLogic->collectMoney($this->userInfo, $bank_name, $bank_number, $bank_owner, $bank_branch, $ali_name, $ali_image, $wx_name, $wx_image, $code));
    }

    /**
     * 问题反馈
     * @param $images
     * @param $remark
     * @return \think\response\Json
     */
    public function feedback($images, $remark)
    {
        return json($this->userLogic->feedback($this->uid, $images, $remark));
    }

    /**
     * 实名认证
     * @param $name
     * @param $card
     * @param $card_front_image
     * @param $card_back_image
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function auth($name, $card)
    {
        (new AuthValidate())->goCheck();
        return json($this->userLogic->auth($this->userInfo, $name, $card));
    }

    /**
     * 小程序授权
     * @param $code
     * @return \think\response\Json
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function wxSmallAuth($code)
    {
        return json((new WxLogic())->auth($this->userInfo, $code));
    }

}
