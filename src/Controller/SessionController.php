<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Session;
use App\Entity\SessionResponse;
use App\Form\Type\SessionResponseType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SessionController extends AbstractController
{
    /**
     * @Route("/session", name="app_session")
     */
    public function index(EntityManagerInterface $em): Response
    {
        $session = $em->getRepository(Session::class)->getActiveSession();
        $allSessions = $em->getRepository(Session::class)->findBy(['user' => $this->getUser()]);

        return $this->render('session/index.html.twig', [
            'session' => $session,
            'allSessions' => $allSessions,
        ]);
    }

    /**
     * @Route("/session/start", name="app_session_start")
     */
    public function start(EntityManagerInterface $em): Response
    {
        // Create a new session
        $session = new Session();
        $session->setUser($this->getUser());
        $em->persist($session);

        // Select question
        $questions = $em->getRepository(Question::class)->selectRandom();
        foreach ( $questions as $position => $question ) {
            $response = new SessionResponse();
            $response
                ->setQuestion($question)
                ->setPosition($position)
                ->setSession($session);
            $em->persist($response);
        }

        $em->flush();

        return $this->redirectToRoute('app_session_show', [
            'session' => $session->getId(),
        ]);
    }

    /**
     * @Route("/session/{session}", name="app_session_show")
     */
    public function show(Session $session, EntityManagerInterface $em): Response
    {
        $nextQuestion = $em->getRepository(SessionResponse::class)->getNextQuestion($session);

        if ( !$nextQuestion ) {
            return $this->redirectToRoute('app_session_stop', [
                'session' => $session->getId(),
            ]);
        }

        return $this->redirectToRoute('app_session_answer', [
            'session'   => $session->getId(),
            'question'  => $nextQuestion->getId(),
        ]);
    }

    /**
     * @Route("/session/{session}/answer/{question}", name="app_session_answer")
     */
    public function answer(Request $request, EntityManagerInterface $em, Session $session, SessionResponse $question): Response
    {
        if ( !$question->getDisplayedAt() ) {
            $question->setDisplayedAt(new \DateTimeImmutable());
            $em->persist($question);
            $em->flush();
        }

        $form = $this->createForm(SessionResponseType::class, $question, [
            'question'  => $question->getQuestion(),
        ]);
        $form->handleRequest($request);

        if ( $form->isSubmitted() ) {
            $question->setAnsweredAt(new \DateTimeImmutable());
            $em->persist($question);
            $em->flush();

            return $this->redirectToRoute('app_session_show', [
                'session' => $session->getId(),
            ]);
        }

        return $this->render('session/answer.html.twig', [
            'question'  => $question,
            'form'      => $form->createView(),
        ]);
    }

    /**
     * @Route("/session/{session}/stop", name="app_session_stop")
     */
    public function stop(Session $session, EntityManagerInterface $em): Response
    {
        $session->setEndedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->redirectToRoute('app_session');
    }
}
