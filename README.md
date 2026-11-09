# Freepaid API examples

Plain PHP and shell cURL examples for SIMcloud and AllNet Airtime. The balance and network lookup examples read from SIMcloud. Airtime, electricity and voucher purchases run against a local server that returns sample JSON. They require no account, wallet funds or live credentials.

Use PHP 8.1+ with the cURL extension for the PHP scripts. The shell examples use the `curl` command (7.76+).

## SIMcloud balance and network lookup

Set `SIMCLOUD_API_TOKEN` in your environment using your own SIMcloud API token. Set `MSISDN` to a South African mobile number you can look up, in local or `+27` format.

~~~sh
php examples/simcloud-balance.php
php examples/simcloud-network.php "$MSISDN"
~~~

The same calls with cURL:

~~~sh
curl -q --fail-with-body -sS https://simcloud.co.za/api/balance.php \
  -H "Authorization: Bearer $SIMCLOUD_API_TOKEN" \
  -H 'Accept: application/json'

curl -q --fail-with-body -sS --get https://simcloud.co.za/api/network.php \
  -H "Authorization: Bearer $SIMCLOUD_API_TOKEN" \
  -H 'Accept: application/json' \
  --data-urlencode "msisdn=$MSISDN"
~~~

The balance endpoint returns the account's last known balance and its update time. Use the lookup result to identify the mobile network before choosing the airtime product.

## Run the purchase examples locally

From the repository root, start the fixture server in one terminal.

~~~sh
php -S 127.0.0.1:8080 mock.php
~~~

In another terminal, run any of the PHP examples.

~~~sh
php examples/simcloud-airtime.php
php examples/simcloud-electricity.php
php examples/allnet-vouchers.php
~~~

The [sample responses](samples/) use made-up order IDs and non-redeemable PIN placeholders. The local server returns a fixed response for each example URL and method. Stop it with Ctrl+C when you finish.

### SIMcloud airtime

Queue an order, then use the returned `request_id` to check delivery. The sample POST returns HTTP 201 and `request_id: 1001`. The sample GET shows a delivered order.

~~~sh
curl -q --noproxy 127.0.0.1 --fail-with-body -sS \
  http://127.0.0.1:8080/api/airtime.php \
  -H 'Authorization: Bearer sample-token' \
  -H 'Content-Type: application/json' \
  --data '{"msisdn":"0821234567","network":"vodacom","amount":10,"reference":"EXAMPLE-AIRTIME-001"}'

curl -q --noproxy 127.0.0.1 --fail-with-body -sS --get \
  http://127.0.0.1:8080/api/airtime.php \
  -H 'Authorization: Bearer sample-token' \
  --data-urlencode 'request_id=1001'
~~~

In a live integration, save the returned ID before polling. A queued order can still be pending or fail. If a submit times out or returns HTTP 409, check the existing order before attempting another purchase.

### SIMcloud electricity

Check that the meter is supported, submit the purchase, then fetch its status by client reference. The sample POST accepts R150, including a R4 fee, and the sample GET shows the completed voucher.

~~~sh
curl -q --noproxy 127.0.0.1 --fail-with-body -sS --get \
  http://127.0.0.1:8080/api/electricity.php \
  -H 'Authorization: Bearer sample-token' \
  --data-urlencode 'meter_number=01234567890'

curl -q --noproxy 127.0.0.1 --fail-with-body -sS \
  http://127.0.0.1:8080/api/electricity.php \
  -H 'Authorization: Bearer sample-token' \
  -H 'Content-Type: application/json' \
  --data '{"meter_number":"01234567890","amount":150,"client_reference":"EXAMPLE-ELEC-001","recipient":"0821234567","send_sms":false}'

curl -q --noproxy 127.0.0.1 --fail-with-body -sS --get \
  http://127.0.0.1:8080/api/electricity.php \
  -H 'Authorization: Bearer sample-token' \
  --data-urlencode 'client_reference=EXAMPLE-ELEC-001'
~~~

Submit only after the meter check returns `supported: true`. If a live purchase returns HTTP 202 or times out, keep checking its original reference before considering another purchase. Treat the voucher as ready when the status is `success` and the response includes the PINs. Follow their `entry_order` when presenting them.

### AllNet Airtime vouchers

Place an order using `quantity` and a whole-rand `value` per voucher. The sample POST returns HTTP 201 and `data.order_id: 2001`. Use that ID to fetch the order and its voucher PINs.

~~~sh
curl -q --noproxy 127.0.0.1 --fail-with-body -sS \
  http://127.0.0.1:8080/api/order.php \
  -H 'X-API-Key: sample-key' \
  -H 'Content-Type: application/json' \
  --data '{"quantity":2,"value":10,"description":"Example reward order"}'

curl -q --noproxy 127.0.0.1 --fail-with-body -sS --get \
  http://127.0.0.1:8080/api/fetch_order.php \
  -H 'X-API-Key: sample-key' \
  --data-urlencode 'order_id=2001'
~~~

Save the AllNet order ID and reference. If the response is lost before either arrives, reconcile the order in the vendor portal before placing another one. Keep live voucher PINs out of application logs.

## API references

- [SIMcloud API documentation](https://simcloud.co.za/api/index.html) and [OpenAPI contract](https://simcloud.co.za/api/openapi.yaml)
- [AllNet Vendor API documentation](https://allnetairtime.co.za/api/docs/) and [OpenAPI contract](https://allnetairtime.co.za/api/docs/openapi.php)

The sample code is available under the [MIT licence](LICENSE).
