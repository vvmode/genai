<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EtherscanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockchainApiController extends Controller
{
    private EtherscanService $etherscan;

    public function __construct(EtherscanService $etherscan)
    {
        $this->etherscan = $etherscan;
    }

    public function getBalance(Request $request): JsonResponse
    {
        $request->validate(['address' => 'required|regex:/^0x[a-fA-F0-9]{40}$/']);

        $result = $this->etherscan->getBalance($request->address);

        if ($result['status'] === '1') {
            return response()->json([
                'status' => 'success',
                'address' => $request->address,
                'balance_wei' => $result['result'],
                'balance_eth' => EtherscanService::weiToEth($result['result']),
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message'] ?? 'Failed to fetch balance',
        ], 422);
    }

    public function getTransactions(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|regex:/^0x[a-fA-F0-9]{40}$/',
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
        ]);

        $result = $this->etherscan->getTransactions(
            $request->address,
            $request->integer('page', 1),
            $request->integer('limit', 10)
        );

        return response()->json([
            'status' => $result['status'] === '1' ? 'success' : 'error',
            'address' => $request->address,
            'transactions' => $result['result'] ?? [],
            'message' => $result['message'] ?? null,
        ]);
    }

    public function getTokenTransfers(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|regex:/^0x[a-fA-F0-9]{40}$/',
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
        ]);

        $result = $this->etherscan->getTokenTransfers(
            $request->address,
            $request->integer('page', 1),
            $request->integer('limit', 10)
        );

        return response()->json([
            'status' => $result['status'] === '1' ? 'success' : 'error',
            'address' => $request->address,
            'transfers' => $result['result'] ?? [],
            'message' => $result['message'] ?? null,
        ]);
    }

    public function getGasPrice(): JsonResponse
    {
        $result = $this->etherscan->getGasPrice();

        if (isset($result['result'])) {
            return response()->json([
                'status' => 'success',
                'gas_prices' => [
                    'low' => $result['result']['SafeGasPrice'] ?? null,
                    'average' => $result['result']['ProposeGasPrice'] ?? null,
                    'high' => $result['result']['FastGasPrice'] ?? null,
                ],
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to fetch gas prices'], 422);
    }

    public function getEthPrice(): JsonResponse
    {
        $result = $this->etherscan->getEthPrice();

        if (isset($result['result'])) {
            return response()->json([
                'status' => 'success',
                'price' => [
                    'eth_usd' => $result['result']['ethusd'] ?? null,
                    'eth_btc' => $result['result']['ethbtc'] ?? null,
                    'timestamp' => $result['result']['ethusd_timestamp'] ?? null,
                ],
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to fetch ETH price'], 422);
    }

    public function getEthSupply(): JsonResponse
    {
        $result = $this->etherscan->getEthSupply();

        if ($result['status'] === '1') {
            return response()->json([
                'status' => 'success',
                'supply_wei' => $result['result'],
                'supply_eth' => EtherscanService::weiToEth($result['result']),
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to fetch supply'], 422);
    }

    public function getTransaction(string $txHash): JsonResponse
    {
        if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid transaction hash'], 422);
        }

        $result = $this->etherscan->getTransactionByHash($txHash);

        return response()->json([
            'status' => isset($result['result']) ? 'success' : 'error',
            'transaction' => $result['result'] ?? null,
        ]);
    }

    public function getBlock(int $blockNumber): JsonResponse
    {
        $result = $this->etherscan->getBlockByNumber($blockNumber);

        return response()->json([
            'status' => isset($result['result']) ? 'success' : 'error',
            'block' => $result['result'] ?? null,
        ]);
    }
}
