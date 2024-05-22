<?php
namespace App\Task;

use App\Service\Fms\FmsWalletServices;
use App\Service\Fms\FmsWalletTradeTrxServices;
use App\Service\Fms\FmsWalletTradeUsdtServices;
use App\Model\Telegram\FmsWalletTradeList;
use App\Library\Log;
// use Swoole\Coroutine\WaitGroup;
use Hyperf\Utils\Coroutine\Concurrent;

class GetFmsWalletTrxUsdtTrade
{
    public function execute()
    {
        try {
            $fmsWallet_services = new FmsWalletServices();
            $list = $fmsWallet_services->getList();      //获取收款列表

            if(!empty($list)){
                try {
                    // $this->log('getfmswallettrxtrade','-----------开始执行:拉取钱包交易列表数据，钱包总数：'.count($list).'个--------------');
                    //协程通知
                    // $wg = new WaitGroup();
                    //协程数量
                    $concurrent = new Concurrent(5);
                    
                    foreach ($list as $k => $v) {
                        // $wg->add();

                        // go(function () use ($wg,$v) {
                        //     $fmsWalletTradeTrx_services = new FmsWalletTradeTrxServices();
                        //     $res = $this->handle($wg,$v,$fmsWalletTradeTrx_services);
                            
                        //     $fmsWalletTradeUsdt_services = new FmsWalletTradeUsdtServices();
                        //     $res = $this->handleUsdt($wg,$v,$fmsWalletTradeUsdt_services);
                            
                        //     $wg->done();
                        // });
                        
                        $concurrent->create(function () use ($v) {
                            // sleep(1); //不容易被api限制
                            $fmsWalletTradeTrx_services = new FmsWalletTradeTrxServices();
                            $res = $this->handle($v,$fmsWalletTradeTrx_services);
                            
                            $fmsWalletTradeUsdt_services = new FmsWalletTradeUsdtServices();
                            $res = $this->handleUsdt($v,$fmsWalletTradeUsdt_services);
                        });
                    }
                    // $wg->wait();

                    // $this->log('getfmswallettrxtrade','-----------结束执行:拉取钱包交易列表数据--------------');
                }catch (\Exception $e){
                    $this->log('getfmswallettrxtrade','拉取失败'.$e->getMessage().'----------');
                }
            }else{
                // $this->log('getfmswallettrxtrade','-----------没有钱包需要拉取交易数据--------');
            }
        }catch (\Exception $e){
            $this->log('getfmswallettrxtrade','----------任务执行报错，请联系管理员。报错原因：----------'.$e->getMessage());
        }
    }

    public function handle($v,$fmsWalletTradeTrx_services){
        try {
            $start_time = FmsWalletTradeList::where('transferto_address',$v['recharge_wallet_addr'])->where('coin_name','trx')->orderBy('timestamp','desc')->value('timestamp');
            if(empty($start_time)){
                $start_time = 0;
            }
            $get_tx_time = strtotime($v['get_tx_time'])*1000-10;
            if($get_tx_time > $start_time){
                $start_time = $get_tx_time;
            }

            // 获取收款数据
            $res = $fmsWalletTradeTrx_services->getList($v,$start_time,thirteenTime());
            return ['code' => 200,'msg'=>'充值钱包地址：'.$v['recharge_wallet_addr'].'，成功总数'.$res['success_count'].'，失败总数'.$res['error_count']];
        }catch (\Exception $e){
            return ['code' => 400,'msg'=>$v['recharge_wallet_addr'].',拉取地址失败,'.$e->getMessage().'----------'];
        }
    }
    
    public function handleUsdt($v,$fmsWalletTradeUsdt_services){
        try {
            $start_time = FmsWalletTradeList::where('transferto_address',$v['recharge_wallet_addr'])->where('coin_name','usdt')->orderBy('timestamp','desc')->value('timestamp');
            if(empty($start_time)){
                $start_time = 0;
            }
            $get_tx_time = strtotime($v['get_tx_time'])*1000-10;
            if($get_tx_time > $start_time){
                $start_time = $get_tx_time;
            }
            // $this->log('getfmswallettrxtrade','拉usdt'.$v['recharge_wallet_addr'].$start_time);

            // 获取收款数据
            $res = $fmsWalletTradeUsdt_services->getList($v,$start_time);
            return ['code' => 200,'msg'=>'充值钱包地址：'.$v['recharge_wallet_addr'].'，成功总数'.$res['success_count'].'，失败总数'.$res['error_count']];
        }catch (\Exception $e){
            return ['code' => 400,'msg'=>$v['recharge_wallet_addr'].',拉取地址失败,'.$e->getMessage().'----------'];
        }
    }
    
    /**
     * 记入日志
     * @param $log_title [日志路径]
     * @param $message [内容，不支持数组]
     * @param $remarks [备注]
    */
    protected function log($log_title,$message,$remarks='info'){
        Log::get($remarks,$log_title)->info($message);
    }

}