<?php
namespace Payum\Swipe;

use Http\Message\MessageFactory;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;

class Api
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @return array
     */
    protected function doRequest($input)
    {
        /** @var \Sylius\Component\Core\Model\OrderInterface $order */
        $order = $input->getFirstModel()->getOrder();

        $public_key = '7b6200442bc5522bbcf2a36dc2a9da81';
        $private_key = '60d72289c30faf7fd2a241c38b8f2707dac477fd06671cc2b8ee8423907640cc';
        $timestamp = (string) time();

        $goods = [];
        /** @var \Sylius\Component\Core\Model\OrderItemInterface[] $items */
        $items = $order->getItems()->toArray();
        for ($i=0; $i < count($items); $i++) {
            $goods[$i]['description'] =
                $items[$i]->getProduct()->getTranslation($order->getLocaleCode())->getName();
            $goods[$i]['price'] = $items[$i]->getTotal()/100;
            $goods[$i]['quantity'] = $items[$i]->getQuantity();
        }

        $params = json_encode(
            array(
                'client' => array('email' => $order->getCustomer()->getEmail()),
                'products' => $goods,
                'success_redirect' => $input->getToken()->getAfterUrl(),
                'language' => $order->getLocaleCode()
            )
        );

        $authorization_message = $timestamp . ' POST /api/v0.5/invoices/ ' . $params;
        $authorization = hash_hmac('sha256', $authorization_message, $private_key);
        $authorization_header = $public_key . ',' . $timestamp . ',' . $authorization;
        $header = "Content-type: application/json\r\nAuthorization: " . $authorization_header;
        $options = array(
            'http' => array(
                'header' => $header,
                'method' => 'POST',
                'content' => $params,
                'ignore_errors' => TRUE,

            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents('https://swipe.lv/api/v0.5/invoices/ ', FALSE, $context);
        /*$request = $this->messageFactory->createRequest("POST", $this->getApiEndpoint(), $options, http_build_query($fields));

        $response = $this->client->send($request);

        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }*/
        return json_decode($result, true);
    }

    /** @return array */
    public function payment($model) {
        return $this->doRequest($model);
    }

    public function createWebhook() {
        $public_key = 'test';
        $private_key = 'test';
        $timestamp = (string) time();

        $params = json_encode([
            'event' => 'payment.paid',
            'url' => 'http://shop.ricberry.lv/en/order/'
        ]);

        $authorization_message = $timestamp . ' POST /api/v0.5/webhooks/ ' . $params;
        $authorization = hash_hmac('sha256', $authorization_message, $private_key);
        $authorization_header = $public_key . ',' . $timestamp . ',' . $authorization;
        $header = "Content-type: application/json\r\nAuthorization: " . $authorization_header;
        $options = array(
            'http' => array(
                'header' => $header,
                'method' => 'POST',
                'content' => $params,
                'ignore_errors' => TRUE,

            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents('https://swipe.lv/api/v0.5/webhooks/ ', FALSE, $context);

        return json_decode($result, true);
    }

    /**
     * @return string
     */
    protected function getApiEndpoint()
    {
        return 'https://swipe.lv/api/v0.5/invoices/';
    }
}
