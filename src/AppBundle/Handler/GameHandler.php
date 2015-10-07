<?php

namespace AppBundle\Handler;

use Symfony\Component\Form\FormFactoryInterface;

class GameHandler
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFacotry;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * Processes the form.
     *
//     * @param PageInterface $page
     * @param               $page
     * @param array         $parameters
     * @param String        $method
     *
//     * @return PageInterface
     * @return object
     *
//     * @throws \Acme\BlogBundle\Exception\InvalidFormException
     */
    private function processForm($page, array $parameters, $method = "PUT")
    {
//        $form = $this->formFactory->create(new UserType(), $page, ['method' => $method]);
        $form = $this->formFactory->create(
            new \Symfony\Component\Form\Extension\Core\Type\ButtonType(),
            $page,
            ['method' => $method]
        );
        $form->submit($parameters, 'PATCH' !== $method);
        if ($form->isValid()) {
            $page = $form->getData();
//            $this->om->persist($page);
//            $this->om->flush($page);

            return $page;
        }

//        throw new InvalidFormException('Invalid submitted data', $form);
    }
}
