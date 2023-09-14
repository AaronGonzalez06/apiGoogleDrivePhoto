<?php

namespace App\Controller;
use App\Entity\Category;
use App\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class PruebaController extends AbstractController
{

    private function resjson($data){
        $json = $this->get('serializer')->serialize($data,'json');
        $response = new Response();
        $response->setContent($json);
        $response->headers->set('Content-Type','application/json');
        return $response;

    }

    public function index(): Response
    {
        $user_repository = $this->getDoctrine()->getRepository(User::class);
        $Category_repository = $this->getDoctrine()->getRepository(Category::class);

        $categories = $Category_repository->findAll();
        $users = $user_repository->findAll();

        //$users = $user_repository->find(1);
        /*foreach($users as $user){
            echo $user->getEmail() . "<br/>";
            foreach($user->getCategories() as $category){
                echo $category->getName() . "<br/>";
                foreach($category->getImages() as $image){
                    echo $image->getName() . "<br/>";
                }
            }
        }*/
        return $this->resjson($categories);
        /*return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/PruebaController.php',
        ]);*/
    }
}
