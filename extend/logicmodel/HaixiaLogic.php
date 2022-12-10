<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/5/5 0010
 * Time: 18:06
 */

namespace logicmodel;


use comservice\GetRedis;
use comservice\Response;
use datamodel\GoodsUsers;
use datamodel\HaixiaTransfer;
use datamodel\Orders;
use datamodel\Goods;
use datamodel\Users;
use Web3\Contract;
use Web3\Contracts\Types\Address;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;
use Web3p\EthereumTx\TypeTransaction;
use xtype\Ethereum\Client;
use xtype\Ethereum\Utils;


use Web3p\EthereumTx\Transaction;

class HaixiaLogic
{
    private $goodsData;
    private $redis;
    private $appId;
    private $appkey;
    private $client;
    private $testAbi;

    public function __construct()
    {
        $this->goodsData = new Goods();
        $this->redis = GetRedis::getRedis();
        $this->appId = 'Ij0krKWs'; // 正式
        $this->appkey = '37072aacc2c98633575d3390d3051215b0d266e0'; // 正式
        $this->url = "https://backend.straitchain.com/webclient/api/develop/straits/action"; // 正式
        $this->companyAddress = "0xbef20d477deab5fa2fdf0517fae42d2a2af33ee0"; // 公司的通行证地址
        $this->client = new Client('https://kovan.infura.io/v3/a0d810fdff64493baba47278f3ebad27');
        $this->infura = 'https://mainnet.infura.io/v3/abb9e2b57a964a81b59e8c9ef30cfad1';
        $this->requestId = 1;
        $this->setTestAbi();
    }

    // 获取指定地址发生的交易数量，即发送交易使用的nonce          {"error":null,"id":"8","jsonrpc":"2.0","result":"0x0"}
    public function scs_getTransactionCount($from_addr)
    {
//        $params_canshu = ["0x9fe047d6a967b051bb76f5fc03278a163db7ef3b", "pending"];
        $params_canshu = [$from_addr, "latest"];
        $paramsAll = $this->getParams("scs_getTransactionCount", $params_canshu, $this->requestId++);
        $result = $this->json_post($this->url, $paramsAll);
        $result = json_decode($result, true);
        return $result;
    }

    // 获取当前系统推荐 gas 价格              {"error":null,"id":"7","jsonrpc":"2.0","result":"0x83156a3e07"}
    public function scs_gasPrice()
    {
        $params_canshu = [];
        $paramsAll = $this->getParams("scs_gasPrice", $params_canshu, $this->requestId++);
        $result = $this->json_post($this->url, $paramsAll);
        $result = json_decode($result, true);
        return $result;
    }

    // 交易是否请求成功
    public function scs_getTransactionReceipt($code)
    {
        $params_canshu = [$code];
        $paramsAll = $this->getParams("scs_getTransactionReceipt", $params_canshu, $this->requestId++);
        $result = $this->json_post($this->url, $paramsAll);
        $result = json_decode($result, true);
        if ($result['result']) return true;
        return false;
//        return $result;
    }

    // 交易转移 支付，官方出售和个人出售
    public function transactionGood($orderId, $result)
    {
        $ordersData = new Orders();
        $info = $ordersData->where(['id' => $orderId])->find();
        return $this->transactionGood_1($info['sale_uid'], $info['buy_uid'], $result);
    }

    // 交易转移 赠送
    public function transactionGood_2($sale_uid, $buy_uid, $goodUserId)
    {
        return $this->transactionGood_1($sale_uid, $buy_uid, $goodUserId);
    }

