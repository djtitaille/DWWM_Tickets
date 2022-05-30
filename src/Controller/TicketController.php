<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/ticket")
 */
class TicketController extends AbstractController
{

    protected TicketRepository $ticketRepository;

    public function __construct(TicketRepository $ticketRepository){
        $this->ticketRepository = $ticketRepository;
    }

    /**
     * @Route("/", name="app_ticket")
     */
    public function index(): Response
    {
        $user = $this->getUser();

        $tickets = $this->ticketRepository->findBy(['user' => $user] );

        //dd($tickets);

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);

        
    }

    /**
     * @Route("/create", name="ticket_create")
     * @Route("/update/{id}", name="ticket_update", requirements={"id"="\d+"})
     */
    public function ticket(Ticket $ticket = null, Request $request) : Response
    {
        if(!$ticket){
        $ticket = new Ticket;

        $ticket->setIsActive(true)
            ->setCreatedAt(new \DateTimeImmutable());
        $title = 'Création d\'un ticket';
        } else {
            $title = "Update du formulaire :  {$ticket->getId()}";
        }

        $form = $this->createForm(TicketType::class, $ticket, []);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           
                //Nouveauté Symfony 5.4
                $this->ticketRepository->add($ticket, true);
                
                return $this->redirectToRoute('app_ticket');
        }
        return $this->render('ticket/userForm.html.twig', [
            'form' => $form->createView(),
            'title' =>'Création d\'un ticket'
        ]);
    }

    /**
     * @Route("/delete/{id}", name="ticket_delete", requirements={"id"="\d+"})
     */
    public function deleteTicket(Ticket $ticket) : Response
    {
        $this->ticketRepository->remove($ticket, true);

        return $this->redirectToRoute('app_ticket');

    }

    /**
     * @Route("/pdf", name="ticket_pdf")
     */
     public function pdf() : Response
     {
        //Données utiles
        $user = $this->getUser();
        $tickets = $this->ticketRepository->findBy(['user' => $user] );

         //Configuration de Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        //Instantiation de Dompdf
        $dompdf = new Dompdf($pdfOptions);

        //Récupération du contenu de la vue
        $html = $this->renderView('ticket/pdf.html.twig', [
            'tickets' => $tickets,
            'title' => "Bienvenue sur notre page PDF"
        ]);

        //Ajout du contenu de la vue dans le PDF
        $dompdf->loadHtml($html);

        //Configuration de la taille et de la largeur du PDF
        $dompdf->setPaper('A4', 'portrait');

        //Render du PDF
        $dompdf->render();

        //dd($html);

        //Création du fichier PDF   
        $dompdf->stream("ticket.pdf", [
            "Attachment" => true
        ]);

      return new Response ('', 200, [
          'Content-Type' => 'application/pdf'
      ]);    
     }

     /**
      * @Route("/excel", name="ticket_excel")
      */
     public function excel() : Response {

            //Données utiles
            $user = $this->getUser();
            $tickets = $this->ticketRepository->findBy(['user' => $user] );


            $spreadsheet = new Spreadsheet ();
            $sheet = $spreadsheet->getActiveSheet ();
            $sheet->setCellValue ('A1', 'Liste des tickets pour l\'utilisateur : ' . $user->getUsername());
            $sheet->mergeCells('A1:E1');
            $sheet->setTitle("Liste des tickets");

            //Set Column names
            $columnNames = [
                'Id',
                'Objet',
                'Date de création',
                'Department',
                'Statut',
            ];
            $columnLetter = 'A';
            foreach ($columnNames as $columnName) {
                $sheet->setCellValue ($columnLetter . '3', $columnName);
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                $columnLetter++;
            }
             foreach ($tickets as $key => $ticket) {
                $sheet->setCellValue ('A' . ($key + 4), $ticket->getId());
                $sheet->setCellValue ('B' . ($key + 4), $ticket->getObject());
                $sheet->setCellValue ('C' . ($key + 4), $ticket->getCreatedAt()->format('d/m/Y'));
                $sheet->setCellValue ('D' . ($key + 4), $ticket->getDepartment()->getName());
                $sheet->setCellValue ('E' . ($key + 4), $ticket->getIsActive());
            }

            // -- Style de la feuille de calcul --
            $styleArrayHead = [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    ],
                    'vertical' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
                
            $styleArray = [
                        'font' => [
                            'bold' => true,
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                        ],
                        'borders' => [
                            'top' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            ],
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                            'rotation' => 90,
                            'startColor' => [
                                'argb' => 'FFA0A0A0',
                            ],
                            'endColor' => [
                                'argb' => 'FFFFFFFF',
                            ],
                        ],
                    ];

            $styleArrayBody = [
                'alignement' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                    ],
                    'vertical' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                    'horizontal' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];

            $sheet->getStyle('A1:E1')->applyFromArray($styleArray);
            $sheet->getStyle('A3:E3')->applyFromArray($styleArrayHead);
            $sheet->getStyle('A4:E' . (count($tickets) + 3))->applyFromArray($styleArrayBody);
            
            //Création du fichier xlsx
            $writer = new Xlsx($spreadsheet);

            //Création d'un fichier temporaire
            $fileName = "Export_Tickets.xlsx";
            $temp_file = tempnam(sys_get_temp_dir(), $fileName);

            //créer le fichier excel dans le dossier tmp du systeme
            $writer->save($temp_file);

            //Renvoie le fichier excel
            return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
     }
}
