<?php


namespace logicmodel;


use app\admin\model\Bill;
use app\lib\exception\Jwt;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use comservice\GetRedis;
use comservice\Response;
use datamodel\Feedback;
use datamodel\GoodsUsers;
use datamodel\Users;
use datamodel\UsersAccount;
use dh2y\qrcode\QRcode;
use Elliptic\EC;
use kornrunner\Keccak;
use logicmodel\award\Recommend;
use think\Db;
use think\Env;
use think\Request;
use Web3p\EthereumUtil\Util;

class UserLogic
{

    private $usersData;

    public function __construct()
    {
        $this->usersData = new Users();
    }

    /**
     * 注册
     * @param $uuid
     * @param $phone
     * @param $password
     * @param $code
     * @param $name
     * @param $card
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function register($uuid, $phone, $password, $pay_password, $code)
    {
        $result = validateCode($phone, $code, 1);
        if (!$result) return Response::fail('验证码输入错误');
        if (strlen(trim($password)) < 6 || strlen(trim($password)) > 16)
            return Response::fail('密码必须是在6到16位之间');
        if (strlen(trim($pay_password)) != 6)
            return Response::fail('支付密码为6位数字');
        $info = $this->usersData->where(['phone' => $phone, 'is_del' => 0])->find();
        if ($info) return Response::fail('手机号已注册');
        $account = (new WalletLogic())->newAccount();
        if ($account === false) return Response::fail('地址生成失败');
        if (!empty($uuid)) {
            $parentInfo = $this->usersData->where(['uuid' => $uuid, 'is_del' => 0])->find();
            if (empty($parentInfo)) return Response::fail('邀请码错误');
        } else {
            $parentInfo = $this->usersData->where(['id' => 1])->find();
        }
        $head_image = defaultImage();
        $salt = rand(1111, 9999);
        $uuid = uuid();
        $pid = $parentInfo['id'];
        $data['phone'] = $phone;
        $data['salt'] = $salt;
        $data['password'] = md5(md5($password) . $salt);
        $salt = rand(1111, 9999);
        $data['pay_password'] = md5(md5($pay_password) . $salt);
        $data['pay_salt'] = $salt;
        $data['head_image'] = $head_image;
        $data['nick_name'] = 'sp_' . rand(111111, 999999);
        $data['pid'] = $pid;
        $data['upid'] = $pid;
        $data['uuid'] = $uuid;
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['parent_member'] = $parentInfo['phone'];
        $data['wallet_address'] = $account['address'];
        $data['wallet_private_key'] = $account['private_key'];
        $data['bsc_wallet_address'] = $account['bsc_address'];
        $data['bsc_private_key'] = $account['bsc_private_key'];
        $data['ht_wallet_address'] = $account['ht_address'];
        $data['ht_private_key'] = $account['ht_private_key'];
        Db::startTrans();
        $user_id = $this->usersData->saveEntityAndGetId($data);
        if ($user_id > 0) {
            $result = $this->updateGroup($pid);
            if ($result) {
                Db::commit();
                return Response::success('注册成功');
            }
            Db::rollback();
            return Response::fail('注册失败');

        }
        Db::rollback();
        return Response::fail('注册失败');
    }

    /**
     * 更新团队信息
     * @param $pid
     * @return bool
     * @throws \think\Exception
     */
    private function updateGroup($pid)
    {

        $field = ['id', 'pid', 'upid'];
        $userData = (new MemberLogic())->listParent($pid, $field, 1, 0);
        $groupArr = array_column($userData, 'id');
        $where['id'] = ['in', $groupArr];
        $result = $this->usersData->updateForInc($where, 'group_person_count', 1); //修改团队成员
        $res = $this->usersData->updateForInc(['id' => $pid], 'total_direct', 1);//修改直推人数
        if ($result > 0 && $res > 0) return true;
        return false;
    }

