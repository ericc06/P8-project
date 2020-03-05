<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class TaskController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/tasks", name="task_list")
     */
    public function list()
    {
        // 'goto_url' is also used to initialize the template 'current_page' variable
        return $this->render('task/list.html.twig', [
            'tasks' => $this
                ->getDoctrine()
                ->getRepository('App:Task')
                ->findBy(['isDone' => false]),
            'goto_url' => 'task_list',
            'alert_label' => 'no_registered_task',
        ]);
    }

    /**
     * @Route("/tasks/done", name="task_done_list")
     */
    public function listDone()
    {
        return $this->render('task/list.html.twig', [
            'tasks' => $this
                ->getDoctrine()
                ->getRepository('App:Task')
                ->findBy(['isDone' => true]),
            'goto_url' => 'task_done_list',
            'alert_label' => 'no_completed_task',
        ]);
    }

    /**
     * @Route("/tasks/create", name="task_create")
     */
    public function create(Request $request)
    {
        $task = new Task($this->getUser());

        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($task);
            $em->flush();

            $this->addFlash('success', $this->translator->trans('task_added'));

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/tasks/{id}/edit", name="task_edit")
     */
    public function edit(Task $task, Request $request)
    {
        // check for "edit" access: calls all voters
        $this->denyAccessUnlessGranted('edit', $task);

        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', $this->translator->trans('task_modified'));

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    /**
     * @Route("/tasks/{id}/toggle/{goto_url}", name="task_toggle")
     */
    public function toggle(Task $task, string $goto_url)
    {
        // check for "toggle" access: calls all voters
        $this->denyAccessUnlessGranted('toggle', $task);

        $task->toggle(!$task->isDone());
        $this->getDoctrine()->getManager()->flush();

        if ($task->isDone()) {
            $this->addFlash('success', $this->translator->trans('task_marked_as_completed', [
                '%task_name%' => $task->getTitle(),
            ]));

            return $this->redirectToRoute($goto_url);
        }

        $this->addFlash('success', $this->translator->trans('task_marked_as_uncompleted', [
            '%task_name%' => $task->getTitle(),
        ]));

        return $this->redirectToRoute($goto_url);
    }

    /**
     * @Route("/tasks/{id}/delete", name="task_delete")
     */
    public function delete(Task $task)
    {
        // check for "delete" access: calls all voters
        $this->denyAccessUnlessGranted('delete', $task);

        $em = $this->getDoctrine()->getManager();
        $em->remove($task);
        $em->flush();

        $this->addFlash('success', $this->translator->trans('task_deleted'));

        return $this->redirectToRoute('task_list');
    }
}
