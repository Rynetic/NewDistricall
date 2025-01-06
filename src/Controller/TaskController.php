<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tasks', name: 'api_tasks_')]
class TaskController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, TaskRepository $taskRepository): JsonResponse
    {
        // Récupération des paramètres de filtrage et pagination
        $page = max(1, $request->query->getInt('page', 1));
        $search = $request->query->get('search');
	$status = $request->query->get('status');

	// Construction de la requête de base
        $queryBuilder = $taskRepository->createQueryBuilder('t')
            ->orderBy('t.status', 'ASC')
	    ->addOrderBy('t.created_at', 'DESC');

        // Ajout du filtre de recherche si présent
        if ($search) {
            $queryBuilder
                ->andWhere('t.title LIKE :search OR t.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $queryBuilder
                ->andWhere('t.status = :status')
                ->setParameter('status', $status);
        }

	$total = count($queryBuilder->getQuery()->getResult());

        // Applique la pagination	
        $tasks = $queryBuilder
            ->setMaxResults(10)
            ->setFirstResult(($page - 1) * 10)
            ->getQuery()
            ->getResult();

        return $this->json([
            'data' => $tasks,
            'total' => $total,
            'page' => $page,
            'last_page' => ceil($total / 10)
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
	    try {
            // Création de la tâche depuis les données JSON
            $task = $this->serializer->deserialize(
                $request->getContent(),
                Task::class,
                'json'
            );
            // Validation des données
            $errors = $this->validator->validate($task);
            if (count($errors) > 0) {
                return $this->json($errors, Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($task);
            $this->entityManager->flush();

            return $this->json($task, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Task $task): JsonResponse
    {
        try {
            $this->serializer->deserialize(
                $request->getContent(),
                Task::class,
                'json',
                ['object_to_populate' => $task]
            );

            $errors = $this->validator->validate($task);
            if (count($errors) > 0) {
                return $this->json($errors, Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            return $this->json($task);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Task $task): JsonResponse
    {
        try {
            $this->entityManager->remove($task);
            $this->entityManager->flush();

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