    /**
     * 忘记密码
     * @param $phone
     * @param $password
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function forgetPassword($phone, $password)
    {
        $userInfo = $this->usersData->where(['phone' => $phone, 'is_del' => 0])->find();
        if (empty($userInfo)) return Response::fail('手机号未注册');
        $salt = rand(1111, 9999);
        $data['password'] = md5(md5($password) . $salt);
        $data['salt'] = $salt;
        $result = $this->usersData->updateByWhere(['id' => $userInfo['id']], $data);
        if ($result) return Response::success('修改成功');
        return Response::fail('修改失败');
    }

    public function validateCode($phone, $code)
    {
        $result = validateCode($phone, $code, 2);
        if ($result) return Response::success('验证成功');
        return Response::fail('验证失败');
    }

    /**
     * 会员登录
     * @param $member
     * @param $password
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function login($member, $password)
    {
        if (strlen(trim($password)) < 6 || strlen(trim($password)) > 16)
            return Response::fail('密码必须是在6到16位之间');
        $userInfo = $this->usersData->where(['phone' => $member, 'is_del' => 0])->find();
        if (empty($userInfo)) return Response::fail('手机号未注册');
        if ($userInfo['status'] == 0) return Response::fail('账号已冻结');
        if (md5(md5($password) . $userInfo['salt']) != $userInfo['password']) return Response::fail('密码错误');
        $jwt = Jwt::encode(['id'=>$userInfo['id'],'member'=>$userInfo['nick_name']], Env::get('jws.secret', '123456'));
        $redis = GetRedis::getRedis();
        $redis->setItem($jwt, $userInfo['id']);
        $result = $this->usersData->updateByWhere(['id' => $userInfo['id']], ['app_token' => $jwt, 'login_time' => date('Y-m-d H:i:s')]);
        if ($result) return Response::success('登录成功', ['app_token' => $jwt]);
        return Response::fail('登录失败');
    }



    /**
     * 验证码登录
     * @param $phone
     * @param $code
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function codeLogin($phone, $code)
    {
        //判断手机号是否注册登录
        $result = validateCode($phone, $code, 3);
        if (!$result) return Response::fail('验证码输入错误');
        $info = $this->usersData->where(['phone' => $phone, 'is_del' => 0])->find();
        if (empty($info)) {
            $parentInfo = $this->usersData->where(['id' => 1])->find();
            $account = (new WalletLogic())->newAccount();
            if ($account === false) return Response::fail('地址生成失败');
            $uuid = uuid();
            $pid = $parentInfo['id'];
            $data['phone'] = $phone;
            $data['head_image'] = defaultImage();
            $data['nick_name'] = 'sp_' . rand(111111, 999999);
            $data['pid'] = $pid;
            $data['upid'] = $pid;
            $data['uuid'] = $uuid;
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['parent_member'] = $parentInfo['phone'];
            $data['wallet_address'] = $account['address'];
            $data['wallet_private_key'] = $account['private_key'];
            $data['bsc_wallet_address'] = $account['bsc_address'];
            $data['bsc_private_key'] = $account['bsc_private_key'];
            $data['ht_wallet_address'] = $account['ht_address'];
            $data['ht_private_key'] = $account['ht_private_key'];
            Db::startTrans();
            $user_id = $this->usersData->saveEntityAndGetId($data);
            if ($user_id <= 0) {
                Db::rollback();
                return Response::fail('注册失败');
            }
            $result = $this->updateGroup($pid);
            if (!$result) {
                Db::rollback();
                return Response::fail('注册失败');
            }
            Db::commit();
        } else {
            $user_id = $info['id'];
        }
        $redis = GetRedis::getRedis();
        $app_token = uniqueNum();
        $redis->setItem($app_token, $user_id);
        $result = $this->usersData->updateByWhere(['id' => $user_id], ['app_token' => $app_token, 'login_time' => date('Y-m-d H:i:s')]);
        if ($result) return Response::success('登录成功', ['app_token' => $app_token]);
        return Response::fail('登录失败');
    }

    /**
     * 修改登录密码
     * @param $userInfo
     * @param $password
     * @param $password_re
     * @param $code
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updatePassword($userInfo, $password, $password_re, $code)
    {
        if ($password != $password_re) return Response::fail('两次密码输入不一致');
        $result = validateCode($userInfo['phone'], $code, 2);
        if (!$result) return Response::fail('验证码错误');
        $salt = rand(1111, 9999);
        $password = md5(md5($password) . $salt);
        $data['salt'] = $salt;
        $data['password'] = $password;
        $result = $this->usersData->updateByWhere(['id' => $userInfo['id']], $data);
        if ($result > 0) return Response::success('修改成功');
        return Response::fail('修改失败');
    }

    /**
     * 修改支付密码
     * @param $userInfo
     * @param $pay_password
     * @param $pay_password_re
     * @param $code
     * @param $type
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updatePayPassword($userInfo, $pay_password, $pay_password_re, $old_password)
    {
        if (md5(md5($old_password) . $userInfo['salt']) != $userInfo['pay_password']) return Response::fail('原始密码错误');
        if ($pay_password != $pay_password_re) return Response::fail('两次密码输入不一致');
        $salt = rand(1111, 9999);
        $password = md5(md5($pay_password) . $salt);
        $data['pay_salt'] = $salt;
        $data['pay_password'] = $password;
        $result = $this->usersData->updateByWhere(['id' => $userInfo['id']], $data);
        if ($result > 0) return Response::success('修改成功');
        return Response::fail('修改失败');
    }

    /**
     * 会员信息
     * @param $userInfo
     * @return array
     */
    public function userInfo($userInfo)
    {
        $sell_num = GoodsUsers::where('uid',$userInfo['id'])->where('status',2)->count();
        $nft_num = GoodsUsers::where('uid',$userInfo['id'])->whereIn('status',[1,2])->count();
        $bsc_wallet_address = $userInfo['bsc_wallet_address'];
        $trc_wallet_address = $userInfo['trc_wallet_address'];
        $erc_wallet_address = $userInfo['erc_wallet_address'];
        if(empty($userInfo['bsc_wallet_address'])) {
            $account = $this->getAccountAddress($userInfo['id']);
            $bsc_wallet_address = $account['bsc_wallet_address'];
            $trc_wallet_address = $account['trc_wallet_address'];
            $erc_wallet_address = $account['erc_wallet_address'];
        }
        $data['head_image'] = $userInfo['head_image'];
        $data['nick_name'] = $userInfo['nick_name'];
        $data['uuid'] = $userInfo['uuid'];
        $data['total_direct'] = $userInfo['total_direct'];
        $data['wallet_address'] = $userInfo['wallet_address'];
        $data['bsc_wallet_address'] = $bsc_wallet_address;
        $data['trc_wallet_address'] = $trc_wallet_address;
        $data['erc_wallet_address'] = $erc_wallet_address;
        $data['sell_num'] = $sell_num;
        $data['nft_num'] = $nft_num;
        $data['name'] = $userInfo['name'];
        $data = addWebSiteUrl($data, ['head_image']);
        $uid = $userInfo['id'];
        $team_already_auth = $this->usersData->where(['pid' => $uid, 'is_del' => 0, 'is_auth' => 1])->count();
        $data['team_already_auth'] = $team_already_auth ?: 0;
        $data['usdt'] = $userInfo['account'] ? $userInfo['account'] : 0;
        $data['ftc'] = $userInfo['ftc'] ? $userInfo['ftc'] : 0;
        $data['is_pay_password'] = empty($userInfo['pay_password']) ? 0 : 1;

        //设置背景图
        $url = config('site.register_url');
        $qrcode = new QRcode();
        $register_url = $url . '?invite=' . $userInfo['uuid'];
        $qr_code_img = $qrcode
            ->png($register_url, 'uploads/qrcode/' . $userInfo['id'] . '.png', 6)
            //->background(240, 600)
            ->text($userInfo['uuid'], 45, ['center', 1000], '#FFF296')
            ->getPath();
        $data['qr_code_img'] = str_replace('\\', '/', $qr_code_img);
        $data['register_url'] = $register_url;
        return Response::success('success', $data);
    }

