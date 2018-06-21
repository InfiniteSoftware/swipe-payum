<?php
namespace Payum\Swipe\Action;

use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetHttpRequest;
use Payum\Swipe\Api;
use Sylius\Bundle\PayumBundle\Provider\PaymentDescriptionProviderInterface;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Payum\Core\Action\ActionInterface;
use Symfony\Bridge\Monolog\Logger;

class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    /**
     * @var PaymentDescriptionProviderInterface
     */
    private $paymentDescriptionProvider;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param PaymentDescriptionProviderInterface $paymentDescriptionProvider
     */
    public function __construct(PaymentDescriptionProviderInterface $paymentDescriptionProvider, Logger $logger)
    {
        $this->paymentDescriptionProvider = $paymentDescriptionProvider;
        $this->apiClass = Api::class;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        $this->gateway->execute($status = new GetStatus($model));
        if ($status->isNew()) {
            $this->logger->addInfo('Order ' .
                $request->getFirstModel()->getOrder()->getNumber() .
                ' redirected to paying form', ['Payum']);
            $details = $this->api->payment($request);
            throw new HttpRedirect($details['full_page_checkout']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
            ;
    }
}
