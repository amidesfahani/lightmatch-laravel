<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users and Orders Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="container mx-auto p-4">
        <h1 class="mb-4 text-2xl font-bold">Users and Orders Dashboard</h1>

        <div class="mb-8">
            <h2 class="mb-2 text-xl font-semibold">Users</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full border bg-white">
                    <thead>
                        <tr>
                            <th class="border px-4 py-2">ID</th>
                            <th class="border px-4 py-2">Name</th>
                            <th class="border px-4 py-2">Email</th>
                            <th class="border px-4 py-2">Wallets</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="user-pagination"></div>
        </div>

        <div id="user-details" class="hidden">
            <h2 id="user-details-title" class="mb-2 text-xl font-semibold"></h2>
            <div class="mb-4">
                <label class="mr-2">Select Symbol:</label>
                <select id="symbol-select" class="rounded border p-2">
                    <option value="BTC/USD">BTC/USD</option>
                    <option value="ETH/USD">ETH/USD</option>
                </select>
            </div>

            <h3 class="mb-2 text-lg font-semibold">Order History</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full border bg-white">
                    <thead>
                        <tr>
                            <th class="border px-4 py-2">Order ID</th>
                            <th class="border px-4 py-2">Type</th>
                            <th class="border px-4 py-2">Amount</th>
                            <th class="border px-4 py-2">Price</th>
                            <th class="border px-4 py-2">Leverage</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Filled Amount</th>
                            <th class="border px-4 py-2">PnL</th>
                            <th class="border px-4 py-2">Opened At</th>
                            <th class="border px-4 py-2">Closed At</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body"></tbody>
                </table>
            </div>
            <div id="order-pagination"></div>
        </div>
    </div>
</body>

</html>
