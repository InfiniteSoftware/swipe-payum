<?php
namespace Payum\Swipe\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Notify;
use Payum\Core\Request\Sync;
use SM\Factory\FactoryInterface;
use Sylius\Bundle\PayumBundle\Extension\UpdatePaymentStateExtension;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Symfony\Component\HttpFoundation\Request;

class NotifyAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @var PaymentRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @param PaymentRepositoryInterface $paymentRepository
     */
    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        FactoryInterface $factory
    )
    {
        $this->paymentRepository = $paymentRepository;
        $this->factory = $factory;
    }
    /**
     * {@inheritDoc}
     *
     * @param Request $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $order = $request->getModel()->request->all();
        if ($order === null) return;

        $base = $this->paymentRepository->findByState(PaymentInterface::STATE_NEW);
        $found = null;
        foreach ($base as $item) {
            if ($item->getDetails()['hash'] === $order['number']) {
                $found = $item;
            }
        }
        if ($found !== null) {
            $stateMachine = $this->factory->get($found, PaymentTransitions::GRAPH);
            $stateMachine->apply('paid');
        }
        $myfile = fopen("swiperesponse.txt", "w") or die("Unable to open file!");
        fwrite($myfile, var_export($order, true));
        fclose($myfile);


//        $model = ArrayObject::ensureArrayObject($request->getModel());

    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify/* &&
            $request->getModel() instanceof \ArrayAccess*/
            ;
    }
}
