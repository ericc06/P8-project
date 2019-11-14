<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Task;
use AppBundle\Form\TaskType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;

class TaskController extends Controller
{
    /**
     * @Route("/tasks", name="task_list")
     */
    public function listAction()
    {
        return $this->render(
            'task/list.html.twig',
            ['tasks' => $this->getDoctrine()->getRepository('AppBundle:Task')->findAll()]
        );
    }

    /**
     * @Route("/tasks/done", name="task_done_list")
     */
    public function listDoneAction()
    {
        return $this->render(
            'task/listDone.html.twig',
            ['tasks' => $this->getDoctrine()->getRepository('AppBundle:Task')->findBy(['isDone' => true])]
        );
    }

    /**
     * @Route("/tasks/create", name="task_create")
     */
    public function createAction(Request $request)
    {
        $task = new Task($this->getUser());
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($task);
            $em->flush();

            $this->addFlash('success', $this->get('translator')->trans('task_added'));

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/tasks/{id}/edit", name="task_edit")
     */
    public function editAction(Task $task, Request $request)
    {
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', $this->get('translator')->trans('task_modified'));

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
    public function toggleTaskAction(Task $task, String $goto_url)
    {
        $task->toggle(!$task->isDone());
        $this->getDoctrine()->getManager()->flush();

        if ($task->isDone()) {
            $this->addFlash('success', $this->get('translator')->trans('task_marked_as_completed', [
                '%task_name%' => $task->getTitle()
            ]));
            return $this->redirectToRoute($goto_url);
        }

        $this->addFlash('success', $this->get('translator')->trans('task_marked_as_uncompleted', [
            '%task_name%' => $task->getTitle()
        ]));

        return $this->redirectToRoute($goto_url);
    }

    /**
     * @Route("/tasks/{id}/delete", name="task_delete")
     */
    public function deleteTaskAction(Task $task)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($task);
        $em->flush();

        $this->addFlash('success', $this->get('translator')->trans('task_deleted'));

        return $this->redirectToRoute('task_list');
    }
}