    /**
     * 编辑会员信息
     * @param $uid
     * @param $nick_name
     * @param $head_image
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editUserInfo($uid, $nick_name, $head_image)
    {
        $data['head_image'] = $head_image;
        $data['nick_name'] = $nick_name;
        $data = trimWebUrl($data, ['head_image']);
        $result = $this->usersData->updateByWhere(['id' => $uid], $data);
        if ($result) return Response::success('修改成功');
        return Response::fail('编辑失败');
    }


    public function setUserFtcNum($uid, $number)
    {
        $data['ftc'] = $number;
        $result = $this->usersData->updateByWhere(['id' => $uid], $data);
        UsersAccount::where('uid',$uid)->where('currency_id',1)->update(['ftc'=>$number]);
        if ($result) return Response::success('修改成功');
        return Response::fail('编辑失败');
    }

    /**
     * 海报分享
     * @param $userInfo
     * @return array
     * @throws \think\Exception
     */
    public function share($userInfo)
    {
        //海报背景图
        $invite_image = config('site.share_image');
        $invite_image = trim($invite_image, '/');
        //设置背景图
        config('qrcode.background', $invite_image);
        $url = config('site.register_url');
        $code = new QRcode();
        $uuid = $userInfo['id'];
        $register_url = $url . '#/pages/login/reg?invite=' . $userInfo['uuid'];
        $qr_code_img = $code
            ->png($register_url, 'uploads/qrcode/' . $uuid . '.png', 6)
            ->background(240, 600)
            ->text($userInfo['uuid'], 45, ['center', 1000], '#FFF296')
            ->getPath();
        $data['qr_code_img'] = str_replace('\\', '/', $qr_code_img);
        $data['register_url'] = $register_url;
        $data['uuid'] = $uuid;
        $data = addWebSiteUrl($data, ['qr_code_img']);
        return Response::success('success', $data);
    }

