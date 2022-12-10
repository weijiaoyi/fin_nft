<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use comservice\Response;
use logicmodel\AccountLogic;
use logicmodel\award\Recommend;
use logicmodel\MemberLogic;
use logicmodel\WalletLogic;
use think\Db;

/**
 *
 *
 * @icon fa fa-users
 */
class Users extends Backend
{

    /**
     * Users模型对象
     * @var \app\admin\model\Users
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Users;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("isDelList", $this->model->getIsDelList());
        $this->view->assign("isAuthList", $this->model->getIsAuthList());
        $this->view->assign("isBankList", $this->model->getIsBankList());
        $this->view->assign("isAliList", $this->model->getIsAliList());
        $this->view->assign("isWxList", $this->model->getIsWxList());
    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->with(['role'])
                ->where(['is_del'=>0])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','member','nick_name','head_image','phone','status','uuid','total_direct','group_person_count','achievement_money','group_achievement_money','parent_member','create_time','name','card','is_bank','is_wx','is_ali','wholesale_account','award_recommend','wallet_address']);
                $row->visible(['role']);
                $row->getRelation('role')->visible(['name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    public function add()
    {

        if(request()->isPost()){
            $data = input('post.');
            $data = $data['row'];
            $parent_member = $data['parent_member'];
            $parentInfo = $this->model->where(['is_del'=>0,'phone'=>$parent_member])->find();
            if(empty($parentInfo))  $this->error('推荐人手机号错误');
            $phone = $data['phone'];
            if(empty($phone))$this->error('手机号不能为空');
            $info = $this->model->where(['phone'=>$phone,'is_del'=>0])->find();
            if($info) $this->error('手机号已注册');
            $account = (new WalletLogic())->newAccount();
            if($account === false) return Response::fail('钱包地址生成失败');
            $member = $phone;
            $password = $data['password'];
            if(empty($password) ) $this->error('请输入登录密码');;
            $salt = rand(1111,9999);
            $pid = $parentInfo['id'];
            $userData['nick_name'] = 'zz_'.rand(111111,999999);
            $userData['member'] = $member;
            $userData['salt'] = $salt;
            $userData['password'] = md5(md5($password).$salt);
            $userData['head_image'] = defaultImage();
            $userData['uuid'] = uuid();
            $userData['pid'] = $pid;
            $userData['upid'] = $pid;
            $userData['phone'] = $phone;
            $userData['parent_member'] = $parentInfo['phone'];
            $userData['create_time'] = date('Y-m-d H:i:s');
            $userData['role_id'] = $data['role_id'];
            $userData['wallet_address'] = $account['address'];
            $userData['wallet_private_key'] = $account['private_key'];
            $userData['bsc_wallet_address'] = $account['bsc_address'];
            $userData['bsc_private_key'] = $account['bsc_private_key'];
            $userData['ht_address'] = $account['ht_address'];
            $userData['ht_private_key'] = $account['ht_private_key'];
            Db::startTrans();
            $user_id = $this->model->insertGetId($userData);
            if($user_id > 0) {
                $result =  $this->updateGroup($pid);
                if(!$result){
                    Db::rollback();
                    $this->error('注册失败');
                }
                Db::commit();
                $this->success('注册成功');

            }
            Db::rollback();
            $this->error('注册失败');
        }
        return $this->fetch();
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
        $where['id']  = ['in', $groupArr];
        $result = $this->model->where($where)->setInc('group_person_count',1); //修改团队成员
        $res = $this->model->where(['id' => $pid])->setInc('total_direct', 1);//修改直推人数
        if ($result > 0 && $res > 0) return true;
        return false;
    }

    public function del($ids = "")
    {
        $result = $this->model->where(['id'=>['in',$ids]])->update(['is_del'=>1]);
        if($result) return json(['code'=>1,'msg'=>'删除成功']);
        return json(['code'=>0,'msg'=>'删除失败']);
    }
    public function edit($ids = null)
    {
        if(request()->isPost()){
            $data = input('post.');
            $data = $data['row'];
            if(!empty($data['password'])){
                $salt = rand(1111,9999);
                $password = md5(md5($data['password']).$salt);
                $data['password'] = $password;
                $data['salt'] = $salt;
            }else{
                unset($data['password']);
            }
            if(!empty($data['pay_password'])){
                $salt = rand(1111,9999);
                $password = md5(md5($data['pay_password']).$salt);
                $data['pay_password'] = $password;
                $data['pay_salt'] = $salt;
            }else{
                unset($data['pay_password']);
            }
            $result = $this->model->where(['id'=>$ids])->update($data);
            if($result)  $this->success('修改成功');
            $this->success('修改失败');
        }
        $row = $this->model->find($ids)->toArray();
        $this->assign('row',$row);
        return  $this->fetch();
    }
    /**
     * 资金操作
     * @param $ids
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function funds($ids){
        if(request()->post()){
            $data = input('post.');
            if(empty($data['currency_id'])) return json(['code'=>0,'msg'=>'请选择要操作币种']);
            if(empty($data['type'])) return json(['code'=>0,'msg'=>'请选择操作类型']);
            if(empty($data['account'])) return json(['code'=>0,'msg'=>'请输入金额']);
            $account = $data['account'];
            if($account <= 0) return json(['code'=>0,'msg'=>'操作金额需大于0']);
            $type = $data['type'];
            $currency_id = $data['currency_id'];
            Db::startTrans();
            if($type == 1){
                $currency_id = $data['currency_id'];
                //充值
                $bil_type = '后台充值';
                $remark = '后台充值';

                $result =  (new AccountLogic())->addAccount($ids,$currency_id,$account,$bil_type,$remark);
                if($result == false){
                    Db::rollback();
                    return json(['code'=>0,'msg'=>'充值失败']);
                }
            }elseif($type == 2){
                //扣费
                //充值
                $bil_type = '后台扣费';
                $remark = '后台扣费';
                $result =  (new AccountLogic())->subAccount($ids,$currency_id,$account,$bil_type,$remark);
                if($result == false){
                    Db::rollback();
                    return json(['code'=>0,'msg'=>'账户余额不足']);
                }

            }else{
                return json(['code'=>0,'msg'=>'操作类型错误']);
            }
            $fundsData = ['uid'=>$ids,'currency_id'=>$currency_id,'account'=>$account,'type'=>$type,'create_time'=>date('Y-m-d H:i:s')];
            $result = Db::name('funds')->insertGetId($fundsData);
            if($result > 0) {
                Db::commit();
                return json(['code'=>1,'msg'=>'操作成功']);
            }
            return json(['code'=>0,'msg'=>'操作失败']);
        }
        $field = ['ua.*','c.name currency_name'];
        $where['uid'] = $ids;
        $userAccount = Db::name('users_account')->alias('ua')
            ->join('currency c','c.id = ua.currency_id')
            ->field($field)
            ->where($where)
            ->select();
        $currencyData = Db::name('currency')->where(['is_del'=>0])->select();
        $userInfo = $this->model->find($ids);
        $this->assign('userInfo',$userInfo);
        $this->assign('currencyData',$currencyData);
        $this->assign('userAccount',$userAccount);
        $this->assign('ids',$ids);
        return $this->fetch();
    }

    public function team($phone=''){
        if(request()->isAjax()){
            if(empty($phone)){
                $data = $this->model->alias('u')
                    ->join('rank r','r.id = u.rank_id')
                    ->join('role ro','ro.id = u.role_id')
                    ->where(['u.pid'=>0])
                    ->field(['u.*','r.name rank_name'])
                    ->select();
                return json($data);
            }
            $field = ['pu.*','r.name rank_name','r.name role_name'];
            $data =  $this->model->alias('u')
                ->join('users pu','u.id = pu.pid')
                ->join('role ro','ro.id = u.role_id')
                ->where(['u.phone'=>$phone,'pu.is_del'=>0])
                ->join('rank r','r.id = pu.rank_id')
                ->field($field)
                ->select();
            return $data;
        }
        return $this->fetch();
    }
    /**
     * 搜索
     * @param string $phone
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search(){
        $phone=input('phone');
        if(empty($phone)){

            $where ['u.pid'] = 0;
        }else{
            $where ['u.phone'] = $phone;
            $where['is_del'] = 0;
        }

        $data = $this->model
            ->alias('u')
            ->join('rank r','r.id = u.rank_id')
            ->join('role ro','ro.id = u.role_id')
            ->where($where)
            ->field(['u.*','r.name rank_name','ro.name role_name'])
            ->select();
        return json($data);
    }
    public function send($ids =''){
        if(request()->isPost()){
            $data = input('post.');
            $goods_id = $data['goods_id'];
           $goods =  (new \app\admin\model\Goods())->where(['id'=>$goods_id,'is_del'=>0])->find();
           if(empty($goods)) return json(['code'=>0,'msg'=>'藏品信息错误']);
            $price = $data['price'];
           if(empty($price)) $price = $goods['price'];
           //生成
            $user = ['uid'=>$ids,'goods_id'=>$goods_id,'price'=>$price,'create_time'=>date('Y-m-d H:i:s'),'is_send'=>1];
            Db::startTrans();
            $result = (new \app\admin\model\GoodsUsers())->insertGetId($user);
            if(!$result){
                Db::rollback();
                return json(['code'=>0,'msg'=>'赠送失败']);
            }
            $send = ['uid'=>$ids,'goods_id'=>$goods_id,'price'=>$price,'create_time'=>date('Y-m-d H:i:s')];
            $result = (new \app\admin\model\GoodsSend())->insertGetId($send);
            if($result){
                Db::commit();
                return json(['code'=>1,'msg'=>'赠送成功']);
            }
            Db::rollback();
            return json(['code'=>0,'msg'=>'赠送失败']);
        }
        $this->assign('ids',$ids);
        return $this->fetch();
    }
}
