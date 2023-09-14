<?php
namespace App\Services;

use Firebase\JWT\JWT;
use App\Entity\User;
use Firebase\JWT\Key;

class JwtAuth
{

    public $manager;
    public $key;
    public $userJwt;

    public function __construct($manager)
    {
        $this->manager = $manager;
        $this->key = "sadfasdfasfasd";
    }

    public function signup($email, $password, $gettoken){

        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'error login'
        ];

        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'password' => $password
        ]);

        if(is_object($user)){

            $token = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60)
            ];
            $jwt = JWT::encode($token,$this->key, 'HS256');
            if($gettoken == "true"){
                $data = $jwt;
            }else{
                $decoded = JWT::decode($jwt, new Key($this->key, 'HS256'));
                $data = $decoded;
            }
        }

        return $data;
    }

    public function checkToken($jwt, $valores = false){

        $auth = false;
        try{
            $decoded = JWT::decode($jwt, new Key($this->key, 'HS256'));
        }catch(\UnexpectedValueException $e){
            $auth = false;
        }catch(\DomainException $e){
            $auth = false;
        }
        if(isset($decoded->id) && !$valores){
            $auth = true;
        }
        if($valores){
            $auth = $decoded;
        }

        return $auth;

    }

}