    /**
     * 切换手机号
     * @param $userInfo
     * @param $phone
     * @param $code
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function checkPhone($userInfo, $phone, $code)
    {
        $result = validateCode($phone, $code, 1);
        if (!$result) return Response::fail('验证码错误');
        if ($phone == $userInfo['phone']) return Response::fail('新旧手机号不能相同');
        $info = $this->usersData->where(['phone' => $phone, 'is_del' => 0])->find();
        if ($info) return Response::fail('新手机号已绑定');
        $result = $this->usersData->updateByWhere(['id' => $userInfo['id']], ['phone' => $phone]);
        if ($result) return Response::success('更换成功');
        return Response::fail('更换失败');
    }

    /**
     * 团队信息
     * @param $userInfo
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function team($level,$page,$pagesize,$userInfo)
    {
        $uid = $userInfo['id'];
        if($level==2){
            $where['pid_2'] = $uid;
        }else{
            $where['pid'] = $uid;
        }
        $where['is_del'] = 0;
        $count = $this->usersData->where($where)->count();
        if ($count <= 0) return Response::success('暂无数据', ['count' => $count, 'data' => [], 'page' => $page, 'pagesize' => $pagesize]);
        $field = ['nick_name', 'create_time'];
        $data = $this->usersData
            ->where($where)
            ->field($field)
            ->order(['id desc'])
            ->page($page, $pagesize)
            ->select();
        return Response::success('暂无数据', ['count' => $count, 'data' => $data, 'page' => $page, 'pagesize' => $pagesize]);
    }

    public function teamStatistics($level,$uid){
        if($level==2){
            $where['pid_2'] = $uid;
            $bill_type = 9;
        }else{
            $where['pid'] = $uid;
            $bill_type = 8;
        }
        $where['is_del'] = 0;
        $num = $this->usersData->where($where)->count();
        $map['uid'] = $uid;
        $map['bill_type'] = $bill_type;
        $total_rebate = Bill::where($map)->sum('account');
        $total_rebate = $total_rebate ? $total_rebate : 0;
        $today = date('Y-m-d');
        $where['create_time'] = ['gt',$today];
        $today_num = $this->usersData->where($where)->count();
        $map['create_time'] = ['gt',$today];
        $today_rebate = Bill::where($map)->sum('account');
        $today_rebate = $today_rebate ? $today_rebate : 0;
        return Response::success('获取成功', ['total_num' => $num, 'today_num' => $today_num, 'total_rebate' => $total_rebate, 'today_rebate' => $today_rebate]);
    }
    /**
     * 收款信息
     * @param $userInfo
     * @return array
     */
    public function collection($userInfo)
    {
        $data['is_bank'] = $userInfo['is_bank'];
        $data['is_ali'] = $userInfo['is_ali'];
        $data['is_wx'] = $userInfo['is_wx'];
        $data['bank_name'] = $userInfo['bank_name'];
        $data['bank_number'] = $userInfo['bank_number'];
        $data['bank_owner'] = $userInfo['bank_owner'];
        $data['bank_branch'] = $userInfo['bank_branch'];
        $data['ali_name'] = $userInfo['ali_name'];
        $data['ali_image'] = $userInfo['ali_image'];
        $data['wx_name'] = $userInfo['wx_name'];
        $data['wx_image'] = $userInfo['wx_image'];
        $data = addWebSiteUrl($data, ['wx_image', 'ali_image']);
        return Response::success('success', $data);
    }

