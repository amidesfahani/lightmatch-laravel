import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
	function renderUsers(users) {
		const tbody = document.getElementById('users-table-body');
		tbody.innerHTML = '';
		users.forEach(user => {
			const wallets = user.wallets.map(wallet =>
				`<div class="mb-1">
                    <span class="font-semibold">${wallet.symbol}:</span>
                    Balance: ${parseFloat(wallet.balance).toFixed(2)},
                    Frozen: ${parseFloat(wallet.frozen_balance).toFixed(2)}
                </div>`).join('');

			const tr = document.createElement('tr');
			tr.className = 'cursor-pointer hover:bg-gray-100 user-row';
			tr.dataset.userId = user.id;
			tr.dataset.userName = user.name;
			tr.innerHTML = `
                <td class="py-2 px-4 border">${user.id}</td>
                <td class="py-2 px-4 border">${user.name}</td>
                <td class="py-2 px-4 border">${user.email}</td>
                <td class="py-2 px-4 border">${wallets}</td>
            `;
			tbody.appendChild(tr);
		});
	}

	function renderPagination(containerId, current, total, callback) {
		const container = document.getElementById(containerId);
		container.innerHTML = `
            <div class="flex justify-center space-x-2 mt-4">
                <button class="px-4 py-2 bg-gray-200 rounded ${current === 1 ? 'opacity-50 cursor-not-allowed' : ''}" 
                        ${current === 1 ? 'disabled' : ''} 
                        data-page="${current - 1}">Previous</button>
                <span class="px-4 py-2">Page ${current} of ${total}</span>
                <button class="px-4 py-2 bg-gray-200 rounded ${current === total ? 'opacity-50 cursor-not-allowed' : ''}" 
                        ${current === total ? 'disabled' : ''} 
                        data-page="${current + 1}">Next</button>
            </div>
        `;
		container.querySelectorAll('button').forEach(btn => {
			btn.addEventListener('click', () => {
				const page = parseInt(btn.dataset.page);
				callback(page);
			});
		});
	}

	function loadUsers(page = 1, perPage = 10) {
		const tbody = document.getElementById('users-table-body');
		tbody.innerHTML = `<tr><td colspan="4" class="py-2 px-4 text-center">Loading...</td></tr>`;
		axios.get('/api/users', { params: { page, per_page: perPage } })
			.then(({ data }) => {
				renderUsers(data.users);
				renderPagination('user-pagination', data.pagination.current_page, data.pagination.total_pages, loadUsers);
			})
			.catch(() => {
				tbody.innerHTML = `<tr><td colspan="4" class="py-2 px-4 text-center">Error loading users</td></tr>`;
			});
	}

	function loadOrders(userId, userName, symbol, page = 1, perPage = 10) {
		document.getElementById('user-details').style.display = 'block';
		document.getElementById('user-details-title').textContent = `Details for ${userName}`;
		const tbody = document.getElementById('orders-table-body');
		tbody.innerHTML = `<tr><td colspan="10" class="py-2 px-4 text-center">Loading...</td></tr>`;
		axios.get('/api/orders', { params: { user_id: userId, symbol, page, per_page: perPage } })
			.then(({ data }) => {
				tbody.innerHTML = '';
				if (data.orders.length === 0) {
					tbody.innerHTML = `<tr><td colspan="10" class="py-2 px-4 text-center">No orders found for ${symbol}</td></tr>`;
				} else {
					data.orders.forEach(order => {
						const tr = document.createElement('tr');
						tr.innerHTML = `
                            <td class="py-2 px-4 border">${order.id}</td>
                            <td class="py-2 px-4 border">${order.type}</td>
                            <td class="py-2 px-4 border">${parseFloat(order.amount).toFixed(8)}</td>
                            <td class="py-2 px-4 border">${parseFloat(order.price).toFixed(2)}</td>
                            <td class="py-2 px-4 border">${order.leverage}</td>
                            <td class="py-2 px-4 border">${order.status}</td>
                            <td class="py-2 px-4 border">${parseFloat(order.filled_amount).toFixed(8)}</td>
                            <td class="py-2 px-4 border">${parseFloat(order.pnl).toFixed(2)}</td>
                            <td class="py-2 px-4 border">${order.opened_at}</td>
                            <td class="py-2 px-4 border">${order.closed_at || '-'}</td>
                        `;
						tbody.appendChild(tr);
					});
				}

				renderPagination('order-pagination', data.pagination.current_page, data.pagination.total_pages, (nextPage) => {
					loadOrders(userId, userName, symbol, nextPage);
				});
			})
			.catch(() => {
				tbody.innerHTML = `<tr><td colspan="10" class="py-2 px-4 text-center">Error loading orders</td></tr>`;
			});
	}

	document.addEventListener('click', event => {
		if (event.target.closest('.user-row')) {
			document.querySelectorAll('.user-row').forEach(row => row.classList.remove('active', 'bg-blue-100'));
			const row = event.target.closest('.user-row');
			row.classList.add('active', 'bg-blue-100');

			const userId = row.dataset.userId;
			const userName = row.dataset.userName;
			const symbol = document.getElementById('symbol-select').value;
			loadOrders(userId, userName, symbol);
		}
	});

	document.getElementById('symbol-select').addEventListener('change', () => {
		const activeRow = document.querySelector('.user-row.active');
		if (activeRow) {
			const userId = activeRow.dataset.userId;
			const userName = activeRow.dataset.userName;
			const symbol = document.getElementById('symbol-select').value;
			loadOrders(userId, userName, symbol);
		}
	});

	loadUsers();
});
