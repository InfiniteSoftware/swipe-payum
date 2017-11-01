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
use Symfony\Bridge\Monolog\Logger;
use \Doctrine\ORM\EntityManager;

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

    /** @var \Symfony\Bridge\Monolog\Logger  */
    private $logger;
    /** @var \Doctrine\ORM\EntityManager  */
    private $manager;

    /**
     * @param PaymentRepositoryInterface $paymentRepository
     */
    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        FactoryInterface $factory,
        Logger $logger,
        EntityManager $manager
    )
    {
        $this->paymentRepository = $paymentRepository;
        $this->factory = $factory;
        $this->logger = $logger;
        $this->manager = $manager;
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
            $this->logger->critical($stateMachine->apply('complete'));
            $this->logger->critical($found->getId());
            $this->manager->flush();
        }


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
