# Freepaid API examples

Plain PHP and shell cURL examples for SIMcloud and AllNet Airtime. The balance and network lookup examples read from SIMcloud. Airtime, electricity and voucher purchases run against a local server that returns sample JSON. They require no account, wallet funds or live credentials.

Use a [supported PHP version](https://www.php.net/supported-versions.php) with the cURL extension. Check it with `php --ri curl`. The shell examples use `curl` 7.76 or later.

## SIMcloud balance and network lookup

In the SIMcloud portal, open **API → Get Token**, then set `SIMCLOUD_API_TOKEN` in your shell environment. Set `MSISDN` to a South African mobile number you can look up, in local or `+27` format. Keep your token out of source files.

~~~sh
php examples/simcloud-balance.php
php examples/simcloud-network.php "$MSISDN"
~~~

The same calls with cURL. The pipe passes the bearer header to cURL without putting the token in its command-line arguments.

~~~sh
printf 'Authorization: Bearer %s\n' "$SIMCLOUD_API_TOKEN" |
  curl -q --connect-timeout 5 --max-time 20 --fail-with-body -sS \
  https://simcloud.co.za/api/balance.php --header @- \
  -H 'Accept: application/json'

printf 'Authorization: Bearer %s\n' "$SIMCLOUD_API_TOKEN" |
  curl -q --connect-timeout 5 --max-time 20 --fail-with-body -sS --get \
  https://simcloud.co.za/api/network.php --header @- \
  -H 'Accept: application/json' \
  --data-urlencode "msisdn=$MSISDN"
~~~

Both endpoints return HTTP 200 on success. The balance script prints the account's last known balance in rand and its update time. Network lookup returns the normalised number and network, or HTTP 404 when it finds no match. HTTP 401 means you should check the token.

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

The [sample responses](samples/) use fictional order IDs and PIN placeholders. The local server checks the method, URL, sample credentials and JSON body before returning the matching fixture. Each PHP script makes one status request to show how to read the result. Stop the server with Ctrl+C when you finish.

### SIMcloud airtime

Queue an order, then use the returned `request_id` to check delivery. The sample POST returns HTTP 201 and `request_id: 1001`. The sample GET shows a delivered order.

~~~sh
curl -q --noproxy 127.0.0.1 --connect-timeout 5 --max-time 20 --fail-with-body -sS \
  http://127.0.0.1:8080/api/airtime.php \
  -H 'Authorization: Bearer sample-token' \
  -H 'Content-Type: application/json' \
  --data '{"msisdn":"0821234567","network":"vodacom","amount":10,"reference":"EXAMPLE-AIRTIME-001"}'

curl -q --noproxy 127.0.0.1 --connect-timeout 5 --max-time 20 --fail-with-body -sS --get \
  http://127.0.0.1:8080/api/airtime.php \
  -H 'Authorization: Bearer sample-token' \
  --data-urlencode 'request_id=1001'
~~~

In a live integration, save your reference before submitting and save the returned `request_id` before polling. Poll while the status is `queued` or `pending`. After a timeout or HTTP 409, look up the existing order by `request_id` or `orderno` if you have one. If neither is available, check your account history or contact SIMcloud support before submitting again.

### SIMcloud electricity

Check that the meter is supported, submit the purchase, then fetch its status by client reference. The sample POST accepts R150, including a R4 fee, and the sample GET shows the completed voucher.

~~~sh
curl -q --noproxy 127.0.0.1 --connect-timeout 5 --max-time 20 --fail-with-body -sS --get \
  http://127.0.0.1:8080/api/electricity.php \
  -H 'Authorization: Bearer sample-token' \
  --data-urlencode 'meter_number=01234567890'

curl -q --noproxy 127.0.0.1 --connect-timeout 5 --max-time 20 --fail-with-body -sS \
  http://127.0.0.1:8080/api/electricity.php \
  -H 'Authorization: Bearer sample-token' \
  -H 'Content-Type: application/json' \
  --data '{"meter_number":"01234567890","amount":150,"client_reference":"EXAMPLE-ELEC-001","recipient":"0821234567","send_sms":false}'

curl -q --noproxy 127.0.0.1 --connect-timeout 5 --max-time 20 --fail-with-body -sS --get \
  http://127.0.0.1:8080/api/electricity.php \
  -H 'Authorization: Bearer sample-token' \
  --data-urlencode 'client_reference=EXAMPLE-ELEC-001'
~~~

Submit only after the meter check returns `supported: true`. An HTTP 201 acceptance can still have `process_status: Pending`. If a live purchase times out or returns HTTP 202 with `reconciliation_pending`, keep checking by the original or returned `client_reference` without submitting another purchase. Treat the voucher as ready when the GET returns `status: success` and includes the PINs. Follow their `entry_order` when presenting them.

### AllNet Airtime vouchers

Place an order using `quantity` and a whole-rand `value` per voucher. The sample POST returns HTTP 201 and `data.order_id: 2001`. Use that ID to fetch the order and its voucher PINs.

~~~sh
curl -q --noproxy 127.0.0.1 --connect-timeout 5 --max-time 20 --fail-with-body -sS \
  http://127.0.0.1:8080/api/order.php \
  -H 'X-API-Key: sample-key' \
  -H 'Content-Type: application/json' \
  --data '{"quantity":2,"value":10,"description":"Example reward order"}'

curl -q --noproxy 127.0.0.1 --connect-timeout 5 --max-time 20 --fail-with-body -sS --get \
  http://127.0.0.1:8080/api/fetch_order.php \
  -H 'X-API-Key: sample-key' \
  --data-urlencode 'order_id=2001'
~~~

Save the AllNet order ID and reference. If the response is lost before either arrives, reconcile the order in the vendor portal before placing another one. The optional `description` field labels the order and the API assigns the order reference. Keep live voucher PINs out of application logs.

## API references

| Example | Live API documentation |
| --- | --- |
| SIMcloud wallet balance | [Balance](https://simcloud.co.za/api/balance.html) |
| SIMcloud network lookup | [Network lookup](https://simcloud.co.za/api/network.html) |
| SIMcloud airtime order and status | [Airtime](https://simcloud.co.za/api/airtime.html) |
| SIMcloud meter check, electricity order and voucher | [Electricity](https://simcloud.co.za/api/electricity.html) |
| AllNet voucher order and retrieval | [AllNet Vendor API](https://allnetairtime.co.za/api/docs/) |

Machine-readable contracts are available for [SIMcloud](https://simcloud.co.za/api/openapi.yaml) and [AllNet](https://allnetairtime.co.za/api/docs/openapi.php).

The sample code is available under the [MIT licence](LICENSE).
