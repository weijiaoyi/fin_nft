<?php


namespace app\api\controller;


use logicmodel\ConfigLogic;
use logicmodel\SendLogic;
use logicmodel\UserLogic;
use think\Controller;
use think\Request;
use validate\ForgetPhonePasswordValidate;
use validate\RegisterValidate;

class Login extends Controller
{
    private $userLogic;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->userLogic = new UserLogic();
    }

    /**
     * 登录
     * @param $phone
     * @param $password
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function login($phone, $password)
    {
        return json($this->userLogic->login($phone, $password));
    }

    /**
     * 登录获取nonce
     * @param $address
     * @return \think\response\Json
     */
    public function metaMaskLogin($address,$invite)
    {
        return json($this->userLogic->metaMaskLogin($address,$invite));
    }

    /**
     * 授权
     * @param $address
     * @param $signature
     * @return \think\response\Json
     */
    public function mateMaskAuth($address,$signature)
    {
        return json($this->userLogic->mateMaskAuth($address,$signature));
    }

    /**
     * 验证码登录
     * @param $phone
     * @param $code
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function codeLogin($phone, $code)
    {
        return json($this->userLogic->codeLogin($phone, $code));
    }

    /**
     * 会员注册
     * @param $uuid
     * @param $phone
     * @param $password
     * @param $code
     * @return \think\response\Json
     * @throws \app\lib\exception\ParamException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function register($uuid, $phone, $password,$pay_password, $code)
    {
        (new RegisterValidate())->goCheck();
        return json($this->userLogic->register($uuid, $phone, $password, $pay_password,$code));
    }

    /**
     * 忘记密码
     * @param $phone
     * @param $code
     * @param $password
     * @return \think\response\Json
     * @throws \app\lib\exception\ParamException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function forgetPassword($phone, $password)
    {
        (new ForgetPhonePasswordValidate())->goCheck();
        return json($this->userLogic->forgetPassword($phone, $password));
    }

    /**
     * 验证手机号验证码
     * @param $phone
     * @param $code
     * @return \think\response\Json
     */
    public function validateCode($phone, $code)
    {
        return json($this->userLogic->validateCode($phone, $code));
    }

    /**
     * 发送验证码
     * * @param  $phone
     * @param int $type
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendCode($phone, $type = 1)
    {
        return json((new SendLogic())->sendPhone($phone, $type));
    }

    /**
     * 系统配置
     * @return \think\response\Json
     */
    public function config()
    {
        return json((new ConfigLogic())->config());
    }

    public function follow()
    {
        return json((new ConfigLogic())->follow());
    }

    /**
     * 微信授权登录
     * @param $wx_open_id
     * @param $wx_union_id
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function wxAuth($wx_open_id, $wx_union_id)
    {
        return json((new UserLogic())->wxAuth($wx_open_id, $wx_union_id));
    }

    /**
     * 绑定手机号
     * @param $phone
     * @param $code
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function bindPhone($phone, $code)
    {
        return json($this->userLogic->bindPhone($phone, $code));
    }

}
