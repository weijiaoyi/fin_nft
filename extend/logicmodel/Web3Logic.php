<?php

namespace logicmodel;

use comservice\Response;
use Web3\Contract;
use Web3\Utils;
use Web3\Web3;
use Web3p\EthereumTx\Transaction;

class Web3Logic
{
    public function withdraw($contractAddress='', $amount='1'){

        $erc721JsonFile = file_get_contents( 'web3/ERC721.json');
        $erc721Json = json_decode($erc721JsonFile);

        $web3 = new Web3('https://data-seed-prebsc-2-s3.binance.org:8545/');
        $eth = $web3->eth;
        // $chainId = 56;
        $chainId = 97;

        $privateKey = '6e573d4db43e0d0d56e686033388622c87ff9d4faa10379fca568c772466724c';
        $ownAccount = '0xE49192af9df9fB450ec48Fea21B2FE92A642D9D9';

        $contract = new Contract($web3->provider, $erc721Json->abi);
        $contract = $contract->at($contractAddress);
        $nonce = $this->getNonce($eth, $ownAccount);
        $gasPrice = '0x' . Utils::toWei('20', 'gwei')->toHex();
        $contract->at($contractAddress)->estimateGas('mint', dechex($amount*100000000), $ownAccount, [
            'from' => $ownAccount,
        ], function ($err, $result) use (&$estimatedGas) {
            if ($err !== null) {
                throw $err;
            }
            $estimatedGas = $result;
        });
        $data = $contract->getData('mint', dechex($amount*100000000), $ownAccount);
        $transaction = new Transaction([
            'nonce' => '0x' . $nonce->toHex(),
            'to' => $contractAddress,
            'gas' => '0x' . $estimatedGas->toHex(),
            'gasPrice' => $gasPrice,
            'data' => '0x' . $data,
            'chainId' => $chainId
        ]);
        $transaction->sign($privateKey);
        $txHash = '';
        $eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $ownAccount, &$txHash) {
            if ($err !== null) {
                return Response::fail($err->getMessage());
            }
            $txHash = $transaction;
        });

        $transaction = $this->confirmTx($eth, $txHash);
        if (!$transaction) {
            return Response::fail('交易未确认');
        }
        /*
        $tokenIds = [
            '340282366951795091156073022757095342089'
        ];
        $nonce = $nonce->add(Utils::toBn(1));
        $contract->at($contractAddress)->estimateGas('safeBatchTransferFrom', $ownAccount, $contractAddress, $tokenIds, '0x1', [
            'from' => $ownAccount,
        ], function ($err, $result) use (&$estimatedGas) {
            if ($err !== null) {
                throw $err;
            }
            $estimatedGas = $result;
        });
        $data = $contract->getData('safeBatchTransferFrom', $ownAccount, $contractAddress, $tokenIds, dechex($amount*100000000));
        $transaction = new Transaction([
            'nonce' => '0x' . $nonce->toHex(),
            'to' => $contractAddress,
            'gas' => '0x' . $estimatedGas->toHex(),
            'gasPrice' => $gasPrice,
            'data' => '0x' . $data,
            'chainId' => $chainId
        ]);
        $transaction->sign($privateKey);
        $txHash = '';
        $eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $ownAccount, &$txHash) {
            if ($err !== null) {
                return Response::fail($err->getMessage());
            }
            $txHash = $transaction;
        });

        $transaction = $this->confirmTx($eth, $txHash);
        if (!$transaction) {
            return Response::fail('交易未确认');
        }
        */
        return Response::success('交易成功');
    }
    public function getNonce($eth, $account) {
        $nonce = 0;
        $eth->getTransactionCount($account, function ($err, $count) use (&$nonce) {
            if ($err !== null) {
                throw $err;
            }
            $nonce = $count;
        });
        return $nonce;
    }

    public function confirmTx($eth, $txHash) {
        $transaction = null;
        $i = 0;
        while (!$transaction) {
            $transaction = $this->getTransactionReceipt($eth, $txHash);
            if ($transaction) {
                return $transaction;
            } else {
                //echo "Sleep one second and wait transaction to be confirmed" . PHP_EOL;
                sleep(1);
            }
            $i++;
            if($i>10){
                echo 'error';die;
            }
        }
    }
    public function getTransactionReceipt($eth, $txHash) {
        $tx='';
        $eth->getTransactionReceipt($txHash, function ($err, $transaction) use (&$tx) {
            if ($err !== null) {
                throw $err;
            }
            $tx = $transaction;
        });
        return $tx;
    }
    //通信
    public function requestclient($method,$param=[])
    {
        $opts = array(
            'http'=>array(
                'ignore_errors' => true, //忽略错误
                'method'=>"POST",
                'header' => "content-type:application/json",
                'timeout'=>10,
                'content' =>json_encode(array('jsonrpc' => '2.0',  'method' => $method,'params'=>$param,'id'=>1)),
            )
        );
        $context = stream_context_create($opts);
        $res =file_get_contents('https://bsc-dataseed.binance.org/', false, $context);
        return $res;
    }

}
