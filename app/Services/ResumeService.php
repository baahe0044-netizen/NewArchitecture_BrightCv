<?php

require_once __DIR__ . '/../Repositories/ResumeRepository.php';

class ResumeService
{
    private $repository;

    public function __construct()
    {
        $this->repository = new ResumeRepository();
    }

    public function dashboardData($userId)
    {
        return $this->repository->getDashboardData($userId);
    }

    public function getResumes($userId)
    {
        return $this->repository->getResumes($userId);
    }

    public function createResume($userId, $title)
    {
        return $this->repository->createResume($userId, $title);
    }

    public function getResumeForPdf($resumeId, $userId)
    {
        return [
            'resume'      => $this->repository->getResume($resumeId, $userId),
            'personal'    => $this->repository->getPersonal($resumeId),
            'experience'  => $this->repository->getExperience($resumeId),
            'education'   => $this->repository->getEducation($resumeId),
            'skills'      => $this->repository->getSkills($resumeId)
        ];
    }
}
