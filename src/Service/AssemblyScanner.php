<?php

namespace App\Service;

use App\Entity\MediaStatus;
use App\Entity\Project;
use App\Entity\ProjectStatus;
use App\Message\AssembleMessage;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Symfony\Component\Messenger\MessageBusInterface;

class AssemblyScanner
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private MessageBusInterface $messageBus
    )
    {
    }

    public function assembleReadyProjects(): void {
        // Check for projects to assemble.
        $criteria = new Criteria();
        $criteria->where(new Comparison('status', Comparison::NIN, [ProjectStatus::Done, ProjectStatus::Failed, ProjectStatus::Assembling]));

        $projects = $this->projectRepository->createQueryBuilder('p')->addCriteria($criteria)->getQuery()->execute();
        if (count($projects) > 0) {
            /** @var Project $project */
            foreach ($projects as $project) {
                $remainingMedia = $project->getMedia()->filter(function($media) {
                    return $media->getStatus() !== MediaStatus::Done;
                });

                if (count($remainingMedia) < 1) {
                    // Everything's encoded.  Check the profile first before executing assembly.
                    // FIXME: Implement this nicely
                    //$assembleAfterTime = $project->getProfile()->getAssembleAfterTime();
                    $this->messageBus->dispatch(new AssembleMessage($project->getId()));
                }
            }
        }
    }
}