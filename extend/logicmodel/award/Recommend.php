<?php


namespace logicmodel\award;


use comservice\Response;
use datamodel\AwardRecommend;

class Recommend extends Award
{
    private $awardRecommendData;
    private $award_id;
    public function __construct()
    {
        parent::__construct();
        $this->awardRecommendData = new AwardRecommend();
        $this->award_id = 1;
    }
    public function award($uid){
      $awardInfo =   $this->awardIsOpen($this->award_id);
      if($awardInfo === false) return false;//奖项未开起
      $info = $this->awardRecordData->where(['uid'=>$uid,'award_id'=>1])->find();
      if($info) return Response::fail('已领取奖励');
      $total_number = $awardInfo['total_number'];
      $total = $this->usersData->where(['pid'=>$uid,'is_del'=>0,'is_auth'=>1])->count();
      if($total == $total_number){
         if($awardInfo['goods_id'] > 0) $this->record($uid,$this->award_id,$awardInfo['goods_id']);
      }
      return true;
    }
}