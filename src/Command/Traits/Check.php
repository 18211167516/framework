<?php

declare(strict_types=1);
/**
 * This file is part of MoChat.
 * @link     https://mo.chat
 * @document https://mochat.wiki
 * @contact  group@mo.chat
 * @license  https://github.com/mochat-cloud/mochat/blob/master/LICENSE
 */
namespace MoChat\Framework\Command\Traits;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
trait Check
{
    protected function wx_post($url, $param,$method='POST')
    {
        $curl = curl_init();
        $headers = [
            'User-Agent:Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
        ];

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        if ($method == 'POST'){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
            curl_setopt($curl, CURLOPT_POST, true);
        }
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }


    private function check(SymfonyStyle $output)
    {
        $appid = env('APPID','');
        $API_BASE_URL = env('API_BASE_URL','');

        if (empty($appid)){
            $output->error(".env APPID未配置");
            exit(0); 
        }

        $res = $this->wx_post("https://scrm.mo.chat//api/dashboard/merchant/check?appid={$appid}&domain={$API_BASE_URL}",'','get');

        if (empty($res))
        {
            $output->error("域名ip校验失败");
            exit(0);
        }else{
            $result = json_decode($res,true);

            if ($result['code'] !==200) {
                $output->error($res);  exit(0);
            }
        }
    }
   
}