    /**
     * 收款信息
     * @param $userInfo
     * @param $bank_name
     * @param $bank_number
     * @param $bank_owner
     * @param $bank_branch
     * @param $ali_name
     * @param $ali_image
     * @param $wx_name
     * @param $wx_image
     * @param $code
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function collectMoney($userInfo, $bank_name, $bank_number, $bank_owner, $bank_branch, $ali_name, $ali_image, $wx_name, $wx_image, $code)
    {
        $result = validateCode($userInfo['phone'], $code, 2);
        if (!$result) return Response::fail('验证码错误');
        $data['bank_name'] = $bank_name;
        $data['bank_number'] = $bank_number;
        $data['bank_owner'] = $bank_owner;
        $data['bank_branch'] = $bank_branch;
        if (!empty($bank_name) && !empty($bank_number) && !empty($bank_owner)) {
            $data['is_bank'] = 1;
        }
        $data['ali_name'] = $ali_name;
        $data['ali_image'] = $ali_image;
        if (!empty($ali_name) && !empty($ali_image)) {
            $data['is_ali'] = 1;
        }
        $data['wx_name'] = $wx_name;
        $data['wx_image'] = $wx_image;
        if (!empty($wx_name) && !empty($wx_image)) {
            $data['is_wx'] = 1;
        }
        $data = trimWebUrl($data, ['ali_image', 'wx_image']);
        $result = $this->usersData->updateByWhere(['id' => $userInfo['id']], $data);
        if ($result) return Response::success('编辑成功');
        return Response::fail('编辑失败');
    }


    /**
     * 问题反馈
     * @param $uid
     * @param $images
     * @param $remark
     * @return array
     */
    public function feedback($uid, $images, $remark)
    {
        $data['uid'] = $uid;
        $data['images'] = $images;
        $data['remark'] = $remark;
        $data['create_time'] = date('Y-m-d H:i:s');
        $data = trimWebUrl($data, ['images']);
        $result = (new Feedback())->saveEntityAndGetId($data);
        if ($result > 0) return Response::success('反馈成功');
        return Response::fail('反馈失败');
    }

