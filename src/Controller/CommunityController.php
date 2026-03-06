<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\CommentReaction;
use App\Entity\Post;
use App\Entity\PostReaction;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostReactionRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

use App\Entity\Notification;
use App\Repository\NotificationRepository;

#[Route('/blog')]
class CommunityController extends AbstractController
{
    #[Route('/', name: 'front_blog')]
    public function index(Request $request, PostRepository $postRepository, EntityManagerInterface $entityManager, SluggerInterface $slugger, \App\Repository\NotificationRepository $notificationRepo): Response
    {
        $user = $this->getUser();
        $form = null;

        if ($user) {
            $post = new Post();
            $form = $this->createForm(PostType::class, $post);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $post->setUser($user);
                $post->setCreatedAt(new \DateTimeImmutable());
                $post->setType('general');

                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir').'/public/uploads/posts',
                            $newFilename
                        );
                        $post->setImage($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload image');
                    }
                }

                $entityManager->persist($post);
                $entityManager->flush();

                $this->addFlash('success', 'Post created successfully!');
                return $this->redirectToRoute('front_blog');
            }
        }

        $query = $request->query->get('q');
        
        if ($query) {
            // We need to add a search method to PostRepository or do a simple custom query here
            // For simplicity, let's fetch all and filter in PHP or add a repository method later.
            // Better: use a query builder here.
             $posts = $postRepository->createQueryBuilder('p')
                ->join('p.user', 'u')
                ->where('u.username LIKE :query')
                ->orWhere('p.content LIKE :query')
                ->setParameter('query', '%'.$query.'%')
                ->orderBy('p.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);
        }

        $notifications = $notificationRepo->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $unreadCount = $notificationRepo->count(['user' => $user, 'isRead' => false]);

        return $this->render('front/feed.html.twig', [
            'posts' => $posts,
            'page' => 'blog',
            'searchQuery' => $query,
            'form' => $form ? $form->createView() : null,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
    }

    #[Route('/post/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    public function deletePost(Post $post, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || $post->getUser() !== $user) {
            return $this->redirectToRoute('front_blog');
        }

        $entityManager->remove($post);
        $entityManager->flush();

        $this->addFlash('success', 'Post deleted successfully.');
        return $this->redirectToRoute('app_my_posts');
    }

    #[Route('/post/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function editPost(Post $post, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, PostRepository $postRepository): Response
    {
        $user = $this->getUser();
        if (!$user || $post->getUser() !== $user) {
            return $this->redirectToRoute('app_my_posts');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $oldImage = $post->getImage();
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/posts',
                        $newFilename
                    );
                    $post->setImage($newFilename);
                    if ($oldImage) {
                        $oldImagePath = $this->getParameter('kernel.project_dir').'/public/uploads/posts/'.$oldImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Post updated successfully!');
            return $this->redirectToRoute('app_my_posts');
        }

        $createPost = new Post();
        $createForm = $this->createForm(PostType::class, $createPost);
        $myPosts = $postRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('front/my_posts.html.twig', [
            'posts' => $myPosts,
            'form' => $createForm->createView(),
            'editForm' => $form->createView(),
            'editPost' => $post,
            'page' => 'my_posts',
            'createAction' => $this->generateUrl('app_my_posts')
        ]);
    }

    #[Route('/my-posts', name: 'app_my_posts')]
    public function myPosts(Request $request, PostRepository $postRepository, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($user);
            $post->setCreatedAt(new \DateTimeImmutable());
            $post->setType('general');

            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/posts',
                        $newFilename
                    );
                    $post->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image');
                }
            }

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post created successfully!');
            return $this->redirectToRoute('app_my_posts');
        }

        $myPosts = $postRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('front/my_posts.html.twig', [
            'posts' => $myPosts,
            'form' => $form->createView(),
            'page' => 'my_posts'
        ]);
    }

    #[Route('/notifications', name: 'app_notifications')]
    public function notifications(NotificationRepository $notificationRepo): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $notifications = $notificationRepo->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('front/notifications.html.twig', [
            'notifications' => $notifications,
            'page' => 'notifications'
        ]);
    }
    
    #[Route('/notification/{id}/read', name: 'app_notification_read')]
    public function readNotification(Notification $notification, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if ($notification->getUser() === $user) {
            $notification->setIsRead(true);
            $entityManager->flush();
            
            // Redirect to the post
            return $this->redirectToRoute('front_blog', ['q' => '@'.$notification->getPost()->getUser()->getUsername()]); // Simple redirect for now, ideally anchor to post
            // Or maybe separate route for single post view
        }
        return $this->redirectToRoute('front_blog');
    }


    #[Route('/post/{id}/react/{type}', name: 'app_post_react', methods: ['POST'])]
    public function reactToPost(Post $post, string $type, EntityManagerInterface $entityManager, PostReactionRepository $reactionRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['error' => 'Unauthorized'], 401);

        $existingReaction = $reactionRepo->findOneBy(['post' => $post, 'user' => $user]);

        if ($existingReaction) {
            if ($existingReaction->getType() === $type) {
                // Toggle off
                $entityManager->remove($existingReaction);
                $action = 'removed';
            } else {
                // Change reaction
                $existingReaction->setType($type);
                $action = 'updated';
            }
        } else {
            // New reaction
            $reaction = new PostReaction();
            $reaction->setPost($post);
            $reaction->setUser($user);
            $reaction->setType($type);
            $entityManager->persist($reaction);
            $action = 'added';

            // Create Notification
            if ($post->getUser() !== $user) {
                $notification = new Notification();
                $notification->setUser($post->getUser());
                $notification->setActor($user);
                $notification->setPost($post);
                $notification->setType('like');
                $notification->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($notification);
            }
        }

        $entityManager->flush();

        return new JsonResponse([
            'action' => $action,
            'count' => count($post->getReactions())
        ]);
    }

    #[Route('/post/{id}/comment', name: 'app_post_comment', methods: ['POST'])]
    public function commentOnPost(Post $post, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['error' => 'Unauthorized'], 401);

        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? null;
        $parentId = $data['parent_id'] ?? null;

        if (!$content) return new JsonResponse(['error' => 'Content required'], 400);

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setPost($post);
        $comment->setUser($user);
        $comment->setCreatedAt(new \DateTimeImmutable());

        if ($parentId) {
            $parent = $entityManager->getRepository(Comment::class)->find($parentId);
            if ($parent) {
                $comment->setParent($parent);
            }
        }

        $entityManager->persist($comment);

        // Create Notification
        if ($post->getUser() !== $user) {
            $notification = new Notification();
            $notification->setUser($post->getUser());
            $notification->setActor($user);
            $notification->setPost($post);
            $notification->setType('comment');
            $notification->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($notification);
        }

        $entityManager->flush();

        return new JsonResponse([
            'id' => $comment->getId(),
            'user' => $user->getUsername(),
            'avatar' => $user->getImage(),
            'content' => $comment->getContent(),
            'created_at' => $comment->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }

    #[Route('/post/{id}/comments', name: 'app_post_comments_list', methods: ['GET'])]
    public function getPostComments(Post $post): Response
    {
        return $this->render('front/modules/comments_list.html.twig', [
            'post' => $post,
            'comments' => $post->getComments()
        ]);
    }

    #[Route('/comment/{id}/react/{type}', name: 'app_comment_react', methods: ['POST'])]
    public function reactToComment(Comment $comment, string $type, EntityManagerInterface $entityManager, \App\Repository\CommentReactionRepository $reactionRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return new JsonResponse(['error' => 'Unauthorized'], 401);

        $existingReaction = $reactionRepo->findOneBy(['comment' => $comment, 'user' => $user]);

        if ($existingReaction) {
            if ($existingReaction->getType() === $type) {
                // Toggle off
                $entityManager->remove($existingReaction);
                $action = 'removed';
            } else {
                // Change reaction
                $existingReaction->setType($type);
                $action = 'updated';
            }
        } else {
            // New reaction
            $reaction = new CommentReaction();
            $reaction->setComment($comment);
            $reaction->setUser($user);
            $reaction->setType($type);
            $entityManager->persist($reaction);
            $action = 'added';
        }

        $entityManager->flush();

        return new JsonResponse([
            'action' => $action,
            'count' => count($comment->getReactions())
        ]);
    }
}
