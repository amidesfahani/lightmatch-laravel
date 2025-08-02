from locust import HttpUser, task, between
import random

symbols = ["BTC/USD", "ETH/USD"]

class TradingUser(HttpUser):
    wait_time = between(0.5, 1)

    def on_start(self):
        self.user_id = random.randint(1, 1000)
        self.symbol = random.choice(symbols)
        self.add_funds()

    def add_funds(self):
        self.client.post("/api/wallets/add-funds", json={
            "user_id": self.user_id,
            "symbol": self.symbol,
            "amount": 100000.00
        })

    @task
    def place_order(self):
        self.client.post("/api/orders", json={
            "user_id": self.user_id,
            "symbol": self.symbol,
            "type": random.choice(["buy", "sell"]),
            "amount": round(random.uniform(0.1, 1.0), 8),
            "price": round(random.uniform(100, 300), 2),
            "leverage": random.choice([1, 5, 10, 20]),
        })
