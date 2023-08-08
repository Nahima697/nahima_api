<?php

namespace App\Controller;
use App\Entity\Question;
use App\Form\QuestionType;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class QuestionController extends AbstractController
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    #[Route('/api/questions', name: 'app_question', methods: ['GET'])]
    public function index(QuestionRepository $questionRepository): JsonResponse
    {
        $questions = $questionRepository->findAll();
        $questions = json_decode($this->serializer->serialize($questions, 'json'));
        return $this->json($questions);
    }

    #[Route('/api/questions/{question}', name: 'question_one', methods: ['GET'])]
    public function getOne(Question $question): JsonResponse
    {
        $serializedObject = json_decode($this->serializer->serialize($question, 'json'));

        return $this->json($serializedObject);
    }

    #[Route('/api/{action}', name: 'change_score', methods: ['PATCH'])]
    public function changeScore(Request $request, QuestionRepository $questionRepository, EntityManagerInterface $em, $action): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['id'])) {
            $response = $this->json([
                "success" => false,
                "message" => "L'id de la question est manquant"
            ]);
            $response->setStatusCode(400);
            return $response;
        }

        $questionId = $data['id'];
        $question = $questionRepository->find($questionId);

        if (!$question) {
            $response = $this->json([
                "success" => false,
                "message" => "Question non trouvée"
            ]);
            $response->setStatusCode(400);
            return $response;
        }

        if ($action === 'up') {
            $scoreChange = 1;
        } elseif ($action === 'down') {
            $scoreChange = -1;
        } else {
            $response = $this->json([
                "success" => false,
                "message" => "Action non valide"
            ]);
            $response->setStatusCode(400);
            return $response;
        }

        $question->setScore($question->getScore() + $scoreChange);
        $em->flush();

        return $this->json(['message' => "Le score a été modifié"]);
    }

    #[Route('/api/questions', name: 'app_question_create', methods: ['POST'])]
    #[IsGranted("ROLE_USER")]

    public function createQuestion(Request $request,EntityManagerInterface $em): JsonResponse
    {
        $objectRequest = json_decode($request->getContent(), true);

        $form = $this->createForm(QuestionType::class);
        $form->submit($objectRequest);

        if ($form->isValid()) {
            $em->persist($form->getData());
            $em->flush();

            $data= json_decode($this->serializer->serialize($form->getData(), "json"));

            $response = $this->json($data);
            $response->setStatusCode(201);
            return $response;

        } else {

            $response = $this->json([
                "success" => false,
                "message" => $form->getErrors(true)
            ]);
            $response->setStatusCode(400);

            return $response;
        }
    }


    #[Route('/api/questions/{question}', name: 'app_question_delete', methods: ['DELETE'])]
    #[IsGranted("ROLE_ADMIN")]
       public function deleteOne(Question $question, EntityManagerInterface $em){
        $em->remove($question);
        $em->flush();

        $response = new JsonResponse();
        $response->setStatusCode(204);
        return $response;
    }

    #[Route('/api/questions/{question}', name: 'app_question_update', methods: ['PUT'])]
    #[IsGranted("ROLE_ADMIN")]
    public function update(Question $question, Request $request, EntityManagerInterface $em){
        $objectRequest = json_decode($request->getContent(), true);

        $form = $this->createForm(QuestionType::class, $question);

        $form->submit($objectRequest);


        if($form->isValid()){
            $em->flush();
            $nft = $form->getData();
            $questionSerialized =
                json_decode($this->serializer->serialize($question, "json"));

            return new JsonResponse($questionSerialized);
        } else{
            $response = $this->json([
                "success" => false,
                "errors" => $form->getErrors(true)
            ]);

            $response->setStatusCode(400);

            return $response;
        }
    }

}
