<?php

namespace App\Controller;
use App\Entity\User;
use App\Entity\Category;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Services\JwtAuth;

class CategoryController extends AbstractController
{
    private function resjson($data){
        $json = $this->get('serializer')->serialize($data,'json');
        $response = new Response();
        $response->setContent($json);
        $response->headers->set('Content-Type','application/json');
        return $response;
    }

    public function add(Request $request, JwtAuth $jwt_auth){

        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Error.'
        ];

        $token = $request->headers->get('Authorization');
        $tokenValidate = $jwt_auth->checkToken($token);

        if($tokenValidate){
            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();
            $category_repository = $doctrine->getRepository(Category::class);
            $user_repository = $doctrine->getRepository(User::class);
            $dataUser = $jwt_auth->checkToken($token,true);

            $json = $request->get('json',null);
            $params = json_decode($json,true);

            if($params !== null){
                $description = (!empty($params['description'])) ? $params['description'] : null;
                $name = (!empty($params['name'])) ? $params['name'] : null;
                if(($description !== null) && ($name !== null)){
                    $user = $user_repository->findOneBy([
                        'id' => $dataUser->id
                    ]);

                    $category = new Category();
                    $category->setName($name);
                    $category->setDescription($description);
                    $category->setUser($user);

                    $em->persist($category);
                    $em->flush();

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Add category.',
                        'Category' => $category
                    ];
                }
            }

        }
        
        return $this->resjson($data);
    }

    public function delete(Request $request, JwtAuth $jwt_auth, $id){
        $data = [
            'status' => 'Error',
            'code' => 400,
            'message' => 'Error',
            'id' => $id
        ];

        $token = $request->headers->get('Authorization');
        $tokenValidate = $jwt_auth->checkToken($token);

        if($tokenValidate){
            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();
            $category_repository = $doctrine->getRepository(Category::class);
            $dataUser = $jwt_auth->checkToken($token,true);

            $category = $category_repository->find($id);

            if(is_object($category) && $dataUser->id == $category->getUser()->getId()){
                $em->remove($category);
                $em->flush();

                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'delete category.',
                    'Category' => $category
                ];
            }

        }

        return $this->resjson($data);
    }

    public function listCategory(Request $request, JwtAuth $jwt_auth){

        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Error.'
        ];

        $token = $request->headers->get('Authorization');
        $tokenValidate = $jwt_auth->checkToken($token);

        if($tokenValidate){
            $doctrine = $this->getDoctrine();
            $category_repository = $doctrine->getRepository(Category::class);
            $dataUser = $jwt_auth->checkToken($token,true);

            $user_repository = $doctrine->getRepository(User::class);
            $user = $user_repository->findOneBy([
                'id' => $dataUser->id
            ]);
            $listCategory = $category_repository->findBy([
                'user' => $user
            ]);

            $data = [
                'status' => 'Success',
                'code' => 200,
                'message' => 'List category.',
                'cantidad' => count($listCategory),
                'list' => $listCategory,

            ];
        }

        return $this->resjson($data);
    }

    public function edit(Request $request, JwtAuth $jwt_auth,$id){

        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Error edit.'
        ];

        $token = $request->headers->get('Authorization');

        $tokenValidate = $jwt_auth->checkToken($token);

        if($tokenValidate){
            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();
            $category_repository = $doctrine->getRepository(Category::class);
            $user_repository = $doctrine->getRepository(User::class);
            $dataUser = $jwt_auth->checkToken($token,true);

            $json = $request->get('json',null);
            $params = json_decode($json,true);

            if($params !== null){
                $name = (!empty($params['name'])) ? $params['name'] : null;
                if($name !== null){
                    $user = $user_repository->findOneBy([
                        'id' => $dataUser->id
                    ]);
                    $category = $category_repository->findOneBy([
                        'id' => $id,
                        'user' => $user
                    ]);
                    if(is_object($category) && $dataUser->id == $category->getUser()->getId()){
                        $category->setName($name);
                        $em->persist($category);
                        $em->flush();

                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'edit.',
                            'category' => $category
                        ];
                    }
                }
            }
        }

        return $this->resjson($data);
    }
    
}
