<?php

namespace App\Controller;
use App\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Services\JwtAuth;

class UserController extends AbstractController
{
    private function resjson($data){
        $json = $this->get('serializer')->serialize($data,'json');
        $response = new Response();
        $response->setContent($json);
        $response->headers->set('Content-Type','application/json');
        return $response;
    }

    public function create(Request $request){

        $json = $request->get('json',null);
        //$params = json_decode($json,true); --> array
        //$params = json_decode($json); --> objeto Php
        $params = json_decode($json,true);

        //responde por defecto
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'User not created'
        ];

        if($params !== null){
            $name = (!empty($params['name'])) ? $params['name'] : null;
            $email = (!empty($params['email'])) ? $params['email'] : null;
            $password = (!empty($params['password'])) ? $params['password'] : null;

            $validation = Validation::createValidator();
            $email_validation = $validation->validate($email,[
                new Email()
            ]);

            if(($name !== null) && ($email !== null) && ($password !== null) && count($email_validation) == 0){
                $user = new User();
                $user->setName($name);
                $user->setEmail($email);

                $pwd = hash('sha256',$password);
                $user->setPassword($pwd);

                $doctrine = $this->getDoctrine();
                $em = $doctrine->getManager();
                $user_repository = $doctrine->getRepository(User::class);
                $exists_email = $user_repository->findBy(array(
                    'email' => $email
                ));

                if(count($exists_email) == 0){
                    $em->persist($user);
                    $em->flush();
                    $data = [
                        'status' => 'sucess',
                        'code' => 200,
                        'message' => 'Add user',
                        'user' => $user
                    ];
                }
            }
        }

        return $this->resjson($data);
    }

    public function login(Request $request, JwtAuth $jwt_auth){
        
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Incorrect Login'
        ];
        $json = $request->get('json',null);
        $params = json_decode($json,true);

        if($params !== null){
            $email = (!empty($params['email'])) ? $params['email'] : null;
            $password = (!empty($params['password'])) ? $params['password'] : null;
            $gettoken = (!empty($params['gettoken'])) ? $params['gettoken'] : null;
            $validation = Validation::createValidator();
            $email_validation = $validation->validate($email,[
                new Email()
            ]);
            if(($email !== null) && ($password !== null) && count($email_validation) == 0){
                $pwd = hash('sha256',$password);
                $data = $jwt_auth->signup($email,$pwd,$gettoken);
            }
        }
        return new JsonResponse($data);
    }

    public function edit(Request $request, JwtAuth $jwt_auth){

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
            $user_repository = $doctrine->getRepository(User::class);
            $dataUser = $jwt_auth->checkToken($token,true);

            $json = $request->get('json',null);
            $params = json_decode($json,true);

            if($params !== null){
                $email = (!empty($params['email'])) ? $params['email'] : null;
                $name = (!empty($params['name'])) ? $params['name'] : null;
                $validation = Validation::createValidator();
                $email_validation = $validation->validate($email,[
                    new Email()
                ]);
                if(($email !== null) && ($name !== null) && count($email_validation) == 0){
                    $user = $user_repository->findOneBy([
                        'id' => $dataUser->id
                    ]);
                    $user->setName($name);
                    $user->setEmail($email);

                    $isset_user = $user_repository->findBy([
                        'email' => $email
                    ]);

                    if(count($isset_user) == 0 || $dataUser->email == $email){
                        $em->persist($user);
                        $em->flush();
                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'edit.',
                            'user' => $user
                        ];
                    }

                }
            }
        }

        return $this->resjson($data);
    }
}
