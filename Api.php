<?php
namespace Payum\Swipe;

use Http\Message\MessageFactory;
use Payum\Core\HttpClientInterface;
use Payum\Core\Bridge\Spl\ArrayObject;

class Api
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $options = [
        'publicKey' => null,
        'privateKey' => null,
    ];

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty(array(
            'publicKey',
            'privateKey',
        ));
        $this->options = $options;
        $this->client = $client;
    }

    /**
     * @return array
     */
    protected function doRequest($input)
    {
        /** @var \Sylius\Component\Core\Model\OrderInterface $order */
        $order = $input->getFirstModel()->getOrder();

        $public_key = $this->options['publicKey'];
        $private_key = $this->options['privateKey'];
        $timestamp = $input->getFirstModel()->getDetails()['referenceId'];

        $goods = [];
        /** @var \Sylius\Component\Core\Model\OrderItemInterface[] $items */
        $items = $order->getItems()->toArray();
        for ($i=0; $i < count($items); $i++) {
            $goods[$i]['description'] =
                $items[$i]->getProduct()->getTranslation($order->getLocaleCode())->getName();
            $goods[$i]['price'] = $items[$i]->getUnitPrice() / 100;
            $goods[$i]['quantity'] = $items[$i]->getQuantity();
        }

        $shipping['description'] = $order->getShipments()->first()->getMethod()->getName();
        $shipping['price'] = $order->getShippingTotal() / 100;
        $shipping['quantity'] = 1;
        $goods[] = $shipping;

        $params = json_encode(
            array(
                'client' => array('email' => $order->getCustomer()->getEmail()),
                'products' => $goods,
                # TODO: should be router
//                'success_redirect' => 'localhost/payment/notify/unsafe/swipe',
                'language' => $order->getLocaleCode(),
                'comment' => $timestamp
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
                'ignore_errors' => true,

            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($this->getApiEndpoint(), false, $context);

        return json_decode($result, true);
    }

    /** @return array */
    public function payment($model) {
        return $this->doRequest($model);
    }

    /**
     * @return string
     */
    protected function getApiEndpoint()
    {
        return 'https://swipe.lv/api/v0.5/invoices/';
    }
}
