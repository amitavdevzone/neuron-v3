# Microservices Demo: Async Event-Driven Analytics in Laravel

## Goal
Build a minimal working demo that showcases **independent deployability** between two Laravel services communicating via Redis as a message broker — no direct HTTP coupling between them.

## Architecture

```
main-app (Laravel)    →    Redis (broker)    →    analytics-app (Laravel)
  fires OrderPlaced             ↑                    custom Artisan consumer
  event → listener         raw JSON payload          reads & dispatches
  pushes raw JSON          queues up when            based on event key
  to Redis                 analytics is down
```

## Services

| Service | Role | Port |
|---|---|---|
| `main-app` | Publishes domain events (e.g. OrderPlaced) | 8000 |
| `redis` | Message broker / queue backend | 6379 |
| `analytics-app` | Consumes events, stores analytics data | 8001 |

## Key Design Decisions

### Event → Listener → Raw Push (not a Job)
main-app fires a Laravel `OrderPlaced` event. A listener catches it and pushes a **plain JSON payload** to Redis using `pushRaw`. No Job class is dispatched — this avoids serializing a Laravel Job object onto the queue.

```php
Queue::connection('redis')->pushRaw(
    json_encode([
        'event'   => 'order.placed',
        'payload' => [
            'order_id'   => $order->id,
            'amount'     => $order->amount,
            'created_at' => now()->toISOString(),
        ]
    ]),
    'analytics'
);
```

### Contract Coupling (key teaching point)
The JSON shape above IS the contract between the two services. Both sides must agree on it independently — there is no shared class or type. This is called **contract coupling** and is one of the trickier parts of async microservices. Changing the payload shape requires coordinating both services.

### Custom Consumer (not `queue:work`)
`pushRaw` puts a bare JSON string on Redis. Laravel's standard `queue:work` expects a specific envelope format (`{"job":"...", "data":{...}}`), so it cannot process raw messages out of the box.

analytics-app uses a **custom Artisan command** that reads directly from Redis and dispatches based on the `event` key:

```php
$raw = Redis::blpop('queues:analytics', 0);
$message = json_decode($raw[1], true);

match($message['event']) {
    'order.placed' => (new ProcessOrderEvent)->handle($message['payload']),
};
```

This reinforces the decoupling story — analytics-app isn't tied to Laravel's queue format. In principle, any service (Node.js, Python, etc.) could read from the same queue.

### Queue Name Convention
Both apps agree on the queue name `analytics`. This is a plain string — there is no shared dependency. It is set via environment variable `ANALYTICS_QUEUE=analytics` in both containers.

## What to Build

### docker-compose.yml
- Five services: `main-app`, `main-db` (PostgreSQL), `analytics-app`, `analytics-db` (PostgreSQL), `redis:alpine`
- Both Laravel apps have `QUEUE_CONNECTION=redis` and `REDIS_HOST=redis`
- Each app connects to its own isolated PostgreSQL instance

### main-app
- `Order` model + migration + `POST /orders` endpoint
- `OrderPlaced` event fired after order is created
- `SendOrderToAnalytics` listener that calls `Queue::connection('redis')->pushRaw(...)` on the `analytics` queue
- No knowledge of analytics-app whatsoever

### analytics-app
- `AnalyticsEvent` model + migration (stores raw event payloads)
- `ProcessOrderEvent` handler class (plain PHP class, not a Job)
- `app:consume-analytics` Artisan command — runs as a daemon, reads from Redis, dispatches to handlers via `match` on event name
- Container starts the consumer command automatically via Docker `CMD`

## Demo Script (Proving Independent Deployability)
1. Start all containers — place an order — verify analytics-app receives and stores it
2. `docker compose stop analytics-app` — place several more orders (main-app unaffected)
3. `docker compose start analytics-app` — consumer catches up, all queued events are processed
4. Main app was unaffected and unaware throughout

## Key Talking Points for the Video
- **Contract coupling** — the JSON shape is the shared contract; changing it requires coordinating both sides
- **Fire-and-forget** — main-app never waits; it doesn't know or care if analytics is down
- **Any consumer** — because the payload is raw JSON, any language/framework could consume from the same Redis queue
- **Independent deployability** — step 2–3 of the demo script is the proof

## Stack
- Laravel 11
- Redis (via `predis/predis`)
- PostgreSQL (two separate instances)
- Docker + Docker Compose

---

## Draft Notes

Step one: random user registration where we create a controller which has a request class that will validate that the email is unique and there is a name. When the validation is done we will pass the data to the controller, and from there we will call an action class called UserCreateAction which will persist the user in the database.

After that the action class will raise an event which will be named UserRegistered. The action class will also set the password of every user to be the same email address.

Next, we will have a listener for this event that was raised by the action class, and this listener should dispatch a job with a payload using pushRaw.

Random user order:
 random user order select user from a drop down and there will be one more field to add quantity which can be a numeric field and then there should be a button called order. Clicking on that order button should create a new order in the order stable with the user ID and quantity, and then and even should be raised, which is all created. Again, the order creation and the event being raised should be inside an action class.

 the front end should be a new page with route tutorial/demo-queue
  in here, I would want a page with two columns, 50-50 percent with on the left side. There will be a form for user create. I should be able to add the email and the name, and then hit a button create to create the user the back and court is already present for this. The next thing that I would want to do is the right side of the page will be one more form, which is  Request for quote. In here, I would want a drop-down with the user list and an input field accepts numeric for quantity. And then when I hit a button request, it should execute me backend code. Remove these code from web.php:
  Route::get('tutorial/quotes', 'index')->name('tutorial.quotes.index');
  Route::post('tutorial/quotes', 'store')->name('tutorial.quotes.store');
  And instead have the new route. And do delete the other files. But the form for the quote is exactly what i wanted so you can take that and then delete the files.