    /**
     * 认证
     * @param $userInfo
     * @param $name
     * @param $card
     * @param $card_front_image
     * @param $card_back_image
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function auth($userInfo, $name, $card)
    {
        if ($userInfo['is_auth'] == 1) return Response::fail('你已实名认证,请勿重复提交');
        $data['name'] = $name;
        $data['card'] = $card;
        // $data['card_front_image'] = $card_front_image;
        // $data['card_back_image'] = $card_back_image;
        $data['is_auth'] = 1;
        // $data = trimWebUrl($data,['card_front_image','card_back_image']);
        $result = $this->usersData->updateByWhere(['id' => $userInfo['id']], $data);
        if ($result) {
            (new Recommend())->award($userInfo['pid']);
            return Response::success('提交成功');
        }
        return Response::fail('提交失败');
    }

    /**
     * 绑定信息
     * @param $wx_open_id
     * @param $wx_union_id
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function wxAuth($wx_open_id, $wx_union_id)
    {
        $user_id = $this->usersData->where(['wx_open_id' => $wx_open_id, 'is_del' => 0])->value('id');
        if (empty($user_id)) {
            $parentInfo = $this->usersData->where(['id' => 1])->find();
            $account = (new WalletLogic())->newAccount();
            if ($account === false) return Response::fail('地址生成失败');
            $uuid = uuid();
            $pid = $parentInfo['id'];
            $data['head_image'] = defaultImage();
            $data['nick_name'] = 'sp_' . rand(111111, 999999);
            $data['pid'] = $pid;
            $data['upid'] = $pid;
            $data['uuid'] = $uuid;
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['parent_member'] = $parentInfo['phone'];
            $data['wallet_address'] = $account['address'];
            $data['wallet_private_key'] = $account['private_key'];
            $data['bsc_wallet_address'] = $account['bsc_address'];
            $data['bsc_private_key'] = $account['bsc_private_key'];
            $data['ht_wallet_address'] = $account['ht_address'];
            $data['ht_private_key'] = $account['ht_private_key'];
            $data['wx_open_id'] = $wx_open_id;
            $data['wx_union_id'] = $wx_union_id;
            Db::startTrans();
            $user_id = $this->usersData->saveEntityAndGetId($data);
            if ($user_id <= 0) {
                Db::rollback();
                return Response::fail('注册失败');
            }
            $result = $this->updateGroup($pid);
            if (!$result) {
                Db::rollback();
                return Response::fail('注册失败');
            }
            Db::commit();
        }
        $redis = GetRedis::getRedis();
        $app_token = uniqueNum();
        $redis->setItem($app_token, $user_id);
        $result = $this->usersData->updateByWhere(['id' => $user_id], ['app_token' => $app_token, 'login_time' => date('Y-m-d H:i:s')]);
        if ($result) return Response::success('登录成功', ['app_token' => $app_token]);
        return Response::fail('登录失败');
    }

    /**
     * 绑定手机号
     * @param $phone
     * @param $code
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function bindPhone($phone, $code)
    {
        $result = validateCode($phone, $code, 3);
        if (!$result) return Response::fail('验证码输入错误');
        $userInfo = $this->usersData->where(['phone' => $phone, 'is_del' => 0])->find();
        $token = Request::instance()->header('token');
        if (empty($token)) return Response::invalidLogin('请先登录');
        $redis = GetRedis::getRedis();
        $uid = $redis->getItem($token);
        if (empty($uid) || $uid == false || !$uid) return Response::invalidLogin('请先登录');
        if (empty($userInfo)) {
            $this->usersData->where(['id' => $uid])->update(['phone' => $phone]);
        } else {
            $info = $this->usersData->find($uid);
            $data['wx_open_id'] = $info['wx_open_id'];
            $data['wx_union_id'] = $info['wx_union_id'];
            $result = $this->usersData->where(['id' => $userInfo['id']])->update($data);
            $res = $this->usersData->where(['id' => $uid])->update(['is_del' => 1, 'status' => 0]);
            if (!$result || !$res) return Response::fail('绑定失败');
            $token = uniqueNum();
            $redis = GetRedis::getRedis();
            $redis->setItem($token, $userInfo['id']);
            $this->usersData->updateByWhere(['id' => $userInfo['id']], ['app_token' => $token, 'login_time' => date('Y-m-d H:i:s')]);
        }
        return Response::success('绑定成功', ['app_token' => $token]);
    }

    public function metaMaskLogin($address,$invite='')
    {
        $pid = 0;
        $pid_2 = 0;
        if (!empty($invite)) {
            $superior = $this->usersData->where(['uuid' => $invite, 'is_del' => 0])->find();
            if($superior){
                $pid = $superior['id'];
                $pid_2 = $superior['pid'];
            }
        }
        $userInfo = $this->usersData->where(['wallet_address' => $address])->find();
        $nonce = uniqid();
        if($userInfo){
            if($userInfo['nonce']) {
                $nonce = $userInfo['nonce'];
            }else{
                $this->usersData->where(['wallet_address' => $address])->update(['nonce'=>$nonce]);
            }
        }else{
            $this->usersData->insert(['wallet_address' => $address,'pid'=>$pid,'pid_2'=>$pid_2,'nonce'=>$nonce,'create_time'=> date('Y-m-d H:i:s')]);
        }
        return Response::success('获取成功', ['nonce' => $nonce]);
    }


    public function mateMaskAuth($address,$signature){
        $userInfo = $this->usersData->where(['wallet_address' => $address])->find();
        $message = $userInfo['nonce'];
        if ($this->verifySignature($message, $signature, $address)) {
            $nonce = uniqid();
            $token = array();
            $token['address'] = $address;
            $token['id'] = $userInfo['id'];
            $jwt = Jwt::encode($token, Env::get('jws.secret', '123456'));
            $redis = GetRedis::getRedis();
            $redis->setItem($jwt, $userInfo['id']);
            $nick_name= 'sp_' . rand(111111, 999999);
            $account = $this->getAccountAddress($userInfo['id']);
            if ($account === false) return Response::fail('地址生成失败');
            $account['uuid'] =  uuid();
            $account['nick_name'] = $nick_name;
            $account['uuid'] = uuid();
            $account['app_token'] = $jwt;
            $account['nonce'] = $nonce;
            $account['ftc'] = 0;
            $account['login_time'] = date('Y-m-d H:i:s');
            $this->usersData->updateByWhere(['id' => $userInfo['id']],$account);
            return Response::success('授权成功', ['app_token' => $jwt]);
        } else {
            return Response::fail('授权失败');
        }

    }

    public function getAccountAddress($uid=0){
        // Bip39
        $math = Bitcoin::getMath();
        $network = Bitcoin::getNetwork();

        $random = new Random();
        // 生成随机数(initial entropy)
        $entropy = $random->bytes(Bip39Mnemonic::MIN_ENTROPY_BYTE_LEN);
        $bip39 = MnemonicFactory::bip39();
        // 通过随机数生成助记词
        $mnemonic = $bip39->entropyToMnemonic($entropy);

        $seedGenerator = new Bip39SeedGenerator();
        // 通过助记词生成种子，传入可选加密串'hello'
        $seed = $seedGenerator->getSeed($mnemonic, 'fin_nft_'.$uid);
       // echo "seed: " . $seed->getHex() . PHP_EOL;
        $hdFactory = new HierarchicalKeyFactory();
        $master = $hdFactory->fromEntropy($seed);
        // 私钥
       // echo "master private key: " . $master->getPrivateKey()->getHex().PHP_EOL;
        // 公钥
      //  echo "master public key: " . $master->getPublicKey()->getHex().PHP_EOL.PHP_EOL;
        $util = new Util();
        // 设置路径account
        try {
            $hardened = $master->derivePath("44'/60'/0/0/0");
            $erc_privateKey = $hardened->getPrivateKey()->getHex();
            $erc_address = $util->publicKeyToAddress($util->privateKeyToPublicKey($erc_privateKey));

            $hardened = $master->derivePath("44'/60'/1/0/0");
            $trc_privateKey = $hardened->getPrivateKey()->getHex();
            $trc_address = $util->publicKeyToAddress($util->privateKeyToPublicKey($trc_privateKey));

            $hardened = $master->derivePath("44'/60'/2/0/0");
            $bsc_privateKey = $hardened->getPrivateKey()->getHex();
            $bsc_address = $util->publicKeyToAddress($util->privateKeyToPublicKey($bsc_privateKey));

            return  [
                'erc_wallet_address'=>$erc_address,'erc_private_key'=>$erc_privateKey,
                'bsc_wallet_address'=>$bsc_address,'bsc_private_key'=>$bsc_privateKey,
                'trc_wallet_address'=>$trc_address,'trc_private_key'=>$trc_privateKey
            ];
        }catch (\Exception $e){
            return false;
        }
    }
    private function verifySignature($message, $signature, $address)
    {
        $msglen = strlen($message);
        $hash   = Keccak::hash("\x19Ethereum Signed Message:\n{$msglen}{$message}", 256);
        $sign   = ["r" => substr($signature, 2, 64),
            "s" => substr($signature, 66, 64)];
        $recid  = ord(hex2bin(substr($signature, 130, 2))) - 27;
        if ($recid != ($recid & 1))
            return false;
        $ec = new EC('secp256k1');
        $pubkey = $ec->recoverPubKey($hash, $sign, $recid);
        return $address == $this->pubKeyToAddress($pubkey);
    }

    private function pubKeyToAddress($pubkey) {
        return "0x" . substr(Keccak::hash(substr(hex2bin($pubkey->encode("hex")), 1), 256), 24);
    }

    public function setPayPassword($userInfo, $pay_password, $pay_password_re)
    {
        if (!empty($userInfo['pay_password'])) return Response::fail('已经设置过支付密码');
        if ($pay_password != $pay_password_re) return Response::fail('两次密码输入不一致');
        $salt = rand(1111, 9999);
        $password = md5(md5($pay_password) . $salt);
        $data['pay_salt'] = $salt;
        $data['pay_password'] = $password;
        $result = $this->usersData->updateByWhere(['id' => $userInfo['id']], $data);
        if ($result > 0) return Response::success('设置成功');
        return Response::fail('设置失败');
    }

}
