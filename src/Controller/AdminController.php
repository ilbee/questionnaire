<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPasswordToken;
use App\Form\Type\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="app_admin")
     */
    public function index(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)
            ->findAll();

        return $this->render('admin/index.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * @Route("/admin/user/delete/{user}", name="app_admin_user_delete")
     */
    public function delete(EntityManagerInterface $em, User $user): Response
    {
        $em->remove($user);
        $em->flush();
        $this->addFlash('warning', 'Utilisateur supprimé');

        return $this->redirectToRoute('app_admin');
    }

    /**
     * @Route("/admin/user/new", name="app_admin_user_new")
     * @Route("/admin/user/edit/{user}", name="app_admin_user_edit")
     */
    public function edit(MailerInterface $mailer, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, User $user = null): Response
    {
        if (!$user) {
            $user = new User();
            $user->setRoles(['ROLE_USER']);
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ( $form->isSubmitted() ) {

            $em->persist($user);

            if ( !$user->getId() || $request->get('generateNewPassword') ) {

                if ( $user->getId() ) {
                    $title = 'Réinitialisation du mot de passe';
                } else {
                    $title = 'Activez votre compte';
                    $template = 'mail/new_user.html.twig';
                }

                $user
                    ->setNeedRenewPassword(true)
                    ->resetPassword($passwordHasher);

                $userPasswordToken = new UserPasswordToken();
                $userPasswordToken
                    ->setUser($user)
                    ->setToken(str_replace('/', '_', base64_encode(User::randomString(100))));
                $em->persist($userPasswordToken);
                $em->flush();

                $schemeAndHttpHost = $request->getSchemeAndHttpHost();

                $email = (new TemplatedEmail())
                    ->from('LesPotoSymfo.tech <postmaster@lespotosymfo.tech>')
                    ->to(new Address($user->getEmail()))
                    ->subject($title)
                    ->htmlTemplate($template)
                    ->context([
                        'schemeAndHttpHost' => $schemeAndHttpHost,
                        'user'              => $user,
                        'token'             => $userPasswordToken,
                    ])
                ;

                $mailer->send($email);
            }

            $em->flush();

            if ( $user->getId() ) {
                $this->addFlash('success', 'Utilisateur modifié avec succès');
            } else {
                $this->addFlash('success', 'Utilisateur créé avec succès');
            }

            dd('ok');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