    // 交易转移 - 出售者默认uid=1，但是记日志要记实际的人
    public function transactionGood_1($real_sale_uid, $buy_uid, $goodUserId)
    {
        $usersData = new Users();
        $sale_uid = 1;
        $sale_user = $usersData->where(['id' => $sale_uid])->find();
        $buy_user = $usersData->where(['id' => $buy_uid])->find();

        $from_user_addr = $sale_user['wallet_address'];
        $credentials = $sale_user['wallet_private_key'];
        $to_user_addr = $buy_user['wallet_address'];

//        $from_user_addr = "0xc4244f49522c32e6181b759f35be5efa2f19d7f9";
//        $credentials = "09f51b8fd9e4124e1b80e4ffd475a5a542a438177fed9d4f10d626958e16b1da";

        $result_1 = $this->scs_getTransactionCount($from_user_addr);
        $scs_getTransactionCount = $result_1['result'];

        $result_2 = $this->scs_gasPrice();
        $scs_gasPrice = $result_2['result'];

        $goodsUsersData = new GoodsUsers();
        $info = $goodsUsersData->alias('gu')
            ->join('goods g', 'g.id = gu.goods_id')
            ->where(['gu.id' => $goodUserId])
            ->field(['g.*', 'gu.number number', 'gu.goods_number goods_number'])
            ->find();

        $contract_addr = $info['contract_address'];
        $tokenId = (int)((string)$info['number']);
//        if ($tokenId <=0 || $tokenId > $info['stock']) {
//            return false;
//            $tokenId = $info['stock'];
//        }

//        $tokenId = (int)((string)$info['number']);
//        $blockchain = json_decode($info['blockchain'], true);
//        $tokenId = isset($blockchain[$tokenId-1]) ? $blockchain[$tokenId-1]['tokenId'] : 0;
//        if ( ! $tokenId) return false;

//        $tokenId = 1;// 暂定1，逻辑还没有搞清楚

        $result_3 = $this->scs_sendRawTransaction_6($from_user_addr, $to_user_addr, $tokenId, $contract_addr, $scs_gasPrice, $scs_getTransactionCount, $credentials);
//        var_dump($result_3);
        $time = date('Y-m-d H:i:s');
        $haixiaTransfer['uid'] = $real_sale_uid;
        $haixiaTransfer['target_uid'] = $buy_uid;
        $haixiaTransfer['goods_users_id'] = $goodUserId;
        $haixiaTransfer['goods_number'] = $info['goods_number'];
        $haixiaTransfer['number'] = $info['number'];
        $haixiaTransfer['goods_id'] = $info['id'];
        $haixiaTransfer['create_time'] = $time;
        $haixiaTransfer['sucKey'] = $result_3['result'] ? $result_3['result'] : "";
        $result_insert = $haixiaTransferData->insertGetId($haixiaTransfer);

        return $result_insert ? true : false;

//        return $result_3;
//        var_dump('$result_3');
//        echo json_encode($result_3);
//        return $result_3;//clrTODO

//        if ( ! ($result_3 && $result_3['result'])) return false;

//        sleep(10);
//        $texId = $result_3['result'];
//        $result_4 = $this->scs_getTransactionReceipt($texId);
//
//        var_dump('$result_4');
//        echo json_encode($result_4);
//
//        if ( ! ($result_4 && $result_4['result'])) return false;
//        if ($result_4['result']) return true;
    }

// 转移 NFT（nft 所有者可执行操作）
    public function scs_sendRawTransaction_6($from_addr, $to_addr, $tokenId, $contract_addr, $gasPrice, $transactionCount, $credentials)
    {
        $gasLimit = "0x" . dechex("110000");
        $tokenId = "0x" . dechex($tokenId);
//        $transactionCount = "0x".dechex($transactionCount);

        $client = $this->client;
        $client->addPrivateKeys([$from_addr => $credentials]);

        $web3 = new Web3(new HttpProvider(new HttpRequestManager($this->infura, 5))); // 'http://localhost:8545'
        $contract = new Contract($web3->provider, $this->testAbi); // $contract_abi

        $data = '0x' . $contract->getData('transferFrom', $from_addr, $to_addr, $tokenId);

        $trans = [
            "from" => $from_addr,
            "to" => $contract_addr,
            "data" => $data,
        ];

        $trans['gas'] = $gasLimit;
        $trans['gasPrice'] = $gasPrice;
        $trans['nonce'] = $transactionCount;

        $str = $client->sendTransaction($trans);

        $paramsAll = $this->getParams("scs_sendRawTransaction", [$str], $this->requestId++);
        $result = $this->json_post($this->url, $paramsAll);
        $result = json_decode($result, true);
        return $result;
    }


