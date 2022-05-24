<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TicketController extends AbstractController
{

    protected TicketRepository $ticketRepository;

    public function __construct(TicketRepository $ticketRepository){
        $this->ticketRepository = $ticketRepository;
    }

    /**
     * @Route("/ticket", name="app_ticket")
     */
    public function index(): Response
    {
        $tickets = $this->ticketRepository->findAll();

        //dd($tickets);

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    /**
     * @Route("/ticket/create", name="ticket_create")
     */
    public function createTicket(Request $request, ManagerRegistry $doctrine): Response{

        $ticket = new Ticket;

        $ticket->setIsActive(true)
            ->setCreatedAt(new \DateTimeImmutable());


        $form = $this->createForm(TicketType::class, $ticket, []);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           
                //Nouveauté Symfony 5.4
                $this->ticketRepository->add($ticket, true);
                
                return $this->redirectToRoute('app_ticket');
        }
        return $this->render('ticket/userForm.html.twig', [
            'form' => $form->createView(),
            'title' => 'Création d\'un ticket'
        ]);
    }

    /**
     * @Route("/ticket/update/{id}", name="ticket_update", requirements={"id"="\d+"})
     */
    public function updateTicket(Ticket $ticket,Request $request) {

        $form = $this->createForm(TicketType::class, $ticket, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
           
            //Nouveauté Symfony 5.4
            $this->ticketRepository->add($ticket, true);
            
            return $this->redirectToRoute('app_ticket');
    }

        return $this->render('ticket/userForm.html.twig', [
            'form' => $form->createView(),
            'title' => "Update du formulaire :  {$ticket->getId()}"
        ]);
    }
  
}
