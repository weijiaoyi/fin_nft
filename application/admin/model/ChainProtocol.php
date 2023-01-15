<?php

namespace app\admin\model;

use think\Model;
/**链上协议
 * Class ChainProtocol
 *
 * @package App\Models
 */
class ChainProtocol extends Model
{

    public function currencyProtocol()
    {
        return $this->belongsTo('CurrencyProtocol', 'id', 'chain_protocol_id','LEFT')->setEagerlyType(0);
    }
}
