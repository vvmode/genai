<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class EtherscanService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('blockchain.etherscan.api_key');
        $this->baseUrl = config('blockchain.etherscan.base_url');
    }

    public function getBalance(string $address): array
    {
        return $this->request([
            'module' => 'account',
            'action' => 'balance',
            'address' => $address,
            'tag' => 'latest',
        ]);
    }

    public function getTransactions(string $address, int $page = 1, int $offset = 10): array
    {
        return $this->request([
            'module' => 'account',
            'action' => 'txlist',
            'address' => $address,
            'startblock' => 0,
            'endblock' => 99999999,
            'page' => $page,
            'offset' => $offset,
            'sort' => 'desc',
        ]);
    }

    public function getTokenTransfers(string $address, int $page = 1, int $offset = 10): array
    {
        return $this->request([
            'module' => 'account',
            'action' => 'tokentx',
            'address' => $address,
            'startblock' => 0,
            'endblock' => 99999999,
            'page' => $page,
            'offset' => $offset,
            'sort' => 'desc',
        ]);
    }

    public function getGasPrice(): array
    {
        return $this->request([
            'module' => 'gastracker',
            'action' => 'gasoracle',
        ]);
    }

    public function getEthPrice(): array
    {
        return $this->request([
            'module' => 'stats',
            'action' => 'ethprice',
        ]);
    }

    public function getEthSupply(): array
    {
        return $this->request([
            'module' => 'stats',
            'action' => 'ethsupply',
        ]);
    }

    public function getTransactionByHash(string $txHash): array
    {
        return $this->request([
            'module' => 'proxy',
            'action' => 'eth_getTransactionByHash',
            'txhash' => $txHash,
        ]);
    }

    public function getBlockByNumber(int $blockNumber): array
    {
        $hexBlock = '0x' . dechex($blockNumber);

        return $this->request([
            'module' => 'proxy',
            'action' => 'eth_getBlockByNumber',
            'tag' => $hexBlock,
            'boolean' => 'true',
        ]);
    }

    private function request(array $params): array
    {
        $params['apikey'] = $this->apiKey;

        $cacheKey = 'etherscan_' . md5(json_encode($params));

        return Cache::remember($cacheKey, 30, function () use ($params) {
            $response = Http::get($this->baseUrl, $params);

            if ($response->failed()) {
                return [
                    'status' => '0',
                    'message' => 'HTTP request failed',
                    'result' => null,
                ];
            }

            return $response->json();
        });
    }

    public static function weiToEth(string $wei): string
    {
        return bcdiv($wei, '1000000000000000000', 18);
    }

    public static function gweiToEth(string $gwei): string
    {
        return bcdiv($gwei, '1000000000', 9);
    }

    public static function formatAddress(string $address): string
    {
        return substr($address, 0, 6) . '...' . substr($address, -4);
    }
}
