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
}