    public function scs_sendRawTransaction_bac($from_addr, $to_addr, $tokenId, $contract_addr, $gasPrice, $transactionCount, $credentials)
    {
//        [{"hash":"0x9a22df89185991a378e17c78a2f869b021671bfc5d309467db1c4cf9498af06e","tokenId":1},{"hash":"0xe8756d5949a3c6651523fee0fa42a92dff2d1f70e59c094c5c8475171dfe95b1","tokenId":2}]

        $gasLimit = "0x" . dechex("1110000");
        $tokenId = "0x" . dechex($tokenId);
        $transactionCount = "0x" . dechex($transactionCount);
//        var_dump('$transactionCount--' . $transactionCount);
//        var_dump('$tokenId--' . $tokenId);
//        var_dump('$from_addr--' . $from_addr);
//        var_dump('$to_addr--' . $to_addr);

        $to_addr = Utils::pubKeyToAddress(substr($to_addr, 2));
        $from_addr = Utils::pubKeyToAddress(substr($from_addr, 2));
        $contract_addr = Utils::pubKeyToAddress(substr($contract_addr, 2));

        $web3 = new Web3(new HttpProvider(new HttpRequestManager($this->infura, 5))); // 'http://localhost:8545'
        $contract = new Contract($web3->provider, $this->testAbi); // $contract_abi
        $data = '0x' . $contract->getData('transferFrom', $from_addr, $to_addr, $tokenId); // 123456789 _TRANSFERFROM transfer transferfrom '0x' .
//        $data = '0x' . $contract->at($contract_addr)->getData('transfer', $from_addr, '0x75BCD15'); // '0x75BCD15'

        $txParams = [
            "from" => $from_addr,
            "to" => $contract_addr,
            "value" => Utils::ethToWei('0.01', true),

            'nonce' => $transactionCount,
            'gasPrice' => $gasPrice,
            'gas' => $gasLimit,
//            'to' => $contract_addr,
//            "value" => "",
            'data' => $data,
        ];
//        $txParams = array_values($txParams);

        $transaction = new Transaction($txParams);
//        $transaction = new TypeTransaction($txParams);
        $signedTransaction = $transaction->sign($credentials);
        $signedTransaction = '0x' . $signedTransaction;

//        var_dump('$signedTransaction');
//        var_dump($signedTransaction);

//        $web3 = new Web3(new HttpProvider(new HttpRequestManager($infura,5)));
//        $contract = new Contract($web3->provider, $contract_abi);
//        $data = '0x' . $contract->at($contract_addr)->getData('transfer', $to_addr, '0x75BCD15'));
//        $txParams = [
//            'from' => $from_addr,
//            'to' => $contract_addr,
//            'value' => '0x0',
//            'nonce' => dec_to_hex($from_addr_nonce->toString()),
//            'gas' => $contract_transfer_gas,
//            'gasPrice' => dec_to_hex($gas_price->toString()),
//            'chainId' => $chain_id,
//            'data' => $data,
//        ];
//        $transaction = new Transaction($txParams);
//        $signedTransaction = $transaction->sign($from_addr_private_key);
//        '0x'. $signedTransaction

        $paramsAll = $this->getParams("scs_sendRawTransaction", [$signedTransaction], $this->requestId++);
        $result = $this->json_post($this->url, $paramsAll);
        $result = json_decode($result, true);
        return $result;
    }

    // 就是转移owner的接口： 为签名交易创建一个新的消息调用交易或合约   DATA - 20 字节，地址
//    public function scs_sendRawTransaction() {
//        $params_canshu = ["data",];
//        $paramsAll = $this->getParams("scs_sendRawTransaction", $params_canshu, 5);
//        $result = $this->json_post($this->url, $paramsAll);
//        $result = json_decode($result, true);
//        return $result;
//    }


