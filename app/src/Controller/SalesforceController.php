<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Salesforce\SalesforceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ACTIVE_USER')]
class SalesforceController extends AbstractController
{
    #[Route('sf/{id}', name: 'user_salesforce_form', methods: ['GET'])]
    public function showForm(User $user): Response
    {
        return $this->render('profile/salesforce_form.html.twig', ['user' => $user]);
    }

    #[Route('sf/{id}', name: 'user_salesforce_submit', methods: ['POST'])]
    public function submitToSalesforce(User $user, Request $request, SalesforceService $sf): Response
    {
        $data = [
            'account_name' => $request->request->get('account_name'),
            'email' => $request->request->get('email'),
            'phone' => $request->request->get('phone'),
            'account_number'=>$request->request->get('account_number'),
            'website' => $request->request->get('website'),
        ];

        $success = $sf->createAccountAndContact($data);

        if ($success) {
            $this->addFlash('success', 'Data sent to Salesforce successfully.');
        } else {
            $this->addFlash('danger', 'Failed to send data to Salesforce.');
        }

        return $this->redirectToRoute('user_salesforce_form', ['id' => $user->getId()]);
    }
}
