<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Actions\AddFundsAction;
use App\Actions\PlaceOrderAction;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
	public function placeOrder(Request $request, PlaceOrderAction $action)
	{
		$data = $request->validate([
			'user_id' => 'required|exists:users,id',
			'symbol' => 'required|in:BTC/USD,ETH/USD',
			'type' => 'required|in:buy,sell',
			'amount' => 'required|numeric|min:0.00000001',
			'price' => 'required|numeric|min:0.00000001',
			'leverage' => 'required|numeric|min:1|max:100',
		]);

		$order = $action->execute($data);
		return response()->json(['order' => $order], 201);
	}

	public function addFunds(Request $request, AddFundsAction $action)
	{
		$data = $request->validate([
			'user_id' => 'required|exists:users,id',
			'symbol' => 'required|in:BTC/USD,ETH/USD',
			'amount' => 'required|numeric|min:0.00000001',
		]);

		$wallet = $action->execute($data['user_id'], $data['symbol'], $data['amount']);
		return response()->json(['wallet' => $wallet], 200);
	}

	public function getOrders(Request $request)
	{
		$data = $request->validate([
			'user_id' => 'required|exists:users,id',
			'symbol' => 'required|in:BTC/USD,ETH/USD',
			'per_page' => 'sometimes|integer|min:1|max:100',
			'page' => 'sometimes|integer|min:1',
		]);

		$perPage = $data['per_page'] ?? 10;
		$page = $data['page'] ?? 1;
		$tableName = 'orders_' . str_replace('/', '_', strtolower($data['symbol']));

		if (!Schema::hasTable($tableName)) {
			return response()->json([
				'orders' => [],
				'pagination' => [
					'current_page' => 1,
					'total_pages' => 1,
					'total' => 0,
					'per_page' => $perPage,
				],
			], 200);
		}

		$orders = Order::forSymbol($data['symbol'])
			->where('user_id', $data['user_id'])
			->paginate($perPage, ['*'], 'page', $page)
			->through(function ($order) use ($tableName) {
				$pnl = Redis::get("pnl:{$order->id}:{$tableName}") ?? 0;
				return [
					'id' => $order->id,
					'type' => $order->type,
					'amount' => $order->amount,
					'price' => $order->price,
					'leverage' => $order->leverage,
					'status' => $order->status,
					'filled_amount' => $order->filled_amount,
					'pnl' => (float)$pnl,
					'opened_at' => $order->opened_at,
					'closed_at' => $order->closed_at,
				];
			});

		return response()->json([
			'orders' => $orders->items(),
			'pagination' => [
				'current_page' => $orders->currentPage(),
				'total_pages' => $orders->lastPage(),
				'total' => $orders->total(),
				'per_page' => $orders->perPage(),
			],
		], 200);
	}
}
