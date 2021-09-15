<?php

namespace App\Controller;

use App\Form\NewslettersType;
use App\Entity\Newsletters\Users;
use App\Form\NewslettersUsersType;
use App\Entity\Newsletters\Newsletters;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/newsletters", name="newsletters_")
 */
class NewslettersController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $user = new Users();
        $form = $this->createForm(NewslettersUsersType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $token = hash('sha256', uniqid());

            $user->setValidationToken($token);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $email = (new TemplatedEmail())
                ->from('newsletter@site.fr')
                ->to($user->getEmail())
                ->subject('Votre inscription à la newsletter')
                ->htmlTemplate('emails/inscription.html.twig')
                ->context(\compact('user', 'token'))
                ;
            
            $mailer->send($email);

            $this->addFlash('message', 'inscription en attente de validation');
            return $this->redirectToRoute('app_home');

        }

        return $this->render('newsletters/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/confirm/{id}/{token}", name="confirm")
     */
    public function confirm(Users $user, $token): Response
    {
        if($user->getValidationToken() != $token) {
            throw $this->createNotFoundException('Page non trouvée');
        }

        $user->setIsValid(true);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $this->addFlash('message', 'Compte activé');

        return $this->redirectToRoute('app_home');
    }

    /**
     * @Route("/prepare", name="prepare")
     */
    public function prepare(Request $request): Response
    {
        $newsletter = new Newsletters();
        $form = $this->createForm(NewslettersType::class, $newsletter);

        return $this->render('newsletters/prepare.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
