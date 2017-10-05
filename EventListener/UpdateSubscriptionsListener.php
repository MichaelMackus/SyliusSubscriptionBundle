<?php

/*
* This file is part of the Sylius package.
*
* (c) Paweł Jędrzejewski
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Sylius\Bundle\SubscriptionBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Sylius\Component\Cart\Event\CartEvent;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\User\Model\CustomerAwareInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class UpdateSubscriptionsListener
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Save & persist the subscriptions.
     *
     * @param CartEvent $event
     */
    public function onCartSave(CartEvent $event)
    {
        $cart = $event->getCart();

        foreach ($cart->getItems() as $item) {
            /** @var OrderItemInterface $item */

            if (null === $subscription = $item->getSubscription()) {
                continue;
            }

            if (null === $subscription->getInterval()) {
                // remove subscription if there's no interval
                $this->manager->remove($subscription);
                $item->setSubscription(null);

                continue;
            }

            $now = new \DateTime();
            $subscription->setVariant($item->getVariant());
            $subscription->setQuantity($item->getQuantity());
            $subscription->setScheduledDate(
                $now->add($subscription->getInterval())
            );

            if ($cart instanceof CustomerAwareInterface && (null !== $customer = $cart->getCustomer())) {
                $subscription->setCustomer($customer);
            }

            if (null === $subscription->getId()) {
                $this->manager->persist($subscription);
            }
        }

        $this->manager->flush();
    }
}