    // 根据交易 hash 获取 token 数组   txId – 交易 hash --- 铸造接口返回的，针对主产品的唯一标识码
    public function scs_getTokenByHash($goodOnlyCode)
    {
        $params_canshu = [$goodOnlyCode];
//        var_dump('scs_getTokenByHash');
//        var_dump($params_canshu);
        $paramsAll = $this->getParams("scs_getTokenByHash", $params_canshu, $this->requestId++);
        $result = $this->json_post($this->url, $paramsAll);
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * 铸造 nft
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function scs_nft_mint($name, $imgUrl, $num, $contractAddress, $goodId)
    {
        $msg = [
            "attributes" => [],
            "image" => 'https://nft2.mianqianba.com' . $imgUrl,
            "name" => $name,
        ];

        $id = $goodId;
        $filename = $id . ".json";
        if (!file_exists(ROOT_PATH . 'public/ntffiles')) {
            mkdir(ROOT_PATH . 'public/ntffiles', 0777, true);
        }
        // 内容写入文件
        $file = ROOT_PATH . "public/ntffiles/" . $filename;
        $myfile = fopen($file, 'w'); //查看有无该文件，没有则创建
        fwrite($myfile, json_encode($msg)); //要写入文件的内容
        fclose($myfile); //关闭文件

        $appId = $this->appId;
        $nftName = $name;
        $cid = "";
        $nftURI = "http://app.xh8896.com/ntffiles/" . $filename;
        $copyright = "";
        $issuer = "";
        $operator = "";
        $remark = "";
        $count = $num; // "message":"铸造数量需与合约最大数量保持一致"
        $owner = $this->companyAddress;
        $collectSn = "-1";
        $serviceId = "";

        $params_canshu = [
            $appId,
            $nftName,
            $cid,
            $nftURI,
            $copyright,
            $issuer,
            $operator,
            $remark,
            $count,
            $owner,
            $contractAddress,
            $collectSn,
            $serviceId,
        ];

        $sign = $this->pinjie($params_canshu);
        $params_canshu[] = $sign;

        $paramsAll = $this->getParams("scs_nft_mint", $params_canshu, $this->requestId++);

//        var_dump('$paramsAll');
//        echo json_encode($paramsAll);

        $result = $this->json_post($this->url, $paramsAll);
        $result = json_decode($result, true);
        return $result;
    }

    // 根据交易 hash 查询合约地址   txId – 交易 hash
    public function scs_contractAddressByHash($jiaoyiHash)
    {
        $params_canshu = [$jiaoyiHash];
        $paramsAll = $this->getParams("scs_contractAddressByHash", $params_canshu, $this->requestId++);
        $result = $this->json_post($this->url, $paramsAll);
        $result = json_decode($result, true);
        return $result;
    }

    // 部署合约
    public function scs_deploy_contract($num)
    {
        $params_canshu = [$this->companyAddress, (string)$num, $this->appId];
        $paramsAll = $this->getParams("scs_deploy_contract", $params_canshu, $this->requestId++);
        $result = $this->json_post($this->url, $paramsAll);
        $result = json_decode($result, true);
        return $result;
    }

    // 后台添加，然后铸造start
    public function productZhuzaoStart($goodInfo)
    {
        $info = [];
        if ($goodInfo['token_id']) {
            $goodOnlyCode = $goodInfo['token_id']; // 针对主产品的唯一标识码
            $result_4 = $this->scs_getTokenByHash($goodOnlyCode);
            if (isset($result_4['error']['message'])) var_dump($result_4['error']['message']);
            if ($result_4 && $result_4['result']) {
                $info['blockchain'] = json_encode($result_4['result']);
                return $info;
            }
        } else {
            return $this->productZhuzao($goodInfo);
        }
    }

    // 后台添加，然后铸造
    public function productZhuzao($goodInfo)
    {
        // 获得的全部参数
        $info = [];
        $name = $goodInfo['name'];
        $imgUrl = $goodInfo['image'];
        $num = $goodInfo['stock'];
        $goodId = $goodInfo['id'];

        // 部署合约
        $jiaoyiHash = $goodInfo['contract_address_url'];
        if (!$jiaoyiHash) {
            $result_1 = $this->scs_deploy_contract($num);

            if (isset($result_1['error']['message'])) var_dump($result_1['error']['message']);
            if ($result_1 && $result_1['result']) {
                $jiaoyiHash = $result_1['result'];
                $info['contract_address_url'] = $jiaoyiHash; // 交易 hash
                $info['casting_time'] = date('Y-m-d H:i:s', time());
                sleep(1);
            }
        }

        if ($jiaoyiHash) {
            $contractAddress = $goodInfo['contract_address'];

            if (!$contractAddress) {
                $result_2 = $this->scs_contractAddressByHash($jiaoyiHash); // 根据交易 hash 查询合约地址   txId – 交易 hash
                if ($result_2 && $result_2['result']) {
                    $contractAddress = $result_2['result'];
                    $info['contract_address'] = $contractAddress;
                    sleep(1);
                }
            }

            if ($contractAddress) {
                // 铸造 nft
                $result_3 = $this->scs_nft_mint($name, $imgUrl, $num, $contractAddress, $goodId);
                echo 'hello:' . json_encode($result_3,JSON_UNESCAPED_UNICODE);exit;
                if ($result_3 && $result_3['result']) {
                    $goodOnlyCode = $result_3['result'];
                    $info['token_id'] = $goodOnlyCode; // 针对主产品的唯一标识码
                    sleep(1);

                    $result_4 = $this->scs_getTokenByHash($goodOnlyCode);
                    if ($result_4 && $result_4['result']) {
                        $info['blockchain'] = json_encode($result_4['result']);
                        return $info;
                    }
                }
            }
        }

        return $info;
    }

    // curl post-json 请求
    function json_post($url, $data = NULL)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!$data) {
            return 'data is null';
        }
        if (is_array($data)) {
            $data = json_encode($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($data),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);

        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;
    }

