<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPasswordToken;
use App\Form\Type\UserPasswordType;
use App\Form\Type\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/renew-password/{user}/{token}", name="app_user_renew-password")
     * @Route("/activate/{user}/{token}", name="app_user_activate")
     */
    public function renewPassword(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, User $user, UserPasswordToken $userPasswordToken, Request $request): Response
    {
        if ( $userPasswordToken->getExpiredAt() < new \DateTimeImmutable() ) {
            throw new AccessDeniedException('Token expired');
        }

        if ( $user !== $userPasswordToken->getUser() ) {
            throw new AccessDeniedHttpException('Mismatch user');
        }

        if ( !$user->isNeedRenewPassword() ) {
            throw new AccessDeniedHttpException('User already renewed password');
        }

        $form = $this->createForm(UserPasswordType::class, $user);
        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() ) {
            $user
                ->resetPassword($passwordHasher, $user->getPassword())
                ->setNeedRenewPassword(false);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été définie avec succès');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/renew_password.html.twig', [
            'passwordForm'  => $form->createView(),
        ]);
    }

    /**
     * @Route("/user/password-forgotten", name="app_user_password-forgotten")
     */
    public function forgotten(MailerInterface $mailer, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'Email',
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ( $form->isSubmitted() && $form->isValid() ) {
            $user = $em->getRepository(User::class)->findOneBy(['email' => $form->getData()['email']]);

            if ( !$user ) {
                $this->addFlash('danger', 'Cet email n\'existe pas');
            } else {
                $user
                    ->setNeedRenewPassword(true)
                    ->resetPassword($passwordHasher);
                $em->persist($user);

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
                    ->subject('Réinitialisation de votre mot de passe')
                    ->htmlTemplate('mail/forgotten_password.html.twig')
                    ->context([
                        'schemeAndHttpHost' => $schemeAndHttpHost,
                        'user'              => $user,
                        'token'             => $userPasswordToken,
                    ])
                ;

                $mailer->send($email);

                $this->addFlash('success', 'Un email vous a été envoyé pour renouveler votre mot de passe');
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('security/forgotten_password.html.twig', [
            'passwordForgottenForm'  => $form->createView(),
        ]);
    }
}
