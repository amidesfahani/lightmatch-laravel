<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class TableManager
{
	public function addSymbolTable($symbol)
	{
		$tableName = 'orders_' . str_replace('/', '_', strtolower($symbol));
		try {
			if (!Schema::hasTable($tableName)) {
				Schema::create($tableName, function (Blueprint $table) {
					$table->id();
					$table->foreignId('user_id')->constrained()->onDelete('cascade');
					$table->enum('type', ['buy', 'sell'])->index();
					$table->decimal('amount', 18, 8);
					$table->decimal('price', 18, 8);
					$table->decimal('leverage', 5, 2)->default(1);
					$table->tinyInteger('status')->default(0)->index(); // 0=open, 1=partially, 2=filled, 3=cancelled
					$table->decimal('filled_amount', 18, 4)->default(0);
					$table->timestamp('opened_at')->nullable();
					$table->timestamp('closed_at')->nullable();
					$table->timestamps();

					$table->index(['type', 'price', 'status']);
					$table->index(['status', 'opened_at', 'created_at']);
				});
				Log::info("Table {$tableName} created successfully.");
				return true;
			}
			return false;
		} catch (\Exception $e) {
			Log::error("Failed to create table for symbol {$symbol}: " . $e->getMessage());
			return false;
		}
	}

	public function removeSymbolTable($symbol)
	{
		$tableName = 'orders_' . str_replace('/', '_', strtolower($symbol));
		try {
			Schema::dropIfExists($tableName);
			Log::info("Table {$tableName} dropped successfully.");
			return true;
		} catch (\Exception $e) {
			Log::error("Failed to drop table {$tableName}: " . $e->getMessage());
			return false;
		}
	}
}
