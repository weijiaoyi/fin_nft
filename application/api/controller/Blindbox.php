<?php


namespace app\api\controller;


use logicmodel\BlindBox as Bbox;
use think\Request;

class Blindbox extends BaseController
{
    private $bBox;
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->bBox = new Bbox();
    }

    public function open(){
        return json($this->bBox->open(1));
    }

    public function blindBoxList(){
        return json($this->bBox->blindBoxList());
    }

    public function details($id){
        return json($this->bBox->details($id));
    }

    public function openBlindBox($id=0,$number=0,$pay_password=''){
        return json($this->bBox->openBlindBox($id,$number,$this->userInfo,$pay_password));
    }

    public function nftRank(){
        return json($this->bBox->nftRank());
    }
}
