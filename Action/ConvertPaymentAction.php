<?php
namespace Payum\Swipe\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Bridge\Spl\ArrayObject;
use Symfony\Component\Routing\Router;

class ConvertPaymentAction implements ActionInterface
{
    use GatewayAwareTrait;

    /**
     * @var Router
     */
    private $router;

    /**
     * ConvertPaymentAction constructor.
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        # these code doing nothing
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getSource();

        $details = ArrayObject::ensureArrayObject($payment->getDetails());
        $details['referenceId'] = (string)time();
        $details['success_url'] = $this->router->generate('sylius_shop_order_thank_you', [
            # TODO: pass order localeCode
        ]);

        $request->setResult((array) $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
            ;
    }
}