    // 签名的时候拼接字符串
    public function pinjie($params)
    {
        $paramsStr = implode($params, '&');
        $paramsStr .= '&' . $this->appkey;
        $paramsStr = md5($paramsStr); // 第二个参数加入true。获取16为二进制字符串   false32位    例子是java代码，java默认15位
        return $paramsStr;
    }

    public function getParams($method, $params, $id)
    {
        $paramsAll = [
            'jsonrpc' => "2.0",
            "method" => $method,
            "params" => $params,
            "id" => $id,
        ];
        return $paramsAll;
    }

    public function setTestAbi()
    {
        $this->testAbi = '[
                                {
                                  "constant": true,
                                  "inputs": [],
                                  "name": "name",
                                  "outputs": [
                                    {
                                      "name": "",
                                      "type": "string"
                                    }
                                  ],
                                  "payable": false,
                                  "stateMutability": "view",
                                  "type": "function"
                                },
                                {
                                  "constant": false,
                                  "inputs": [
                                    {
                                      "name": "_spender",
                                      "type": "address"
                                    },
                                    {
                                      "name": "_value",
                                      "type": "uint256"
                                    }
                                  ],
                                  "name": "approve",
                                  "outputs": [
                                    {
                                      "name": "success",
                                      "type": "bool"
                                    }
                                  ],
                                  "payable": false,
                                  "stateMutability": "nonpayable",
                                  "type": "function"
                                },
                                {
                                  "constant": true,
                                  "inputs": [],
                                  "name": "totalSupply",
                                  "outputs": [
                                    {
                                      "name": "",
                                      "type": "uint256"
                                    }
                                  ],
                                  "payable": false,
                                  "stateMutability": "view",
                                  "type": "function"
                                },
                                {
                                  "constant": false,
                                  "inputs": [
                                    {
                                      "name": "_from",
                                      "type": "address"
                                    },
                                    {
                                      "name": "_to",
                                      "type": "address"
                                    },
                                    {
                                      "name": "_value",
                                      "type": "uint256"
                                    }
                                  ],
                                  "name": "transferFrom",
                                  "outputs": [
                                    {
                                      "name": "success",
                                      "type": "bool"
                                    }
                                  ],
                                  "payable": false,
                                  "stateMutability": "nonpayable",
                                  "type": "function"
                                },
                                {
                                  "constant": true,
                                  "inputs": [],
                                  "name": "decimals",
                                  "outputs": [
                                    {
                                      "name": "",
                                      "type": "uint8"
                                    }
                                  ],
                                  "payable": false,
                                  "stateMutability": "view",
                                  "type": "function"
                                },
                                {
                                  "constant": true,
                                  "inputs": [],
                                  "name": "standard",
                                  "outputs": [
                                    {
                                      "name": "",
                                      "type": "string"
                                    }
                                  ],
                                  "payable": false,
                                  "stateMutability": "view",
                                  "type": "function"
                                },
                                {
                                  "constant": true,
                                  "inputs": [
                                    {
                                      "name": "",
                                      "type": "address"
                                    }
                                  ],
                                  "name": "balanceOf",
                                  "outputs": [
                                    {
                                      "name": "",
                                      "type": "uint256"
                                    }
                                  ],
                                  "payable": false,
                                  "stateMutability": "view",
                                  "type": "function"
                                },
                                {
                                  "constant": true,
                                  "inputs": [],
                                  "name": "symbol",
                                  "outputs": [
                                    {
                                      "name": "",
                                      "type": "string"
                                    }
                                  ],
                                  "payable": false,
                                  "stateMutability": "view",
                                  "type": "function"
                                },
                                {
                                  "constant": false,
                                  "inputs": [
                                    {
                                      "name": "_to",
                                      "type": "address"
                                    },
                                    {
                                      "name": "_value",
                                      "type": "uint256"
                                    }
                                  ],
                                  "name": "transfer",
                                  "outputs": [],
                                  "payable": false,
                                  "stateMutability": "nonpayable",
                                  "type": "function"
                                },
                                {
                                  "constant": true,
                                  "inputs": [
                                    {
                                      "name": "",
                                      "type": "address"
                                    },
                                    {
                                      "name": "",
                                      "type": "address"
                                    }
                                  ],
                                  "name": "allowance",
                                  "outputs": [
                                    {
                                      "name": "",
                                      "type": "uint256"
                                    }
                                  ],
                                  "payable": false,
                                  "stateMutability": "view",
                                  "type": "function"
                                },
                                {
                                  "inputs": [
                                    {
                                      "name": "initialSupply",
                                      "type": "uint256"
                                    },
                                    {
                                      "name": "tokenName",
                                      "type": "string"
                                    },
                                    {
                                      "name": "decimalUnits",
                                      "type": "uint8"
                                    },
                                    {
                                      "name": "tokenSymbol",
                                      "type": "string"
                                    }
                                  ],
                                  "payable": false,
                                  "stateMutability": "nonpayable",
                                  "type": "constructor"
                                },
                                {
                                  "anonymous": false,
                                  "inputs": [
                                    {
                                      "indexed": true,
                                      "name": "from",
                                      "type": "address"
                                    },
                                    {
                                      "indexed": true,
                                      "name": "to",
                                      "type": "address"
                                    },
                                    {
                                      "indexed": false,
                                      "name": "value",
                                      "type": "uint256"
                                    }
                                  ],
                                  "name": "Transfer",
                                  "type": "event"
                                },
                                {
                                  "anonymous": false,
                                  "inputs": [
                                    {
                                      "indexed": true,
                                      "name": "_owner",
                                      "type": "address"
                                    },
                                    {
                                      "indexed": true,
                                      "name": "_spender",
                                      "type": "address"
                                    },
                                    {
                                      "indexed": false,
                                      "name": "_value",
                                      "type": "uint256"
                                    }
                                  ],
                                  "name": "Approval",
                                  "type": "event"
                                }
                            ]';
    }
}
