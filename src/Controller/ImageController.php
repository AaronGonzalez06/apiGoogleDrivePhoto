<?php

namespace App\Controller;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Image;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Services\JwtAuth;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\EntityManagerInterface;

class ImageController extends AbstractController
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
            $image_repository = $doctrine->getRepository(Image::class);
            $category_repository = $doctrine->getRepository(Category::class);

            $json = $request->get('json',null);
            $params = json_decode($json,true);

            $params = json_decode($json,true);
            if($params !== null){
                $categoryId = (!empty($params['categoryId'])) ? $params['categoryId'] : null;
                $description = (!empty($params['description'])) ? $params['description'] : null;
                if(($categoryId !== null) && ($description !== null)){
                    $image = $request->files->get('file0');
                    $nombreArchivo = $nombreArchivo = uniqid() . '.' . $image->guessExtension();
                    //$image->getClientOriginalName();
                    $image->move('uploads', $nombreArchivo);

                    $category = $category_repository->findOneBy([
                        'id' => $categoryId
                    ]);

                    $image = new Image();
                    $image->setName($nombreArchivo);
                    $image->setDescription($description);
                    $image->setCategory($category);
                    $image->setCreatedAt(new \DateTime());
                    $em->persist($image);
                    $em->flush();

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Add image.',
                        'Category' => $image
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
            $image_repository = $doctrine->getRepository(Image::class);
            $dataUser = $jwt_auth->checkToken($token,true);

            $image = $image_repository->find($id);

            if(is_object($image) && $dataUser->id == $image->getCategory()->getUser()->getId()){
                $em->remove($image);
                $em->flush();

                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'delete category.',
                    'image' => $image
                ];
            }

        }

        return $this->resjson($data);
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
            $category_repository = $doctrine->getRepository(Category::class);
            $image_repository = $doctrine->getRepository(Image::class);
            $dataUser = $jwt_auth->checkToken($token,true);

            $json = $request->get('json',null);
            $params = json_decode($json,true);

            if($params !== null){
                $description = (!empty($params['description'])) ? $params['description'] : null;
                $imageId = (!empty($params['imageId'])) ? $params['imageId'] : null;
                $categoryId = (!empty($params['categoryId'])) ? $params['categoryId'] : null;
                if(($description !== null) && ($imageId !== null) && ($categoryId !== null)){
                    $category = $category_repository->findOneBy([
                        'id' => $categoryId
                    ]);
                    $image = $image_repository->findOneBy([
                        'id' => $imageId
                    ]);
                    if(is_object($category) && is_object($image) && $dataUser->id == $category->getUser()->getId()){
                        $image->setDescription($description);
                        $image->setCategory($category);
                        $em->persist($category);
                        $em->flush();

                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'edit.',
                            'image' => $image
                        ];
                    }
                }
            }
        }

        return $this->resjson($data);
    }

    public function listImage(Request $request, JwtAuth $jwt_auth,EntityManagerInterface $entityManager){

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
            $user_repository = $doctrine->getRepository(User::class);
            $image_repository = $doctrine->getRepository(Image::class);
            $dataUser = $jwt_auth->checkToken($token,true);
            
            $user = $user_repository->findOneBy([
                'id' => $dataUser->id
            ]);
            $listCategory = $category_repository->findBy([
                'user' => $user
            ]);

            /*$listImage = $image_repository->findBy([
                'category' => $listCategory
            ]);*/

            //prueba
            $query = $entityManager->createQuery('
            SELECT i
            FROM App\Entity\Image i
            WHERE i.category IN (:listCategory)
            ORDER BY i.id DESC
            ');

            $query->setParameter('listCategory', $listCategory);

            $resultados = $query->getResult();
            //fin prueba

            $data = [
                'status' => 'Success',
                'code' => 200,
                'message' => 'List images.',
                'cantidad' => count($resultados),
                'list' => $resultados,

            ];
        }

        return $this->resjson($data);
    }

    public function listImageForCategory(Request $request, JwtAuth $jwt_auth,$id){

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
            $user_repository = $doctrine->getRepository(User::class);
            $image_repository = $doctrine->getRepository(Image::class);
            $dataUser = $jwt_auth->checkToken($token,true);
            
            $user = $user_repository->findOneBy([
                'id' => $dataUser->id
            ]);
            $Category = $category_repository->findBy([
                'id' => $id,
                'user' => $user
            ]);

            $listImage = $image_repository->findBy([
                'category' => $Category
            ]);

            $data = [
                'status' => 'Success',
                'code' => 200,
                'message' => 'List images.',
                'cantidad' => count($listImage),
                'list' => $listImage,

            ];
        }

        return $this->resjson($data);
    }

    public function image(Request $request, JwtAuth $jwt_auth,$id){

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
            $user_repository = $doctrine->getRepository(User::class);
            $image_repository = $doctrine->getRepository(Image::class);
            $dataUser = $jwt_auth->checkToken($token,true);
            
            $user = $user_repository->findOneBy([
                'id' => $dataUser->id
            ]);
            $Category = $category_repository->findBy([
                'user' => $user
            ]);

            $image = $image_repository->findBy([
                'id' => $id,
                'category' => $Category
            ]);

            $data = [
                'status' => 'Success',
                'code' => 200,
                'message' => 'Image.',
                'cantidad' => count($image),
                'list' => $image,

            ];
        }

        return $this->resjson($data);
    }

    public function filterImageDate(Request $request, JwtAuth $jwt_auth, $from, $until,EntityManagerInterface $entityManager){

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
            $user_repository = $doctrine->getRepository(User::class);
            $image_repository = $doctrine->getRepository(Image::class);
            $dataUser = $jwt_auth->checkToken($token,true);
            
            $user = $user_repository->findOneBy([
                'id' => $dataUser->id
            ]);
            $listCategory = $category_repository->findBy([
                'user' => $user
            ]);

            $listImage = $image_repository->findBy([
                'category' => $listCategory
            ]);

            $data = [
                'status' => 'Success',
                'code' => 200,
                'message' => 'List images.',
                'cantidad' => count($listImage),
                'list' => $listImage,

            ];

            $query = $entityManager->createQuery('
            SELECT i
            FROM App\Entity\Image i
            WHERE i.category IN (:listCategory) AND i.createdAt BETWEEN :inicio AND :fin
        ');

        $query->setParameter('listCategory', $listCategory);
        $query->setParameter('inicio', $from);
        $query->setParameter('fin', $until);

        $resultados = $query->getResult();

        $data = [
            'status' => 'Success',
            'code' => 200,
            'message' => 'List images.',
            'cantidad' => count($resultados),
            'list' => $resultados,

        ];
        }

        return $this->resjson($data);
    }

}
