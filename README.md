<p align="center">
  <a href="https://freepaid.co.za/">
    <img src="https://avatars.githubusercontent.com/u/161700599?v=4&amp;s=160" width="80" height="80" alt="Freepaid">
  </a>
</p>

<h1 align="center">Freepaid API Examples</h1>

<p align="center">
  <strong>Build with SIMcloud and AllNet Airtime</strong><br>
  Plain PHP and cURL examples for South African airtime, prepaid electricity and airtime voucher APIs, with wallet balance and mobile network lookup.
</p>

<p align="center">
  <a href="#quick-start">Quick start</a> ·
  <a href="#choose-an-example">Examples</a> ·
  <a href="#api-reference">API reference</a> ·
  <a href="#support-and-contributions">Support</a>
</p>

> [!NOTE]
> **Try purchases locally with sample responses.** The airtime, electricity and voucher demos use fictional orders and PIN placeholders. You can run them without an account or wallet funds. Balance and network lookup use a live SIMcloud API token.

<a name="run-the-purchase-examples-locally"></a>

## Quick start

Use PHP 8.5 or another [supported PHP version](https://www.php.net/supported-versions.php) with the cURL extension. Run `php --ri curl` to check. The shell examples use cURL 7.76 or later.

**1. Start the local sample server.**

~~~sh
git clone https://github.com/FreepaidPtyLtd/freepaid-api-examples.git
cd freepaid-api-examples
php -S 127.0.0.1:8080 mock.php
~~~

**2. Run an airtime order in a second terminal.**

~~~sh
cd freepaid-api-examples
php examples/simcloud-airtime.php
~~~

The script prints a queued response with `request_id: 1001`, followed by a sample order with `status: delivered`. Stop the local server with Ctrl+C when you finish.

## Choose an example

| Example | What it shows | Runs against |
| --- | --- | --- |
| [SIMcloud wallet balance](examples/simcloud-balance.php) | Read the last known balance in rand and its update time. | Live SIMcloud |
| [SIMcloud network lookup](examples/simcloud-network.php) | Find the mobile network for a South African number. | Live SIMcloud |
| [SIMcloud airtime order](examples/simcloud-airtime.php) | Queue a top-up and check delivery by request ID. | Local samples |
| [SIMcloud prepaid electricity](examples/simcloud-electricity.php) | Check meter support, submit an order and retrieve a sample token. | Local samples |
| [AllNet Airtime vouchers](examples/allnet-vouchers.php) | Place a voucher order and retrieve the sample PINs. | Local samples |

<a name="simcloud-balance-and-network-lookup"></a>

## Live SIMcloud balance and network lookup

In the SIMcloud portal, open **API → Get Token** and set `SIMCLOUD_API_TOKEN` in your shell environment. Set `MSISDN` to a South African mobile number you can look up, in local or `+27` format.

~~~sh
php examples/simcloud-balance.php
php examples/simcloud-network.php "$MSISDN"
~~~

HTTP `401` means you should check the API token. Network lookup returns HTTP `404` when it cannot match the number to a network.

<details>
<summary>cURL commands for balance and network lookup</summary>

The pipe passes the bearer header to cURL without putting the token in cURL's command-line arguments.

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

</details>

## Local purchase walkthroughs

Run these with the [local sample server](#quick-start) running. The server checks the method, URL, sample credentials and JSON request body before returning a [sample response](samples/). Each script checks order status once.

<a name="simcloud-airtime"></a>

### SIMcloud airtime API

Run `php examples/simcloud-airtime.php` to queue a sample airtime order and check delivery. The POST returns HTTP `201` with a `request_id`. The GET uses that ID to read the order status.

For a live integration, save your reference before submitting and store the returned `request_id` before polling. Continue checking while the status is `queued` or `pending`. If the POST times out or returns HTTP `409`, check by `request_id` or `orderno` if available. Otherwise, check your account history or contact SIMcloud support before submitting again.

<details>
<summary>cURL commands for an airtime order</summary>

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

</details>

<a name="simcloud-electricity"></a>

### SIMcloud prepaid electricity API

Run `php examples/simcloud-electricity.php` to check a meter, submit a sample electricity order and retrieve the voucher. The script submits only after the meter check returns `supported: true`. The sample order is R150 including a R4 fee.

A live HTTP `201` response can still have `process_status: Pending`. If a live request times out or returns HTTP `202` with `reconciliation_pending`, check the original or returned `client_reference` before placing another order. Treat the voucher as ready when the GET returns `status: success` with PINs, then present them in `entry_order`.

<details>
<summary>cURL commands for a meter check and electricity order</summary>

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

</details>

<a name="allnet-airtime-vouchers"></a>

### AllNet Airtime voucher API

Run `php examples/allnet-vouchers.php` to order two sample R10 airtime vouchers. The POST takes `quantity` and a whole-rand `value` per voucher. It returns HTTP `201` with `data.order_id`. Use that ID to fetch the order and its voucher PINs.

For live orders, save the order ID and the reference assigned by AllNet. If the response is lost before either arrives, check the vendor portal before placing another order. Keep live voucher PINs out of application logs.

<details>
<summary>cURL commands for ordering and fetching vouchers</summary>

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

</details>

<a name="api-references"></a>

## API reference

| Service | Official documentation |
| --- | --- |
| SIMcloud REST API | [Wallet balance](https://simcloud.co.za/api/balance.html) · [Network lookup](https://simcloud.co.za/api/network.html) · [Airtime](https://simcloud.co.za/api/airtime.html) · [Prepaid electricity](https://simcloud.co.za/api/electricity.html) |
| AllNet Airtime Vendor API | [Voucher ordering and retrieval](https://allnetairtime.co.za/api/docs/) |

For machine-readable contracts, use the official [SIMcloud OpenAPI specification](https://simcloud.co.za/api/openapi.yaml) and [AllNet OpenAPI specification](https://allnetairtime.co.za/api/docs/openapi.php).

## Support and contributions

- Report a problem with an example on [GitHub Issues](https://github.com/FreepaidPtyLtd/freepaid-api-examples/issues). Include the PHP or cURL version, the command, and the error with account details and credentials removed.
- For account, integration or order enquiries, use the [Freepaid contact page](https://freepaid.co.za/contact-airtime-factory.php).

### PHP style

The examples use the [PHP: The Right Way](https://phptherightway.com/) guidance for documentation and error handling. Run [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer) to check PSR-12 formatting.

~~~sh
phpcs --standard=PSR12 examples mock.php
~~~

Maintained by [Freepaid (Pty) Ltd](https://freepaid.co.za/). Sample code is available under the [MIT licence](LICENSE).
